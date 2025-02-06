<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\ClientBuilder;
use Exception;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateElasticsearchIndex extends Command
{
    protected $signature = 'elasticsearch:create-index';
    protected $description = 'Create Elasticsearch index for Articles and Article tags';

    protected $elasticsearch;

    public function __construct()
    {
        parent::__construct();

        $this->elasticsearch = ClientBuilder::create()->setBasicAuthentication(config('elasticsearch.elasticsearch_username'), config('elasticsearch.elasticsearch_password'))->setHosts([config('elasticsearch.elasticsearch_host')])->setSSLVerification(false)->build();
    }

    public function handle()
    {
        $this->info('Indexing all articles...');

        if(config('elasticsearch.include_tags_in_search')) {

            $params = [
                'index' => config('elasticsearch.elasticsearch_index'),
                'body'  => [
                    'mappings' => [
                        'properties' => [
                            'id' => [
                                'type' => 'integer'
                            ],
                            'heading' => [
                                'type' => 'text'
                            ],
                            'preheading' => [
                                'type' => 'text'
                            ],
                            'lead' => [
                                'type' => 'text'
                            ],
                            'tags' => [
                                'type' => 'text'
                            ],
                            'category' => [
                                'type' => 'nested',
                                'properties' => [
                                    'name' => [
                                        'type' => 'text'
                                    ],
                                    'url' => [
                                        'type' => 'keyword'
                                    ],
                                ]
                            ],
                            'subcategory' => [
                                'type' => 'nested',
                                'properties' => [
                                    'name' => [
                                        'type' => 'text'
                                    ],
                                    'url' => [
                                        'type' => 'keyword'
                                    ],
                                ]
                            ],
                            'image_m' => [
                                'type' => 'keyword'
                            ],
                            // 'image_kf' => [
                            //     'type' => 'keyword'
                            // ],
                            'image_ig' => [
                                'type' => 'keyword'
                            ],
                            // 'image_xs' => [
                            //     'type' => 'keyword'
                            // ],
                            'image_t' => [
                                'type' => 'keyword'
                            ],
                            'url' => [
                                'type' => 'keyword'
                            ],
                            'time_created' => [
                                'type' => 'date'
                            ],
                            'time_created_real' => [
                                'type' => 'date'
                            ],
                            'time_updated_real' => [
                                'type' => 'date'
                            ],
                            'time_changed' => [
                                'type' => 'date'
                            ],
                            'publish_at' => [
                                'type' => 'date'
                            ],
                            'comments' => [
                                'type' => 'integer'
                            ],
                            'comments_count' => [
                                'type' => 'integer'
                            ],
                            'has_video' => [
                                'type' => 'integer'
                            ],
                            'published' => [
                                'type' => 'integer'
                            ],
                        ]
                    ]
                ]
            ];

            try {

                //if index alredy exists, delete index and create new one
                if ($this->elasticsearch->indices()->exists(['index' => config('elasticsearch.elasticsearch_index')])) {
                    $this->elasticsearch->indices()->delete(['index' => config('elasticsearch.elasticsearch_index')]);
                }
    
                // Create the index
                $this->elasticsearch->indices()->create($params);
    
                // Fetch all articles with their tags
                Article::with('tags')->where('published', 1)->orderBy('publish_at', 'desc')->where('deleted_at', null)->chunk(5000, function ($articles) {
    
                    if (isset($articles) && !empty($articles)) {
    
                        foreach ($articles as $article) {
    
                            $output['article'] = self::dataNormalization($article);
                            $output['tags'] = $article->tags->pluck('title')->toArray();
    
                            $params = [
                                'index' => config('elasticsearch.elasticsearch_index'),
                                'id'    => 'article_' . $output['article']['id'],
                                'body'  => [
                                    'id'    => $output['article']['id'],
                                    'heading' => $output['article']['heading'],
                                    'preheading' => $output['article']['preheading'],
                                    'lead' => $output['article']['lead'],
                                    'tags' => $output['tags'],
                                    'category' => $output['article']['category'],
                                    'subcategory' => $output['article']['subcategory'],
                                    'image_m' => $output['article']['image_m'],
                                    // 'image_kf' => $output['article']['image_kf'],
                                    'image_ig' => $output['article']['image_ig'],
                                    // 'image_s' => $output['article']['image_s'],
                                    'image_t' => $output['article']['image_t'],
                                    'url' => $output['article']['url'],
                                    'publish_at' => $output['article']['publish_at'],
                                    'time_created' => $output['article']['time_created'],
                                    'time_created_real' => $output['article']['time_created_real'],
                                    'time_updated_real' => $output['article']['time_updated_real'],
                                    'time_changed' => $output['article']['time_changed'],
                                    'comments' => $output['article']['comments'],
                                    'comments_count' => $output['article']['comments_count'],
                                    'has_video' => $output['article']['has_video'],
                                    'published' => $output['article']['published'],
                                ],
                            ];
                            
                            $this->elasticsearch->index($params);
                        }
                    }
                });
                $this->info('Elasticsearch index created and populated successfully.');
            } catch (Exception $e) {
                Log::error("Elasticsearch index failed to created: " . $e->getMessage());
                $this->info('Elasticsearch index failed to created, check log.');
            }

        } else {

            $params = [
                'index' => config('elasticsearch.elasticsearch_index'),
                'body'  => [
                    'mappings' => [
                        'properties' => [
                            'id' => [
                                'type' => 'integer'
                            ],
                            'heading' => [
                                'type' => 'text'
                            ],
                            'preheading' => [
                                'type' => 'text'
                            ],
                            'lead' => [
                                'type' => 'text'
                            ],
                            'category' => [
                                'type' => 'nested',
                                'properties' => [
                                    'name' => [
                                        'type' => 'text'
                                    ],
                                    'url' => [
                                        'type' => 'keyword'
                                    ],
                                ]
                            ],
                            'subcategory' => [
                                'type' => 'nested',
                                'properties' => [
                                    'name' => [
                                        'type' => 'text'
                                    ],
                                    'url' => [
                                        'type' => 'keyword'
                                    ],
                                ]
                            ],
                            'image_m' => [
                                'type' => 'keyword'
                            ],
                            // 'image_kf' => [
                            //     'type' => 'keyword'
                            // ],
                            'image_ig' => [
                                'type' => 'keyword'
                            ],
                            // 'image_xs' => [
                            //     'type' => 'keyword'
                            // ],
                            'image_t' => [
                                'type' => 'keyword'
                            ],
                            'url' => [
                                'type' => 'keyword'
                            ],
                            'time_created' => [
                                'type' => 'date'
                            ],
                            'time_created_real' => [
                                'type' => 'date'
                            ],
                            'time_updated_real' => [
                                'type' => 'date'
                            ],
                            'time_changed' => [
                                'type' => 'date'
                            ],
                            'publish_at' => [
                                'type' => 'date'
                            ],
                            'comments' => [
                                'type' => 'integer'
                            ],
                            'comments_count' => [
                                'type' => 'integer'
                            ],
                            'has_video' => [
                                'type' => 'integer'
                            ],
                            'published' => [
                                'type' => 'integer'
                            ],
                        ]
                    ]
                ]
            ];

            try {

                //if index alredy exists, delete index and create new one
                if ($this->elasticsearch->indices()->exists(['index' => config('elasticsearch.elasticsearch_index')])) {
                    $this->elasticsearch->indices()->delete(['index' => config('elasticsearch.elasticsearch_index')]);
                }
    
                // Create the index
                $this->elasticsearch->indices()->create($params);
    
                // Fetch all articles without their tags
                Article::where('published', 1)->orderBy('publish_at', 'desc')->where('deleted_at', null)->chunk(5000, function ($articles) {
    
                    if (isset($articles) && !empty($articles)) {
    
                        foreach ($articles as $article) {
    
                            $output['article'] = self::dataNormalization($article);
    
                            $params = [
                                'index' => config('elasticsearch.elasticsearch_index'),
                                'id'    => 'article_' . $output['article']['id'],
                                'body'  => [
                                    'id'    => $output['article']['id'],
                                    'heading' => $output['article']['heading'],
                                    'preheading' => $output['article']['preheading'],
                                    'lead' => $output['article']['lead'],
                                    'category' => $output['article']['category'],
                                    'subcategory' => $output['article']['subcategory'],
                                    'image_m' => $output['article']['image_m'],
                                    // 'image_kf' => $output['article']['image_kf'],
                                    'image_ig' => $output['article']['image_ig'],
                                    // 'image_s' => $output['article']['image_s'],
                                    'image_t' => $output['article']['image_t'],
                                    'url' => $output['article']['url'],
                                    'publish_at' => $output['article']['publish_at'],
                                    'time_created' => $output['article']['time_created'],
                                    'time_created_real' => $output['article']['time_created_real'],
                                    'time_updated_real' => $output['article']['time_updated_real'],
                                    'time_changed' => $output['article']['time_changed'],
                                    'comments' => $output['article']['comments'],
                                    'comments_count' => $output['article']['comments_count'],
                                    'has_video' => $output['article']['has_video'],
                                    'published' => $output['article']['published'],
                                ],
                            ];
                            
                            $this->elasticsearch->index($params);
                        }
                    }
                });

                $this->info('Elasticsearch index created and populated successfully.');
            } catch (Exception $e) {
                Log::error("Elasticsearch index failed to created: " . $e->getMessage());
                $this->info('Elasticsearch index failed to created, check log.');
            }

        }
        
    }

    public function dataNormalization($article)
    {

        $category = Category::where('id', $article->category_id)->first();
        $categoryName = $category->name;
        $categoryUrl = '/' . Str::slug($categoryName);
        $categoryColor = $category->color;
        $subcategory = Category::where('id', $article->subcategory_id)->first();
        $subcategoryName = $subcategory->name;
        $subcategoryUrl = $categoryUrl . '/' . Str::slug($subcategoryName);

        $articleUrl = config('elasticsearch.main_site_url') . Str::slug($categoryName) . '/' . Str::slug($subcategoryName) . '/' . $article->id . '/' . Str::slug($article->heading) . '/news';
        $articleUrl = base64_encode($articleUrl);

        $article = [
            'id'    => $article->id,
            'heading' => $article->heading,
            'preheading' => $article->preheading,
            'lead' => $article->lead,
            'category' => [
                'name' => $categoryName,
                'url' => $categoryUrl,
                'color' => $categoryColor
            ],
            'subcategory' => [
                'name' => $subcategoryName,
                'url' => $subcategoryUrl
            ],
            'image_m' => $article->image_m,
            // 'image_kf' => $article->image_kf,
            'image_ig' => $article->image_ig,
            // 'image_xs' => $article->image_xs,
            'image_t' => $article->image_t,
            'url' => $articleUrl,
            'time_created' => strtotime($article->time_created),
            'time_created_real' => strtotime($article->time_created_real),
            'time_updated_real' => strtotime($article->time_updated_real),
            'time_changed' => strtotime($article->time_changed),
            'publish_at' => strtotime($article->publish_at),
            'comments' => $article->comments,
            'comments_count' => $article->comments_count,
            'has_video' => $article->has_video,
            'published' => $article->published,
        ];

        return $article;
    }
}
