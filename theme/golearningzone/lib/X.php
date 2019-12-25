<?php

namespace GoLearningZone;

class X extends \Exception 
{
    protected $fileds;
    protected $type;
    protected $_message = '';

    public function __construct( $attrs = [] ) {

        if ( isset($attrs['message']) ) {
            $this->_message = $attrs['message'];
        }

        if ( isset($attrs['fields']) ) {
            $this->fields = $attrs['fields'];
        }

        if ( isset($attrs['type']) ) {
            $this->type = $attrs['type'];
        }
    }

    public function getError() {

        return [
            'error' => [
                'fields'  => $this->fields,
                'type'    => $this->type,
                'message' => $this->_message,
            ]
        ];
    }
}
