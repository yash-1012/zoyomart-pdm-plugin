<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zoyomart\PDM\Infrastructure\WordPress\ImportPreviewRepository;
use Zoyomart\PDM\Infrastructure\WordPress\ImportProfileRepository;
use Zoyomart\PDM\Infrastructure\WooCommerce\ProductSkuLookup;
use Zoyomart\PDM\Services\Import\DryRunValidator;
use Zoyomart\PDM\Services\Excel\WorkbookProfileReader;

/**
 * Handles the read-only workbook preview stage. Product writes are deliberately
 * deferred to a later import action.
 */
class Zoyomart_PDM_Import {
	private ImportProfileRepository $profiles;
	private ImportPreviewRepository $previews;
	private WorkbookProfileReader $reader;

	public function __construct() {
		$this->profiles = new ImportProfileRepository();
		$this->previews = new ImportPreviewRepository();
		$this->reader   = new WorkbookProfileReader();
		add_action( 'admin_init', array( $this, 'handle_upload' ) );
	}

	public function handle_upload(): void {
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( $_POST['action'] ) );
		if ( 'zoyomart_dry_run_import' === $action ) {
			$this->handle_dry_run();
		}
		if ( 'zoyomart_preview_workbook' !== $action ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to preview imports.', 'zoyomart-product-importer' ) );
		}
		check_admin_referer( 'zoyomart_preview_workbook', 'zoyomart_nonce' );

		$profile_id = isset( $_POST['profile_id'] ) ? sanitize_key( wp_unslash( $_POST['profile_id'] ) ) : '';
		$profile    = $this->profiles->find( $profile_id );
		if ( ! $profile ) {
			$this->redirect_with_notice( __( 'Choose a valid import profile.', 'zoyomart-product-importer' ) );
		}
		if ( empty( $_FILES['import_file']['tmp_name'] ) || ! empty( $_FILES['import_file']['error'] ) ) {
			$this->redirect_with_notice( __( 'Choose a valid Excel or CSV file.', 'zoyomart-product-importer' ) );
		}

		try {
			$file = $this->store_upload( $_FILES['import_file'] );
			$inspection = $this->reader->inspect( $file['file'], $profile );
			$preview = $this->reader->preview( $file['file'], $profile );
			$this->previews->save( get_current_user_id(), array(
				'profile_id' => $profile->id(),
				'file_name' => $file['name'],
				'file_path' => $file['file'],
				'inspection' => $inspection,
				'preview' => $preview,
			) );
			$this->redirect_to_preview();
		} catch ( Throwable $exception ) {
			$this->redirect_with_notice( $exception->getMessage() );
		}
	}

	private function handle_dry_run(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to validate imports.', 'zoyomart-product-importer' ) );
		}
		check_admin_referer( 'zoyomart_dry_run_import', 'zoyomart_dry_run_nonce' );
		$preview = $this->previews->get( get_current_user_id() );
		$profile = $preview ? $this->profiles->find( $preview['profile_id'] ) : null;
		if ( ! $preview || ! $profile ) {
			$this->redirect_with_notice( __( 'Your workbook preview has expired. Upload it again to run a dry run.', 'zoyomart-product-importer' ) );
		}
		try {
			$validator = new DryRunValidator( new ProductSkuLookup() );
			$preview['dry_run'] = $validator->validate( $this->reader->mapped_rows( $preview['file_path'], $profile ) );
			$this->previews->save( get_current_user_id(), $preview );
			wp_safe_redirect( add_query_arg( array( 'page' => 'zoyomart-import-products', 'preview' => 1, 'dry-run' => 1 ), admin_url( 'admin.php' ) ) );
			exit;
		} catch ( Throwable $exception ) {
			$this->redirect_with_notice( $exception->getMessage() );
		}
	}

	private function store_upload( array $upload ): array {
		$file_name = sanitize_file_name( (string) ( $upload['name'] ?? '' ) );
		$extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
		$mimes = array(
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'xls' => 'application/vnd.ms-excel',
			'csv' => 'text/csv',
		);
		if ( ! isset( $mimes[ $extension ] ) || (int) $upload['size'] > wp_max_upload_size() ) {
			throw new InvalidArgumentException( __( 'Upload an Excel or CSV file within the configured upload limit.', 'zoyomart-product-importer' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$result = wp_handle_upload( $upload, array( 'test_form' => false, 'mimes' => $mimes ) );
		if ( isset( $result['error'] ) ) {
			throw new RuntimeException( $result['error'] );
		}
		return $result;
	}

	private function redirect_with_notice( string $message ): void {
		set_transient( 'zoyomart_pdm_import_notice_' . get_current_user_id(), $message, MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'page', 'zoyomart-import-products', admin_url( 'admin.php' ) ) );
		exit;
	}

	private function redirect_to_preview(): void {
		wp_safe_redirect( add_query_arg( array( 'page' => 'zoyomart-import-products', 'preview' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
