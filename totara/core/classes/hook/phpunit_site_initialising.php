<?php

namespace totara_core\hook;

class phpunit_site_initialising extends \totara_core\hook\base {

    protected $sitealreadyinitialised = false;

    public function __construct() {

    }

    public function set_site_already_initalised($value = true) {
        $this->sitealreadyinitialised = (bool)$value;
    }

    public function is_site_already_initialised() {
        return $this->sitealreadyinitialised;
    }

}