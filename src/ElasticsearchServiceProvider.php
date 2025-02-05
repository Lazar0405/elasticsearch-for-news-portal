<?php

namespace News\Elasticsearch;

use Illuminate\Support\ServiceProvider;
use News\Elasticsearch\Commands\CreateElasticsearchIndex;
use News\Elasticsearch\Commands\UpdateElasticsearchIndex;
use News\Elasticsearch\Observers\ArticleObserver;

class ElasticsearchServiceProvider extends ServiceProvider{

    public function boot(){

        //publish config
        $this->publishes([
            __DIR__.'/config/elasticsearch.php' => config_path('elasticsearch.php')
        ],'config');

        //publish SearchController
        $this->publishes([
            __DIR__.'/News/Elasticsearch/Controllers/SearchController.php' => app_path('Http/Controllers/SearchController.php')
        ], 'controller');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateElasticsearchIndex::class,
                UpdateElasticsearchIndex::class
            ]);
        }
        
        if(config('elasticsearch.run_observer') == true) {
            $className = config('elasticsearch.article_model');
            if (class_exists($className)) { 
                $className::observe(ArticleObserver::class); 
            }
        }
    }

    public function register(){
        $this->mergeConfigFrom(__DIR__.'/config/elasticsearch.php', 'elasticsearch');
    }
}