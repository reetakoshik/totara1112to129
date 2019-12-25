<?php

namespace format_activity_strips\hook;

class self_completion_form extends \totara_core\hook\base
{
	public $form;
	public $params;
  
    public function __construct(&$form, $params)
    {
    	$this->form = &$form;
    	$this->params = $params;
    }
}