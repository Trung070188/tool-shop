<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class PhpDoc
{
    private $_lines = [' /**'];
    private $_func;
    private $_funcParams;

    public function __construct()
    {
    }

    public function addBlock($name, $content)
    {
        $contentLines = explode("\n", $content);

        if (count($contentLines) > 1) {
            for ($i = 1; $i < count($contentLines); ++$i) {
                $contentLines[$i] = ' * '.$contentLines[$i];
            }
            $content = implode("\n", $contentLines);
        }

        if (!empty($content)) {
            $this->_lines[] = ' * @'.$name.' '.$content;
        }

        return $this;
    }

    public function addFunction($name, array $params = [])
    {
        $this->_func = $name;
        $this->_funcParams = $params;

        return $this;
    }

    public function render()
    {
        $lines = $this->_lines;
        $lines[] = ' */';

        if ($this->_func) {
            $lines[] = sprintf("public function %s () {\n\treturn [];\n}", $this->_func);
        }

        return implode("\n", $lines);
    }

    public static function renderTableField($table, $return = false)
    {
        $phpdoc = new static();

        $fields = DB::select("SHOW COLUMNS FROM `{$table}`");

        foreach ($fields as $field) {
            $type = 'string';

            if (strpos($field->Type, 'int') !== false) {
                $type = 'int';
            } elseif (strpos($field->Type, 'decimal') !== false) {
                $type = 'double';
            } elseif ($field->Type === 'datetime' || $field->Type === 'date') {
                $type = '\DateTime';
            }
            $needSpace = 10 - mb_strlen($type);
            $space = '';

            for ($i = 0; $i < $needSpace; ++$i) {
                $space .= ' ';
            }

            $phpdoc->addBlock('property', $type.$space.'$'.$field->Field);
        }

        if ($return) {
            return $phpdoc->render();
        }
        echo $phpdoc->render();
    }
}
