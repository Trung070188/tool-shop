<?php

namespace App\Services;

use ZipArchive;

class ZipService
{

    /**
     * @throws \Exception
     */
    public static function zip($inputFileName): string
    {
        $zip = new ZipArchive;
        $password = config_env('APP_ZIP_PASSWORD');
        if (empty($password)) {
            throw new \Exception("env.APP_ZIP_PASSWORD is empty");
        }

        $pathinfo = pathinfo($inputFileName);

        $dirname = $pathinfo['dirname'];
        $outputZipFileName = $pathinfo['filename'] . '.zip';

        $res = $zip->open($dirname . '/'.$outputZipFileName, ZipArchive::CREATE); //Add your file name
        if ($res === TRUE) {
            $zip->addFromString($pathinfo['basename'], file_get_contents($inputFileName)); //Add your file name
            $zip->setEncryptionName($pathinfo['basename'], ZipArchive::EM_AES_256, $password); //Add file name and password dynamically
            $zip->close();
            return $outputZipFileName;
        }

        throw new \Exception("Zip file failed");
    }
}
