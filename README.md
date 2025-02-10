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
If your portal is 

## Enabling Search for Serbian Special Characters
If your portal is in the Serbian language and you want to include special characters such as Č, Ć, Š, Ž, Đ in the search functionality, you need to add the following configuration to the index at the beginning of the index body:
```php
        'settings' => [
            'analysis' => [
                'filter' => [
                    'serbian_folding' => [
                        'type' => 'asciifolding', // Converts diacritical characters
                        'preserve_original' => true
                    ]
                ],
                'analyzer' => [
                    'serbian_analyzer' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => ['lowercase', 'serbian_folding']
                    ]
                ]
            ]
        ],
```

For each field you want to search by, add 'analyzer' => 'serbian_analyzer'. For example, if you're searching by title, it would look like this:

```php
        'heading' => [
            'type' => 'text',
            'analyzer' => 'serbian_analyzer'
        ],
```

This will ensure that Serbian diacritical characters are properly handled in the search functionality of your portal.