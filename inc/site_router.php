<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Router Table
 *
 * match supports exact strings or regex (starting with ~).
 * type determines handler: page=static page config, tag/search/article=dynamic page handler.
 *
 * @package NoDB-WebBase
 */

return [
    // Static pages: exact match
    ['match' => '/',              'type' => 'page', 'key' => 'index'],
    ['match' => '/about.html',    'type' => 'page', 'key' => 'about'],
    ['match' => '/product.html',  'type' => 'page', 'key' => 'product'],
    ['match' => '/terms.html',    'type' => 'page', 'key' => 'terms'],

    // Dynamic pages: regex match, handler reserved
    ['match' => '~^/tag/([a-zA-Z0-9]+)$~',               'type' => 'tag',     'handler' => 'getTagPage'],
    ['match' => '~^/search$~',                            'type' => 'search',  'handler' => 'getSearchPage'],
    ['match' => '~^/article/([a-zA-Z0-9]{13})\.html$~',  'type' => 'article', 'handler' => 'getArticlePage'],
    ['match' => '~^/category/([a-zA-Z0-9]+)$~',           'type' => 'category','handler' => 'getCategoryPage'],
    ['match' => '~^/news/([a-zA-Z0-9]+)$~',               'type' => 'news',    'handler' => 'getNewsPage'],
    ['match' => '~^/info/([a-zA-Z0-9]{13})\.html$~',     'type' => 'info',    'handler' => 'getInfoPage'],
];
