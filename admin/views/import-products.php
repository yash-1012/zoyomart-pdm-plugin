<?php

$preview = get_transient( 'zoyomart_excel_preview' );

?>

<div class="wrap">

    <h1>Product Importer</h1>

    <form method="post" enctype="multipart/form-data">

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

        <?php submit_button( 'Upload & Preview' ); ?>

    </form>

    <?php if ( ! empty( $preview ) ) : ?>

        <hr>

        <h2>Excel Preview</h2>

        <p>

            <strong>Total Rows:</strong>

            <?php echo count( $preview ); ?>

        </p>

        <table class="widefat striped">

            <?php

            foreach ( array_slice( $preview, 0, 10 ) as $row ) :

                ?>

                <tr>

                    <?php foreach ( $row as $cell ) : ?>

                        <td>

                            <?php echo esc_html( $cell ); ?>

                        </td>

                    <?php endforeach; ?>

                </tr>

            <?php endforeach; ?>

        </table>

    <?php endif; ?>

</div>