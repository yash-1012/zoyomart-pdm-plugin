<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Zoyomart\PDM\Domain\Import\ImportProfile;
use Zoyomart\PDM\Infrastructure\WordPress\ImportProfileRepository;

class Zoyomart_PDM_Import_Profiles_Admin {
	private ImportProfileRepository $profiles;

	public function __construct() {
		$this->profiles = new ImportProfileRepository();
		add_action( 'admin_init', array( $this, 'handle_request' ) );
	}

	public function handle_request(): void {
		if ( ! isset( $_POST['zoyomart_pdm_profile_action'] ) && ! isset( $_GET['zoyomart_pdm_delete_profile'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage import profiles.', 'zoyomart-product-importer' ) );
		}

		if ( isset( $_GET['zoyomart_pdm_delete_profile'] ) ) {
			$id = sanitize_key( wp_unslash( $_GET['zoyomart_pdm_delete_profile'] ) );
			check_admin_referer( 'zoyomart_pdm_delete_profile_' . $id );
			$this->profiles->delete( $id );
			$this->redirect( 'deleted' );
		}

		check_admin_referer( 'zoyomart_pdm_save_profile', 'zoyomart_pdm_profile_nonce' );
		$data = wp_unslash( $_POST );
		$name = sanitize_text_field( $data['profile_name'] ?? '' );
		if ( '' === $name ) {
			$this->redirect( 'invalid' );
		}
		$id = sanitize_key( $data['profile_id'] ?? '' );
		if ( '' === $id ) {
			$id = sanitize_title( $name );
		}
		$profile = new ImportProfile(
			$id,
			$name,
			sanitize_text_field( $data['sheet_name'] ?? '' ),
			max( 1, absint( $data['header_row'] ?? 1 ) ),
			max( 1, absint( $data['data_start_row'] ?? 2 ) ),
			array_map( 'sanitize_text_field', (array) ( $data['column_mapping'] ?? array() ) )
		);
		$this->profiles->save( $profile );
		$this->redirect( 'saved', $id );
	}

	private function redirect( string $notice, string $id = '' ): void {
		$args = array( 'page' => 'zoyomart-import-profiles', 'notice' => $notice );
		if ( '' !== $id ) {
			$args['profile'] = $id;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}
}
