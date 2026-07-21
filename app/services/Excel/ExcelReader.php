<?php

namespace Zoyomart\Services\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ExcelReader
{
    private $spreadsheet;

    public function load(string $filePath): bool
    {
        $this->spreadsheet = IOFactory::load($filePath);

        return true;
    }

    public function getSheetNames(): array
    {
        return $this->spreadsheet->getSheetNames();
    }

    public function getSheet(string $sheetName)
    {
        return $this->spreadsheet->getSheetByName($sheetName);
    }
}