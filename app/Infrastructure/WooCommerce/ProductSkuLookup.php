<?php

namespace Zoyomart\PDM\Infrastructure\WooCommerce;

/**
 * Resolves product IDs by SKU in a single query per validation batch.
 */
final class ProductSkuLookup {
	public function find_existing( array $skus ): array {
		global $wpdb;

		$skus = array_values( array_unique( array_filter( array_map( 'strval', $skus ) ) ) );
		if ( ! $skus ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $skus ), '%s' ) );
		$query = "SELECT postmeta.meta_value AS sku, postmeta.post_id AS product_id
			FROM {$wpdb->postmeta} AS postmeta
			INNER JOIN {$wpdb->posts} AS posts ON posts.ID = postmeta.post_id
			WHERE postmeta.meta_key = '_sku'
			AND postmeta.meta_value IN ({$placeholders})
			AND posts.post_type IN ('product', 'product_variation')
			AND posts.post_status NOT IN ('trash', 'auto-draft')";
		$results = $wpdb->get_results( $wpdb->prepare( $query, $skus ), ARRAY_A );
		return wp_list_pluck( $results, 'product_id', 'sku' );
	}
}
