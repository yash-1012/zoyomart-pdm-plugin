<?php

use Zoyomart\PDM\Domain\Import\ImportProfile;
use Zoyomart\PDM\Infrastructure\WordPress\ImportProfileRepository;

$repository = new ImportProfileRepository();
$profile_id = isset( $_GET['profile'] ) ? sanitize_key( wp_unslash( $_GET['profile'] ) ) : '';
$profile    = $profile_id ? $repository->find( $profile_id ) : null;
$profile    = $profile ?: new ImportProfile( '', '', 'Sheet1', 2, 3, ImportProfile::default_mapping() );
$notice     = isset( $_GET['notice'] ) ? sanitize_key( wp_unslash( $_GET['notice'] ) ) : '';
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Import Profiles', 'zoyomart-product-importer' ); ?></h1>

    <?php if ( 'saved' === $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Import profile saved.', 'zoyomart-product-importer' ); ?></p></div>
    <?php elseif ( 'deleted' === $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Import profile deleted.', 'zoyomart-product-importer' ); ?></p></div>
    <?php elseif ( 'invalid' === $notice ) : ?>
        <div class="notice notice-error"><p><?php esc_html_e( 'A supplier name is required.', 'zoyomart-product-importer' ); ?></p></div>
    <?php endif; ?>

    <p><?php esc_html_e( 'Profiles define a supplier workbook without relying on fixed column positions.', 'zoyomart-product-importer' ); ?></p>

    <h2><?php echo $profile_id ? esc_html__( 'Edit profile', 'zoyomart-product-importer' ) : esc_html__( 'Add profile', 'zoyomart-product-importer' ); ?></h2>
    <form method="post">
        <input type="hidden" name="zoyomart_pdm_profile_action" value="save">
        <input type="hidden" name="profile_id" value="<?php echo esc_attr( $profile->id() ); ?>">
        <?php wp_nonce_field( 'zoyomart_pdm_save_profile', 'zoyomart_pdm_profile_nonce' ); ?>
        <table class="form-table" role="presentation">
            <tr><th scope="row"><label for="profile_name"><?php esc_html_e( 'Supplier name', 'zoyomart-product-importer' ); ?></label></th><td><input class="regular-text" id="profile_name" name="profile_name" required value="<?php echo esc_attr( $profile->name() ); ?>"></td></tr>
            <tr><th scope="row"><label for="sheet_name"><?php esc_html_e( 'Sheet name', 'zoyomart-product-importer' ); ?></label></th><td><input class="regular-text" id="sheet_name" name="sheet_name" required value="<?php echo esc_attr( $profile->sheet_name() ); ?>"></td></tr>
            <tr><th scope="row"><label for="header_row"><?php esc_html_e( 'Header row', 'zoyomart-product-importer' ); ?></label></th><td><input id="header_row" name="header_row" type="number" min="1" value="<?php echo esc_attr( $profile->header_row() ); ?>"></td></tr>
            <tr><th scope="row"><label for="data_start_row"><?php esc_html_e( 'Data start row', 'zoyomart-product-importer' ); ?></label></th><td><input id="data_start_row" name="data_start_row" type="number" min="1" value="<?php echo esc_attr( $profile->data_start_row() ); ?>"></td></tr>
        </table>

        <h3><?php esc_html_e( 'Column mapping', 'zoyomart-product-importer' ); ?></h3>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e( 'PDM field', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Workbook header', 'zoyomart-product-importer' ); ?></th></tr></thead>
            <tbody>
            <?php foreach ( ImportProfile::FIELDS as $field => $label ) : ?>
                <tr><td><?php echo esc_html( $label ); ?></td><td><input class="regular-text" name="column_mapping[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $profile->column_mapping()[ $field ] ?? '' ); ?>"></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php submit_button( $profile_id ? __( 'Save profile', 'zoyomart-product-importer' ) : __( 'Create profile', 'zoyomart-product-importer' ) ); ?>
    </form>

    <h2><?php esc_html_e( 'Saved profiles', 'zoyomart-product-importer' ); ?></h2>
    <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Supplier', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Sheet', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Rows', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Actions', 'zoyomart-product-importer' ); ?></th></tr></thead><tbody>
    <?php foreach ( $repository->all() as $saved_profile ) : ?>
        <tr><td><?php echo esc_html( $saved_profile->name() ); ?></td><td><?php echo esc_html( $saved_profile->sheet_name() ); ?></td><td><?php echo esc_html( $saved_profile->header_row() . ' / ' . $saved_profile->data_start_row() ); ?></td><td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'zoyomart-import-profiles', 'profile' => $saved_profile->id() ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'zoyomart-product-importer' ); ?></a> | <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'zoyomart-import-profiles', 'zoyomart_pdm_delete_profile' => $saved_profile->id() ), admin_url( 'admin.php' ) ), 'zoyomart_pdm_delete_profile_' . $saved_profile->id() ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this import profile?', 'zoyomart-product-importer' ) ); ?>');"><?php esc_html_e( 'Delete', 'zoyomart-product-importer' ); ?></a></td></tr>
    <?php endforeach; ?>
    <?php if ( ! $repository->all() ) : ?><tr><td colspan="4"><?php esc_html_e( 'No profiles saved yet.', 'zoyomart-product-importer' ); ?></td></tr><?php endif; ?>
    </tbody></table>
</div>
