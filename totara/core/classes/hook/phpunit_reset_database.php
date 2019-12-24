<?php

namespace totara_core\hook;

class phpunit_reset_database extends \totara_core\hook\base {

    protected $databasealreadyreset = false;

    public function __construct() {

    }

    public function set_database_already_reset($value = true) {
        $this->databasealreadyreset = (bool)$value;
    }

    public function is_database_already_reset() {
        return $this->databasealreadyreset;
    }

}