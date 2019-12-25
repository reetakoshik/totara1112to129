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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   theme_roots
 */

defined('MOODLE_INTERNAL') || die();

?>
<footer id="page-footer" class="page-footer">
    <div class="container-fluid page-footer-main-content">
        <div class="row">
            <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
        </div>

        <?php
        if (!empty($PAGE->theme->settings->footnote)) {
            echo '<div class="footnote">'.format_text($PAGE->theme->settings->footnote).'</div>';
        }
        ?>

        <div class="row">
            <div class="page-footer-loggedin-info">
                <?php echo $OUTPUT->login_info(); ?>
            </div>
        </div>
        <div class="row">
            <?php echo $OUTPUT->standard_footer_html(); ?>
        </div>
    </div>
    <small class="page-footer-poweredby"><?php echo $OUTPUT->powered_by_totara(); ?></small>
</footer>
