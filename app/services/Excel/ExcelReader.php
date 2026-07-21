<?php

namespace Zoyomart\PDM\Services\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader {
	private $spreadsheet;

	public function load( string $file_path ): bool {
		$this->spreadsheet = IOFactory::load( $file_path );

		return true;
	}

	public function get_sheet_names(): array {
		return $this->spreadsheet->getSheetNames();
	}

	public function get_sheet( string $sheet_name ) {
		return $this->spreadsheet->getSheetByName( $sheet_name );
	}
}
