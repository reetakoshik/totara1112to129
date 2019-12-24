<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Alastair Munro <alastair.munro@totaralms.com>>
 * @package   core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit unit tests for the help icon.
 */
class totara_core_help_icon_testcase extends advanced_testcase {

    public function test_render_flex_icon() {
        global $OUTPUT;

        $help_icon = new help_icon('allowbookingscancellationsdefault', 'facetoface');

        $renderedicon = $OUTPUT->help_icon('allowbookingscancellationsdefault', 'facetoface');

        $this->assertContains('title="Help with Default ‘allow cancellations’ setting for all events"', $renderedicon);

        $this->assertNotContains('title="Help with Default &amp;lsquo;allow cancellations&amp;rsquo; setting for all events"', $renderedicon);
    }
}
