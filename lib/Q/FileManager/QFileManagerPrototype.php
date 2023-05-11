<?php

namespace Q\FileManager;

use Illuminate\Http\Request;
use Q\FileManager\Types\QFile;
use Q\FileManager\Types\QFileAddFolderResponse;
use Q\FileManager\Types\QFileGetResponse;
use Q\FileManager\Types\QFileResponse;
use Q\FileManager\Types\QFileUploadResponse;

interface QFileManagerPrototype
{
    /**
     * @param Request $request
     * @return QFile[]
     */
    public function get(Request $request) : QFileGetResponse;
    public function upload(Request $request): QFileUploadResponse;
    public function addFolder(Request $request) : QFileAddFolderResponse;
    public function move(Request $request) : QFileResponse;
    public function remove(Request $request): QFileResponse;
    public function rename(Request $request): QFileResponse;
    public function currentUser();

}
