<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Carrousel block tests.
 *
 * @package    block_carrousel
 * @category   test
 * @copyright  2015 Buriak Dmitry <dmitry.buriak@kineo.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Carrousel block tests class.
 *
 * @package    block_carrousel
 * @category   test
 * @copyright  2015 Buriak Dmitry <dmitry.buriak@kineo.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;
require_once(__DIR__.'/../lib.php');
/**
 * Unit tests for {@link block_carrousel}.
 * @group kineo_custom
 */
class block_carrousel_testcase extends advanced_testcase {
   
    /**
     * Setup test data.
     */
    public function setUp() {
        $this->resetAfterTest();
        $this->setAdminUser();
    }
    
    public function test_create_slide() {
        global $DB;

        $blockid = 19;
        $slide = block_carrousel_create_slide($blockid);
        $fromform = new stdClass();
        $fromform->title = 'UnitTestTitle';
        $newslide = block_carrousel_process_form_submition($slide, $fromform);
    
        $getslide = block_carrousel_get_slide($newslide->id);
        $this->assertEquals($getslide->title, $getslide->title);
    }
    
    public function test_remove_created() {
        global $DB;
        
        $blockid = 19;
        $slide = block_carrousel_create_slide($blockid);
        $fromform = new stdClass();
        $fromform->title = 'UnitTestTitle';
        $newslide = block_carrousel_process_form_submition($slide, $fromform);
        $deleteslide = block_carrousel_delete_slide($newslide->id);
        $getslide = block_carrousel_get_slide($newslide->id);   
        $this->assertEquals($getslide, NULL);
    }
    
    public function test_changeorder_slide() {
        global $DB;
        
        $blockid = 19;
        
        //First slide
        $slide = block_carrousel_create_slide($blockid);
        $fromform = new stdClass();
        $fromform->title = 'UnitTestTitle1';
        $firstslide = block_carrousel_process_form_submition($slide, $fromform);
        $this->assertEquals($firstslide->title, $fromform->title);
       
        //Second slide
        $slide = block_carrousel_create_slide($blockid);
        $fromform = new stdClass();
        $fromform->title = 'UnitTestTitle2';
        $secondslide = block_carrousel_process_form_submition($slide, $fromform);
        $this->assertEquals($secondslide->title, $fromform->title);
        
        //Third slide
        $slide = block_carrousel_create_slide($blockid);
        $fromform = new stdClass();
        $fromform->title = 'UnitTestTitle3';
        $newslide = block_carrousel_process_form_submition($slide, $fromform);
        $thirdslide = block_carrousel_process_form_submition($slide, $fromform);
        $this->assertEquals($thirdslide->title, $fromform->title);
        
    }
}
