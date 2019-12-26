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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("{$CFG->dirroot}/totara/customfield/field/text/define.class.php");
require_once("{$CFG->dirroot}/totara/cohort/lib.php");
require_once("{$CFG->dirroot}/totara/cohort/cohort_forms.php");

class totara_cohort_multi_language_filter_testcase extends advanced_testcase {
    /**
     * @return array
     */
    public function provide_language_testdata(): array {
        return [
            ['fr', '#french#', ['#english#', '#hebrew#', '#swedish#', '#japanese#']],
            ['en', '#english#', ['#french#', '#hebrew#', '#swedish#', '#japanese#']],
            ['he', '#hebrew#', ['#english#', '#french#', '#swedish#', '#japanese#']],
            ['sv', '#swedish#', ['#english#', '#hebrew#', '#french#', '#japanese#']],
            ['ja', '#japanese#', ['#swedish#', '#hebrew#', '#french#', '#english#']] 
        ];
    }
    
    /**
     * @dataProvider provide_language_testdata
     * @param string $lang
     * @param string $expected
     * @param array $unexpected
     * 
     * @return void
     */
    public function test_position_customfield_filter(string $lang, string $expected, array $unexpected): void {
        global $USER, $DB, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $USER->lang = $lang ;

        // Enable filter multilang here
        $DB->insert_record('filter_active', (object)[
            'filter' => 'multilang',
            'contextid' => $PAGE->context->id,
            'active' => 1,
            'sortorder' => 4
        ]);
        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $gen = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $posgen */
        $posgen = $gen->get_plugin_generator('totara_hierarchy');

        $typeid = $posgen->create_pos_type([
            'shortname' => "shortname"
        ]);


        $name = '<span lang="en" class="multilang">#english#</span>
                 <span lang="he" class="multilang">#hebrew#</span>
                 <span lang="fr" class="multilang">#french#</span>
                 <span lang="sv" class="multilang">#swedish#</span>
                 <span lang="ja" class="multilang">#japanese#</span>';

        $data = (object)[
            'id' => 0,
            'datatype' => 'text',
            'fullname' => $name,
            'shortname' => 'name',
            'description' => '',
            'defaultdata' => '',
            'forceunique' => 0,
            'hidden' => 0,
            'locked' => 0,
            'required' => 0,
            'description_editor' => array('text' => '', 'format' => 0),
            'typeid' => $typeid,
        ];

        $formfield = new customfield_define_text();
        $formfield->define_save($data, 'pos_type');

        /** @var totara_cohort_generator $cohortgen */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');
        $cohort = $cohortgen->create_cohort([
            'cohorttype' => cohort::TYPE_DYNAMIC
        ]);

        $customdata = array(
            'cohort' => $cohort,
            'rulesets' => []
        );
        $form = new cohort_rules_form(null, $customdata);

        ob_start();
        $form->display();
        $content = ob_get_contents();
        ob_end_clean();

        $this->assertContains($expected, $content);
        foreach ($unexpected as $a) {
            $this->assertNotContains($a, $content);
        }
    }

    /**
     * @dataProvider provide_language_testdata
     * @param string $lang
     * @param string $expected
     * @param array $unexpected
     * 
     * @return void
     */
    public function test_organisation_customfield_filter(string $lang, string $expected, array $unexpected): void {
        global $DB, $USER, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $USER->lang = $lang;

        $DB->insert_record('filter_active', (object)[
            'filter' => 'multilang',
            'contextid' => $PAGE->context->id,
            'active' => 1,
            'sortorder' => 4
        ]);

        set_config('filterall', 1);
        set_config('stringfilters', 'multilang');

        $gen = $this->getDataGenerator();
        /** @var totara_hierarchy_generator $orggen */
        $orggen = $gen->get_plugin_generator('totara_hierarchy');
        $typeid = $orggen->create_org_type([
            'shortname' => 'shortname'
        ]);

        $name = '<span lang="en" class="multilang">#english#</span>
                 <span lang="he" class="multilang">#hebrew#</span>
                 <span lang="fr" class="multilang">#french#</span>
                 <span lang="sv" class="multilang">#swedish#</span>
                 <span lang="ja" class="multilang">#japanese#</span>';
        
        $data = (object)[
            'id' => 0,
            'datatype' => 'text',
            'fullname' => $name,
            'shortname' => 'name',
            'description' => '',
            'defaultdata' => '',
            'forceunique' => 0,
            'hidden' => 0,
            'locked' => 0,
            'required' => 0,
            'description_editor' => array('text' => '', 'format' => 0),
            'typeid' => $typeid
        ];

        $formfield = new customfield_define_text();
        $formfield->define_save($data, 'org_type');

        /** @var totara_cohort_generator $cohortgen */
        $cohortgen = $gen->get_plugin_generator('totara_cohort');
        $cohort = $cohortgen->create_cohort([
            'cohorttype' => cohort::TYPE_DYNAMIC
        ]);

        $customdata = array(
            'cohort' => $cohort,
            'rulesets' => []
        );

        $form = new cohort_rules_form(null, $customdata);
        
        ob_start();
        $form->display();
        $content = ob_get_contents();
        ob_end_clean();
    
        $this->assertContains($expected, $content);
        foreach ($unexpected as $a) {
            $this->assertNotContains($a, $content);
        }
    }
}