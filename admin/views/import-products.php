<?php

use Zoyomart\PDM\Domain\Import\ImportProfile;
use Zoyomart\PDM\Infrastructure\WordPress\ImportPreviewRepository;
use Zoyomart\PDM\Infrastructure\WordPress\ImportProfileRepository;

$profiles = ( new ImportProfileRepository() )->all();
$preview  = ( new ImportPreviewRepository() )->get( get_current_user_id() );
$notice   = get_transient( 'zoyomart_pdm_import_notice_' . get_current_user_id() );
delete_transient( 'zoyomart_pdm_import_notice_' . get_current_user_id() );
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Import Products', 'zoyomart-product-importer' ); ?></h1>

    <?php if ( $notice ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $notice ); ?></p></div>
    <?php endif; ?>

    <?php if ( ! $profiles ) : ?>
        <div class="notice notice-warning"><p><?php esc_html_e( 'Create an import profile before previewing a workbook.', 'zoyomart-product-importer' ); ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=zoyomart-import-profiles' ) ); ?>"><?php esc_html_e( 'Create profile', 'zoyomart-product-importer' ); ?></a></p></div>
    <?php else : ?>
        <p><?php esc_html_e( 'Upload a workbook and profile to preview mapped data. This step never creates or changes products.', 'zoyomart-product-importer' ); ?></p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="zoyomart_preview_workbook">
            <?php wp_nonce_field( 'zoyomart_preview_workbook', 'zoyomart_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="profile_id"><?php esc_html_e( 'Import profile', 'zoyomart-product-importer' ); ?></label></th><td><select id="profile_id" name="profile_id" required><option value=""><?php esc_html_e( 'Select a profile', 'zoyomart-product-importer' ); ?></option><?php foreach ( $profiles as $profile ) : ?><option value="<?php echo esc_attr( $profile->id() ); ?>"><?php echo esc_html( $profile->name() . ' — ' . $profile->sheet_name() ); ?></option><?php endforeach; ?></select></td></tr>
                <tr><th scope="row"><label for="import_file"><?php esc_html_e( 'Workbook', 'zoyomart-product-importer' ); ?></label></th><td><input id="import_file" type="file" name="import_file" accept=".xlsx,.xls,.csv" required></td></tr>
            </table>
            <?php submit_button( __( 'Read workbook and preview', 'zoyomart-product-importer' ) ); ?>
        </form>
    <?php endif; ?>

    <?php if ( $preview ) : ?>
        <hr>
        <h2><?php esc_html_e( 'Workbook validation', 'zoyomart-product-importer' ); ?></h2>
        <p><strong><?php esc_html_e( 'File:', 'zoyomart-product-importer' ); ?></strong> <?php echo esc_html( $preview['file_name'] ); ?></p>
        <p><strong><?php esc_html_e( 'Available sheets:', 'zoyomart-product-importer' ); ?></strong> <?php echo esc_html( implode( ', ', $preview['inspection']['sheet_names'] ) ); ?></p>
        <?php if ( empty( $preview['inspection']['missing_mappings'] ) ) : ?>
            <div class="notice notice-success inline"><p><?php esc_html_e( 'All configured column headers were found.', 'zoyomart-product-importer' ); ?></p></div>
        <?php else : ?>
            <div class="notice notice-error inline"><p><?php esc_html_e( 'Some configured headers are missing:', 'zoyomart-product-importer' ); ?> <?php echo esc_html( implode( ', ', $preview['inspection']['missing_mappings'] ) ); ?></p></div>
        <?php endif; ?>

        <h2><?php esc_html_e( 'Mapped data preview', 'zoyomart-product-importer' ); ?></h2>
        <p><?php esc_html_e( 'First 10 data rows, read through the selected profile. No product data has been changed.', 'zoyomart-product-importer' ); ?></p>
        <div style="overflow-x:auto">
            <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Row', 'zoyomart-product-importer' ); ?></th><?php foreach ( ImportProfile::FIELDS as $field => $label ) : ?><th><?php echo esc_html( $label ); ?></th><?php endforeach; ?></tr></thead><tbody>
            <?php foreach ( $preview['preview'] as $row ) : ?><tr><td><?php echo esc_html( $row['row_number'] ); ?></td><?php foreach ( ImportProfile::FIELDS as $field => $label ) : ?><td><?php echo esc_html( $row['data'][ $field ] ?? '' ); ?></td><?php endforeach; ?></tr><?php endforeach; ?>
            </tbody></table>
        </div>

        <form method="post">
            <input type="hidden" name="action" value="zoyomart_dry_run_import">
            <?php wp_nonce_field( 'zoyomart_dry_run_import', 'zoyomart_dry_run_nonce' ); ?>
            <?php submit_button( __( 'Run Dry Run Validation', 'zoyomart-product-importer' ), 'secondary', 'submit', false ); ?>
        </form>

        <?php if ( ! empty( $preview['dry_run'] ) ) : $dry_run = $preview['dry_run']; ?>
            <h2><?php esc_html_e( 'Dry Run Summary', 'zoyomart-product-importer' ); ?></h2>
            <table class="widefat striped"><tbody>
                <tr><th><?php esc_html_e( 'Rows processed', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['processed'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Products to create', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['to_create'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Products to update', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['to_update'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Rows skipped', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['skipped'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Categories to create', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['categories_to_create'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Images missing', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['images_missing'] ); ?></td></tr>
                <tr><th><?php esc_html_e( 'Errors / warnings', 'zoyomart-product-importer' ); ?></th><td><?php echo esc_html( $dry_run['errors'] . ' / ' . $dry_run['warnings'] ); ?></td></tr>
            </tbody></table>
            <?php if ( $dry_run['issues'] ) : ?>
                <h3><?php esc_html_e( 'Validation issues', 'zoyomart-product-importer' ); ?></h3>
                <table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Severity', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Row', 'zoyomart-product-importer' ); ?></th><th><?php esc_html_e( 'Message', 'zoyomart-product-importer' ); ?></th></tr></thead><tbody>
                <?php foreach ( $dry_run['issues'] as $issue ) : ?><tr><td><?php echo esc_html( ucfirst( $issue['severity'] ) ); ?></td><td><?php echo esc_html( $issue['row_number'] ); ?></td><td><?php echo esc_html( $issue['message'] ); ?></td></tr><?php endforeach; ?>
                </tbody></table>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
