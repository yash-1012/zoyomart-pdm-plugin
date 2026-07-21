<?php

$notice = get_transient( 'zoyomart_import_notice_' . get_current_user_id() );
delete_transient( 'zoyomart_import_notice_' . get_current_user_id() );

?>

<div class="wrap">

    <h1>Product Importer</h1>

    <?php if ( ! empty( $notice['error'] ) ) : ?>
        <div class="notice notice-error"><p><?php echo esc_html( $notice['error'] ); ?></p></div>
    <?php elseif ( is_array( $notice ) ) : ?>
        <div class="notice notice-success"><p>
            <?php
            printf(
                esc_html__( 'Import complete: %1$d created, %2$d updated, %3$d skipped, %4$d failed.', 'zoyomart-product-importer' ),
                (int) $notice['created'],
                (int) $notice['updated'],
                (int) $notice['skipped'],
                (int) $notice['failed']
            );
            ?>
        </p></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">

        <input type="hidden" name="action" value="zoyomart_import_products">

        <?php wp_nonce_field(
            'zoyomart_upload_excel',
            'zoyomart_nonce'
        ); ?>

        <table class="form-table">

            <tr>

                <th>Select Excel File</th>

                <td>

                    <input
                        type="file"
                        name="import_file"
                        accept=".xlsx,.xls,.csv"
                        required>

                </td>

            </tr>

        </table>

        <?php submit_button( 'Import Products' ); ?>

    </form>

</div>
