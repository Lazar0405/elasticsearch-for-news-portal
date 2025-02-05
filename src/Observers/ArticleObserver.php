<?php

namespace News\Elasticsearch\Observers;

use App\Models\Article;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ArticleObserver
{
    public function updated(Article $article)
    {
        try{
            Artisan::call('elasticsearch:update-index', ['action' => 'update', 'id' => $article->id]);
        } catch(Exception $e) {
            Log::error($e);
        }
        
    }

    public function deleted(Article $article)
    {
        try{
            Artisan::call('elasticsearch:update-index', ['action' => 'delete', 'id' => $article->id]);
        } catch(Exception $e) {
            Log::error($e);
        }

        
    }

    public function created(Article $article)
    {
        try{
            Artisan::call('elasticsearch:update-index', ['action' => 'create', 'id' => $article->id]);
        } catch(Exception $e) {
            Log::error($e);
        }
        
    }
}