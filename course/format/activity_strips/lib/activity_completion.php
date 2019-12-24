<?php

namespace format_activity_strips;

use totara_form\form\element\checkbox;
use totara_form\form\element\hidden;
use totara_form\form\clientaction\onchange_ajaxsubmit;
use core_completion\form_controller\activity_completion_controller;


class activity_completion extends \core_completion\form\activity_completion
{ 
    const RESOURCE_MOD_ID = 19;
}