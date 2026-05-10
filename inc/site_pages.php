<?php
/**
 * NoDB-WebBase
 * GitHub: https://github.com/max-godman
 *
 * Front-end Static Pages Config
 *
 * return array, keyed by page key, corresponding to site_router.php static page keys.
 * Each page contains: path, title, description, content
 *
 * @package NoDB-WebBase
 */

return [
    'index' => [
        'path'        => '/',
        'title'       => 'Home',
        'description' => 'NoDB-WebBase - Minimalist Management System',
        'content'     => '<h2>Welcome</h2><p>This is the front-end demo page. Content can be edited via the admin "Pages" panel.</p>',
    ],
    'about' => [
        'path'        => '/about.html',
        'title'       => 'About',
        'description' => 'About the NoDB-WebBase team and project',
        'content'     => '<h2>About Us</h2><p>NoDB-WebBase is a minimalist PHP management system for developers and small teams, supporting rapid deployment and continuous expansion.</p>',
    ],
    'product' => [
        'path'        => '/product.html',
        'title'       => 'Products',
        'description' => 'Products and Services by NoDB-WebBase',
        'content'     => '<h2>Products & Services</h2><p>We provide minimalist, extensible management solutions with zero framework dependencies, built on pure PHP.</p>',
    ],
    'terms' => [
        'path'        => '/terms.html',
        'title'       => 'Terms',
        'description' => 'NoDB-WebBase Terms of Service & Privacy Policy',
        'content'     => '<h2>Terms of Service & Privacy Policy</h2><p>Please read the following terms carefully...</p>',
    ],
];
