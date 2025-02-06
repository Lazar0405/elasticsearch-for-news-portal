<?php

namespace News\Elasticsearch;

use Illuminate\Support\ServiceProvider;
use News\Elasticsearch\Observers\ArticleObserver;

class ElasticsearchServiceProvider extends ServiceProvider{

    public function boot(){

        //publish config
        $this->publishes([
            __DIR__.'/config/elasticsearch.php' => config_path('elasticsearch.php')
        ],'config');

        //publish SearchController
        $this->publishes([
            __DIR__.'/Controllers/SearchController.php' => app_path('Http/Controllers/SearchController.php')
        ], 'controller');

        //publish SearchController
        $this->publishes([
            __DIR__.'/Commands/CreateElasticsearchIndex.php' => app_path('Console/Commands/CreateElasticsearchIndex.php'),
            __DIR__.'/Commands/UpdateElasticsearchIndex.php' => app_path('Console/Commands/UpdateElasticsearchIndex.php')
        ], 'commands');

        // if ($this->app->runningInConsole()) {
        //     $this->commands([
        //         CreateElasticsearchIndex::class,
        //         UpdateElasticsearchIndex::class
        //     ]);
        // }
        
        if(config('elasticsearch.run_observer')) {
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