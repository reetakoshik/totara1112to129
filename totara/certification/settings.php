<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_certification
 */

defined('MOODLE_INTERNAL') || die();

$ADMIN->add('certifications', new admin_externalpage('managecertifications', new lang_string('managecertifications', 'totara_core'),
    $CFG->wwwroot . '/totara/program/manage.php?viewtype=certification',
    array('totara/certification:createcertification', 'totara/certification:configurecertification'),
    totara_feature_disabled('certifications')
));

// TODO create link to custom fields.
