<?php

namespace Q\FileManager\Drivers;

use Illuminate\Http\Request;
use Q\FileManager\QFileManagerPrototype;
use Illuminate\Support\Facades\DB;
use \Illuminate\Database\Query\Builder;
use Q\FileManager\Types\QFile;
use Q\FileManager\Types\QFileAddFolderResponse;
use Q\FileManager\Types\QFileGetResponse;
use Q\FileManager\Types\QFileResponse;
use Q\FileManager\Types\QFileUploadResponse;
use Ramsey\Uuid\Uuid;

class QFileManagerMysql implements QFileManagerPrototype
{
    protected $table = 'files';
    protected $tableUser = 'users';
    protected $tableUserFields = ['id', 'name', 'email'];

    private function query(): Builder
    {
        return DB::table($this->table)->whereNull('deleted_at');
    }

    public function currentUser()
    {
        return auth_user();
    }

    private function getFileTree($id): array
    {
        $result = [];
        $file = $this->query()->find($id);

        if ($file) {
            $result[] = $file;
            if ($file->parent_id) {
                foreach ($this->getFileTree($file->parent_id) as $f) {
                    $result[] = $f;
                }
            }
        }

        return $result;
    }

    private function checkFileNameExists($folderId, string $name): bool
    {
        if ($folderId == null) {
            return $this->query()->whereNull('parent_id')->where('name', $name)->count() > 0;
        }

        return $this->query()->where('parent_id', $folderId)->where('name', $name)->count() > 0;
    }

    public function get(Request $request): QFileGetResponse
    {
        $keyword = $request->get('keyword');
        $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';
        $sortField = $request->get('sort_field');
        $fileType = $request->get('file_type');
        $parentId = $request->get('parent_id');

        if ($parentId === 'null' || $parentId === 'undefined') {
            $parentId = null;
        }

        $allowedSortFiled = [
            'id' => true,
            'name' => true,
            'extension' => true,
            'size' => true,
            'created_at' => true
        ];

        if (!isset($allowedSortFiled[$sortField])) {
            $sortField = 'id';
        }


        $query = $this->query()
            ->orderBy($sortField, $sortDirection);

        $parentFolder = null;
        if ($parentId) {
            $parentFolder = $this->query()->find($parentId);

            if (!$parentFolder) {
                return new QFileGetResponse(5, 'Invalid parent folder');
            }

            $parentFolder->User = DB::table($this->tableUser)->select($this->tableUserFields)->find($parentFolder->user_id);

            $parentFolder = QFile::fromObject($parentFolder);

            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }

        if ($keyword) {
            $query->where('name', 'LIKE', '%'.$keyword.'%');
        }

        if ($fileType === 'image') {
            $query->where('is_image', 1);
        } elseif ($fileType === 'excel') {
            $query->where('extension', 'xlsx');
        }

        $qFiles = [];
        if ($parentId) {
            $qFiles[] = [
                'id' => $parentFolder?->parent_id,
                'name' => '..',
                'is_folder' => true
            ];
        }

        $files = $query->get();
        $userIDs = [];
        foreach ($files as $file) {
            $userIDs[] = $file->user_id;
        }

        $owners = DB::table($this->tableUser)
            ->select($this->tableUserFields)
            ->whereIn('id', $userIDs)
            ->get();

        $ownerMap = [];
        foreach ($owners as $owner) {
            $ownerMap[$owner->id] = $owner;
        }

        foreach ($files as $file) {
            $file->User = $ownerMap[$file->user_id] ?? null;
            $qFiles[] = QFile::fromObject($file);
        }

        return new QFileGetResponse(200, 'OK', [
            'parent' => $parentFolder,
            'paths' => $parentFolder ? $this->getFileTree($parentFolder->id) : [],
            'files' => $qFiles
        ]);
    }

    public function upload(Request $request): QFileUploadResponse
    {
        if (empty($_FILES['file_0'])) {
            return new QFileUploadResponse(1, 'File Missing');
        }

        $file0 = $_FILES['file_0'];
        $parentId = $request->get('parent_id');

        if ($parentId === 'null' || $parentId === 'undefined') {
            $parentId = null;
        }

        if ($file0['error']) {
            return new QFileUploadResponse(2,'File Error Code: '.$file0['error']);
        }

        $y = date('Y');
        $m = date('m');

        $dir = public_path("files/attachments/{$y}/{$m}");

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $allowed = [
            'jpg', 'jpeg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'png'
        ];

        $imageExtension = [
            'jpg' => true, 'jpeg' => true, 'png' => true
        ];

        $info = pathinfo($file0['name']);
        $extension = strtolower($info['extension']);

        if (!in_array($extension, $allowed)) {
            return new QFileUploadResponse(3, 'Extension: '.$extension.' is now allowed');
        }

        if ($this->checkFileNameExists($parentId, $info['filename'])) {
            return new QFileUploadResponse(21, "File ${$info['filename']} already exists");
        }


        $hash = sha1(uniqid());
        $newFilePath = $dir.'/'.$hash.'.'.$extension;

        $newUrl = url("/files/attachments/{$y}/{$m}/{$hash}.{$extension}");

        $ok = move_uploaded_file($file0['tmp_name'], $newFilePath);

        if (!$ok) {
            return new QFileUploadResponse(4, 'Move uploaded failed');
        }


        $user = $this->currentUser();
        $file = new QFile();
        $file->id = Uuid::uuid4();
        $file->type = $file0['type'];
        $file->file_type = 'file';
        $file->hash = sha1($newFilePath);
        $file->url = $newUrl;
        $file->parent_id = $parentId;
        $file->is_image = isset($imageExtension[$extension]) ? 1 : 0;
        $file->size = $file0['size'];
        $file->name = $info['filename'];
        $file->path = $newFilePath;
        $file->uploaded_by = $user->name;
        $file->user_id = $user->id;
        $file->extension = $extension;
        $file->updated_at = date('Y-m-d H:i:s');
        $file->created_at = date('Y-m-d H:i:s');


        $saveData = $file->toArray();

        unset($saveData['children'], $saveData['User']);
        $this->query()->insert($saveData);

        return new QFileUploadResponse(200, 'OK', $file);
    }

    public function addFolder(Request $request): QFileAddFolderResponse
    {
        $parentId = $request->get('parent_id');
        $name = trim(strip_tags($request->get('name')));

        if ($parentId === 'null' || $parentId === 'undefined') {
            $parentId = null;
        }

        if (!$name) {
            return new QFileAddFolderResponse(5, 'Missing file name');
        }

        if ($name === '.' || $name === '..') {
            return new QFileAddFolderResponse(6, 'File name must not equals . or ..');
        }

        if ($parentId) {
            $parent = $this->query()->where('is_folder', 1)->where('id', $parentId)->first();
            if (!$parent) {
                return new QFileAddFolderResponse(1, 'Parent folder does not exists');
            }
        }

        if ($this->checkFileNameExists($parentId, $name)) {
            return new QFileAddFolderResponse(2, "File ${name} already exists");
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->query()->insertGetId([
            'id' => Uuid::uuid4(),
            'name' => $name,
            'is_folder' => 1,
            'user_id' => $this->currentUser()->id,
            'parent_id' => $parentId,
            'created_at' => $now,
            'updated_at' => $now
        ]);

        $file = QFile::fromObject($this->query()->find($id));


        return new QFileAddFolderResponse(200, 'OK', $file);
    }

    public function move(Request $request) : QFileResponse
    {
        $ids = $request->get('ids');
        $parentId = $request->get('parent_id');
        $parent = $this->query()->where('is_folder', 1)->find($parentId);
        $now = date('Y-m-d H:i:s');

        if (!$parent) {
            $this->query()->whereIn('id', $ids)->update([
                'parent_id' => null,
                'updated_at' => $now,
            ]);
        } else {
            foreach ($ids as $id) {
                if (intval($id) === $parentId) {
                    return new QFileResponse(3, 'Invalid params');
                }
            }

            $this->query()->whereIn('id', $ids)->update([
                'parent_id' => $parentId,
                'updated_at' => $now,
            ]);
        }

        return new QFileResponse(200, 'Di chuyển thành công');
    }

    public function remove(Request $request): QFileResponse
    {
        $ids = $request->get('ids');
        $files = $this->query()->whereIn('id', $ids)->get();

        foreach ($files as $file) {
            if ($file->is_folder) {
                $countFileInFolder = $this->query()->where('parent_id', $file->id)->count();
                if ($countFileInFolder > 0) {
                    return new QFileResponse(1, 'Không thể xóa thư mục có file bên trong');
                }
            }
        }

        $this->query()->whereIn('id', $ids)->update([
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->currentUser()->email
        ]);

        return new QFileResponse(200, 'Xóa file thành công');
    }

    public function rename(Request $request): QFileResponse
    {
        $id = $request->get('id');
        $name = trim(strip_tags($request->get('name')));
        if (!$name) {
            return new QFileResponse(1, 'Invalid file name');
        }

        $file = $this->query()->where('id', $id)->first();

        if (!$file) {
            return new QFileResponse(3, 'File not found');
        }


        if ($this->checkFileNameExists($file->parent_id, $name)) {
            return new QFileResponse(21, "File $name already exists");
        }

        $this->query()->where('id', $file->id)->update([
            'name' => $name,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return new QFileResponse(200, 'Đổi tên file thành công');
    }
}
