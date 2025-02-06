# elasticsearch-for-news-portal
## Installation:
If Front and CMS are separate projects, the installation procedure is as follows.
1. composer require lazar/elastic (on both projects)
2. php artisan vendor:publish --tag=config --provider="News\Elasticsearch\ElasticsearchServiceProvider" (on both projects)
3. Set up config/elasticsearch.php (on both projects)
4. php artisan vendor:publish --tag=controller --provider="News\Elasticsearch\ElasticsearchServiceProvider" (front)
5. php artisan vendor:publish --tag=commands --provider="News\Elasticsearch\ElasticsearchServiceProvider" (cms)

## CMS
The console command to create the index is run manually.
The console command for updating the index is started with the help of ArticleObserver, so that it "listens" to what is happening with the article. Depending on whether it is created, updated or deleted, it will start the corresponding method.
In the config file, the "run_observer" key is set to "true" to enable the index to be updated.
Also, if you want to enable search by tag, you can do so by setting the key "include_tags_in_search" to "true" in the config file.

## Front
Adjust the config file depending on the config file from the CMS.
Customize the view blade based on the data you get from the controller.