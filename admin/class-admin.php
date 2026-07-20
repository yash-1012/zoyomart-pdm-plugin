<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Zoyomart_PDM_Admin {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'register_menu' ) );

	}

	public function register_menu() {

		add_menu_page(
			'Zoyomart Product Importer',
			'Zoyomart',
			'manage_options',
			'zoyomart-pdm',
			array( $this, 'dashboard_page' ),
			'dashicons-database-import',
			56
		);

		add_submenu_page(
			'zoyomart-pdm',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'zoyomart-pdm',
			array( $this, 'dashboard_page' )
		);

		add_submenu_page(
			'zoyomart-pdm',
			'Import Products',
			'Import Products',
			'manage_options',
			'zoyomart-import-products',
			array( $this, 'import_products_page' )
		);

		add_submenu_page(
			'zoyomart-pdm',
			'Import Logs',
			'Import Logs',
			'manage_options',
			'zoyomart-import-logs',
			array( $this, 'import_logs_page' )
		);

		add_submenu_page(
			'zoyomart-pdm',
			'Settings',
			'Settings',
			'manage_options',
			'zoyomart-settings',
			array( $this, 'settings_page' )
		);
	}

	public function dashboard_page() {

		require_once ZOYOMART_PDM_PLUGIN_PATH . 'admin/views/dashboard.php';

	}

	public function import_products_page() {

		require_once ZOYOMART_PDM_PLUGIN_PATH . 'admin/views/import-products.php';

	}

	public function import_logs_page() {

		require_once ZOYOMART_PDM_PLUGIN_PATH . 'admin/views/import-logs.php';

	}

	public function settings_page() {

		require_once ZOYOMART_PDM_PLUGIN_PATH . 'admin/views/settings.php';

	}

}