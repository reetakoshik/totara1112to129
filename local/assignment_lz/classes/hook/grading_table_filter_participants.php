<?php

namespace local_assignment_lz\hook;

class grading_table_filter_participants extends \totara_core\hook\base
{
    public $additionaljoins;
    public $additionalfilters;
    public $params;
    public $obj;
    public $instance;

    public function __construct(&$additionaljoins, &$additionalfilters, &$params, $obj)
    {
        $this->additionaljoins = &$additionaljoins;
        $this->additionalfilters = &$additionalfilters;
        $this->params = &$params;
        $this->obj = $obj;
        $this->instance = $obj->get_instance();
    }
}