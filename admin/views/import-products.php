<div class="wrap">

    <h1>Product Importer</h1>

    <form method="post" enctype="multipart/form-data">

        <?php wp_nonce_field( 'zoyomart_upload_excel', 'zoyomart_nonce' ); ?>

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

</div>