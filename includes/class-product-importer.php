<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates or updates WooCommerce products using SKU as the stable identifier.
 */
class Zoyomart_Product_Importer {
	private const COLUMNS = array(
		'brand' => 0, 'variant' => 1, 'sku' => 2, 'name' => 3, 'short_description' => 4,
		'description' => 5, 'gst' => 6, 'hsn' => 7, 'stock' => 8, 'moq' => 9,
		'unit' => 10, 'mrp' => 11, 'discount' => 12, 'price' => 13, 'weight' => 14,
		'attributes' => 15, 'main_image' => 16, 'gallery_1' => 17, 'gallery_2' => 18,
		'gallery_3' => 19, 'gallery_4' => 20, 'video' => 21, 'category' => 22,
		'fitment' => 23, 'car_details' => 24, 'options' => 25, 'warranty_applicable' => 26,
		'warranty_period' => 27, 'tags' => 28,
	);

	public function import_sheet( array $rows ) {
		$summary = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0 );

		// The supplied Zoyomart template contains its labels in row 2.
		foreach ( array_slice( $rows, 2 ) as $row ) {
			try {
				$result = $this->import( $row );
				if ( isset( $summary[ $result ] ) ) {
					++$summary[ $result ];
				}
			} catch ( Throwable $exception ) {
				++$summary['failed'];
				error_log( sprintf( 'Zoyomart importer: SKU "%s" failed: %s', $this->value( $row, 'sku' ), $exception->getMessage() ) );
			}
		}

		return $summary;
	}

	public function import( array $row ) {
		$sku = $this->value( $row, 'sku' );
		$name = $this->value( $row, 'name' );

		if ( '' === $sku || '' === $name ) {
			return 'skipped';
		}

		$product_id = wc_get_product_id_by_sku( $sku );
		$existing   = $product_id ? wc_get_product( $product_id ) : false;
		$is_variable = $this->is_yes( $this->value( $row, 'variant' ) ) && '' !== $this->value( $row, 'options' );

		if ( $existing && ( $is_variable !== $existing->is_type( 'variable' ) ) ) {
			wp_set_object_terms( $product_id, $is_variable ? 'variable' : 'simple', 'product_type' );
			$existing = wc_get_product( $product_id );
		}

		$product = $existing ?: ( $is_variable ? new WC_Product_Variable() : new WC_Product_Simple() );
		$this->set_product_data( $product, $row, $is_variable );
		$product_id = $product->save();

		$this->set_categories( $product_id, $this->value( $row, 'category' ) );
		$this->set_tags( $product_id, $this->value( $row, 'tags' ) );
		$this->set_meta( $product_id, $row );
		$this->set_images( $product, $row );

		if ( $is_variable ) {
			$this->set_variations( $product_id, $row );
		}

		wc_delete_product_transients( $product_id );
		return $existing ? 'updated' : 'created';
	}

	public function import_related_products( array $rows ) {
		foreach ( array_slice( $rows, 1 ) as $row ) {
			$sku = trim( (string) ( $row[0] ?? '' ) );
			if ( '' === $sku || ! ( $product_id = wc_get_product_id_by_sku( $sku ) ) ) {
				continue;
			}

			$related_skus = array_filter( array_map( 'trim', explode( ',', (string) ( $row[1] ?? '' ) ) ) );
			$related_ids  = array();
			foreach ( $related_skus as $related_sku ) {
				$related_id = wc_get_product_id_by_sku( $related_sku );
				if ( $related_id && $related_id !== $product_id ) {
					$related_ids[] = $related_id;
				}
			}
			update_post_meta( $product_id, '_zoyomart_related_product_ids', array_values( array_unique( $related_ids ) ) );
		}
	}

	private function set_product_data( WC_Product $product, array $row, $is_variable ) {
		$product->set_sku( $this->value( $row, 'sku' ) );
		$product->set_name( $this->value( $row, 'name' ) );
		$product->set_short_description( $this->value( $row, 'short_description' ) );
		$product->set_description( $this->value( $row, 'description' ) );

		if ( ! $is_variable ) {
			$this->set_price( $product, $this->value( $row, 'price' ), $this->value( $row, 'mrp' ) );
			$this->set_stock( $product, $this->value( $row, 'stock' ) );
		}

		if ( '' !== $this->value( $row, 'weight' ) ) {
			$product->set_weight( $this->normalise_number( $this->value( $row, 'weight' ) ) );
		}

		$attributes = $this->parse_attributes( $this->value( $row, 'attributes' ) );
		if ( $is_variable ) {
			$attributes = array_merge( $attributes, $this->variation_attributes( $this->value( $row, 'options' ) ) );
		}
		$product->set_attributes( $attributes );
	}

	private function set_price( WC_Product $product, $selling_price, $mrp ) {
		$selling_price = $this->normalise_number( $selling_price );
		$mrp           = $this->normalise_number( $mrp );
		if ( '' === $selling_price ) {
			return;
		}
		$product->set_regular_price( '' !== $mrp ? $mrp : $selling_price );
		$product->set_sale_price( '' !== $mrp && (float) $selling_price < (float) $mrp ? $selling_price : '' );
	}

	private function set_stock( WC_Product $product, $stock ) {
		if ( '' === $stock || ! is_numeric( $stock ) ) {
			return;
		}
		$product->set_manage_stock( true );
		$product->set_stock_quantity( (int) $stock );
		$product->set_stock_status( (int) $stock > 0 ? 'instock' : 'outofstock' );
	}

	private function set_meta( $product_id, array $row ) {
		$meta = array(
			'_zoyomart_brand' => 'brand', '_hsn_code' => 'hsn', '_gst_rate' => 'gst',
			'_zoyomart_moq' => 'moq', '_zoyomart_unit' => 'unit', '_zoyomart_discount' => 'discount',
			'_zoyomart_video_url' => 'video', '_zoyomart_fitment_type' => 'fitment',
			'_zoyomart_car_details' => 'car_details', '_zoyomart_warranty_applicable' => 'warranty_applicable',
			'_zoyomart_warranty_period' => 'warranty_period', '_zoyomart_options' => 'options',
		);
		foreach ( $meta as $meta_key => $column ) {
			$value = $this->value( $row, $column );
			if ( '' !== $value ) {
				update_post_meta( $product_id, $meta_key, $value );
			}
		}
	}

	private function set_categories( $product_id, $categories ) {
		$ids = array();
		if ( '' === trim( $categories ) ) {
			return;
		}
		foreach ( preg_split( '/\s*,\s*/', $categories ) as $path ) {
			$parent = 0;
			foreach ( array_filter( array_map( 'trim', explode( '>', $path ) ) ) as $name ) {
				$term = term_exists( $name, 'product_cat', $parent );
				if ( ! $term ) {
					$term = wp_insert_term( $name, 'product_cat', array( 'parent' => $parent ) );
				}
				if ( is_wp_error( $term ) ) {
					break;
				}
				$parent = (int) ( is_array( $term ) ? $term['term_id'] : $term );
			}
			if ( $parent ) {
				$ids[] = $parent;
			}
		}
		if ( $ids ) {
			wp_set_object_terms( $product_id, array_values( array_unique( $ids ) ), 'product_cat', false );
		}
	}

	private function set_tags( $product_id, $tags ) {
		if ( '' === trim( $tags ) ) {
			return;
		}
		$tags = array_filter( array_map( 'trim', explode( ',', $tags ) ) );
		if ( $tags ) {
			wp_set_object_terms( $product_id, $tags, 'product_tag', false );
		}
	}

	private function parse_attributes( $source ) {
		$attributes = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $source ) as $line ) {
			$parts = array_map( 'trim', explode( ':', $line, 2 ) );
			if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
				continue;
			}
			$attribute = new WC_Product_Attribute();
			$attribute->set_name( $parts[0] );
			$attribute->set_options( array_filter( array_map( 'trim', preg_split( '/\s*[,|]\s*/', $parts[1] ) ) ) );
			$attribute->set_visible( true );
			$attribute->set_variation( false );
			$attributes[ sanitize_title( $parts[0] ) ] = $attribute;
		}
		return $attributes;
	}

	private function variation_attributes( $source ) {
		$attributes = array();
		foreach ( $this->parse_options( $source ) as $option ) {
			$key = sanitize_title( $option['name'] );
			if ( isset( $attributes[ $key ] ) ) {
				$attributes[ $key ]->set_options( array_unique( array_merge( $attributes[ $key ]->get_options(), array( $option['value'] ) ) ) );
				continue;
			}
			$attribute = new WC_Product_Attribute();
			$attribute->set_name( $option['name'] );
			$attribute->set_options( array( $option['value'] ) );
			$attribute->set_visible( true );
			$attribute->set_variation( true );
			$attributes[ $key ] = $attribute;
		}
		return $attributes;
	}

	private function set_variations( $parent_id, array $row ) {
		$options = $this->parse_options( $this->value( $row, 'options' ) );
		foreach ( $options as $option ) {
			$found = false;
			foreach ( wc_get_products( array( 'type' => 'variation', 'parent' => $parent_id, 'limit' => -1 ) ) as $variation ) {
				if ( $variation->get_attributes() === array( sanitize_title( $option['name'] ) => $option['value'] ) ) {
					$found = $variation;
					break;
				}
			}
			$variation = $found ?: new WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			$variation->set_attributes( array( sanitize_title( $option['name'] ) => $option['value'] ) );
			$this->set_price( $variation, $this->add_price( $this->value( $row, 'price' ), $option['retail_increment'] ), $this->add_price( $this->value( $row, 'mrp' ), $option['mrp_increment'] ) );
			if ( is_numeric( $option['quantity'] ) ) {
				$variation->set_manage_stock( $this->is_yes( $option['subtract_stock'] ) );
				$variation->set_stock_quantity( (int) $option['quantity'] );
				$variation->set_stock_status( (int) $option['quantity'] > 0 ? 'instock' : 'outofstock' );
			}
			if ( '' !== $this->normalise_number( $option['weight_increment'] ) ) {
				$variation->set_weight( $this->add_price( $this->value( $row, 'weight' ), $option['weight_increment'] ) );
			}
			$variation->save();
		}
		WC_Product_Variable::sync( $parent_id );
	}

	private function parse_options( $source ) {
		$options = array();
		foreach ( array_filter( array_map( 'trim', explode( ',', $source ) ) ) as $entry ) {
			$parts = array_map( 'trim', explode( '-', $entry ) );
			if ( count( $parts ) < 2 || '' === $parts[0] || '' === $parts[1] ) {
				continue;
			}
			$options[] = array(
				'name' => $parts[0], 'value' => $parts[1], 'quantity' => $parts[3] ?? '', 'subtract_stock' => $parts[4] ?? '',
				'retail_increment' => $parts[10] ?? '', 'mrp_increment' => $parts[11] ?? '', 'weight_increment' => $parts[12] ?? '',
			);
		}
		return $options;
	}

	private function set_images( WC_Product $product, array $row ) {
		$image_urls = array_filter( array_map( array( $this, 'valid_url' ), array(
			$this->value( $row, 'main_image' ), $this->value( $row, 'gallery_1' ), $this->value( $row, 'gallery_2' ),
			$this->value( $row, 'gallery_3' ), $this->value( $row, 'gallery_4' ),
		) ) );
		if ( ! $image_urls ) {
			return;
		}
		$ids = array();
		foreach ( $image_urls as $url ) {
			$attachment_id = $this->attachment_id_from_url( $url );
			if ( ! $attachment_id ) {
				$attachment_id = $this->sideload_image( $url, $product->get_id() );
			}
			if ( $attachment_id ) {
				$ids[] = $attachment_id;
			}
		}
		if ( $ids ) {
			$product->set_image_id( array_shift( $ids ) );
			$product->set_gallery_image_ids( array_values( array_unique( $ids ) ) );
			$product->save();
		}
	}

	private function sideload_image( $url, $product_id ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$file = download_url( $url, 30 );
		if ( is_wp_error( $file ) ) {
			return 0;
		}
		$attachment_id = media_handle_sideload( array( 'name' => wp_basename( wp_parse_url( $url, PHP_URL_PATH ) ) ?: 'product-image.jpg', 'tmp_name' => $file ), $product_id );
		if ( is_wp_error( $attachment_id ) ) {
			wp_delete_file( $file );
			return 0;
		}
		update_post_meta( $attachment_id, '_zoyomart_source_image_url', $url );
		return (int) $attachment_id;
	}

	private function attachment_id_from_url( $url ) {
		$ids = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'fields' => 'ids', 'posts_per_page' => 1, 'meta_key' => '_zoyomart_source_image_url', 'meta_value' => $url ) );
		return $ids ? (int) $ids[0] : 0;
	}

	private function valid_url( $url ) {
		$url = esc_url_raw( trim( (string) $url ) );
		return $url && wp_http_validate_url( $url ) ? $url : '';
	}

	private function value( array $row, $column ) { return trim( (string) ( $row[ self::COLUMNS[ $column ] ] ?? '' ) ); }
	private function is_yes( $value ) { return in_array( strtolower( trim( (string) $value ) ), array( 'yes', 'y', '1', 'true' ), true ); }
	private function normalise_number( $value ) { return is_numeric( str_replace( ',', '', (string) $value ) ) ? (string) str_replace( ',', '', $value ) : ''; }
	private function add_price( $base, $increment ) { $base = $this->normalise_number( $base ); $increment = $this->normalise_number( $increment ); return '' === $base ? '' : (string) ( (float) $base + ( '' === $increment ? 0 : (float) $increment ) ); }
}
