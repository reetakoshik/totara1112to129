<?php

class X extends Exception
{
    protected $fields = [];
    protected $type = 'UNKNOWN_ERROR';
    protected $message = '';

    public function __construct($attrs = [])
    {
        if (isset($attrs['Message'])) {
            $this->message = $attrs['Message'];
        }

        if (isset($attrs['Fields'])) {
            $this->fields = $attrs['Fields'];
        }

        if (isset($attrs['Type'])) {
            $this->type = $attrs['Type'];
        }
    }

    public function getError()
    {
        return [
            'Status' => 0,
            'Error'  => [
                'Fields'  => $this->fields,
                'Type'    => $this->type,
                'Message' => $this->message,
            ]
        ];
    }
}
