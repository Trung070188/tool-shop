<?php

namespace Q\FileManager\Types;

class QFileResponse implements \JsonSerializable
{
    protected int $code;
    protected string $message;

    public function __construct(int $code, string $message, $data = null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        if ($this->data) {
            return [
                'code' => $this->code,
                'message' => $this->message,
                'data' => $this->data
            ];
        }

        return [
            'code' => $this->code,
            'message' => $this->message,
        ];
    }
}
