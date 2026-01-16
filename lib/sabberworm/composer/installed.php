<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

return array(
    'root' => array(
        'name' => 'rmpel/wp-email-essentials',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => 'd8217b528893ab32d5bfdb2005cf8120a98d0119',
        'type' => 'library',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'rmpel/wp-email-essentials' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'd8217b528893ab32d5bfdb2005cf8120a98d0119',
            'type' => 'library',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'sabberworm/php-css-parser' => array(
            'pretty_version' => 'v8.8.0',
            'version' => '8.8.0.0',
            'reference' => '3de493bdddfd1f051249af725c7e0d2c38fed740',
            'type' => 'library',
            'install_path' => __DIR__ . '/../sabberworm/php-css-parser',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
