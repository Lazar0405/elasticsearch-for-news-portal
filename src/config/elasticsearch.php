<?php

return [

    'main_site_url' => '',
    'elasticsearch_index' => '',
    //default options
    'elasticsearch_host' => 'http://elasticsearch:9200',
    'elasticsearch_username' => 'elastic',
    'elasticsearch_password' => 'elastic',

    'article_model' => \App\Models\Article::class,
    'articles_per_page' => 20,

    'run_observer' => false,
    'include_tags_in_search' => false
];