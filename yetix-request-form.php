<?php
/*
Plugin Name: Yetix Request Form for Zendesk
Plugin URI:  https://www.yetix.io/
Description: Add a Zendesk Support Form easily to your WordPress site.
Version:     1.1.1
Author:      Whodunit
Author URI:  https://www.whodunit.fr
License:     GPLv2
Text Domain: yetix-request-form
*/
if( !function_exists( 'add_action' ) ){ exit; }
define( 'YETIX_REQUEST__NAME',                'yetix-request-form' );
define( 'YETIX_REQUEST__DISPLAY_NAME',        'Yetix Request Form for Zendesk' );
define( 'YETIX_REQUEST__VERSION',             '1.1.1' );
define( 'YETIX_REQUEST__DISPLAY_VERSION',     'Version 1.1.1' );
define( 'YETIX_REQUEST__MINIMUM_WP_VERSION',  '5.0' );
define( 'YETIX_REQUEST__PLUGIN_FILE',          __FILE__ );
define( 'YETIX_REQUEST__PLUGIN_FILE_BASENAME', plugin_basename( __FILE__ ) );
define( 'YETIX_REQUEST__PLUGIN_URL',           plugin_dir_url( __FILE__ ) );
define( 'YETIX_REQUEST__PLUGIN_DIR',           plugin_dir_path( __FILE__ ) );
define( 'YETIX_REQUEST__METAS_OPTIONS',       'request_form_options' );

require dirname( __FILE__ ).'/vendor/autoload.php';
whodunit\yetix\Plugin::getInstance();