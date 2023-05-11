<?php

namespace Q\FileManager\Types;

/**
 * @property QFile[] $children
 */
class QFile implements \JsonSerializable
{
    public $id;
    public $uuid;
    public $name;
    public $path;
    public $url;
    public $size;
    public $type;
    public $is_image;
    public $extension;
    public $user_id;
    public $is_folder;
    public $parent_id;
    public $updated_at;
    public $created_at;
    public $children = [];
    public $User;

    public function toArray(): array
    {
        $isFolder = (bool) $this->is_folder;
        $isImage = (bool) $this->is_image;

        $output = [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'url' => $this->url,
            'size' => $this->size,
            'type' => $this->type,
            'is_image' => $isImage,
            'extension' => $this->extension,
            'parent_id' => $this->parent_id,
            'user_id' => $this->user_id,
            'is_folder' => $isFolder,
            'User' => $this->User,
            'children' => $this->children,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];

        if ($this->id === null) {
            unset($output['id']);
        }

        return $output;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public static function fromObject($input): QFile
    {
        $input = (array)$input;
        $e = new QFile();
        foreach ($input as $k => $v) {
            $e->$k = $v;
        }

        return $e;
    }

    public static function fromArray(array $input): QFile
    {
        $e = new QFile();
        foreach ($input as $k => $v) {
            $e->$k = $v;
        }

        return $e;
    }
}
