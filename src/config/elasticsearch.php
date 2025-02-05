<?php

return [

    'main_site_url' => 'http://newsmax.local:2020/',
    'elasticsearch_host' => 'http://elasticsearch:9200',
    'elasticsearch_index' => 'articles_search_test',
    'elasticsearch_username' => 'elastic',
    'elasticsearch_password' => 'elastic',

    'article_model' => \App\Models\Article::class,

    'run_observer' => false,
    'include_tags_in_search' => false
];