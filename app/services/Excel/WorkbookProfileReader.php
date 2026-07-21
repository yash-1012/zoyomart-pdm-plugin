<?php

namespace Zoyomart\PDM\Services\Excel;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Zoyomart\PDM\Domain\Import\ImportProfile;

/**
 * Reads supplier workbooks using a profile's sheet and header configuration.
 */
final class WorkbookProfileReader {
	public function inspect( string $file_path, ImportProfile $profile ): array {
		$spreadsheet = IOFactory::load( $file_path );
		try {
			$sheet_names = $spreadsheet->getSheetNames();
			$worksheet  = $spreadsheet->getSheetByName( $profile->sheet_name() );
			if ( null === $worksheet ) {
				throw new InvalidArgumentException( sprintf( 'The sheet "%s" was not found.', $profile->sheet_name() ) );
			}
			$headers = array_values( $worksheet->rangeToArray( 'A' . $profile->header_row() . ':' . $worksheet->getHighestColumn() . $profile->header_row(), null, true, true, false )[0] );
			return array(
				'sheet_names' => $sheet_names,
				'headers' => $headers,
				'missing_mappings' => $this->missing_mappings( $headers, $profile->column_mapping() ),
			);
		} finally {
			$spreadsheet->disconnectWorksheets();
		}
	}

	public function missing_mappings( array $headers, array $mapping ): array {
		$available = array_map( array( $this, 'normalise_header' ), $headers );
		$missing   = array();
		foreach ( $mapping as $field => $header ) {
			if ( ! in_array( $this->normalise_header( $header ), $available, true ) ) {
				$missing[ $field ] = $header;
			}
		}
		return $missing;
	}

	public function preview( string $file_path, ImportProfile $profile, int $limit = 10 ): array {
		$spreadsheet = IOFactory::load( $file_path );
		try {
			$worksheet = $spreadsheet->getSheetByName( $profile->sheet_name() );
			if ( null === $worksheet ) {
				throw new InvalidArgumentException( sprintf( 'The sheet "%s" was not found.', $profile->sheet_name() ) );
			}
			$headers = array_values( $worksheet->rangeToArray( 'A' . $profile->header_row() . ':' . $worksheet->getHighestColumn() . $profile->header_row(), null, true, true, false )[0] );
			$lookup = array_flip( array_map( array( $this, 'normalise_header' ), $headers ) );
			$end_row = min( $worksheet->getHighestDataRow(), $profile->data_start_row() + $limit - 1 );
			$source_rows = $end_row >= $profile->data_start_row()
				? $worksheet->rangeToArray( 'A' . $profile->data_start_row() . ':' . $worksheet->getHighestColumn() . $end_row, null, true, true, false )
				: array();
			$rows = array();
			foreach ( $source_rows as $offset => $source_row ) {
				$mapped = array();
				foreach ( $profile->column_mapping() as $field => $header ) {
					$index = $lookup[ $this->normalise_header( $header ) ] ?? null;
					$mapped[ $field ] = null === $index ? '' : (string) ( $source_row[ $index ] ?? '' );
				}
				$rows[] = array( 'row_number' => $profile->data_start_row() + $offset, 'data' => $mapped );
			}
			return $rows;
		} finally {
			$spreadsheet->disconnectWorksheets();
		}
	}

	/**
	 * Streams non-empty data rows so validation can process large files in batches.
	 */
	public function mapped_rows( string $file_path, ImportProfile $profile ): \Generator {
		$spreadsheet = IOFactory::load( $file_path );
		try {
			$worksheet = $spreadsheet->getSheetByName( $profile->sheet_name() );
			if ( null === $worksheet ) {
				throw new InvalidArgumentException( sprintf( 'The sheet "%s" was not found.', $profile->sheet_name() ) );
			}
			$headers = array_values( $worksheet->rangeToArray( 'A' . $profile->header_row() . ':' . $worksheet->getHighestColumn() . $profile->header_row(), null, true, true, false )[0] );
			$lookup = array_flip( array_map( array( $this, 'normalise_header' ), $headers ) );
			$last_row = $worksheet->getHighestDataRow();
			for ( $row_number = $profile->data_start_row(); $row_number <= $last_row; ++$row_number ) {
				$source = array_values( $worksheet->rangeToArray( 'A' . $row_number . ':' . $worksheet->getHighestColumn() . $row_number, null, true, true, false )[0] );
				$data = $this->map_row( $source, $lookup, $profile->column_mapping() );
				if ( array_filter( $data, static fn( $value ): bool => '' !== trim( (string) $value ) ) ) {
					yield array( 'row_number' => $row_number, 'data' => $data );
				}
			}
		} finally {
			$spreadsheet->disconnectWorksheets();
		}
	}

	private function normalise_header( $header ): string {
		return strtolower( preg_replace( '/\s+/', ' ', trim( (string) $header ) ) );
	}

	private function map_row( array $source_row, array $lookup, array $mapping ): array {
		$mapped = array();
		foreach ( $mapping as $field => $header ) {
			$index = $lookup[ $this->normalise_header( $header ) ] ?? null;
			$mapped[ $field ] = null === $index ? '' : (string) ( $source_row[ $index ] ?? '' );
		}
		return $mapped;
	}
}
