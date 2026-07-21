<?php

namespace Zoyomart\PDM\Services\Import;

use Zoyomart\PDM\Infrastructure\WooCommerce\ProductSkuLookup;

/**
 * Produces an import report without creating or changing WordPress data.
 */
final class DryRunValidator {
	private const BATCH_SIZE = 250;
	private const ISSUE_LIMIT = 100;

	private ProductSkuLookup $sku_lookup;
	private array $seen_skus = array();
	private array $virtual_categories = array();
	private array $report = array();

	public function __construct( ProductSkuLookup $sku_lookup ) {
		$this->sku_lookup = $sku_lookup;
	}

	public function validate( \Generator $rows ): array {
		$this->report = array(
			'processed' => 0, 'to_create' => 0, 'to_update' => 0, 'skipped' => 0,
			'categories_to_create' => 0, 'images_missing' => 0, 'errors' => 0,
			'warnings' => 0, 'issues' => array(),
		);
		$batch = array();
		foreach ( $rows as $row ) {
			$batch[] = $row;
			if ( count( $batch ) >= self::BATCH_SIZE ) {
				$this->validate_batch( $batch );
				$batch = array();
			}
		}
		if ( $batch ) {
			$this->validate_batch( $batch );
		}
		return $this->report;
	}

	private function validate_batch( array $rows ): void {
		$skus = array_map( static fn( array $row ): string => trim( (string) ( $row['data']['sku'] ?? '' ) ), $rows );
		$existing = $this->sku_lookup->find_existing( $skus );
		foreach ( $rows as $row ) {
			$this->validate_row( $row, $existing );
		}
	}

	private function validate_row( array $row, array $existing ): void {
		++$this->report['processed'];
		$data = $row['data'];
		$sku = trim( (string) ( $data['sku'] ?? '' ) );
		$valid = true;
		if ( '' === $sku ) {
			$this->issue( 'error', $row['row_number'], __( 'Missing SKU.', 'zoyomart-product-importer' ) );
			$valid = false;
		} elseif ( isset( $this->seen_skus[ $sku ] ) ) {
			$this->issue( 'error', $row['row_number'], sprintf( __( 'Duplicate SKU; first seen on row %d.', 'zoyomart-product-importer' ), $this->seen_skus[ $sku ] ) );
			$valid = false;
		} else {
			$this->seen_skus[ $sku ] = $row['row_number'];
		}
		if ( '' === trim( (string) ( $data['name'] ?? '' ) ) ) {
			$this->issue( 'error', $row['row_number'], __( 'Missing product name.', 'zoyomart-product-importer' ) );
			$valid = false;
		}
		$this->validate_number( $data['selling_price'] ?? '', __( 'selling price', 'zoyomart-product-importer' ), $row['row_number'], $valid );
		$this->validate_number( $data['stock'] ?? '', __( 'stock', 'zoyomart-product-importer' ), $row['row_number'], $valid );
		$this->validate_image( $data['main_image'] ?? '', $row['row_number'], $valid );
		$this->validate_category( $data['category'] ?? '', $row['row_number'] );

		if ( ! $valid ) {
			++$this->report['skipped'];
			return;
		}
		if ( isset( $existing[ $sku ] ) ) {
			++$this->report['to_update'];
		} else {
			++$this->report['to_create'];
		}
	}

	private function validate_number( $value, string $label, int $row_number, bool &$valid ): void {
		$value = trim( (string) $value );
		if ( '' !== $value && ! is_numeric( str_replace( ',', '', $value ) ) ) {
			$this->issue( 'error', $row_number, sprintf( __( 'Invalid %s.', 'zoyomart-product-importer' ), $label ) );
			$valid = false;
		}
	}

	private function validate_image( $url, int $row_number, bool &$valid ): void {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			++$this->report['images_missing'];
			$this->issue( 'warning', $row_number, __( 'Main image is missing.', 'zoyomart-product-importer' ) );
			return;
		}
		if ( ! wp_http_validate_url( esc_url_raw( $url ) ) ) {
			$this->issue( 'error', $row_number, __( 'Invalid main image URL.', 'zoyomart-product-importer' ) );
			$valid = false;
		}
	}

	private function validate_category( $categories, int $row_number ): void {
		$categories = trim( (string) $categories );
		if ( '' === $categories ) {
			$this->issue( 'warning', $row_number, __( 'Category is missing.', 'zoyomart-product-importer' ) );
			return;
		}
		foreach ( preg_split( '/\s*,\s*/', $categories ) as $path ) {
			$this->check_category_path( $path );
		}
	}

	private function check_category_path( string $path ): void {
		$parent_id = 0;
		$path_key = '';
		foreach ( array_filter( array_map( 'trim', explode( '>', $path ) ) ) as $name ) {
			$path_key .= '>' . sanitize_title( $name );
			if ( isset( $this->virtual_categories[ $path_key ] ) ) {
				$parent_id = $this->virtual_categories[ $path_key ];
				continue;
			}
			$term = term_exists( $name, 'product_cat', $parent_id );
			if ( $term ) {
				$parent_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
				continue;
			}
			++$this->report['categories_to_create'];
			$parent_id = -$this->report['categories_to_create'];
			$this->virtual_categories[ $path_key ] = $parent_id;
		}
	}

	private function issue( string $severity, int $row_number, string $message ): void {
		++$this->report[ $severity . 's' ];
		if ( count( $this->report['issues'] ) < self::ISSUE_LIMIT ) {
			$this->report['issues'][] = array( 'severity' => $severity, 'row_number' => $row_number, 'message' => $message );
		}
	}
}
