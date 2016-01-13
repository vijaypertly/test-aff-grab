<?php
/*
Plugin Name: Affiliate Products
Plugin URI: http://www.wpsupport.io/
Description: Affiliate Products listing from different store.
Author: Vijay M
Text Domain: affiliate-products
Domain Path: /languages/
Version: 1.0
*/
defined( 'ABSPATH' ) or die('');

define( 'AP_ACCESS', true );
define( 'AP', '1.0' );
define( 'AP_PLUGIN', __FILE__ );

if (!defined('WP_CONTENT_URL')){
	define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
}
if (!defined('WP_CONTENT_DIR')){
	define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}
if (!defined('WP_PLUGIN_URL') ){
	define('WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins');
}
if (!defined('WP_PLUGIN_DIR') ){
	define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');		
}
if (!defined('AP_PLUGIN_BASENAME') ){
	define( 'AP_PLUGIN_BASENAME', plugin_basename( AP_PLUGIN ) );
}
if (!defined('AP_PLUGIN_NAME') ){
	define( 'AP_PLUGIN_NAME', trim( dirname( AP_PLUGIN_BASENAME ), '/' ) );
}
if (!defined('AP_PLUGIN_DIR') ){
	define( 'AP_PLUGIN_DIR', untrailingslashit( dirname( AP_PLUGIN ) ) );
}
if (!defined('AP_PLUGIN_URL') ){
	define( 'AP_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
}

if (!defined('DS') ){
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('AP_PLUGIN_ASSETS_URL') ){
	define('AP_PLUGIN_ASSETS_URL', AP_PLUGIN_URL.'/assets');
}
if (!defined('AP_PLUGIN_HTML_DIR') ){
	define('AP_PLUGIN_HTML_DIR', AP_PLUGIN_DIR.DS.'html');
}

require_once AP_PLUGIN_DIR.DS.'class'.DS.'class-aff.php';
require_once AP_PLUGIN_DIR.DS.'class'.DS.'class-aff-fns.php';
new AffiliateProducts();

register_activation_hook( __FILE__, array('AffFns', 'activatePlugin') );
register_deactivation_hook( __FILE__, array('AffFns', 'deactivatePlugin') );


?>