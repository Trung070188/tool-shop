<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxService
{
    private static function convertValue($value)
    {
        if (is_array($value) || $value instanceof \stdClass) {
            return json_encode($value, JSON_PRETTY_PRINT);
        }

        return $value;
    }

    public static function exportChunk($toExports, $filename, $chunkCallback)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $index = 0;

        foreach ($toExports as $key) {
            $col = excelColumnFromIndex($index);
            $sheet->setCellValue($col . '1', $key);

            ++$index;
        }

        $chunkCallback(function($entries) use($toExports, $sheet) {
            foreach ($entries as $index => $entry) {
                $idx = $index + 2;

                if (!is_array($entry)) {
                    $entry = (array)$entry;
                }

                $index = 0;

                foreach ($toExports as $key) {
                    $value = self::convertValue(data_get($entry, $key));
                    $col = excelColumnFromIndex($index);
                    $sheet->setCellValue("{$col}{$idx}", $value);
                    ++$index;
                }
            }
        });

        $writer = new Xlsx($spreadsheet);


        // Write file to the browser
        $writer->save($filename);
    }

    /**
     * @throws \Exception
     */
    public static function exportChunkZip($toExports, $filename, $chunkCallback): void
    {
        self::exportChunk($toExports, $filename, $chunkCallback);
        $output = ZipService::zip($filename);
        @unlink($filename);
        echo "Saved to $output\n";
    }

    /**
     * @throws \Exception
     */
    public static function exportZip($toExports, $entries, $filename, $php_output=false): void
    {
        self::export($toExports,$entries, $filename);
        $output = ZipService::zip($filename);
        @unlink($filename);
        echo "Saved to $output\n";
    }

    public static function export($toExports, $entries, $filename, $php_output=false)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $index = 0;

        foreach ($toExports as $key => $label) {
            $col = excelColumnFromIndex($index);
            $sheet->setCellValue($col . '1', $label);

            ++$index;
        }

        foreach ($entries as $index => $entry) {
            $idx = $index + 2;

            if (!is_array($entry)) {
                $entry = (array)$entry;
            }

            $index = 0;

            foreach ($toExports as $key => $label) {
                $value = self::convertValue(data_get($entry, $key));
                $col = excelColumnFromIndex($index);
                $sheet->setCellValue("{$col}{$idx}", $value);
                ++$index;
            }
        }

        $writer = new Xlsx($spreadsheet);

        // It will be called file.xls
        if ($php_output) {
            header('Content-type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            $writer->save('php://output');
            die;
        }

        // Write file to the browser
        $writer->save($filename);
    }

}
