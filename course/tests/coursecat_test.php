<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir.'/coursecatlib.php');

class core_course_cat_lib_testcase extends advanced_testcase {

    /*
     * Test creating a course category with the data generator.
     */
    public function test_create_category_with_generator() {
        global $DB;
        $this->resetAfterTest(true);
        // Create a new course category and check it exists.
        $record = $this->getDataGenerator()->create_category();
        $exists = $DB->record_exists('course_categories', array('id' => $record->id));
        // Assert the existance of the record.
        $this->assertTrue($exists);
    }

}