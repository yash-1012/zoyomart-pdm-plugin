<?php

namespace Zoyomart\PDM\Domain\Import;

/**
 * Immutable configuration for one supplier workbook format.
 */
final class ImportProfile {
	public const FIELDS = array(
		'brand' => 'Brand',
		'variant' => 'Variant',
		'sku' => 'SKU',
		'name' => 'Product name',
		'short_description' => 'Short description',
		'description' => 'Long description',
		'gst' => 'GST',
		'hsn' => 'HSN',
		'stock' => 'Current stock',
		'moq' => 'MOQ',
		'unit' => 'Unit',
		'mrp' => 'MRP',
		'discount' => 'Discount',
		'selling_price' => 'Selling price',
		'weight' => 'Weight',
		'attributes' => 'Attributes',
		'main_image' => 'Main image',
		'image_1' => 'Gallery image 1',
		'image_2' => 'Gallery image 2',
		'image_3' => 'Gallery image 3',
		'image_4' => 'Gallery image 4',
		'video' => 'Video',
		'category' => 'Category',
		'fitment_type' => 'Universal / car specific',
		'car_details' => 'Car details',
		'options' => 'Options',
		'warranty' => 'Warranty',
		'warranty_period' => 'Warranty period',
		'tags' => 'Tags',
	);

	private string $id;
	private string $name;
	private string $sheet_name;
	private int $header_row;
	private int $data_start_row;
	private array $column_mapping;

	public function __construct( string $id, string $name, string $sheet_name, int $header_row, int $data_start_row, array $column_mapping ) {
		$this->id             = $id;
		$this->name           = $name;
		$this->sheet_name     = $sheet_name;
		$this->header_row     = $header_row;
		$this->data_start_row = $data_start_row;
		$this->column_mapping = self::clean_mapping( $column_mapping );
	}

	public static function from_array( array $profile ): self {
		return new self(
			(string) ( $profile['id'] ?? '' ),
			(string) ( $profile['name'] ?? '' ),
			(string) ( $profile['sheet_name'] ?? '' ),
			(int) ( $profile['header_row'] ?? 1 ),
			(int) ( $profile['data_start_row'] ?? 2 ),
			(array) ( $profile['column_mapping'] ?? array() )
		);
	}

	public static function default_mapping(): array {
		return array(
			'brand' => 'Brand', 'variant' => 'Variant', 'sku' => 'SKU', 'name' => 'Product Title',
			'short_description' => 'Short Description', 'description' => 'Long Description', 'gst' => 'GST%',
			'hsn' => 'HSN', 'stock' => 'Current Stock', 'moq' => 'MOQ', 'unit' => 'Unit', 'mrp' => 'MRP',
			'discount' => 'Discount', 'selling_price' => 'Selling Price', 'weight' => 'Weight (Optional)',
			'attributes' => 'Attributes', 'main_image' => 'Main Image', 'image_1' => 'Image 1', 'image_2' => 'Image 2',
			'image_3' => 'Image 3', 'image_4' => 'Image 4', 'video' => 'Video', 'category' => 'Category',
			'fitment_type' => 'Universal/ Car Specific',
			'car_details' => 'Car Details(Manufacaturer>Model1>Model2>Model3, "repeat")',
			'options' => 'Options(Option Name-Option Value-Required-Quantity-Substract stock-Increment in b2b price-increment in royal dealer price-increment in Gold dealer price-increment in Platinum dealer price-increment in Silver dealer price-increment in retail price-increment in MRP-increment in weight)',
			'warranty' => 'Warranty Applicable (Yes/No)?', 'warranty_period' => 'Warranty Period',
			'tags' => 'Product tags (Search Keywords)',
		);
	}

	public function to_array(): array {
		return array(
			'id' => $this->id, 'name' => $this->name, 'sheet_name' => $this->sheet_name,
			'header_row' => $this->header_row, 'data_start_row' => $this->data_start_row,
			'column_mapping' => $this->column_mapping,
		);
	}

	public function id(): string { return $this->id; }
	public function name(): string { return $this->name; }
	public function sheet_name(): string { return $this->sheet_name; }
	public function header_row(): int { return $this->header_row; }
	public function data_start_row(): int { return $this->data_start_row; }
	public function column_mapping(): array { return $this->column_mapping; }

	private static function clean_mapping( array $mapping ): array {
		$clean = array();
		foreach ( self::FIELDS as $field => $label ) {
			if ( isset( $mapping[ $field ] ) && '' !== trim( (string) $mapping[ $field ] ) ) {
				$clean[ $field ] = trim( (string) $mapping[ $field ] );
			}
		}
		return $clean;
	}
}
