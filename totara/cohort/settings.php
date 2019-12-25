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
 * @author Petr Skoda <pter.skoda@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

global $COHORT_ALERT;

$ADMIN->add('audiences', new admin_externalpage('cohorts', new lang_string('cohorts', 'cohort'), $CFG->wwwroot . '/cohort/index.php', array('moodle/cohort:manage', 'moodle/cohort:view')));

$globalsettings = new admin_settingpage('cohortglobalsettings', new lang_string('cohortglobalsettings', 'totara_cohort'), 'moodle/cohort:manage');
$globalsettings->add(new admin_setting_configmulticheckbox('cohort/alertoptions',
    new lang_string('cohortalertoptions', 'totara_cohort'), new lang_string('cohortalertoptions_help', 'totara_cohort'),
    array(COHORT_ALERT_NONE => 1, COHORT_ALERT_AFFECTED => 1, COHORT_ALERT_ALL => 1), $COHORT_ALERT
));

$globalsettings->add(new admin_setting_configcheckbox('cohort/applyinbackground',
    new lang_string('cohortapplyinbackground', 'totara_cohort'), new lang_string('cohortapplyinbackground_help', 'totara_cohort'), 0
));

$ADMIN->add('audiences', $globalsettings);
