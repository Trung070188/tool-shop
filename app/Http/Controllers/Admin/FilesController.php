<?php

namespace App\Http\Controllers\Admin;

use App\Models\File;
use Illuminate\Http\Request;
use Q\FileManager\Drivers\QFileManagerMysql;

class FilesController extends AdminBaseController
{
    private QFileManagerMysql $fileManager;

    public function __construct()
    {
        $this->fileManager = new QFileManagerMysql();
    }

    public function index()
    {
        return view('admin.layouts.file_manager');
    }

    public function data(Request $request)
    {
        return $this->fileManager->get($request);
    }

    /**
     * error: 0
     * name: "10951873_433905603431654_87195138_n.jpg"
     * size: 55820
     * tmp_name: "C:\xampp74\tmp\php9E00.tmp"
     * type: "image/jpeg".
     *
     * @return array
     */
    public function upload(Request $request)
    {
        return $this->fileManager->upload($request)->toArray();
    }

    public function rename(Request $request)
    {
        $id = $request->get('id');
        $newName = trim($request->get('name'));

        if (!$newName) {
            return [
                'code' => 404,
                'message' => 'Vui lòng nhập tên file mới'
            ];
        }

        $file = File::where('id', $id)->first();

        if (!$file) {
            return [
                'code' => 404,
                'message' => 'File not found'
            ];
        }

        $file->name = $newName;
        $file->save();

        return [
            'code' => 200,
            'message' => 'Đã lưu'
        ];
    }

    public function remove(Request $request)
    {
        $ids = $request->get('ids');
        foreach($ids as $id) {
            if ($id) {
                $file = File::where('id', $id)->first();
            }
            if (!$file) {
                return [
                    'code' => 404,
                    'message' => 'File not found'
                ];
            }
    
            try {
                if (is_file($file->path)) {
                    @unlink($file->path);
                }
    
                $file->delete();
    
                return [
                    'code' => 200,
                    'message' => 'Đã xóa file'
                ];
            } catch (\Exception $e) {
                return [
                    'code' => 503,
                    'message' => $e->getMessage()
                ];
            }
        }
    }

    public function addFolder(Request $req)
    {
        return $this->fileManager->addFolder($req);
    }
}
