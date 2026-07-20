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

		if ( ! isset( $_POST['zoyomart_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['zoyomart_nonce'], 'zoyomart_upload_excel' ) ) {
			wp_die( 'Security check failed.' );
		}

		if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
			return;
		}

		try {

			$spreadsheet = IOFactory::load(
				$_FILES['import_file']['tmp_name']
			);

			$sheet = $spreadsheet->getActiveSheet();

			$data = $sheet->toArray();

			set_transient(
				'zoyomart_excel_preview',
				$data,
				300
			);

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'zoyomart-import-products',
						'preview' => 1,
					),
					admin_url( 'admin.php' )
				)
			);

			exit;

		} catch ( Exception $e ) {

			wp_die(
				'Excel Error : ' . esc_html( $e->getMessage() )
			);

		}

	}

}