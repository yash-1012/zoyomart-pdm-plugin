<?php
/**
 * Plugin Name: Zoyomart Product Importer
 * Plugin URI: https://zoyomart.com
 * Description: Advanced WooCommerce Product Importer for Zoyomart.
 * Version: 1.0.0
 * Author: Yash Jain
 * License: GPL-2.0-or-later
 * Text Domain: zoyomart-product-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Composer Autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Plugin Constants
define( 'ZOYOMART_PDM_VERSION', '1.0.0' );
define( 'ZOYOMART_PDM_PATH', plugin_dir_path( __FILE__ ) );
define( 'ZOYOMART_PDM_URL', plugin_dir_url( __FILE__ ) );

// Load Admin
if ( is_admin() ) {
    require_once ZOYOMART_PDM_PATH . 'admin/class-admin.php';
}