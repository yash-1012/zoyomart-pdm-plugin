<?php
/**
 * Plugin Name: Zoyomart Product Importer
 * Plugin URI: https://zoyomart.com
 * Description: Advanced WooCommerce Product Importer with Excel Mapping, Dry Run, Rollback and Batch Import.
 * Version: 1.0.0
 * Author: Yash Jain
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: zoyomart-product-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Plugin Constants
|--------------------------------------------------------------------------
*/

define( 'ZOYOMART_PDM_VERSION', '1.0.0' );
define( 'ZOYOMART_PDM_PLUGIN_FILE', __FILE__ );
define( 'ZOYOMART_PDM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZOYOMART_PDM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| Composer Autoload
|--------------------------------------------------------------------------
*/

if ( file_exists( ZOYOMART_PDM_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
	require_once ZOYOMART_PDM_PLUGIN_PATH . 'vendor/autoload.php';
}

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

if ( is_admin() ) {

    error_log( 'Loading Product Importer admin...' );

    require_once ZOYOMART_PDM_PLUGIN_PATH . 'admin/class-admin.php';

    add_action( 'plugins_loaded', function () {
        new Zoyomart_PDM_Admin();
    } );

}