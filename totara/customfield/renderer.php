<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

/**
* Standard HTML output renderer for totara_customfield module
*/
class totara_customfield_renderer extends plugin_renderer_base {

    /**
    * Display table with customfields and options to create a new one.
    *
    * @param array $fields the customfield object.
    * @param bool  $can_manage the customfield object.
    * @param array $options Options of custom field types that can be created.
    * @param string $urlbase The url that manage all the actions.
    * @param array $paramsurlbase The url params base used to create the urls.
    * @return string HTML to output.
    */
    public function totara_customfield_print_list($fields, $can_manage, $options, $urlbase, $paramsurlbase) {
        $table = new \html_table();
        $table->head  = array(get_string('customfield', 'totara_customfield'), get_string('type', 'totara_hierarchy'));

        if ($can_manage) {
            $table->head[] = get_string('edit');
        }
        $table->id = 'customfields_program';
        $table->data = array();

        $fieldcount = count($fields);
        $position = 1;
        foreach ($fields as $field) {

            $reserved = false;
            $hook = new \totara_customfield\hook\field_form_render_data($reserved, $field, $paramsurlbase);
            $hook->execute();

            $helpicon = $reserved ? $this->output->help_icon('customfieldshortnamereserved', 'totara_customfield') : '';
            $class    = $field->hidden ? 'dimmed_text' : '';
            $fullname = \html_writer::span(format_string($field->fullname), $class);
            $row = array($fullname.$helpicon, get_string('customfieldtype'.$field->datatype, 'totara_customfield'));
            if ($can_manage) {
                $row[] = $this->customfield_edit_icons($field, $fieldcount, $urlbase, $paramsurlbase, $can_manage, $position);
            }
            $table->data[] = $row;
            $position++;
        }

        $output = '';
        if (count($table->data)) {
            $output .= html_writer::table($table);
        } else {
            $output .= $this->output->notification(get_string('nocustomfieldsdefined', 'totara_customfield'));
        }
        $output .= html_writer::empty_tag('br');

        if ($can_manage) {
            $paramsurlbase['id'] = 0;
            $paramsurlbase['action'] = 'editfield';
            $urlbase =  new moodle_url($urlbase, $paramsurlbase);
            $select = new single_select($urlbase, 'datatype', $options, '', array('' => 'choosedots'), 'newfieldform');
            $select->set_label(get_string('createnewcustomfield', 'totara_customfield'));
            $output .= $this->output->render($select);
        }

        return $output;
    }

    /**
     * Generate customfield delete confirmation box.
     *
     * @param int $datacount
     * @param string $redirectpage
     * @param array $optioncontinue
     * @param array $optioncancel
     * @return string
     */
    public function totara_customfield_delete_confirmation($datacount, $redirectpage, $optioncontinue, $optioncancel) {
        switch ($datacount) {
            case 0:
                $deletestr = get_string('confirmfielddeletionnodata', 'totara_customfield');
                break;
            case 1:
                $deletestr = get_string('confirmfielddeletionsingle', 'totara_customfield');
                break;
            default:
                $deletestr = get_string('confirmfielddeletionplural', 'totara_customfield', $datacount);
        }
        $formcontinue = new single_button(new moodle_url($redirectpage, $optioncontinue), get_string('yes'), 'post');
        $formcancel = new single_button(new moodle_url($redirectpage, $optioncancel), get_string('no'), 'get');

        return $this->output->confirm($deletestr, $formcontinue, $formcancel);
    }

    /**
     * Get admin page.
     *
     * @param string $prefix
     * @return string
     */
    public function get_admin_page($prefix) {
        switch ($prefix) {
            case 'program':
                return 'programcustomfields';
                break;
            case 'course':
                return 'coursecustomfields';
                break;
            default:
                return $prefix . 'typemanage';
                break;
        }
    }

    /**
     * Generate the navbar according to the customfield prefix type.
     *
     * @param string $prefix
     * @param string $fullname
     * @return array
     */
    public function get_navbar($prefix, $fullname) {
        $navbar = array();
        switch ($prefix) {
            case 'program':
                $navbar[] = array(get_string('programcertcustomfields', 'totara_customfield'));
                break;
            case 'course':
                $navbar[] = array(get_string('coursecustomfields', 'totara_customfield'));
                break;
            default:
                $navbar[] = array(
                    get_string($prefix.'types', 'totara_hierarchy'),
                    new moodle_url('/totara/hierarchy/type/index.php',
                    array('prefix' => $prefix))
                );
                $navbar[] = array(format_string($fullname));
                break;
        }
        return $navbar;
    }

    /**
     * Get the page title
     *
     * @param string $prefix
     * @return string The page title
     */
    public function get_page_title($prefix) {
        switch ($prefix) {
            case 'program':
                return format_string(get_string('programcertcustomfields', 'totara_customfield'));
                break;
            case 'course':
                return format_string(get_string('coursecustomfields', 'totara_customfield'));
                break;
            default:
                return format_string(get_string($prefix.'depthcustomfields', 'totara_hierarchy'));
                break;
        }
    }

    /**
     * Get heading according to the prefix type.
     *
     * @param string $prefix Customfield prefix
     * @param string $action the action being executed
     * @param string $heading optional Predefined heading.
     * @return string
     */
    public function get_heading($prefix, $action, $heading = '') {
        // Set default heading.
        switch ($prefix) {
            case 'program':
                $strheading = get_string('programcertcustomfields', 'totara_customfield');
                break;
            case 'course':
                $strheading = get_string('coursecustomfields', 'totara_customfield');
                break;
            case 'evidence':
                $strheading = get_string('availableevdiencecustomfields', 'totara_customfield');
                break;
            default:
                $strheading = format_string($heading);
                break;
        }

        // Heading if action is set.
        switch ($action) {
            case 'createfield':
                $strheading = get_string('createnewfield', 'totara_customfield', $heading);
                break;
            case 'editfield':
                $strheading = get_string('editfield', 'totara_customfield', format_string($heading));
                break;
            case 'deletefield':
                $strheading = get_string('deletefield', 'totara_customfield');
        }

        return $this->output->heading($strheading);
    }

    /**
     * Get link or tabs depending on the customfield prefix type.
     *
     * @param $prefix
     * @param $urlparams
     * @return string
     */
    public function customfield_tabs_link($prefix, $urlparams) {
        switch ($prefix) {
            case 'program':
            case 'course':
                return $this->customfield_management_tabs($prefix);
                break;
            case 'goal':
            case 'position':
            case 'organisation':
            case 'competency':
                // Return link.
                $urlbase = new moodle_url('/totara/hierarchy/type/index.php', $urlparams);
                $text = "&laquo; " . get_string('alltypes', 'totara_hierarchy');
                return html_writer::tag('p', $this->output->action_link($urlbase, $text));
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * Manage customfield tabs displayed in customfield/index.php
     *
     * @param string $currenttab
     * @return string tabs
     */
    public function customfield_management_tabs($currenttab = 'general') {
        $tabs = array();
        $row = array();
        $activated = array();
        $inactive = array();

        $systemcontext = context_system::instance();

        $row[] = new tabobject('course', new moodle_url('/totara/customfield/index.php', array('prefix' => 'course')),
            get_string('courses'));
        if ((totara_feature_visible('programs') || totara_feature_visible('certifications')) && has_capability('totara/core:programmanagecustomfield', $systemcontext)) {
            $row[] = new tabobject('program', new moodle_url('/totara/customfield/index.php', array('prefix' => 'program')),
                get_string('programscerts', 'totara_program'));
        }

        $tabs[] = $row;
        $activated[] = $currenttab;

        return print_tabs($tabs, $currenttab, $inactive, $activated, true);
    }

    /**
     * Get redirect url params
     *
     * @param string $prefix
     * @param int $id
     * @param int $typeid
     * @param string $class
     * @return array
     */
    public function get_redirect_options($prefix, $id, $typeid, $class) {
        $redirectoptions = array('prefix' => $prefix);

        if ($typeid) {
            $redirectoptions['typeid'] = $typeid;
        }

        if ($id) {
            $redirectoptions['id'] = $id;
        }

        if ($class) {
            $redirectoptions['class'] = $class;
        }

        return $redirectoptions;
    }

    /**
     * Create a string containing the editing icons for custom fields
     * @param   object   $field the field object.
     * @param   int      $fieldcount the fieldcount.
     * @param   string   $urlbase Url where all the actions should be pointing at.
     * @param   array    $paramsurlbase Url params.
     * @param   bool     $can_manage Can the user edit custom fields.
     * @param   int      $fieldposition The field position, between 1 and $fieldcount.
     * @return  string   the icon string
     */
    public function customfield_edit_icons($field, $fieldcount, $urlbase, $paramsurlbase, $can_manage, $fieldposition = null) {
        global $OUTPUT;

        if (empty($str)) {
            $strdelete   = get_string('delete');
            $strmoveup   = get_string('moveup');
            $strmovedown = get_string('movedown');
            $stredit     = get_string('edit');
            $strhide     = (bool)$field->hidden ? get_string('show') : get_string('hide');
        }

        $editstr = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
        $deletestr = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
        $upstr = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
        $downstr = $OUTPUT->spacer(array('height' => 11, 'width' => 11));
        $hidestr = $OUTPUT->spacer(array('height' => 11, 'width' => 11));

        // Set id in the urlbase for all the actions.
        $paramsurlbase['id'] = $field->id;

        if ($can_manage) {
            $params = $paramsurlbase;
            $params['action'] = 'editfield';
            $editstr = $OUTPUT->action_icon(new moodle_url($urlbase, $params),
                new pix_icon('t/edit', $stredit), null, array('title' => $stredit));

            $params = $paramsurlbase;
            $params['action'] = 'hide';
            $icon = (bool)$field->hidden ? 't/show' : 't/hide';
            $hidestr = $OUTPUT->action_icon(new moodle_url($urlbase, $params),
                new pix_icon($icon, $strhide), null, array('title' => $strhide));

            $can_delete = true;
            $hook = new \totara_customfield\hook\field_form_render_icons($can_delete, $field, $paramsurlbase);
            $hook->execute();
            if ($can_delete) {
                $params = $paramsurlbase;
                $params['action'] = 'deletefield';
                $deletestr = $OUTPUT->action_icon(new moodle_url($urlbase, $params),
                    new pix_icon('t/delete', $strdelete), null, array('title' => $strdelete));
            }
        }

        if ($fieldcount > 1 && $can_manage) {
            if (is_null($fieldposition)) {
                // It wasn't passed, we don't know.
                $moveup = ($field->sortorder > 1);
                $movedown = ($field->sortorder < $fieldcount);
            } else {
                $moveup = $fieldposition > 1;
                $movedown = $fieldposition < $fieldcount;
            }
            // Only show up and down if there are two or more fields.
            if ($moveup) {
                $params = $paramsurlbase;
                $params['action'] = 'movefield';
                $params['dir'] = 'up';
                $params['sesskey'] = sesskey();
                $upstr = $OUTPUT->action_icon(
                    new moodle_url($urlbase, $params),
                    new pix_icon('t/up', $strmoveup),
                    null,
                    array('title' => $strmoveup)
                );
            }
            if ($movedown) {
                $params = $paramsurlbase;
                $params['action'] = 'movefield';
                $params['dir'] = 'down';
                $params['sesskey'] = sesskey();
                $downstr = $OUTPUT->action_icon(
                    new moodle_url($urlbase, $params),
                    new pix_icon('t/down', $strmovedown),
                    null,
                    array('title' => $strmovedown)
                );
            }
        }

        return $editstr . $hidestr . $deletestr . $upstr . $downstr;
    }

    /**
     * @param string $prefix The prefix customfield type.
     * @param int $typeid Type ID in case it's a hierarchy.
     * @param string $tableprefix The table prefix where the customfield definition are.
     * @param stdClass $field Customfield information
     * @param moodle_url $redirect The redirect url.
     * @param string $heading Heading to be displayed.
     * @param $tabs (optional) Tabs to be displayed.
     * @param array $elements (optional) Aditional form fields for the customfield.
     * @param \totara_customfield\prefix\type_base|null $customfieldtype The custom field type if known.
     * @param string $class
     */
    public function customfield_manage_edit_form($prefix, $typeid, $tableprefix, $field,
        $redirect, $heading, $tabs, $elements = array(), $class = '', \totara_customfield\prefix\type_base $customfieldtype = null) {

        global $CFG, $TEXTAREA_OPTIONS;
        require_once($CFG->dirroot . '/totara/customfield/index_field_form.php');

        $datatype = $field->datatype;
        require_once($CFG->dirroot.'/totara/customfield/index_field_form.php');
        $field->descriptionformat = FORMAT_HTML;
        $field = file_prepare_standard_editor($field, 'description', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_customfield', 'textarea', $field->id);
        if ($datatype == 'textarea') {
            $field->defaultdataformat = FORMAT_HTML;
            $field = file_prepare_standard_editor($field, 'defaultdata', $TEXTAREA_OPTIONS, $TEXTAREA_OPTIONS['context'], 'totara_customfield', 'textarea', $field->id);
        }

        $datatosend = array('datatype' => $datatype,
            'prefix' => $prefix, 'typeid' => $typeid, 'tableprefix' => $tableprefix, 'additionalelements' => $elements, 'class' => $class);
        $fieldform = new \field_form(null, $datatosend);
        $fieldform->set_data($field);

        if ($fieldform->is_cancelled()) {
            redirect($redirect);
        } else {
            if ($data = $fieldform->get_data()) {
                require_once($CFG->dirroot.'/totara/customfield/field/'. $datatype .'/define.class.php');
                $newfield = 'customfield_define_'. $datatype;
                /** @var customfield_define_base $formfield */
                $formfield = new $newfield();
                $formfield->define_save($data, $tableprefix, $customfieldtype);
                redirect($redirect);
            }
            echo $this->output->header();
            echo $tabs;
            echo $heading;
            $fieldform->display();
        }
    }

    public function customfield_render($datatype, $fielddata, $options = array()) {
        global $CFG;

        if (empty($datatype)) {
            return "";
        }

        $customfieldclassfile = $CFG->dirroot . '/totara/customfield/field/' . $datatype . '/field.class.php';
        if (!file_exists($customfieldclassfile)) {
            return "";
        }

        require_once($customfieldclassfile);

        $fieldname = 'customfield_'. $datatype;
        return $fieldname::display_item_data($fielddata, $options);
    }
}
