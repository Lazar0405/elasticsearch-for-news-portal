<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\ClientBuilder;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use App\Models\Category;
use Illuminate\Support\Str;

class UpdateElasticsearchIndex extends Command
{
    protected $signature = 'elasticsearch:update-index {action} {id?}';
    protected $description = 'Update Elasticsearch index for articles: create/update/delete';

    protected $elasticsearch;

    public function __construct()
    {
        parent::__construct();
        $this->elasticsearch = ClientBuilder::create()->setBasicAuthentication(config('elasticsearch.elasticsearch_username'), config('elasticsearch.elasticsearch_password'))->setHosts([config('elasticsearch.elasticsearch_host')])->setSSLVerification(false)->build();
    }

    public function handle()
    {
        
        $action = $this->argument('action');
        $articleId = $this->argument('id');
        
        switch ($action) {
            case 'create':
                $this->addToIndex($articleId);
                break;

            case 'update':
                $this->updateIndex($articleId);
                break;

            case 'delete':
                $this->deleteFromIndex($articleId);
                break;
            default:
                $this->error('Invalid action. Use "add", "update", or "delete".');
                break;
        }
    }

    protected function addToIndex($id)
    {
        try {
            $article = Article::find($id);
            if(isset($article) && !empty($article)) {

                if(config('elasticsearch.include_tags_in_search')) {

                    $output['article'] = self::dataNormalization($article);
                    $output['tags'] = $article->tags->pluck('title')->toArray();
        
                    $params = [
                        'index' => config('elasticsearch.elasticsearch_index'),
                        'id'    => 'article_' . $output['article']['id'],
                        'body'  => [ // Korišćenje 'body', a ne 'doc'
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
                            // 'image_xs' => $output['article']['image_xs'],
                            'image_t' => $output['article']['image_t'],
                            'url' => $output['article']['url'],
                            'time_created_real' => $output['article']['time_created_real'],
                            'time_updated_real' => $output['article']['time_updated_real'],
                            'time_changed' => $output['article']['time_changed'],
                            'publish_at' => $output['article']['publish_at'],
                            'comments' => $output['article']['comments'],
                            'comments_count' => $output['article']['comments_count'],
                            'has_video' => $output['article']['has_video'],
                            'published' => $output['article']['published'],
                        ],
                    ];
                }else {

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
                            // 'image_xs' => $output['article']['image_xs'],
                            'image_t' => $output['article']['image_t'],
                            'url' => $output['article']['url'],
                            'time_created_real' => $output['article']['time_created_real'],
                            'time_updated_real' => $output['article']['time_updated_real'],
                            'time_changed' => $output['article']['time_changed'],
                            'publish_at' => $output['article']['publish_at'],
                            'comments' => $output['article']['comments'],
                            'comments_count' => $output['article']['comments_count'],
                            'has_video' => $output['article']['has_video'],
                            'published' => $output['article']['published'],
                        ],
                    ];

                }
    
                $this->elasticsearch->index($params); 
                $this->info("Elasticsearch index added successfully for article ID: $id");
            }
        } catch (\Exception $e) {
            Log::error("Failed to add to Elasticsearch index for article ID: $id. Error: " . $e->getMessage());
            $this->error("Failed to add to Elasticsearch index. Check logs for details.");
        }
    }
    

    protected function updateIndex($id)
    {
        try {
            $article = Article::where('id', $id)->first();

            if (isset($article) && !empty($article)) {

                if(config('elasticsearch.include_tags_in_search') == true) {

                    $output['article'] = self::dataNormalization($article);
                    $output['tags'] = $article->tags->pluck('title')->toArray();

                    $params = [
                        'index' => config('elasticsearch.elasticsearch_index'),
                        'id'    => 'article_' . $output['article']['id'],
                        'body'  => [
                            'doc' => [
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
                                // 'image_xs' => $output['article']['image_xs'],
                                'image_t' => $output['article']['image_t'],
                                'url' => $output['article']['url'],
                                'time_created_real' => $output['article']['time_created_real'],
                                'time_updated_real' => $output['article']['time_updated_real'],
                                'time_changed' => $output['article']['time_changed'],
                                'publish_at' => $output['article']['publish_at'],
                                'comments' => $output['article']['comments'],
                                'comments_count' => $output['article']['comments_count'],
                                'has_video' => $output['article']['has_video'],
                                'published' => $output['article']['published'],
                            ],
                            'doc_as_upsert' => true
                        ]
                    ];
                }else {

                    $output['article'] = self::dataNormalization($article);

                    $params = [
                        'index' => config('elasticsearch.elasticsearch_index'),
                        'id'    => 'article_' . $output['article']['id'],
                        'body'  => [
                            'doc' => [
                                'id'    => $output['article']['id'],
                                'heading' => $output['article']['heading'],
                                'preheading' => $output['article']['preheading'],
                                'lead' => $output['article']['lead'],
                                'category' => $output['article']['category'],
                                'subcategory' => $output['article']['subcategory'],
                                'image_m' => $output['article']['image_m'],
                                // 'image_kf' => $output['article']['image_kf'],
                                'image_ig' => $output['article']['image_ig'],
                                // 'image_xs' => $output['article']['image_xs'],
                                'image_t' => $output['article']['image_t'],
                                'url' => $output['article']['url'],
                                'time_created_real' => $output['article']['time_created_real'],
                                'time_updated_real' => $output['article']['time_updated_real'],
                                'time_changed' => $output['article']['time_changed'],
                                'publish_at' => $output['article']['publish_at'],
                                'comments' => $output['article']['comments'],
                                'comments_count' => $output['article']['comments_count'],
                                'has_video' => $output['article']['has_video'],
                                'published' => $output['article']['published'],
                            ],
                            'doc_as_upsert' => true
                        ]
                    ];
                }

                $this->elasticsearch->update($params);

                $this->info("Elasticsearch index updated successfully for article ID: $id");
            } else {
                $this->error("Article with ID: $id not found.");
            }
        } catch (\Exception $e) {
            Log::error('Failed to update Elasticsearch index for article ID: ' . $id . '. Error: ' . $e->getMessage());
            $this->error("Failed to update Elasticsearch index. Check logs for details.");
        }
    }


    protected function deleteFromIndex($id)
    {
        if (!$id) {
            $this->error("Article ID is required for deletion.");
            return;
        }

        $params = [
            'index' => config('elasticsearch.elasticsearch_index'),
            'id'    => 'article_' . $id,
        ];

        try {
            $this->elasticsearch->delete($params);
            $this->info("Elasticsearch index deleted successfully for article ID: $id");
        } catch (\Exception $e) {
            Log::error("Failed to delete from Elasticsearch index: " . $e->getMessage());
            $this->error("Failed to delete from Elasticsearch index. Check logs for details.");
        }
    }

    public function dataNormalization($article) {

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
