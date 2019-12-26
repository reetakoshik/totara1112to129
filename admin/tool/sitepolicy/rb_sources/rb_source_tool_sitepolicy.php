<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

use tool_sitepolicy\policyversion;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/sitepolicy/rb_sources/rb_filter_policy_select_version.php');

class rb_source_tool_sitepolicy extends rb_base_source {
    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        $this->usedcomponents[] = 'tool_sitepolicy';

        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('base', 'userid');

        $this->base = '{tool_sitepolicy_user_consent}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_tool_sitepolicy');
        parent::__construct();
    }

    protected function define_joinlist() {
        $joinlist = array(
            new rb_join(
                'consentoption',
                'INNER',
                '{tool_sitepolicy_consent_options}',
                'base.consentoptionid = consentoption.id'),

            new rb_join(
                'policyversion',
                'INNER',
                '{tool_sitepolicy_policy_version}',
                'policyversion.id = consentoption.policyversionid',
                null,
                'consentoption'),

            new rb_join(
                'localisedpolicy',
                'INNER',
                '{tool_sitepolicy_localised_policy}',
                'localisedpolicy.policyversionid = policyversion.id
                 AND localisedpolicy.language = base.language',
                null,
                'policyversion'),

            new rb_join(
                'localisedconsent',
                'INNER',
                '{tool_sitepolicy_localised_consent}',
                'localisedconsent.localisedpolicyid = localisedpolicy.id
                 AND localisedconsent.consentoptionid = base.consentoptionid',
                null,
                'localisedpolicy'),

            new rb_join(
                'primarylocalisedpolicy',
                'INNER',
                '{tool_sitepolicy_localised_policy}',
                'primarylocalisedpolicy.policyversionid = policyversion.id
                 AND primarylocalisedpolicy.isprimary = 1',
                null,
                'policyversion'),

            new rb_join(
                'primarylocalisedconsent',
                'INNER',
                '{tool_sitepolicy_localised_consent}',
                'primarylocalisedconsent.localisedpolicyid = primarylocalisedpolicy.id
                 AND primarylocalisedconsent.consentoptionid = base.consentoptionid',
                null,
                'primarylocalisedpolicy'),

            new rb_join(
                'author',
                'INNER',
                '{user}',
                'primarylocalisedpolicy.authorid = author.id',
                null,
                'primarylocalisedpolicy'),

            new rb_join(
                'publisher',
                'INNER',
                '{user}',
                'policyversion.publisherid = publisher.id',
                null,
                'policyversion')

        );
        // optionally include some standard joins
        $this->add_core_user_tables($joinlist, 'base', 'userid');
        return $joinlist;
    }

    protected function define_columnoptions() {
        $statusdraft = policyversion::STATUS_DRAFT;
        $statuspublished = policyversion::STATUS_PUBLISHED;
        $statusarchived = policyversion::STATUS_ARCHIVED;

        $columnoptions = array(
            new rb_column_option(
                'primarypolicy',
                'primarytitle',
                get_string('policytitle', 'rb_source_tool_sitepolicy'),
                'primarylocalisedpolicy.title',
                array('joins' => 'primarylocalisedpolicy',
                      'displayfunc' => 'format_string')),

            new rb_column_option(
                'primarypolicy',
                'primarydatecreated',
                get_string('policydatecreated', 'rb_source_tool_sitepolicy'),
                'policyversion.timecreated',
                array('joins' => 'policyversion',
                      'displayfunc' => 'nice_datetime')),

            new rb_column_option(
                'primarypolicy',
                'primarycreatedby',
                get_string('policycreatedby', 'rb_source_tool_sitepolicy'),
                'author.username',
                array('joins' => 'author',
                      'displayfunc' => 'plaintext')),

            new rb_column_option(
                'primarypolicy',
                'versionnumber',
                get_string('policyversion', 'rb_source_tool_sitepolicy'),
                'policyversion.versionnumber',
                array('joins' => 'policyversion',
                      'displayfunc' => 'plaintext')),

            new rb_column_option(
                'primarypolicy',
                'status',
                get_string('policystatus', 'rb_source_tool_sitepolicy'),
                "(CASE
                    WHEN policyversion.timepublished IS NULL THEN '{$statusdraft}'
                    WHEN policyversion.timearchived IS NOT NULL THEN '{$statusarchived}'
                    ELSE '{$statuspublished}'
                  END)",
                array('joins' => 'policyversion',
                      'displayfunc' => 'sitepolicy_versionstatus')),

            new rb_column_option(
                'primarypolicy',
                'datepublished',
                get_string('policydatepublished', 'rb_source_tool_sitepolicy'),
                'policyversion.timepublished',
                array('joins' => 'policyversion',
                      'displayfunc' => 'nice_datetime')),

            new rb_column_option(
                'primarypolicy',
                'publishedby',
                get_string('policypublishedby', 'rb_source_tool_sitepolicy'),
                'publisher.username',
                array('joins' => 'publisher',
                      'displayfunc' => 'plaintext')),

            new rb_column_option(
                'primarypolicy',
                'primarystatement',
                get_string('policystatement', 'rb_source_tool_sitepolicy'),
                'primarylocalisedconsent.statement',
                array('joins' => 'primarylocalisedconsent',
                      'displayfunc' => 'format_text')),

            new rb_column_option(
                'primarypolicy',
                'primaryresponse',
                get_string('policyresponse', 'rb_source_tool_sitepolicy'),
                'base.hasconsented',
                array('joins' => 'primarylocalisedconsent',
                      'displayfunc' => 'sitepolicy_userresponse',
                      'extrafields' => array(
                            'primarylocalisedconsent.nonconsentoption',
                            'primarylocalisedconsent.consentoption'
                       ))),

            new rb_column_option(
                'userpolicy',
                'consented',
                get_string('userreponseconsented', 'rb_source_tool_sitepolicy'),
                'base.hasconsented',
                array(
                    'displayfunc' => 'yes_or_no')),

            new rb_column_option(
                'userpolicy',
                'language',
                get_string('userreponselanguage', 'rb_source_tool_sitepolicy'),
                'base.language',
                array(
                    'displayfunc' => 'language_code')),

            new rb_column_option(
                'userpolicy',
                'statement',
                get_string('userreponsestatement', 'rb_source_tool_sitepolicy'),
                'localisedconsent.statement',
                array('joins' => 'localisedconsent',
                      'displayfunc' => 'format_text')),

            new rb_column_option(
                'userpolicy',
                'response',
                get_string('userresponseoption', 'rb_source_tool_sitepolicy'),
                'base.hasconsented',
                array('joins' => 'localisedconsent',
                      'displayfunc' => 'sitepolicy_userresponse',
                      'extrafields' => array(
                            'localisedconsent.nonconsentoption',
                            'localisedconsent.consentoption'
                       ))),

            new rb_column_option(
                'userpolicy',
                'timeconsented',
                get_string('usertimeconsented', 'rb_source_tool_sitepolicy'),
                'base.timeconsented',
                array('displayfunc' => 'nice_datetime')),
            );
        $this->add_core_user_columns($columnoptions);
        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'primarypolicy',
                'versionnumber',
                get_string('policyversion', 'rb_source_tool_sitepolicy'),
                'policy_select_version', [
                    'selectchoices' => [],
                ]
            ),

            new rb_filter_option(
                'userpolicy',
                'consented',
                get_string('userconsentoptions','rb_source_tool_sitepolicy'),
                'select', [
                    'selectchoices' => [
                        get_string('no','rb_source_tool_sitepolicy'),
                        get_string('yes','rb_source_tool_sitepolicy'),
                    ],
                    'simplemode' => true,
                ]
            ),

            new rb_filter_option(
                'primarypolicy',
                'status',
                get_string('policystatus','rb_source_tool_sitepolicy'),
                'multicheck', [
                    'selectchoices' => [
                        $status = policyversion::STATUS_PUBLISHED => get_string("versionstatus{$status}", 'tool_sitepolicy'),
                        $status = policyversion::STATUS_ARCHIVED => get_string("versionstatus{$status}", 'tool_sitepolicy'),
                    ],
                    'simplemode' => true,
                ]
            ),

            new rb_filter_option(
                'userpolicy',
                'policystatement',
                get_string('policystatement','rb_source_tool_sitepolicy'),
                'select', [
                    'selectfunc' => 'consent_statements',
                    'simplemode' => true,
                    'help' => [
                        'filter_consent_statement',
                        'rb_source_tool_sitepolicy',
                    ],
                ],
                'base.consentoptionid'
            ),

            new rb_filter_option(
                'primarypolicy',
                'policytitle',
                get_string('policy','rb_source_tool_sitepolicy'),
                'multicheck', [
                    'selectfunc' => 'policies',
                    'simplemode' => true,
                ],
                'policyversion.sitepolicyid',
                'policyversion'
            ),

            new rb_filter_option(
                'userpolicy',
                'language',
                get_string('userreponselanguage','rb_source_tool_sitepolicy'),
                'select', [
                    'selectfunc' => 'userlanguage',
                    'simplemode' => true,
                ]),

        );
        $this->add_core_user_filters($filteroptions);
        return $filteroptions;
    }

    protected function define_paramoptions() {
        $paramoptions = array(
            new rb_param_option(
                'userid',
                'base.userid'
            )
        );
        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        return self::get_default_columns();
    }

    /**
     * Return default columns for the report source
     *
     * @return array[]
     */
    public static function get_default_columns() {
        return [
            [
                'type' => 'user',
                'value' => 'namelink',
                'heading' => get_string('userfullname', 'totara_reportbuilder'),
            ],
            [
                'type' => 'primarypolicy',
                'value' => 'primarytitle',
                'heading' => get_string('embeddedprimarytitle', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'primarypolicy',
                'value' => 'versionnumber',
                'heading' => get_string('embeddedversionnumber', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'userpolicy',
                'value' => 'statement',
                'heading' => get_string('embeddeduserstatement', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'userpolicy',
                'value' => 'response',
                'heading' => get_string('embeddeduserresponse', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'userpolicy',
                'value' => 'consented',
                'heading' => get_string('embeddeduserconsented', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'userpolicy',
                'value' => 'language',
                'heading' => get_string('embeddeduserlanguage', 'rb_source_tool_sitepolicy'),
            ],
            [
                'type' => 'userpolicy',
                'value' => 'timeconsented',
                'heading' => get_string('embeddedusertimeconsented', 'rb_source_tool_sitepolicy'),
            ],
        ];
    }

    protected function define_defaultfilters()
    {
        return self::get_default_filters();
    }

    /**
     * Return default filter set for the report
     *
     * @return array[]
     */
    public static function get_default_filters()
    {
        return [
            [
                'type' => 'primarypolicy',
                'value' => 'policytitle',
            ],
            [
                'type' => 'primarypolicy',
                'value' => 'versionnumber',
            ],
            [
                'type' => 'userpolicy',
                'value' => 'policystatement',
            ]
        ];
    }

    /**
     * Retrieve the list of all possible consent statements for the filter
     * It retrieves unique consent statements and all matching ids for them.
     *
     * @return array
     */
    public function rb_filter_consent_statements() {
        global $DB;

        $statements = $DB->get_records_sql("SELECT DISTINCT statements.statement as statement
                                                 FROM {tool_sitepolicy_localised_consent} statements
                                                  JOIN {tool_sitepolicy_localised_policy} policies
                                                   ON statements.localisedpolicyid = policies.id
                                                  JOIN {tool_sitepolicy_policy_version} versions
                                                   ON versions.id = policies.policyversionid
                                                   AND versions.timepublished IS NOT NULL
                                                 WHERE policies.isprimary = 1
                                                 ORDER BY statement");

        // Unfortunately we can't make it in one query due to group concat not working with sub-queries.
        foreach ($statements as $statement) {
            // Another unnecessary complication.
            $statementsql = $DB->sql_compare_text('statement', 500);

            $ids = $DB->get_records_sql(
                "SELECT DISTINCT consentoptionid as option_id FROM {tool_sitepolicy_localised_consent}
                      WHERE {$statementsql} = ?", [$statement->statement]);

            // It should never be empty though, just a precaution.
            $statement->ids = !empty($ids) ? implode(',', array_column($ids, 'option_id')) : '-1';
        }

        return array_combine(array_column($statements, 'ids'), array_column($statements, 'statement'));
    }

    /**
     * Retrieve a list of all possible policies for the filter
     * It will display the latest policy title in the primary language
     *
     * @return array
     */
    public function rb_filter_policies() {
        global $DB;

        return $DB->get_records_sql_menu(
            "SELECT policies.id as id, titles.title as title
                  FROM {tool_sitepolicy_site_policy} policies
                INNER JOIN {tool_sitepolicy_policy_version} versions
                  ON policies.id = versions.sitepolicyid
                  AND versions.timepublished = 
                      (SELECT max(vs.timepublished) FROM {tool_sitepolicy_policy_version} vs
                       WHERE vs.timepublished IS NOT NULL AND vs.sitepolicyid = policies.id)
                JOIN {tool_sitepolicy_localised_policy} titles
                  ON titles.policyversionid = versions.id AND titles.isprimary = 1
                ORDER BY title");
    }

    public function rb_filter_userlanguage() {
        $userlanguage = array();
        global $DB;
        $sql = "SELECT distinct language FROM {tool_sitepolicy_localised_policy}";
        $languages = $DB->get_records_sql($sql);
        foreach ($languages as $language) {
            $userlanguage[$language->language] = get_string_manager()->get_list_of_translations()[$language->language];
        }
        return $userlanguage;
    }

    /**
     * Inject column_test data into database.
     * @param totara_reportbuilder_column_testcase $testcase
     */
    public function phpunit_column_test_add_data(totara_reportbuilder_column_testcase $testcase) {
        global $DB;

        if (!PHPUNIT_TEST) {
            throw new coding_exception('phpunit_column_test_add_data() cannot be used outside of unit tests');
        }

        // Create report
        $totara_report_builder_data = [
            'id' => 1,
            'fullname' => 'Site Policy Report',
            'shortname' => 'tool_sitepolicy',
            'source' => 'tool_sitepolicy',
            'hidden' => 0,
            'cache' => 0,
            'accessmode' => 0,
            'contentmode' => 0,
            'description' => 'Report description',
            'recordsperpage' => 10,
            'defaultsortcolumn' => null,
            'defaultsortorder' => 0,
            'embedded' => 0,
            'initialdisplay' => 0,
            'toolbarsearch' => 1,
            'globalrestriction' => 0,
            'timemodified' => 0,
            'showtotalcount' => 0,
            'useclonedb' => 0,
        ];

        $DB->insert_record('report_builder', $totara_report_builder_data);

        // Create policy
        $sitepolicy_data = ['timecreated' => 1515529244];
        $sitepolicy_data['id'] = $DB->insert_record('tool_sitepolicy_site_policy', $sitepolicy_data);

        // Create policy version
        $policyversion_data = [
            'versionnumber' => 5,
            'timecreated' => 1515461888,
            'timepublished' => 1515462800,
            'sitepolicyid' => $sitepolicy_data['id'],
            'publisherid' => 2,
        ];

        $policyversion_data['id'] = $DB->insert_record('tool_sitepolicy_policy_version', $policyversion_data);

        // Create localised policy
        $localisedpolicy_data = [
            'language' => 'en',
            'title' => 'Terms and Conditions',
            'policytext' => 'statment',
            'timecreated' => 1515461888,
            'isprimary' => 1,
            'authorid' => 2,
            'policyversionid' => $policyversion_data['id'],
        ];

        $localisedpolicy_data['id'] = $DB->insert_record('tool_sitepolicy_localised_policy', $localisedpolicy_data);

        // Create consent option
        $consentoption_data = [
            'mandatory' => 1,
            'policyversionid' => $policyversion_data['id'],
        ];

        $consentoption_data['id'] = $DB->insert_record('tool_sitepolicy_consent_options', $consentoption_data);

        // Create localised consent option
        $localisedconsent_data = [
            'statement' => 'Consent',
            'consentoption' => 'yes',
            'nonconsentoption' => 'no',
            'localisedpolicyid' => $localisedpolicy_data['id'],
            'consentoptionid' => $consentoption_data['id'],
        ];

        $localisedconsent_data['id'] = $DB->insert_record('tool_sitepolicy_localised_consent', $localisedconsent_data);

        // Create user consent
        $userconsent_data = [
            'userid' => 2,
            'timeconsented' => 1515617791,
            'hasconsented' => 1,
            'consentoptionid' => $consentoption_data['id'],
            'language' => 'en',
        ];

        $userconsent_data['id'] = $DB->insert_record('tool_sitepolicy_user_consent', $userconsent_data);
    }

    public function global_restrictions_supported() {
        return true;
    }
}
