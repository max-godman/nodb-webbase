<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Page Config
 *
 * Unified page configuration for all 4 types:
 *   page  — Pure static (code block inactive)
 *   code  — Code block before head, renders template
 *   paged — Code block with pagination, renders template
 *   api   — Code block only, no template (JSON output etc.)
 *
 * Variables set in tpl/code_{key}.log are available
 * as {code:varname} placeholders in title/description/content.
 *
 * @package NoDB-WebBase
 */

return [
    'index' => [
        'type'        => 'page',
        'title'       => 'Home',
        'description' => 'NoDB-WebBase - Minimalist Management System',
        'content'     => '<h2>Welcome</h2><p>This is the front-end demo page. Content can be edited via the admin Content panel.</p>',
    ],
    'about' => [
        'type'        => 'page',
        'title'       => 'About',
        'description' => 'About the NoDB-WebBase team and project',
        'content'     => '<h2>About Us</h2><p>NoDB-WebBase is a minimalist PHP management system for developers and small teams.</p>',
    ],
    'product' => [
        'type'        => 'page',
        'title'       => 'Products',
        'description' => 'Products and Services by NoDB-WebBase',
        'content'     => '<h2>Products & Services</h2><p>We provide minimalist, extensible management solutions.</p>',
    ],
    'terms' => [
        'type'        => 'page',
        'title'       => 'Terms',
        'description' => 'NoDB-WebBase Terms of Service & Privacy Policy',
        'content'     => '<h2>Terms of Service & Privacy Policy</h2><p>Please read the following terms carefully...</p>',
    ],
    'tag' => [
        'type'        => 'code',
        'title'       => 'Tag: {code:tagname} - Page {code:page}',
        'description' => 'Related articles about {code:tagname}',
        'content'     => '<h1>Tag: {code:tagname}</h1><p>Page {code:page}</p>{code:list_html}',
    ],
    'search' => [
        'type'        => 'code',
        'title'       => 'Search: {code:keyword}',
        'description' => 'Search results for {code:keyword}',
        'content'     => '<h1>Search: {code:keyword}</h1>{code:list_html}',
    ],
    'article' => [
        'type'        => 'code',
        'title'       => 'Article #{code:fn}',
        'description' => 'Article details',
        'content'     => '<h1>Article #{code:fn}</h1>{code:detail_html}',
    ],
    'post' => [
        'type'        => 'code',
        'title'       => 'Post',
        'description' => 'Post submission page',
        'content'     => '<h1>Post</h1>{code:form_html}',
    ],
    'sub' => [
        'type'        => 'api',
        'title'       => '',
        'description' => '',
        'content'     => '',
    ],
];
