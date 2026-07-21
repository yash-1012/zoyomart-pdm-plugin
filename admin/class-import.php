<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

class Zoyomart_PDM_Import {

	public function __construct() {

		add_action(
			'admin_init',
			array( $this, 'handle_upload' )
		);

	}

	public function handle_upload() {

		if ( ! isset( $_POST['action'] ) || 'zoyomart_import_products' !== $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import products.', 'zoyomart-product-importer' ) );
		}

		check_admin_referer( 'zoyomart_upload_excel', 'zoyomart_nonce' );

		if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			$this->redirect_with_notice( array( 'error' => __( 'WooCommerce must be active before products can be imported.', 'zoyomart-product-importer' ) ) );
		}

		if ( empty( $_FILES['import_file']['tmp_name'] ) || ! empty( $_FILES['import_file']['error'] ) ) {
			$this->redirect_with_notice( array( 'error' => __( 'Please select a valid Excel or CSV file.', 'zoyomart-product-importer' ) ) );
		}

		try {
			$file_name = isset( $_FILES['import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['import_file']['name'] ) ) : '';
			$extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

			if ( ! in_array( $extension, array( 'xlsx', 'xls', 'csv' ), true ) ) {
				throw new Exception( __( 'Only .xlsx, .xls, and .csv files are supported.', 'zoyomart-product-importer' ) );
			}

			$spreadsheet = IOFactory::load( $_FILES['import_file']['tmp_name'] );

			$sheet = $spreadsheet->getActiveSheet();
			$data  = $sheet->toArray( '', true, true, false );

			$importer = new Zoyomart_Product_Importer();
			$summary  = $importer->import_sheet( $data );

			if ( $spreadsheet->getSheetCount() > 1 ) {
				$importer->import_related_products( $spreadsheet->getSheet( 1 )->toArray( '', true, true, false ) );
			}

			$spreadsheet->disconnectWorksheets();
			$this->redirect_with_notice( $summary );

		} catch ( Throwable $e ) {
			$this->redirect_with_notice( array( 'error' => $e->getMessage() ) );
		}

	}

	private function redirect_with_notice( array $notice ) {
		set_transient( 'zoyomart_import_notice_' . get_current_user_id(), $notice, MINUTE_IN_SECONDS );
		wp_safe_redirect( add_query_arg( 'page', 'zoyomart-import-products', admin_url( 'admin.php' ) ) );
		exit;
	}

}
