<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
 * Copyright (C) 1999 onwards Martin Dougiamas
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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */

global $CFG;
require_once($CFG->dirroot.'/totara/message/lib.php');

defined('MOODLE_INTERNAL') || die();

class rb_source_totaramessages extends rb_base_source {
    public $base, $joinlist, $columnoptions, $filteroptions;
    public $contentoptions, $paramoptions, $defaultcolumns;
    public $defaultfilters, $requiredcolumns, $sourcetitle;

    public function __construct($groupid, rb_global_restriction_set $globalrestrictionset = null) {
        if ($groupid instanceof rb_global_restriction_set) {
            throw new coding_exception('Wrong parameter orders detected during report source instantiation.');
        }
        // Remember the active global restriction set.
        $this->globalrestrictionset = $globalrestrictionset;

        // Apply global user restrictions.
        $this->add_global_report_restriction_join('msg', 'useridfrom');
        $this->add_global_report_restriction_join('msg', 'useridto');

        $this->base = '{message_metadata}';
        $this->joinlist = $this->define_joinlist();
        $this->columnoptions = $this->define_columnoptions();
        $this->filteroptions = $this->define_filteroptions();
        $this->contentoptions = $this->define_contentoptions();
        $this->paramoptions = $this->define_paramoptions();
        $this->defaultcolumns = $this->define_defaultcolumns();
        $this->defaultfilters = $this->define_defaultfilters();
        $this->requiredcolumns = $this->define_requiredcolumns();
        $this->sourcetitle = get_string('sourcetitle', 'rb_source_totaramessages');

        parent::__construct();
    }

    /**
     * Global report restrictions are implemented in this source.
     * @return boolean
     */
    public function global_restrictions_supported() {
        return true;
    }

    //
    //
    // Methods for defining contents of source
    //
    //

    protected function define_joinlist() {
        global $CFG;

        $joinlist = array(
            new rb_join(
                'msg',
                'INNER',
                '{message}',
                'msg.id = base.messageid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'wrk',
                'INNER',
                '{message_working}',
                'wrk.unreadmessageid = base.messageid',
                REPORT_BUILDER_RELATION_ONE_TO_ONE
            ),
            new rb_join(
                'processor',
                'INNER',
                '{message_processors}',
                'wrk.processorid = processor.id',
                REPORT_BUILDER_RELATION_ONE_TO_ONE,
                array('base','wrk')
            ),
        );

        // Include a join for the user that the message was sent to.
        $this->add_user_table_to_joinlist($joinlist, 'msg', 'useridto', 'userto');

        // Include some standard joins. Including the user the message was sent from.
        $this->add_user_table_to_joinlist($joinlist, 'msg', 'useridfrom');
        $this->add_job_assignment_tables_to_joinlist($joinlist, 'msg', 'useridfrom');

        return $joinlist;
    }

    protected function define_columnoptions() {
        global $DB;
        $columnoptions = array(
            new rb_column_option(
                'message_values',
                'subject',
                get_string('subject', 'rb_source_totaramessages'),
                'msg.subject',
                array('joins' => 'msg',
                      'dbdatatype' => 'text',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'message_values',
                'statement',
                get_string('statement', 'rb_source_totaramessages'),
                'msg.fullmessagehtml',
                array('joins' => 'msg',
                      'dbdatatype' => 'text',
                      'outputformat' => 'text')
            ),
            new rb_column_option(
                'message_values',
                'urgency',
                get_string('msgurgencyicon', 'rb_source_totaramessages'),
                'base.urgency',
                array(
                    'defaultheading' => get_string('msgurgency', 'rb_source_totaramessages'),
                    'displayfunc' => 'urgency_link'
                )
            ),
            new rb_column_option(
                'message_values',
                'urgency_text',
                get_string('msgurgency', 'rb_source_totaramessages'),
                'base.urgency',
                array('displayfunc' => 'urgency_text')
                ),
            new rb_column_option(
                'message_values',
                'msgtype',
                get_string('msgtype', 'rb_source_totaramessages'),
                'base.msgtype',
                array(
                    'joins' => array('msg'),
                    'displayfunc' => 'msgtype_link',
                    'extrafields' => array(
                        'msgid' => 'base.messageid',
                        'msgsubject' => 'msg.subject',
                        'msgicon' => 'base.icon'
                    ),
                )
            ),
            new rb_column_option(
                'message_values',
                'msgtype_text',
                get_string('msgtypetext', 'rb_source_totaramessages'),
                'base.msgtype',
                array(
                    'defaultheading' => get_string('msgtype', 'rb_source_totaramessages'),
                    'displayfunc' => 'msgtype_text'
                )
            ),
            new rb_column_option(
                'message_values',
                'category',
                get_string('msgcategory', 'rb_source_totaramessages'),
                // icon uses format like 'competency-regular'
                // strip from first '-' to get general message category
                "CASE WHEN ". $DB->sql_position("'-'", 'base.icon') ." > 0 THEN " .
                $DB->sql_substr('base.icon', 1, $DB->sql_position("'-'", 'base.icon').'-1') .
                " ELSE 'base.icon' " .
                " END ",
                array('displayfunc' => 'msgcategory_text')
            ),
            new rb_column_option(
                'message_values',
                'sent',
                get_string('sent', 'rb_source_totaramessages'),
                'msg.timecreated',
                array('joins' => 'msg',
                      'displayfunc' => 'nice_date',
                      'dbdatatype' => 'timestamp'
                )
            ),
            new rb_column_option(
                'message_values',
                'checkbox',
                get_string('select', 'rb_source_totaramessages'),
                'base.messageid',
                array('displayfunc' => 'message_checkbox',
                      'noexport' => true,
                      'nosort' => true)
            ),
            new rb_column_option(
                'message_values',
                'msgid',
                get_string('msgid', 'rb_source_totaramessages'),
                'base.messageid',
                array('nosort' => true,
                      'noexport' => true,
                      'hidden' => 1,
                    )
            ),
        );

        // Add columns for the user the message was sent to.
        $this->add_user_fields_to_columns($columnoptions, 'userto', 'userto', true);

        // Include some standard columns. Including the user that the message was sent from.
        $this->add_user_fields_to_columns($columnoptions, 'auser', 'user', true);
        $this->add_job_assignment_fields_to_columns($columnoptions);

        return $columnoptions;
    }

    protected function define_filteroptions() {
        $filteroptions = array(
            new rb_filter_option(
                'message_values',       // type
                'sent',                 // value
                get_string('datesent', 'rb_source_totaramessages'),            // label
                'date',                 // filtertype
                array()                 // options
            ),
            new rb_filter_option(
                'message_values',
                'statement',
                get_string('statement', 'rb_source_totaramessages'),
                'text'
            ),
            new rb_filter_option(
                'message_values',
                'urgency',
                get_string('msgurgency', 'rb_source_totaramessages'),
                'select',
                array(
                    'selectfunc' => 'message_urgency_list',
                    'attributes' => rb_filter_option::select_width_limiter(),
                )
            ),
            new rb_filter_option(
                'message_values',
                'category',
                get_string('msgtype', 'rb_source_totaramessages'),
                'multicheck',
                array(
                    'selectfunc' => 'message_type_list',
                )
            ),
        );

        // Add filters for the user the message was sent to.
        $this->add_user_fields_to_filters($filteroptions, 'userto', true);

        // Include some standard filters. Including the user that the message was sent from.
        $this->add_user_fields_to_filters($filteroptions, 'user', true);
        $this->add_job_assignment_fields_to_filters($filteroptions, 'msg', 'useridfrom'); // Note these relate to the sender.

        return $filteroptions;
    }

    protected function define_contentoptions() {
        $contentoptions = array();

        // Add the manager/position/organisation content options.
        $this->add_basic_user_content_options($contentoptions, 'userto');

        // Add the time created content option.
        $contentoptions[] = new rb_content_option(
            'date',
            get_string('timecreated', 'rb_source_user'),
            'base.timecreated'
        );

        return $contentoptions;
    }

    protected function define_paramoptions() {
        // this is where you set your hardcoded filters
        $paramoptions = array(
            new rb_param_option(
                'userid',        // parameter name
                'msg.useridto',  // field
                'msg'            // joins
            ),
            new rb_param_option(
                'name',            // parameter name
                'processor.name',  // field
                'processor'        // joins
            ),
        );

        return $paramoptions;
    }

    protected function define_defaultcolumns() {
        $defaultcolumns = array(
            array(
                'type' => 'user',
                'value' => 'fullname'
            ),
            array(
                'type' => 'userto',
                'value' => 'fullname'
            ),
            array(
                'type' => 'message_values',
                'value' => 'subject'
            ),
            array(
                'type' => 'message_values',
                'value' => 'msgtype'
            ),
            array(
                'type' => 'message_values',
                'value' => 'sent'
            ),
        );
        return $defaultcolumns;
    }

    protected function define_defaultfilters() {
        $defaultfilters = array(
        );
        return $defaultfilters;
    }

    protected function define_requiredcolumns() {
        $requiredcolumns = array(
            new rb_column(
                'message_values',
                'dismiss_link',
                get_string('dismissmsg', 'rb_source_totaramessages'),
                'base.messageid',
                array('displayfunc' => 'dismiss_link',
                      'required' => true,
                      'noexport' => true,
                      //'capability' => 'moodle/local:updatethingy',
                      'nosort' => true)
            ),
        );
        return $requiredcolumns;
    }

    //
    //
    // Source specific column display methods
    //
    //

    // generate urgency icon link
    function rb_display_urgency_link($comp, $row, $export = 0) {
        global $OUTPUT;
        $display = totara_message_urgency_text($row->message_values_urgency);
        if ($export) {
            return $display['text'];
        }

        return $OUTPUT->pix_icon($display['icon'], $display['text'], 'moodle', array('title' => $display['text'], 'class' => 'iconsmall'));
    }

    // generate urgency text
    function rb_display_urgency_text($urgency, $row) {
        $display = totara_message_urgency_text($urgency);
        return $display['text'];
    }

    // generate type icon link
    function rb_display_msgtype_link($comp, $row, $export = 0) {
        global $OUTPUT;
        $subject = format_string($row->msgsubject);
        $icon = !empty($row->msgicon) ? format_string($row->msgicon) : 'default';
        if ($export) {
            return $this->rb_display_msgtype_text($comp, $row);
        }
        return $OUTPUT->pix_icon("/msgicons/" . $icon, $subject, 'totara_core', array('title' => $subject));
    }

    // generate status type text
    function rb_display_msgtype_text($msgtype, $row) {
        $display = totara_message_msgtype_text($msgtype);
        return $display['text'];
    }

    /**
     * Display category
     * @param string $comp
     * @param stdClass $row
     * @return string
     */
    function rb_display_msgcategory_text($comp, $row) {
        global $TOTARA_MESSAGE_CATEGORIES;
        if ($comp != '' && in_array($comp, $TOTARA_MESSAGE_CATEGORIES)) {
            return get_string($comp, 'totara_message');
        }
        return $comp;
    }

    // generate dismiss message link
    function rb_display_dismiss_link($id, $row) {
        $out = totara_message_dismiss_action($id);
        $out .= html_writer::checkbox('totara_message_' . $id, $id, false, '', array('id' => 'totara_msgcbox_'.$id, 'class' => "selectbox"));

        return $out;
    }

    // generate message checkbox
    function rb_display_message_checkbox($id, $row) {
        return html_writer::checkbox('totara_message_' . $id, $id, false, '', array('id' => 'totara_message', 'class' => "selectbox"));
    }

    //
    //
    // Source specific filter display methods
    //
    //

    function rb_filter_message_urgency_list() {
        global $CFG;
        require_once($CFG->dirroot . '/totara/message/messagelib.php');
        $urgencyselect = array();
        $urgencyselect[TOTARA_MSG_URGENCY_NORMAL] = get_string('urgencynormal', 'totara_message');
        $urgencyselect[TOTARA_MSG_URGENCY_URGENT] = get_string('urgencyurgent', 'totara_message');
        return $urgencyselect;
    }

    function rb_filter_message_type_list() {
        global $OUTPUT;
        $out = array();

        $componentskeys = array_flip(array('competency', 'course', 'evidence', 'facetoface', 'learningplan', 'objective', 'resource', 'program'));
        if (totara_feature_disabled('competencies')) {
            unset($componentskeys['competency']);
        }
        if (totara_feature_disabled('learningplans')) {
            unset($componentskeys['learningplan']);
        }
        if (totara_feature_disabled('programs') && totara_feature_disabled('certifications')) {
            unset($componentskeys['program']);
        }
        $components = array_flip($componentskeys);

        foreach ($components as $type) {
            $typename = get_string($type, 'totara_message');
            $out[$type] = $OUTPUT->pix_icon('/msgicons/' . $type . '-regular', $typename, 'totara_core') . '&nbsp;' . $typename;
        }

        return $out;
    }

    public function get_required_jss() {
        global $CFG;

        require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');
        $code = array();
        $code[] = TOTARA_JS_DIALOG;
        local_js($code);

        $jsdetails = new stdClass();
        $jsdetails->initcall = 'M.totara_message.init';
        $jsdetails->jsmodule = array('name' => 'totara_message',
            'fullpath' => '/totara/message/module.js');
        $jsdetails->strings = array(
            'block_totara_alerts' => array('reviewitems')
        );

        return array($jsdetails);
    }
}
