<?php

namespace News\Elasticsearch\Controllers;

use App\Repositories\ArticleRepository;
use Elasticsearch\ClientBuilder;
use App\Http\Controllers\Controller;

class SearchController extends Controller
{

    protected $elasticsearch;

    public function __construct()
    {
        $this->elasticsearch = ClientBuilder::create()->setBasicAuthentication(config('elasticsearch.elasticsearch_username'), config('elasticsearch.elasticsearch_password'))->setHosts([config('elasticsearch.elasticsearch_host')])->setSSLVerification(false)->build();
    }

    public function search()
    {
        $articles = [];
        $pagination = [];
        $errors = [];
        $total = 0;

        // validate request
        if (!isset(request()->search_parameter) || empty(request()->search_parameter)) {
            $search_parameter = "";
        }

        $search_parameter = trim(strip_tags(request()->search_parameter));

        if (!is_string($search_parameter)) {
            $errors['string'] = __('The search parameter must be a string');
        }

        if (mb_strlen($search_parameter) < 3) {
            $errors['min_3']= __('The search parameter must be longer than 2 characters!');
        }

        if (mb_strlen($search_parameter) > 50) {
            $errors['max_50']= __('The search parameter must not be longer than 50 characters!');
        }

        if (empty($search_parameter)) {
            $errors['empty'] = __('Please enter a search parameter!');
        }

        //If error exists, return error view blade
        if (count($errors) > 0) {
            return view('search', compact('search_parameter', 'errors'));
        }

        // Validation $page parameter
        if ( isset(request()->page) && !empty(request()->page) ) {

            $page = (int) strip_tags(request()->page);

            if (!is_numeric($page) || $page <= 0) {
                $page = 1;
            }

        } else {
            $page = 1;
        }

        if($page > 500) {
            $page = 500;
        }

        $size = 20;
        $from = ($page - 1) * $size;

        if(config('elasticsearch.include_tags_in_search')) {

            $params = [
                'index' => config('elasticsearch.elasticsearch_index'),
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                //Publish date condition (publish_at must be less than or equal to the current time)
                                [
                                    'range' => [
                                        'publish_at' => [
                                            'lte' => time(), //Return only those records whose publish_at is less than or equal to the current time
                                        ]
                                    ]
                                ],
                                //Condition that the news must be published (published = 1)
                                [
                                    'term' => [
                                        'published' => 1 //Return only published records
                                    ]
                                ]
                            ],
                            'should' => [
                                //Search in title with wildcard
                                [
                                    'query_string' => [
                                        'default_field' => 'heading',
                                        'query' => '*' . $search_parameter . '*',
                                        'default_operator' => 'AND'
                                    ]
                                ],
                                //Search in tag with wildcard
                                [
                                    'query_string' => [
                                        'default_field' => 'tags',
                                        'query' => '*' . $search_parameter . '*',
                                        'default_operator' => 'AND'
                                    ]
                                ],
                                //Fuzzy title search
                                [
                                    'match' => [
                                        'heading' => [
                                            'query' => $search_parameter,  //Error tolerant title search
                                            'fuzziness' => '1', 
                                            'operator' => 'AND'
                                        ]
                                    ]
                                ],
                                // Fuzzy pretraga tagova
                                [
                                    'match' => [
                                        'tags' => [
                                            'query' => $search_parameter,  //Error tolerant tag search
                                            'fuzziness' => '1', 
                                            'operator' => 'AND'
                                        ]
                                    ]
                                ]
                            ],
                            //We ensure that at least one of the should queries must be fulfilled
                            'minimum_should_match' => 1,
                        ]
                    ],
                    'from' => $from,
                    'size' => $size,
                    'sort' => [
                        'publish_at' => 'desc' //Sort by publication date (newest first)
                    ],
                ]
            ];

        }else {
            $params = [
                'index' => config('elasticsearch.elasticsearch_index'),
                'body' => [
                    'query' => [
                        'bool' => [
                            'must' => [
                                //Publish date condition (publish_at must be less than or equal to the current time)
                                [
                                    'range' => [
                                        'publish_at' => [
                                            'lte' => time(), //Return only those records whose publish_at is less than or equal to the current time
                                        ]
                                    ]
                                ],
                                //Condition that the news must be published (published = 1)
                                [
                                    'term' => [
                                        'published' => 1 //Return only published records
                                    ]
                                ]
                            ],
                            'should' => [
                                //Search in title with wildcard
                                [
                                    'query_string' => [
                                        'default_field' => 'heading',
                                        'query' => '*' . $search_parameter . '*',
                                        'default_operator' => 'AND'
                                    ]
                                ],
                                //Fuzzy title search
                                [
                                    'match' => [
                                        'heading' => [
                                            'query' => $search_parameter,  //Error tolerant title search
                                            'fuzziness' => '1', 
                                            'operator' => 'AND'
                                        ]
                                    ]
                                ],
                            ],
                            //We ensure that at least one of the should queries must be fulfilled
                            'minimum_should_match' => 1,
                        ]
                    ],
                    'from' => $from,
                    'size' => $size,
                    'sort' => [
                        'publish_at' => 'desc' //Sort by publication date (newest first)
                    ],
                ]
            ];
        }

        $results = $this->elasticsearch->search($params);
        $total = $results['hits']['total']['value'];

        $paginationParams = ArticleRepository::paginationParams($total, $size, $page);
        $lastPage = $paginationParams['last_page'];
        $page = $paginationParams['page'];
        $from = $paginationParams['offset'];
        $hasMorePages = $paginationParams['has_more_pages'];

        //ako je trenutni page veci od poslednje stranice onda cemo setovati da trenutni page bude poslednja stranica
        if($page > $lastPage) {
            $page = $lastPage;
        }

        $pagination = [
            'total' => $total,
            'current_page' => $page,
            'offset' => $from,
            'has_more_pages' => $hasMorePages,
            'last_page' => $lastPage,
            'per_page' => $size
        ];

        $data = [
            'results' => $results['hits']['hits'],
            'pagination' => $pagination
        ];

        return view('search', compact('data', 'search_parameter', 'errors', 'total'));
    }
}