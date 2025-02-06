<?php

namespace App\Http\Controllers;

use App\Repositories\ArticleRepository;
use Elastic\Elasticsearch\ClientBuilder;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Log;

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
        $page = 1;

        // validate request
        if (empty(request()->getQueryString())) {
            $query = "";
            return view('search', compact('query', 'total', 'errors', 'page'));
        }

        $query = trim(strip_tags(request('search')));
        $queryString = request()->getQueryString();

        if (!empty($queryString)) {
            if (!is_string($query)) {
                $errors['string'] = __('The search parameter must be a string!');
            }

            if (mb_strlen($query) < 3 && !empty($query)) {
                $errors['min_3'] = __('The search parameter must be longer than 2 characters!');
            }

            if (mb_strlen($query) > 50) {
                $errors['max_50'] = __('The search parameter must not be longer than 50 characters!');
            }

            if (empty($query)) {
                $errors['empty'] = __('Please enter a search parameter!');
            }
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

        // if the current page is bigger than the 500, then we will set the last page to be 500 
        if($page > 500) {
            $page = 500;
        }

        try {

            $size = config('elasticsearch.articles_per_page');
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
                                            'query' => '*' . $query . '*',
                                            'default_operator' => 'AND'
                                        ]
                                    ],
                                    //Search in tag with wildcard
                                    [
                                        'query_string' => [
                                            'default_field' => 'tags',
                                            'query' => '*' . $query . '*',
                                            'default_operator' => 'AND'
                                        ]
                                    ],
                                    //Fuzzy title search
                                    [
                                        'match' => [
                                            'heading' => [
                                                'query' => $query,  //Error tolerant title search
                                                'fuzziness' => '1', 
                                                'operator' => 'AND'
                                            ]
                                        ]
                                    ],
                                    // Fuzzy pretraga tagova
                                    [
                                        'match' => [
                                            'tags' => [
                                                'query' => $query,  //Error tolerant tag search
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
                                            'query' => '*' . $query . '*',
                                            'default_operator' => 'AND'
                                        ]
                                    ],
                                    //Fuzzy title search
                                    [
                                        'match' => [
                                            'heading' => [
                                                'query' => $query,  //Error tolerant title search
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

            //pagination (modify depending on the project)
            $paginationParams = ArticleRepository::paginationParams($total, $size, $page);
            $lastPage = $paginationParams['last_page'];
            $page = $paginationParams['page'];
            $from = $paginationParams['offset'];
            $hasMorePages = $paginationParams['has_more_pages'];

            //if the current page is bigger than the last page, then we will set the current page to be the last page
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

            //final data with articles and pagination
            $data = [
                'results' => $results['hits']['hits'],
                'pagination' => $pagination
            ];

        } catch (Exception $e) {
            Log::error('Elasticsearch filed: ' . $e->getMessage());
        }
        
        return view('search', compact('data', 'query', 'errors', 'total'));
    }
}