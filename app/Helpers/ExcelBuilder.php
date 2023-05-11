<?php

namespace App\Helpers;
use \PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use \PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;

class ExcelBuilder
{
    private string $sample;

    public function __construct(string $sample)
    {
        $this->sample = $sample;
    }

    public function toArray() {
        return excel2Array($this->sample);
    }

    public function dump() {
        //$raw = excel2Array($this->sample);
        $this->write();
    }

    public function write() {
        $reader = new XlsxReader();

        /** Load $inputFileName to a Spreadsheet Object  */

//        $inputFileType = IOFactory::identify($inputFileName);
        //      $spreadsheet = $reader->load($inputFileType);
        /**  Create a new Reader of the type defined in $inputFileType  */
        ///$reader = IOFactory::createReader('xlsx');
        /**  Load $inputFileName to a Spreadsheet Object  */
        $spreadsheet = $reader->load($this->sample);
        $sheet = $spreadsheet->getSheet(0);
        $sheet->setCellValue("A3", "Hello World");
        $sheet->insertNewRowBefore(30, 1);
        $writer = new XlsxWriter($spreadsheet);
        $writer->save("test.xlsx");
    }
}
