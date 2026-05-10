<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Dynamic Page Templates
 *
 * Supports variable substitution:
 *   {tagname}      - tag page slug
 *   {keyword}      - search page query
 *   {fn}           - article/info page code
 *   {categoryname} - category page slug
 *   {newsname}     - news page slug
 *   {content}      - handler-generated list/detail HTML insertion point
 *
 * @package NoDB-WebBase
 */

return [
    'tag' => [
        'title'       => 'Tag: {tagname}',
        'description' => 'Related articles about {tagname}',
        'content'     => '<h1>Tag: {tagname}</h1><p>Related articles:</p>{content}',
    ],
    'search' => [
        'title'       => 'Search: {keyword}',
        'description' => 'Search results for {keyword}',
        'content'     => '<h1>Search: {keyword}</h1><p>Search results:</p>{content}',
    ],
    'article' => [
        'title'       => 'Article #{fn}',
        'description' => 'Article details',
        'content'     => '<h1>Article #{fn}</h1>{content}',
    ],
    'category' => [
        'title'       => 'Category: {categoryname}',
        'description' => 'Information about {categoryname}',
        'content'     => '<h1>Category: {categoryname}</h1><p>Related content:</p>{content}',
    ],
    'news' => [
        'title'       => 'News: {newsname}',
        'description' => 'News about {newsname}',
        'content'     => '<h1>News: {newsname}</h1><p>Related news:</p>{content}',
    ],
    'info' => [
        'title'       => 'Article #{fn}',
        'description' => 'Article details',
        'content'     => '<h1>Article #{fn}</h1>{content}',
    ],
];
