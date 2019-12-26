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
 * @author Aaron Wells <aaronw@catalyst.net.nz>
 * @package totara
 * @subpackage cohort/rules
 */
/**
 * This class defines the cohort_rule_ui class and its subclasses, which
 * handle the front-end stuff for rules for dynamic cohorts
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}
require_once($CFG->dirroot.'/totara/cohort/rules/lib.php');
require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_certifications.class.php');


/**
 * An empty form, useful for individual UIs to create their own mini-forms
 */
class emptyruleuiform extends moodleform {
    public function __construct($action){
        parent::__construct($action, null, 'post', '', null, true, null, 'cohortruledialogform');
    }
    public function definition(){}

}

/**
 * Base class for a cohort ui. This handles all the content that goes inside the dialog for the rule,
 * also processing the input from the dialog, and printing a description of the rule
 */
abstract class cohort_rule_ui {
    /**
     * These variables will match one of the group & names in the rule definition list
     * @var string
     */
    public $group, $name;

    public $ruleinstanceid;

    /**
     * A list of the parameters this rule passes on to its sqlhandler. (The sqlhandler's $param
     * variable should match exactly.)
     * @var array
     */
    public $params = array(
        'operator' => 0,
        'lov' => 1
    );

    /**
     * The actual values to the parameters (if we're printing a dialog to edit an existing rule instance)
     * @var unknown_type
     */
    public $paramvalues = array();

    /**
     * Which dialog handler type should be used. The dialog handler types are defined in cohort/rules/ruledialog.js.php
     * @var string
     */
    public $handlertype = '';

    public function setGroupAndName($group, $name) {
        $this->group = $group;
        $this->name = $name;
    }

    public function setParamValues($paramvalues) {
        $this->paramvalues = $paramvalues;
        foreach ($paramvalues as $k=>$v) {
            $this->{$k} = $v;
        }
    }

    /**
     *
     * @param array $hidden hidden variables to add to forms in the dialog (if needed)
     * @param int $ruleinstanceid The instance of the rule, or false if for a new rule
     */
    abstract public function printDialogContent($hidden=array(), $ruleinstanceid=false);

    /**
     *
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    abstract public function handleDialogUpdate($sqlhandler);

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    abstract public function getRuleDescription($ruleid, $static=true);

    /**
     * Print the user params (used in logging)
     */
    public function printParams() {
        $ret = '';
        foreach ($this->params as $k=>$v) {
            $ret .= $k.':'.print_r($this->{$k}, true)."\n";
        }
        return $ret;
    }

    /**
     * Validate the response
     */
    public function validateResponse() {
        return true;
    }
    /**
     * @global core_renderer $OUTPUT
     * @param int $paramid
     * @return string
     */
    public function param_delete_action_icon($paramid) {
        global $OUTPUT;

        $icon = new \core\output\flex_icon('delete', array(
            'alt' => get_string('deleteruleparam', 'totara_cohort'),
            'classes' => 'ruleparam-delete'
        ));
        return $OUTPUT->action_icon('#', $icon, null, array('data-ruleparam-id' => $paramid));
    }

    /**
     * A method of adding missing rule params within all the rule's instances that are going to be added into the rule
     * description. Before returning as a complete string, within method getRuleDescription, this method should be called
     * to detect any parameters within rule are actually invalid ones.
     *
     * @param array $ruledescriptions   => passed by references, as it needed to be updated
     * @param int   $ruleinstanceid     => The rule's id that is going to be checked against
     * @param bool  $static             => Whether the renderer is about displaying readonly text or read with action text
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static=true) {
        // Implementation at the children level
        return;
    }
}

/**
 * For cohorts that use the form handler as their UI
 */
abstract class cohort_rule_ui_form extends cohort_rule_ui {

    public $handlertype = 'form';
    public $formclass = 'emptyruleuiform';
    protected $rule = null;

    /**
     *
     * @var emptyruleuiform
     */
    public $form = null;

    public function validateResponse() {
        $form = $this->constructForm();
        if (!$form->is_validated()){
            return false;
        }
        return true;
    }

    public function constructForm(){
        global $CFG;
        if ($this->form == null) {
            $this->form = new $this->formclass($CFG->wwwroot.'/totara/cohort/rules/ruledetail.php');

            /* @var $mform MoodleQuickForm */
            $mform = $this->form->_form;

            // Add hidden variables
            $mform->addElement('hidden', 'update', 1);
            $mform->setType('update', PARAM_INT);

            $this->form->set_data($this->addFormData());
            $this->addFormFields($mform);
        }
        return $this->form;
    }

    /**
     *
     * @param array $hidden An array of values to be passed into the form as hidden variables
     */
    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $OUTPUT, $PAGE;

        if (isset($hidden['rule'])) {
            $this->rule = $hidden['rule'];
        }
        echo $OUTPUT->heading(get_string('ruledialogdesc', 'totara_cohort', $this->description), '2', 'cohort-rule-dialog-heading');
        echo $OUTPUT->box_start('cohort-rule-dialog-setting');

        $form = $this->constructForm();
        foreach ($hidden as $name=>$value) {
            $form->_form->addElement('hidden', $name, $value);
        }
        $form->display();
        echo $OUTPUT->box_end();
        echo $PAGE->requires->get_end_code(false);
    }

    /**
     * Get items to add to the form's formdata
     * @return array The data to add to the form
     */
    protected function addFormData() {
        return $this->paramvalues;
    }

    /**
     * Add form fields to this form's dialog. (This should usually be over-ridden by subclasses.)
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {
        $mform->addElement('static', 'noconfig', '', get_string('ruleneedsnoconfiguration', 'totara_cohort'));
    }
}


/**
 * UI for a rule that is defined by a text field (which takes a comma-separated list of values) and an equal/not-equal operator.
 */
class cohort_rule_ui_text extends cohort_rule_ui_form {
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /**
     *
     * @param string $description Brief description of this rule
     * @param string $example Example text to put below the text field
     */
    public function __construct($description, $example) {
        $this->description = $description;
        $this->example = $example;
    }

    /**
     * Fill in default form data. For this dialog, we need to take the listofvalues and concatenate it
     * into a comma-separated list
     * @return array
     */
    protected function addFormData() {
        // Figure out starting data
        $formdata = array();
        if (isset($this->equal)) {
            $formdata['equal'] = $this->equal;
        }
        if (isset($this->listofvalues)) {
            $formdata['listofvalues'] = implode(',',$this->listofvalues);
        }
        return $formdata;
    }

    /**
     * Form elements for this dialog. That'll be the equal/notequal menu, and the text field
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {

        // Put everything in one row to make it look cooler
        global $COHORT_RULES_OP_IN_LIST;
        $row = array();
        $row[0] = $mform->createElement(
            'select',
            'equal',
            '',
            $COHORT_RULES_OP_IN_LIST
        );
        $row[1] = $mform->createElement('text', 'listofvalues', '');
        $mform->addGroup($row, 'row1', ' ', ' ', false);
        if (isset($this->example)) {
            $mform->addElement('static', 'exampletext', '', $this->example);
        }

        // Make sure they filled in the text field
        $mform->addGroupRule(
            'row1',
                array(
                    1 => array(
                        array(0 => get_string('error:mustpickonevalue', 'totara_cohort'), 1 => 'callback', 2 => 'validate_emptyruleuiform', 3 => 'client')
                    )
                )
        );

        $error = get_string('error:mustpickonevalue', 'totara_cohort');
        $isemptyopt = COHORT_RULES_OP_IN_ISEMPTY;

        // Allow empty value for ​​listofvalues as long as the rule is "is empty"
        $js = <<<JS
<script type="text/javascript">
function validate_emptyruleuiform() {
    var sucess = true;

    if ($('#id_listofvalues').val() === '' && $('#id_equal').val() !== '$isemptyopt') {
        if ($('#id_error_listofvalues').length == 0 ) {
            $('div#fgroup_id_row1 > fieldset').prepend('<span id="id_error_listofvalues" class="error">{$error}</span><br>');
        }
        sucess = false;
    }
    return sucess;
}
</script>
JS;
        $mform->addElement('html', $js);
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULES_OP_IN_LIST;
        if (!isset($this->equal) || !isset($this->listofvalues)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;
        $strvar->join = $COHORT_RULES_OP_IN_LIST[$this->equal];

        // Show list of values only if the rule is different from "is_empty"
        $strvar->vars = '';
        if ($this->equal != COHORT_RULES_OP_IN_ISEMPTY) {
            $strvar->vars = '"' . htmlspecialchars(implode('", "', $this->listofvalues)) . '"';
        }

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $equal = required_param('equal', PARAM_INT);
        $listofvalues = required_param('listofvalues', PARAM_RAW);
        $listofvalues = explode(',', $listofvalues);
        array_walk(
            $listofvalues,
            function(&$value, $key){
                $value = trim($value);
            }
        );
        $this->equal = $sqlhandler->equal = $equal;
        $this->listofvalues = $sqlhandler->listofvalues = $listofvalues;
        $sqlhandler->write();
    }
}


/**
 * UI for a rule defined by a multi-select menu, and a equals/notequals operator
 */
class cohort_rule_ui_menu extends cohort_rule_ui_form {
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /**
     * The list of options in the menu. $value=>$label
     * @var array
     */
    public $options;

    /**
     * Create a menu, passing in the list of options
     * @param $menu mixed An array of menu options (value=>label), or a user_info_field1 id
     */
    public function __construct($description, $options){
        $this->description = $description;

        // This may be a string rather than a proper array, but we'll wait to clean
        // it up until it's actually needed.
        $this->options = $options;
    }


    /**
     * The form fields needed for this dialog. That'll be, the "equal/notequal" menu, plus
     * the menu of options. Since the menu of options is a multiple select, it needs validation
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {

        // Put the two menus on one row so it'll look cooler
        $row = array();
        $row[0] = $mform->createElement(
            'select',
            'equal',
            '',
            array(
                // TODO TL-7096 - use COHORT_RULES_OP_IN_ISEQUALTO and COHORT_RULES_OP_IN_NOTEQUALTO, it will require db upgrade.
                COHORT_RULES_OP_IN_EQUAL    => get_string('equalto','totara_cohort'),
                COHORT_RULES_OP_IN_NOTEQUAL => get_string('notequalto', 'totara_cohort')
            )
        );
        if (is_object($this->options)) {
            $options = $this->options_from_sqlobj($this->options);
        } else {
            $options = $this->options;
        }
        // Remove empty values from select $options.
        // Should not use UserCustomField(Choose) to select empty values.
        $options = array_filter($options);
        $row[1] = $mform->createElement(
            'select',
            'listofvalues',
            '',
            $options,
            array('size' => 10)
        );
        // todo: The UI mockup shows a fancy ajax thing to add/remove selected items.
        // For now, using a humble multi-select
        $row[1]->setMultiple(true);
        $mform->addGroup($row, 'row1', '', '', false);

        // Make sure they selected at least one item from the multi-select. Sadly, formslib's
        // client-side stuff is broken for multi-selects (because it adds "[]" to their name),
        // so we'll need to do this as a custom callback rule. And because it only executes
        // custom callback rules if the field actually contains a value, we'll key it to the
        // equal/notequal menu, which will always have a value.
        $mform->addGroupRule(
            'row1',
                array(
                    0=>array(
                        array(0=>get_string('error:mustpickonevalue', 'totara_cohort'), 1=>'callback',2=>'validate_menu', 3=>'client')
                    )
                )
        );
        $js = <<<JS
<script type="text/javascript">
function validate_menu(value) {
    return $('#id_listofvalues option:selected').length;
}
</script>
JS;
        $mform->addElement('html', $js);
    }


    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $equal = required_param('equal', PARAM_INT);
        $listofvalues = required_param_array('listofvalues', PARAM_TEXT);
        if (!is_array($listofvalues)) {
            $listofvalues = array($listofvalues);
        }
        array_walk(
            $listofvalues,
            function(&$value, $key){
                $value = trim($value);
            }
        );
        $this->equal = $sqlhandler->equal = $equal;
        $this->listofvalues = $sqlhandler->listofvalues = $listofvalues;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULES_OP_IN;
        // TODO TL-7096 - use COHORT_RULES_OP_IN_ISEQUALTO and COHORT_RULES_OP_IN_NOTEQUALTO, it will require db upgrade.

        if (!isset($this->equal) || !isset($this->listofvalues)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;
        $strvar->join = get_string("is{$COHORT_RULES_OP_IN[$this->equal]}to", 'totara_cohort');

        if (is_object($this->options)) {
            $selected = $this->options_from_sqlobj($this->options, $this->listofvalues);
        } else {
            $selected = array_intersect_key($this->options, array_flip($this->listofvalues));
        }

        array_walk($selected, function (&$value, $key) {
            // Adding quotations marks here.
            $value = htmlspecialchars("\"{$value}\"");
        });

        $this->add_missing_rule_params($selected, $ruleid, $static);
        // append the list of selected items
        $strvar->vars = implode(', ', $selected);

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * A method for validating the form submitted data
     * @return bool
     */
    public function validateResponse() {
        /** @var core_renderer $OUTPUT */
        global $OUTPUT;
        $form = $this->constructForm();
        if ($data = $form->get_submitted_data()) {
            // Checking whether the listofvalues being passed is empty or not. If it is empty, error should be returned
            if (empty($data->listofvalues)) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }
            return true;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }

    /**
     * Retrieve menu options by constructing sql string from an sql object
     * and then querying the database
     *
     * @param object $sqlobj the sql object instance to construct the query from
     *                      e.g stdClass Object
                                (
                                    [select] => DISTINCT data AS mkey, data AS mval
                                    [from] => {user_info_data}
                                    [where] => fieldid = ?
                                    [orderby] => data
                                    [valuefield] => data
                                    [sqlparams] => Array
                                        (
                                            [0] => 1
                                        )

                                )
     * @param array $selectedvals selected values (optional)
     * @return array of menu options
     */
    protected function options_from_sqlobj($sqlobj, $selectedvals=null) {
        global $DB;

        $sql = "SELECT {$sqlobj->select} FROM {$sqlobj->from} ";

        $sqlparams = array();
        if ($selectedvals !== null) {
            if (!empty($selectedvals)) {
                list($sqlin, $sqlparams) = $DB->get_in_or_equal($selectedvals);
            } else {
                // dummiez to ensure nothing gets returned :D
                $sqlin = ' IN (?) ';
                $sqlparams = array(0);
            }
        }
        if (empty($sqlobj->where)) {
            $sql .= ' WHERE ';
        } else {
            $sql .= " WHERE {$sqlobj->where} ";
        }
        if (!empty($sqlin)) {
            $sql .= " AND {$DB->sql_compare_text($sqlobj->valuefield, 255)} {$sqlin} ";
        }

        if (!empty($sqlobj->orderby)) {
            $sql .= " ORDER BY {$sqlobj->orderby}";
        }

        if (!empty($sqlobj->sqlparams)) {
            $sqlparams = array_merge($sqlobj->sqlparams, $sqlparams);
        }

        return $DB->get_records_sql_menu($sql, $sqlparams, 0, COHORT_RULES_UI_MENU_LIMIT);
    }

    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofvalues)) {
            // Detected that there are missing records in cohort's rules params.
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofvalues'
            ), "", " value AS optionid, id AS paramid");

            if (is_object($this->options)) {
                $options = $this->options_from_sqlobj($this->options);
            } else {
                $options = $this->options;
            }

            foreach ($this->listofvalues as $optioninstanceid) {
                if (!isset($options[$optioninstanceid])) {
                    $item = isset($fullparams[$optioninstanceid]) ? $fullparams[$optioninstanceid] : null;
                    if (!$item) {
                        debugging("Missing {$optioninstanceid} in full params");
                        continue;
                    }

                    $a = (object) array('id' => $optioninstanceid);
                    $value = "\"" . get_string("deleteditem", "totara_cohort", $a) . "\"";

                    $ruledescriptions[$optioninstanceid] = html_writer::tag('span', $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}


/**
 * UI for a rule that indicates whether or not a checkbox is ticked
 */
class cohort_rule_ui_checkbox extends cohort_rule_ui_menu {
    public $params = array(
        'equal' => 0,
        'listofvalues' => 1
    );

    /**
     * The list of options in the menu. $value=>$label
     * @var array
     */
    public $options;

    /**
     * Create a menu, passing in the list of options
     * @param $menu mixed An array of menu options (value=>label), or a user_info_field1 id
     */
    public function __construct($description, $options=false){
        $this->description = $description;

        // This may be a string rather than a proper array, but we'll wait to clean
        // it up until it's actually needed.
        if (!$options){
            $this->options = array(
                0=>get_string('checkboxno', 'totara_cohort'),
                1=>get_string('checkboxyes', 'totara_cohort')
            );
        } else {
            $this->options = $options;
        }
    }

    /**
     * The form elements needed for this UI (just the "checked/not-checked" menu!)
     * @param MoodleQuickForm $mform
     */
    protected function addFormFields(&$mform) {
        $mform->addElement(
            'select',
            'listofvalues',
            '',
            $this->options
        );
    }

    /**
     * A method for validating the form submitted data
     * @return bool
     */
    public function validateResponse() {
        /** @var core_renderer $OUTPUT */
        global $OUTPUT;
        $form = $this->constructForm();
        if ($data = $form->get_submitted_data()) {
            // Checking whether the listofvalues being passed is set, and in the acceptable options.
            if (!isset($data->listofvalues) || !in_array($data->listofvalues, [0,1])) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('rule_selector_failure', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
                return false;
            }
            return true;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }


    /**
     * Process the data returned by this UI element's form elements
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $listofvalues = required_param('listofvalues', PARAM_BOOL);
        if (is_array($listofvalues)) {
            $listofvalues = array_pop($listofvalues);
        }
        // Checkbox operator is always "equal"
        $this->equal = $sqlhandler->equal = 1;
        $this->listofvalues = $sqlhandler->listofvalues = (int) $listofvalues;
        $sqlhandler->write();
        $this->listofvalues = array($listofvalues);
    }
}


/**
 * An empty form with validation for a cohort_rule_ui_date
 */
class ruleuidateform extends emptyruleuiform {
    public function validation($data, $files){
        $errors = parent::validation($data, $files);

        // If they haven't ticked the radio button (somehow), then print an error text over the top row,
        // and highlight the bottom row but without any error text
        if (empty($data['fixedordynamic']) || !in_array($data['fixedordynamic'], array(1,2))) {
            $errors['beforeafterrow'] = get_string('error:baddateoption', 'totara_cohort');
            $errors['durationrow'] = ' ';
        }

        if ($data['fixedordynamic'] == 1 && empty($data['beforeafterdatetime']) &&
            (
                empty($data['beforeafterdate'])
                || !preg_match('/^[0-9]{1,2}[\/\-][0-9]{1,2}[\/\-](19|20)?[0-9]{2}$/', $data['beforeafterdate'])
            )
        ) {
            $errors['beforeafterrow'] = get_string('error:baddate', 'totara_cohort');
        }

        if (
            $data['fixedordynamic'] == 2
            && (
                !isset($data['durationdate'])
                || !preg_match('/^[0-9]+$/', $data['durationdate'])
            )
        ) {
            $errors['durationrow'] = get_string('error:badduration', 'totara_cohort');
        }

        return $errors;
    }
}


/**
 * UI for a rule that needs a date picker
 */
class cohort_rule_ui_date extends cohort_rule_ui_form {

    public $params = array(
        'operator' => 0,
        'date' => 0,
    );

    public $description;

    public $formclass = 'ruleuidateform';

    public function __construct($description){
        $this->description = $description;
    }

    /**
     * Fill in the default form values. For this dialog, we need to specify which of the two
     * rows is active based on the selected operator. And if it's the date row, we need to
     * format the date from a timestamp to a user date
     */
    protected function addFormData() {
        global $CFG;

        // Set up default values and stuff
        $formdata = array();
        $formdata['fixedordynamic'] = 1;
        if (isset($this->operator)) {
            if ($this->operator == COHORT_RULE_DATE_OP_AFTER_FIXED_DATE || $this->operator == COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE) {
                $formdata['fixedordynamic'] = 1;
                $formdata['beforeaftermenu'] = $this->operator;
                if (!empty($this->date)) {
                    $formdata['beforeafterdatetime'] = $this->date;
                }
            } else if (
                    in_array(
                        $this->operator,
                        array(
                            COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION,
                            COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION,
                            COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                            COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION
                        )
                    )
            ) {
                $formdata['fixedordynamic'] = 2;
                $formdata['durationmenu'] = $this->operator;
                if (isset($this->date)) {
                    $formdata['durationdate'] = $this->date;
                }
            } else {
                $formdata['fixedordynamic'] = 1;
            }
        }
        return $formdata;
    }

    /**
     * Form fields for this dialog. We have the elements on two rows, with the top row being for before/after a fixed date,
     * and the bottom row being for before/after/within a fixed present/past duration. A radio button called "fixedordynamic"
     * indicates which one is selected
     *
     * @param MoodleQuickForm $mform
     */
    public function addFormFields(&$mform) {
        global $PAGE;
        $mform->updateAttributes(array('class' => 'dialog-nobind mform'));

        // Put everything on two rows to make it look cooler.
        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', 1);
        $row[1] = $mform->createElement(
            'select',
            'beforeaftermenu',
            '',
            array(
                COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE=>get_string('datemenufixeddatebeforeandon', 'totara_cohort'),
                COHORT_RULE_DATE_OP_AFTER_FIXED_DATE=>get_string('datemenufixeddateafterandon', 'totara_cohort')
            )
        );
        $row[2] = $mform->createElement('date_time_selector', 'beforeafterdatetime', '', array('showtimezone' => true));
        $mform->addGroup($row, 'beforeafterrow', '', null, false);

        $durationmenu = array(
            COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION =>   get_string('datemenudurationbeforepast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION =>   get_string('datemenudurationwithinpast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION => get_string('datemenudurationwithinfuture', 'totara_cohort'),
            COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION =>  get_string('datemenudurationafterfuture', 'totara_cohort'),
        );
        if ($this->rule == 'systemaccess-firstlogin' || $this->rule == 'systemaccess-lastlogin') {
            // Remove COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION and COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION.
            unset($durationmenu[COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION], $durationmenu[COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION]);
        }
        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', 2);
        $row[1] = $mform->createElement('select', 'durationmenu', '', $durationmenu);
        $row[2] = $mform->createElement('text', 'durationdate', '');
        $row[3] = $mform->createElement('static', '', '', get_string('durationdays', 'totara_cohort'));
        $mform->addGroup($row, 'durationrow', '', '', false);

        $mform->disabledIf('beforeaftermenu','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[day]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[month]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[year]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[hour]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[minute]','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdatetime[calendar]','fixedordynamic','neq',1);
        $mform->disabledIf('durationmenu','fixedordynamic','neq',2);
        $mform->disabledIf('durationdate','fixedordynamic','neq',2);
    }

    /**
     * Print a description of the rule in text, for the rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $CFG, $COHORT_RULE_DATE_OP;

        if (!isset($this->operator) || !isset($this->date)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;

        switch ($this->operator) {
            case COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE:
            case COHORT_RULE_DATE_OP_AFTER_FIXED_DATE:
                $a = userdate($this->date, get_string('strftimedatetimelong', 'langconfig'));
                break;
            case COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION:
            case COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION:
            case COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION:
            case COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION:
                $a = $this->date;
                break;
        }

        $strvar->vars = get_string("dateis{$COHORT_RULE_DATE_OP[$this->operator]}", 'totara_cohort', $a);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     *
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler){
        $formdata = $this->form->get_data();
        $fixedordynamic = $formdata->fixedordynamic;
        switch($fixedordynamic) {
            case 1:
                $operator =  $formdata->beforeaftermenu;
                $date = $formdata->beforeafterdatetime;
                break;
            case 2:
                $operator =  $formdata->durationmenu;
                $date = $formdata->durationdate;
                break;
            default:
                return false;
        }
        $this->operator = $sqlhandler->operator = $operator;
        $this->date = $sqlhandler->date = $date;
        $sqlhandler->write();
    }
}


/**
 * UI for rule that uses date without timezone
 */
class cohort_rule_ui_date_no_timezone extends cohort_rule_ui_form {

    public $params = array(
        'operator' => 0,
        'date' => 0,
    );

    public $description;

    public $formclass = 'ruleuidateform';

    public function __construct($description){
        $this->description = $description;
    }

    /**
     * Fill in the default form values. For this dialog, we need to specify which of the two
     * rows is active based on the selected operator. And if it's the date row, we need to
     * format the date from a timestamp to a user date
     *
     * @return array of data to be added to the form
     */
    protected function addFormData() {
        // Set up default values
        $formdata = array();
        $formdata['fixedordynamic'] = 1;
        $formdata['beforeafterdate'] = get_string('datepickerlongyearplaceholder', 'totara_core');
        if (isset($this->operator)) {
            if ($this->operator == COHORT_RULE_DATE_OP_AFTER_FIXED_DATE || $this->operator == COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE) {
                $formdata['fixedordynamic'] = 1;
                $formdata['beforeaftermenu'] = $this->operator;
                if (!empty($this->date)) {
                    // For the custom date field, the date is always saved as UTC.
                    $formdata['beforeafterdate'] = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 'UTC', false);
                }
            } else if (
            in_array(
                $this->operator,
                array(
                    COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION,
                    COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION,
                    COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION,
                    COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION
                )
            )
            ) {
                $formdata['fixedordynamic'] = 2;
                $formdata['durationmenu'] = $this->operator;
                if (isset($this->date)) {
                    $formdata['durationdate'] = $this->date;
                }
            } else {
                $formdata['fixedordynamic'] = 1;
            }
        }
        return $formdata;
    }

    /**
     * Form fields for this dialog. We have the elements on two rows, with the top row being for before/after a fixed date,
     * and the bottom row being for before/after/within a fixed present/past duration. A radio button called "fixedordynamic"
     * indicates which one is selected
     *
     * @param MoodleQuickForm $mform
     */
    public function addFormFields(&$mform) {

        // Put everything on two rows to make it look cooler.
        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', 1);
        $row[1] = $mform->createElement(
            'select',
            'beforeaftermenu',
            '',
            array(
                COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE=>get_string('datemenufixeddatebefore', 'totara_cohort'),
                COHORT_RULE_DATE_OP_AFTER_FIXED_DATE=>get_string('datemenufixeddateafter', 'totara_cohort')
            )
        );
        $row[2] = $mform->createElement('text', 'beforeafterdate', '');
        $mform->addGroup($row, 'beforeafterrow', ' ', ' ', false);

        $datepickerjs = <<<JS
<script type="text/javascript">

    $(function() {
        $('#id_beforeafterdate').datepicker(
            {
                dateFormat: '
JS;
        $datepickerjs .= get_string('datepickerlongyeardisplayformat', 'totara_core');
        $datepickerjs .= <<<JS
',
                showOn: 'both',
                buttonImage: M.util.image_url('t/calendar'),
                buttonImageOnly: true,
                beforeShow: function() { $('#ui-datepicker-div').css('z-index', 1600); },
                constrainInput: true
            }
        );
    });
    </script>
JS;
        $mform->addElement('html', $datepickerjs);
        $durationmenu = array(
            COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION =>   get_string('datemenudurationbeforepast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION =>   get_string('datemenudurationwithinpast', 'totara_cohort'),
            COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION => get_string('datemenudurationwithinfuture', 'totara_cohort'),
            COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION =>  get_string('datemenudurationafterfuture', 'totara_cohort'),
        );
        if ($this->rule == 'systemaccess-firstlogin' || $this->rule == 'systemaccess-lastlogin') {
            // Remove COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION and COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION.
            unset($durationmenu[COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION], $durationmenu[COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION]);
        }
        $row = array();
        $row[0] = $mform->createElement('radio', 'fixedordynamic', '', '', 2);
        $row[1] = $mform->createElement('select', 'durationmenu', '', $durationmenu);
        $row[2] = $mform->createElement('text', 'durationdate', '');
        $row[3] = $mform->createElement('static', '', '', get_string('durationdays', 'totara_cohort'));
        $mform->addGroup($row, 'durationrow', ' ', ' ', false);

        $mform->disabledIf('beforeaftermenu','fixedordynamic','neq',1);
        $mform->disabledIf('beforeafterdate','fixedordynamic','neq',1);
        $mform->disabledIf('durationmenu','fixedordynamic','neq',2);
        $mform->disabledIf('durationdate','fixedordynamic','neq',2);
    }

    /**
     * Print a description of the rule in text, for the rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $CFG, $COHORT_RULE_DATE_OP;

        if (!isset($this->operator) || !isset($this->date)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;

        switch ($this->operator) {
            case COHORT_RULE_DATE_OP_BEFORE_FIXED_DATE:
            case COHORT_RULE_DATE_OP_AFTER_FIXED_DATE:
                $a = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 'UTC', false);
                break;
            case COHORT_RULE_DATE_OP_BEFORE_PAST_DURATION:
            case COHORT_RULE_DATE_OP_WITHIN_PAST_DURATION:
            case COHORT_RULE_DATE_OP_WITHIN_FUTURE_DURATION:
            case COHORT_RULE_DATE_OP_AFTER_FUTURE_DURATION:
                $a = $this->date;
                break;
        }

        $strvar->vars = get_string("dateis{$COHORT_RULE_DATE_OP[$this->operator]}", 'totara_cohort', $a);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * Writes the new rule to the database
     *
     * @param cohort_rule_sqlhandler $sqlhandler
     */
    public function handleDialogUpdate($sqlhandler) {
        $fixedordynamic = required_param('fixedordynamic', PARAM_INT);
        switch($fixedordynamic) {
            case 1:
                $operator = required_param('beforeaftermenu', PARAM_INT);
                $dateparam = required_param('beforeafterdate', PARAM_TEXT);
                $dateformat = get_string('datepickerlongyearparseformat', 'totara_core');
                // We save the date as a timestamp with time of midday UTC.
                $dateparam .= " 12:00:00";
                $dateformat .= " H:i:s";
                $date = totara_date_parse_from_format($dateformat, $dateparam, false, 'UTC');
                break;
            case 2:
                $operator = required_param('durationmenu', PARAM_INT);
                $date = required_param('durationdate', PARAM_INT);
                break;
            default:
                return false;
        }
        $this->operator = $sqlhandler->operator = $operator;
        $this->date = $sqlhandler->date = $date;
        $sqlhandler->write();
    }
}


require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_hierarchy.class.php');
class totara_dialog_content_hierarchy_multi_cohortrule extends totara_dialog_content_hierarchy_multi {

    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param   $elements    array elements to be created in the pane
    * @return  $html
    */
    public function populate_selected_items_pane($elements) {

        $operatormenu = array();
        $operatormenu[1] = get_string('equalto', 'totara_cohort');
        $operatormenu[0] = get_string('notequalto', 'totara_cohort');
        $selected = isset($this->equal) ? $this->equal : '';
        $html = html_writer::select($operatormenu, 'equal', $selected, array(),
            array('id' => 'id_equal', 'class' => 'cohorttreeviewsubmitfield'));

        $childmenu = array();
        $childmenu[0] = get_string('includechildrenno', 'totara_cohort');
        $childmenu[1] = get_string('includechildrenyes', 'totara_cohort');
        $selected = isset($this->includechildren) ? $this->includechildren : '';
        $html .= html_writer::select($childmenu, 'includechildren', $selected, array(),
            array('id' => 'id_includechildren', 'class' => 'cohorttreeviewsubmitfield'));

        return $html . parent::populate_selected_items_pane($elements);
    }
}

require_once($CFG->dirroot.'/totara/hierarchy/lib.php');
class cohort_rule_ui_picker_hierarchy extends cohort_rule_ui {
    public $params = array(
        'equal'=>0,
        'includechildren'=>0,
        'listofvalues'=>1,
    );
    public $handlertype = 'treeview';
    public $prefix;
    public $shortprefix;

    /**
     * @param string $description Brief description of this rule
     */
    public function __construct($description, $prefix) {
        $this->description = $description;
        $this->prefix = $prefix;
        $this->shortprefix = hierarchy::get_short_prefix($prefix);
    }

    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/adminlib.php');

        require_once($CFG->dirroot.'/totara/hierarchy/prefix/competency/lib.php');
        require_once($CFG->dirroot.'/totara/core/js/lib/setup.php');


        ///
        /// Setup / loading data
        ///

        // Competency id
//        $compid = required_param('id', PARAM_INT);

        // Parent id
        $parentid = optional_param('parentid', 0, PARAM_INT);

        // Framework id
        $frameworkid = optional_param('frameworkid', 0, PARAM_INT);

        // Only return generated tree html
        $treeonly = optional_param('treeonly', false, PARAM_BOOL);

        // should we show hidden frameworks?
        $showhidden = optional_param('showhidden', false, PARAM_BOOL);

        // check they have permissions on hidden frameworks in case parameter is changed manually
        $context = context_system::instance();
        if ($showhidden && !has_capability('totara/hierarchy:updatecompetencyframeworks', $context)) {
            print_error('nopermviewhiddenframeworks', 'hierarchy');
        }

        // show search tab instead of browse
        $search = optional_param('search', false, PARAM_BOOL);

        // Setup page
        $alreadyrelated = array();
        $hierarchy = $this->shortprefix;
        if ($ruleinstanceid) {
            $sql = "SELECT hier.id, hier.fullname
                FROM {{$hierarchy}} hier
                INNER JOIN {cohort_rule_params} crp
                    ON hier.id=" . $DB->sql_cast_char2int('crp.value') . "
                INNER JOIN {{$hierarchy}_framework} fw
                    ON hier.frameworkid = fw.id
                WHERE crp.ruleid = {$ruleinstanceid} AND crp.name='listofvalues'
                ORDER BY fw.sortorder, hier.sortthread
                ";
            $alreadyselected = $DB->get_records_sql($sql);
            if (!$alreadyselected) {
                $alreadyselected = array();
            }
        } else {
            $alreadyselected = array();
        }

        ///
        /// Display page
        ///
        // Load dialog content generator
        $dialog = new totara_dialog_content_hierarchy_multi_cohortrule($this->prefix, $frameworkid, $showhidden);

        // Toggle treeview only display
        $dialog->show_treeview_only = $treeonly;

        // Load items to display
        $dialog->load_items($parentid);

        if (!empty($hidden)) {
            $dialog->urlparams = $hidden;
        }

        // Set disabled/selected items
        $dialog->disabled_items = $alreadyrelated;
        $dialog->selected_items = $alreadyselected;
        if (isset($this->equal)) {
            $dialog->equal = $this->equal;
        }
        if (isset($this->includechildren)) {
            $dialog->includechildren = $this->includechildren;
        }

        // Set title
        $dialog->select_title = '';
        $dialog->selected_title = '';

        // Display
        $markup = $dialog->generate_markup();
        // Hack to get around the hack that prevents deleting items via dialogs
        $markup = str_replace('<td class="selected" ', '<td class="selected selected-shown" ', $markup);
        echo $markup;
    }

    public function handleDialogUpdate($sqlhandler){
        $equal = required_param('equal', PARAM_BOOL);
        $includechildren = required_param('includechildren', PARAM_BOOL);
        $listofvalues = required_param('selected', PARAM_SEQUENCE);
        $listofvalues = explode(',',$listofvalues);
        $this->includechildren = $sqlhandler->includechildren = (int) $includechildren;
        $this->equal = $sqlhandler->equal = (int) $equal;
        $this->listofvalues = $sqlhandler->listofvalues = $listofvalues;
        $sqlhandler->write();
    }

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $COHORT_RULES_OP_IN, $DB;

        if (
            !isset($this->equal)
            || !isset($this->listofvalues)
            || !is_array($this->listofvalues)
            || !count($this->listofvalues)
        ) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;
        $strvar->join = get_string("is{$COHORT_RULES_OP_IN[$this->equal]}to", 'totara_cohort');
        if ($this->includechildren) {
            $strvar->ext = get_string('orachildof', 'totara_cohort');
        }

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofvalues);
        $sqlparams[] = $ruleid;
        $hierarchy = $this->shortprefix;
        $sql = "SELECT h.id, h.frameworkid, h.fullname, h.sortthread, hfw.fullname AS frameworkname, hfw.sortorder, crp.id AS paramid
            FROM {{$hierarchy}} h
            INNER JOIN {{$hierarchy}_framework} hfw ON h.frameworkid = hfw.id
            INNER JOIN {cohort_rule_params} crp ON h.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE h.id {$sqlin}
            AND crp.name = 'listofvalues' AND crp.ruleid = ?
            ORDER BY hfw.sortorder, h.sortthread";
        $items = $DB->get_records_sql($sql, $sqlparams);
        if (!$items) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $frameworkid = current($items)->frameworkid;
        $frameworkname = current($items)->frameworkname;
        reset($items);
        $hierarchylist = array();
        $get_rule_markup = function($hierarchylist, $frameworkid, $frameworkname) use($paramseparator) {
            $a = new stdClass();
            $a->hierarchy = implode($paramseparator, $hierarchylist);
            $a->framework = $frameworkname;
            $frameworkstr = get_string('ruleformat-framework', 'totara_cohort', $a);
            $frameworkspan = html_writer::tag('span', $frameworkstr,
                array('class' => 'ruleparamcontainer', 'data-ruleparam-framework-id' => $frameworkid));
            return get_string('ruleformat-vars', 'totara_cohort', $a) . $frameworkspan;
        };
        $itemlist = array();
        foreach ($items as $i => $h) {
            $value = '"' . $h->fullname . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($h->paramid);
            }
            if ($frameworkid != $h->frameworkid) {
                $itemlist[] = $get_rule_markup($hierarchylist, $frameworkid, $frameworkname);
                $hierarchylist = array();
                $frameworkid = $h->frameworkid;
            }
            $hierarchylist[$i] = html_writer::tag('span', $value,
                array('class' => 'ruleparamcontainer', 'data-ruleparam-frameworkid' => $frameworkid));
            $frameworkname = $h->frameworkname;
        };
        // Processing the missing position/organisation here
        $this->add_missing_rule_params($hierarchylist, $ruleid, $static);

        // Process last item.
        $itemlist[] = $get_rule_markup($hierarchylist, $frameworkid, $frameworkname);

        $strvar->vars = implode($paramseparator, $itemlist);
        if (!empty($strvar->ext)) {
            return get_string('ruleformat-descjoinextvars', 'totara_cohort', $strvar);
        } else {
            return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
        }
    }

    protected function add_missing_rule_params(array &$hierarchylist, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($hierarchylist) < $this->listofvalues) {
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofvalues',
            ), "", 'value as instanceid, id as paramid');

            // Need full hierarchy list as the contextualised hierarchy list may be incomplete when multiple frameworks are in play.
            $fullhierarchylist = array_flip($DB->get_fieldset_sql("SELECT id FROM {{$this->shortprefix}}"));

            foreach ($this->listofvalues as $instanceid) {
                if (!isset($fullhierarchylist[$instanceid])) {
                    // Detected one of the missing hierachy instance here
                    $item = isset($fullparams[$instanceid]) ? $fullparams[$instanceid] : null;
                    if (!$item) {
                        debugging("Missing the rule param for {$this->prefix} {$instanceid}");
                        continue;
                    }
                    $a = (object) array('id' => $instanceid);
                    $value = "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";

                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $hierarchylist[$instanceid] =
                        html_writer::tag('span', $value, array('class' =>  'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}


require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_courses.class.php');
class totara_dialog_content_cohort_rules_courses extends totara_dialog_content_courses {

    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param   $elements    array elements to be created in the pane
    * @return  $html
    */
    public function populate_selected_items_pane($elements) {
        $html = $this->cohort_rule_ui->getExtraSelectedItemsPaneWidgets();
        return $html . parent::populate_selected_items_pane($elements);
    }
}

abstract class cohort_rule_ui_picker_course_program extends cohort_rule_ui {
    public $handlertype = 'treeview';
    protected $pickertype;

    /**
     * @param string $description Brief description of this rule
     */
    public function __construct($description, $pickertype) {
        $this->description = $description;
        $this->pickertype = $pickertype;
    }


    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $CFG, $DB;

        if (!in_array($this->pickertype, array(COHORT_PICKER_COURSE_COMPLETION, COHORT_PICKER_PROGRAM_COMPLETION,
            COHORT_PICKER_CERTIFICATION_COMPLETION))) {
            echo get_string('error:typecompletion', 'totara_cohort');
            return;
        }

        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_courses.class.php');
        } else if ($this->pickertype == COHORT_PICKER_PROGRAM_COMPLETION) {
            require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_programs.class.php');
        }

        ///
        /// Setup / loading data
        ///

        // Category id
        $categoryid = optional_param('parentid', 'cat0', PARAM_ALPHANUM);

        // Strip cat from begining of categoryid
        $categoryid = (int) substr($categoryid, 3);

        ///
        /// Setup dialog
        ///

        // Load dialog content generator.
        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            $dialog = new totara_dialog_content_cohort_rules_courses($categoryid);
        } else if ($this->pickertype == COHORT_PICKER_CERTIFICATION_COMPLETION) {
            $dialog = new totara_dialog_content_cohort_rules_certifications($categoryid);
        } else {
            $dialog = new totara_dialog_content_cohort_rules_programs($categoryid);
        }

        // Set type to multiple.
        $dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->selected_title = '';

        $dialog->urlparams = $hidden;

        // Add data.
        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            $dialog->load_courses();
        } else if ($this->pickertype == COHORT_PICKER_CERTIFICATION_COMPLETION) {
            $dialog->load_certifications();
        } else {
            $dialog->load_programs();
        }

        // Set selected items.
        if ($ruleinstanceid) {
            if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
                $sql = "SELECT course.id, course.fullname
                        FROM {course} course
                        INNER JOIN {cohort_rule_params} crp
                            ON course.id=" . $DB->sql_cast_char2int('crp.value') . "
                        WHERE crp.ruleid = ? and crp.name='listofids'
                        ORDER BY course.fullname
                        ";
            } else {
                $sql = "SELECT program.id, program.fullname
                        FROM {prog} program
                        INNER JOIN {cohort_rule_params} crp
                            ON program.id=" . $DB->sql_cast_char2int('crp.value') . "
                        WHERE crp.ruleid = ? and crp.name='listofids'
                        ORDER BY program.fullname
                        ";
            }
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
            if (!$alreadyselected) {
                $alreadyselected = array();
            }
        } else {
            $alreadyselected = array();
        }
        $dialog->selected_items = $alreadyselected;

        // Set unremovable items.
        $dialog->unremovable_items = array();

        // Semi-hack to allow for callback to this ui class to generate some elements of the treeview.
        $dialog->cohort_rule_ui = $this;

        // Display.
        $markup = $dialog->generate_markup();

        echo $markup;
    }

    /**
     * Provide extra elements to insert into the top of the "selected items" pane of the treeview
     */
    abstract public function getExtraSelectedItemsPaneWidgets();
}

class cohort_rule_ui_picker_course_allanynotallnone extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets(){
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_ALL] = get_string('completionmenuall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_ANY] = get_string('completionmenuany', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NOTALL] = get_string('completionmenunotall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NONE] = get_string('completionmenunotany', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';

        return html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));
    }

    public function handleDialogUpdate($sqlhandler){
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = $listofids;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_ALL:
                $strvar->desc = get_string('ccdescall', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_ANY:
                $strvar->desc = get_string('ccdescany', 'totara_cohort');
                break;
            case COHORT_RULE_COMPLETION_OP_NOTALL:
                $strvar->desc = get_string('ccdescnotall', 'totara_cohort');
                break;
            default:
                $strvar->desc = get_string('ccdescnotany', 'totara_cohort');
        }

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT c.id, c.fullname, crp.id AS paramid
            FROM {course} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE c.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $courselist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courselist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $courselists
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @throws coding_exception
     * @throws dml_exception
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$courselists, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($courselists) < count($this->listofids)) {
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid'    => $ruleinstanceid,
                'name'  => 'listofids'
            ), "", "value AS courseid, id AS paramid");

            foreach ($this->listofids as $courseid) {
                if (!isset($courselists[$courseid])) {
                    // Missing couse here
                    $item = isset($fullparams[$courseid]) ? $fullparams[$courseid] : null;
                    if(!$item) {
                        debugging("Missing the rule parameter for course {$courseid}");
                        continue;
                    }

                    $a = (object) array('id' => $courseid);
                    $value = "\"". get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courselists[$courseid]  =
                        html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}

class cohort_rule_ui_picker_course_duration extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'date' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets(){
        $html = '<div class="mform cohort-treeview-dialog-extrafields">';
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] = get_string('completiondurationmenulessthan', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('completiondurationmenumorethan', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';
        $html .= html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));

        $html .= '<fieldset>';
        $html .= '<input class="cohorttreeviewsubmitfield" id="completionduration" name="date" value="';
        if (isset($this->date)) {
            $html .= htmlspecialchars($this->date);
        }
        $html .= '" /> ' . get_string('completiondurationdays', 'totara_cohort');
        $html .= '</fieldset>';
        $html .= '</div>';
        $validnumberstr = get_string('error:badduration', 'totara_cohort');
        $html .= <<<JS

<script type="text/javascript">
$(function() {
    var valfunc = function(element){
        element = $(element);
        var parent = element.parent();
        if (!element.val().match(/[1-9]+[0-9]*/)){
            parent.addClass('error');
            if ( $('#id_error_completionduration').length == 0 ) {
                parent.prepend('<span id="id_error_completionduration" class="error">{$validnumberstr}</span>');
            }
            return false;
        } else {
            $('#id_error_completionduration').remove();
            parent.removeClass('error');
            return true;
        }
    };
    $('#completionduration').get(0).cohort_validation_func = valfunc;
    $('#completionduration').change(
        function(){
            valfunc(this);
        }
    );
});
</script>

JS;
        return $html;
    }

    public function handleDialogUpdate($sqlhandler){
        $date = required_param('date', PARAM_INT);
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->date = $sqlhandler->date = $date;
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = $listofids;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
                $descstr = 'ccdurationdesclessthan';
                break;
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $descstr = 'ccdurationdescmorethan';
                break;
        }

        $strvar = new stdClass();
        $strvar->desc = get_string($descstr, 'totara_cohort', $this->date);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT c.id, c.fullname, crp.id AS paramid
            FROM {course} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE c.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $courselist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courselist as $i => $c) {
            $value = '"' . $c->fullname . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $courselists
     * @param int $ruleinstanceid
     * @param bool $static
     * @throws coding_exception
     * @throws dml_exception
     * @inheritdoc
     * @return void
     */
    protected function add_missing_rule_params(array &$courselists, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($courselists) < count($this->listofids)) {
            // There are missing courses found at rendering.
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids'
            ), "" , " value as courseid, id as paramid ");

            foreach ($this->listofids as $courseid) {
                if (!isset($courselists[$courseid])) {
                    // Detected that a course with id {$courseid} is missing here
                    $item = isset($fullparams[$courseid]) ? $fullparams[$courseid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for course {$courseid}");
                        continue;
                    }

                    $a = (object) array('id' => $courseid);
                    $value =  "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courselists[$courseid] =
                        html_writer::tag('span', $value, array('class' => "ruleparamcontainer cohortdeletedparam"));
                }
            }
        }
    }
}

class cohort_rule_ui_picker_course_program_date extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'date' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets(){
        global $CFG;

        $html = '';
        $html .= html_writer::start_div('mform cohort-treeview-dialog-extrafields');
        $html .= html_writer::start_tag('form', array('id' => 'form_course_program_date'));

        $opmenufix = array(); // Operator menu for fixed date options.
        $opmenurel = array(); // Operator menu for relative date options.

        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] = get_string('datemenufixeddatebefore', 'totara_cohort');
        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('datemenufixeddateafter', 'totara_cohort');

        $opmenurel[COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION] = get_string('datemenudurationbeforepast', 'totara_cohort');
        $opmenurel[COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION] = get_string('datemenudurationwithinpast', 'totara_cohort');
        $opmenurel[COHORT_RULE_COMPLETION_OP_WITHIN_FUTURE_DURATION] = get_string('datemenudurationwithinfuture', 'totara_cohort');
        $opmenurel[COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION] = get_string('datemenudurationafterfuture', 'totara_cohort');

        // Set default values.
        $selected = isset($this->operator) ? $this->operator : '';
        $htmldate = get_string('datepickerlongyearplaceholder', 'totara_core');
        $class = 'cohorttreeviewsubmitfield';
        $duration = '';
        $radio2prop = $radio1prop = array('type' => 'radio', 'name' => 'fixeddynamic', 'checked' => 'checked', 'class' => $class);
        if (isset($this->operator) && array_key_exists($this->operator, $opmenufix)) {
            array_splice($radio2prop, 2);
            $htmldate = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
        } else if (isset($this->operator) && array_key_exists($this->operator, $opmenurel)) {
            array_splice($radio1prop, 2);
            $duration = htmlspecialchars($this->date);
        } else {
            array_splice($radio2prop, 2);
        }

        // Fixed date.
        $html .= get_string('completionusercompletedbeforeafter', 'totara_cohort');
        $html .= html_writer::start_tag('fieldset');
        $html .= html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic1', 'value' => '1'), $radio1prop));
        $html .= html_writer::select($opmenufix, 'beforeaftermenu', $selected, array(), array('class' => $class));
        $html .= html_writer::empty_tag('input', array('type' => 'text', 'size' => '10', 'id' => 'completiondate',
            'name' => 'date', 'value' => htmlspecialchars($htmldate), 'class' => $class));
        $html .= html_writer::end_tag('fieldset');

        // Relative date.
        $html .= get_string('or', 'totara_cohort');
        $html .= html_writer::start_tag('fieldset');
        $html .= html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic2', 'value' => '2'), $radio2prop));
        $html .= html_writer::select($opmenurel, 'durationmenu', $selected, array(), array('class' => $class));
        $html .= html_writer::empty_tag('input', array('type' => 'text', 'size' => '3', 'id' => 'completiondurationdate',
            'name' => 'durationdate', 'value' => $duration, 'class' => $class));
        $html .= get_string('completiondurationdays', 'totara_cohort');
        $html .= html_writer::end_tag('fieldset');

        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div();

        return $html;
    }

    public function handleDialogUpdate($sqlhandler){
        $fixedordynamic = required_param('fixeddynamic', PARAM_INT);
        switch($fixedordynamic) {
            case 1:
                $operator = required_param('beforeaftermenu', PARAM_INT);
                $date = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core'),
                    required_param('date', PARAM_TEXT));
                break;
            case 2:
                $operator = required_param('durationmenu', PARAM_INT);
                $date = required_param('durationdate', PARAM_INT); // Convert number to seconds.
                break;
            default:
                return false;
        }
        $this->date = $sqlhandler->date = $date;
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = explode(',', required_param('selected', PARAM_SEQUENCE));
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB, $CFG, $COHORT_RULE_COMPLETION_OP;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $a = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
                break;
            case COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_FUTURE_DURATION:
            case COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION:
                $a = $this->date;
                break;
        }
        $strvar->join = get_string("dateis{$COHORT_RULE_COMPLETION_OP[$this->operator]}", 'totara_cohort', $a);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            $sql = "SELECT c.id, c.fullname, crp.id AS paramid
                FROM {course} c
                INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE c.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        } else {
            $sql = "SELECT p.id, p.fullname, crp.id AS paramid
                FROM {prog} p
                INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE p.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        }

        $courseprogramlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courseprogramlist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $courseprogramlists
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @throws coding_exception
     * @throws dml_exception
     * @return void
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$courseprogramlists, $ruleinstanceid, $static = true) {
        global $DB;
        if (count($courseprogramlists) < count($this->listofids)) {
            // Detected that there are invalid records. Therefore, this method will automatically state which
            // recorded was deleted.
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids'
            ), "", "value as instanceid, id as paramid");

            foreach($this->listofids as $instanceid) {
                if (!isset($courseprogramlists[$instanceid])) {
                    // If the program id was not found in the $ruledescriptionlists, then it means that the
                    // record/instance was deleted

                    $item = isset($fullparams[$instanceid]) ? $fullparams[$instanceid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for program/course id {$instanceid}");
                        continue;
                    }
                    $a = (object) array('id' => $instanceid);
                    $value = "\"". get_string("deleteditem", "totara_cohort", $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $courseprogramlists[$instanceid] =
                        html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}

class cohort_rule_ui_picker_certification_completion_date extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'date' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets() {
        global $OUTPUT;

        $html = '';
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('form', array('id' => 'form_course_program_date', 'class' => 'mform cohort-treeview-dialog-extrafields'));

        $opmenufix = array(); // Operator menu for fixed date options.
        $opmenurel = array(); // Operator menu for relative date options.

        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] = get_string('datemenufixeddatebefore', 'totara_cohort');
        $opmenufix[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('datemenufixeddateafter', 'totara_cohort');

        $opmenurel[COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION] = get_string('datemenudurationwithinpast', 'totara_cohort');
        $opmenurel[COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION] = get_string('datemenudurationbeforepast', 'totara_cohort');

        // Set default values.
        $selected = isset($this->operator) ? $this->operator : '';
        $htmldate = get_string('datepickerlongyearplaceholder', 'totara_core');
        $class = 'cohorttreeviewsubmitfield';
        $duration = '';
        $radio2prop = $radio1prop = array('type' => 'radio', 'name' => 'fixeddynamic', 'checked' => 'checked', 'class' => $class);
        if (isset($this->operator) && array_key_exists($this->operator, $opmenufix)) {
            array_splice($radio2prop, 2);
            $htmldate = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
        } else if (isset($this->operator) && array_key_exists($this->operator, $opmenurel)) {
            array_splice($radio1prop, 2);
            $duration = htmlspecialchars($this->date);
        } else {
            array_splice($radio2prop, 2);
        }

        // Fixed date.
        $html .= get_string('completionusercertifiedbeforeafter', 'totara_cohort');
        $html .= $OUTPUT->help_icon('rulehelp-cert-date', 'totara_cohort');
        $html .= html_writer::start_tag('fieldset');

        $html .= html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-completedfixeddate', 'totara_cohort');
        $html .= html_writer::end_tag('legend');

        $html .= html_writer::start_tag('label', array('for' => 'fixedordynamic1', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-fixeddate', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic1', 'value' => '1'), $radio1prop));

        $html .= html_writer::start_tag('label', array('for' => 'beforeaftermenu', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-beforeorafter', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::select($opmenufix, 'beforeaftermenu', $selected, array(), array('id' => 'beforeaftermenu', 'class' => $class));

        $html .= html_writer::start_tag('label', array('for' => 'completiondate', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-completiondate', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::empty_tag('input', array('type' => 'text', 'size' => '10', 'id' => 'completiondate',
            'name' => 'date', 'value' => htmlspecialchars($htmldate), 'class' => $class));
        $html .= html_writer::end_tag('fieldset');

        // Relative date.
        $html .= get_string('or', 'totara_cohort');
        $html .= html_writer::start_tag('fieldset');

        $html .= html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-completedrelativedate', 'totara_cohort');
        $html .= html_writer::end_tag('legend');

        $html .= html_writer::start_tag('label', array('for' => 'fixedordynamic2', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-relativedate', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::empty_tag('input', array_merge(array('id' => 'fixedordynamic2', 'value' => '2'), $radio2prop));

        $html .= html_writer::start_tag('label', array('for' => 'menudurationmenu', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-withinorbeforeprevious', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::select($opmenurel, 'durationmenu', $selected, array(), array('class' => $class));

        $html .= html_writer::start_tag('label', array('for' => 'completiondurationdate', 'class' => 'sr-only'));
        $html .= get_string('rulelabel-days', 'totara_cohort');
        $html .= html_writer::end_tag('label');

        $html .= html_writer::empty_tag('input', array('type' => 'text', 'size' => '3', 'id' => 'completiondurationdate',
            'name' => 'durationdate', 'value' => $duration, 'class' => $class));
        $html .= get_string('completiondurationdays', 'totara_cohort');
        $html .= html_writer::end_tag('fieldset');

        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div();

        return $html;
    }

    public function handleDialogUpdate($sqlhandler){
        $fixedordynamic = required_param('fixeddynamic', PARAM_INT);
        switch($fixedordynamic) {
            case 1:
                $operator = required_param('beforeaftermenu', PARAM_INT);
                $date = totara_date_parse_from_format(get_string('datepickerlongyearparseformat', 'totara_core'),
                    required_param('date', PARAM_TEXT));
                break;
            case 2:
                $operator = required_param('durationmenu', PARAM_INT);
                $date = required_param('durationdate', PARAM_INT); // Convert number to seconds.
                break;
            default:
                return false;
        }
        $this->date = $sqlhandler->date = $date;
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = explode(',', required_param('selected', PARAM_SEQUENCE));
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB, $CFG, $COHORT_RULE_COMPLETION_OP;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $a = userdate($this->date, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
                break;
            case COHORT_RULE_COMPLETION_OP_BEFORE_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_PAST_DURATION:
            case COHORT_RULE_COMPLETION_OP_WITHIN_FUTURE_DURATION:
            case COHORT_RULE_COMPLETION_OP_AFTER_FUTURE_DURATION:
                $a = $this->date;
                break;
        }
        $strvar->join = get_string("dateis{$COHORT_RULE_COMPLETION_OP[$this->operator]}", 'totara_cohort', $a);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        if ($this->pickertype == COHORT_PICKER_COURSE_COMPLETION) {
            $sql = "SELECT c.id, c.fullname, crp.id AS paramid
                FROM {course} c
                INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE c.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        } else {
            $sql = "SELECT p.id, p.fullname, crp.id AS paramid
                FROM {prog} p
                INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE p.id {$sqlin}
                AND crp.name = 'listofids' AND crp.ruleid = ?
                ORDER BY sortorder, fullname";
        }

        $courseprogramlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courseprogramlist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-descjoinvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     *
     * @return void
     */
    public function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofids)) {
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofids'
            ), "", "value AS certid, id AS paramid");

            foreach ($this->listofids as $certid) {
                if (!isset($ruledescriptions[$certid])) {
                    $item = isset($fullparams[$certid]) ? $fullparams[$certid] : null;

                    if (!$item) {
                        debugging("Missing certification id: {$certid} in rule params");
                        continue;
                    }

                    $a = (object) array('id' => $item->certid);
                    $value = get_string('deleteditem', 'totara_cohort', $a);
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$certid] = html_writer::tag("span", $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}

class cohort_rule_ui_picker_certification_status extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'listofids' => 1,
        'status' => 0,
        'assignmentstatus' => 0
    );

    public function getExtraSelectedItemsPaneWidgets() {

        $status = isset($this->status) ? explode(',', $this->status) : array(cohort_rule_sqlhandler_certification_status::CERTIFIED);
        $assignmentstatus = isset($this->assignmentstatus) ? explode(',', $this->assignmentstatus) :
            array(cohort_rule_sqlhandler_certification_status::ASSIGNED, cohort_rule_sqlhandler_certification_status::UNASSIGNED);

        $checkboxatr = array('type' => 'checkbox', 'value' => '0', 'class' => 'cohorttreeviewsubmitfield');

        $html = '';
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('form', array('id' => 'form_certification_status', 'class' => 'mform cohort-treeview-dialog-extrafields'));

        // Certification status.
        $html .= html_writer::start_tag('fieldset', array('id' => 'certifstatus', 'name' => 'certifstatus', 'class' => 'cohorttreeviewsubmitfield'));
        $html .= html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-certificationstatus', 'totara_cohort');
        $html .= html_writer::end_tag('legend');
        $html .= html_writer::tag('p', get_string('rulename-learning-certificationstatus', 'totara_cohort'));

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_currentlycertified', 'name' => 'certifstatus_currentlycertified'));
        if (in_array(cohort_rule_sqlhandler_certification_status::CERTIFIED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('label', array('for' => 'certifstatus_currentlycertified', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-currentlycertified', 'totara_cohort');
        $html .= html_writer::end_tag('label');
        $html .= html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-currentlycertified', 'totara_cohort');
        $html .= html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_currentlyexpired', 'name' => 'certifstatus_currentlyexpired'));
        if (in_array(cohort_rule_sqlhandler_certification_status::EXPIRED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('label', array('for' => 'certifstatus_currentlyexpired', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-currentlyexpired', 'totara_cohort');
        $html .= html_writer::end_tag('label');
        $html .= html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-currentlyexpired', 'totara_cohort');
        $html .= html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifstatus_nevercertified', 'name' => 'certifstatus_nevercertified'));
        if (in_array(cohort_rule_sqlhandler_certification_status::NEVER_CERTIFIED, $status)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('label', array('for' => 'certifstatus_nevercertified', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-nevercertified', 'totara_cohort');
        $html .= html_writer::end_tag('label');
        $html .= html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-nevercertified', 'totara_cohort');
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('fieldset');

        $html .= html_writer::empty_tag('br');

        // Assignment status.
        $html .= html_writer::start_tag('fieldset', array('id' => 'certifassignmentstatus', 'name' => 'certifassignmentstatus', 'class' => 'cohorttreeviewsubmitfield'));

        $html .= html_writer::start_tag('legend', array('class' => 'sr-only'));
        $html .= get_string('rulelegend-certificationassignmentstatus', 'totara_cohort');
        $html .= html_writer::end_tag('legend');

        $html .= html_writer::tag('p', get_string('ruledesc-learning-certificationstatus-assignmentstatus', 'totara_cohort'));

        $atr = array_merge($checkboxatr, array('id' => 'certifassignmentstatus_assigned', 'name' => 'certifassignmentstatus_assigned'));
        if (in_array(cohort_rule_sqlhandler_certification_status::ASSIGNED, $assignmentstatus)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('label', array('for' => 'certifassignmentstatus_assigned', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-assigned', 'totara_cohort');
        $html .= html_writer::end_tag('label');
        $html .= html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-assigned', 'totara_cohort');
        $html .= html_writer::end_div();

        $atr = array_merge($checkboxatr, array('id' => 'certifassignmentstatus_unassigned', 'name' => 'certifassignmentstatus_unassigned'));
        if (in_array(cohort_rule_sqlhandler_certification_status::UNASSIGNED, $assignmentstatus)) {
            $atr['checked'] = 'checked';
            $atr['value'] = '1';
        }
        $html .= html_writer::start_div();
        $html .= html_writer::start_tag('label', array('for' => 'certifassignmentstatus_unassigned', 'class' => 'sr-only'));
        $html .= get_string('ruledesc-learning-certificationstatus-unassigned', 'totara_cohort');
        $html .= html_writer::end_tag('label');
        $html .= html_writer::empty_tag('input', $atr);
        $html .= get_string('ruledesc-learning-certificationstatus-unassigned', 'totara_cohort');
        $html .= html_writer::end_div();
        $html .= html_writer::end_tag('fieldset');

        $html .= html_writer::empty_tag('br');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_div();

        return $html;
    }

    public function handleDialogUpdate($sqlhandler) {
        $certifstatus_currentlycertified = optional_param('certifstatus_currentlycertified', false, PARAM_TEXT);
        $certifstatus_currentlyexpired = optional_param('certifstatus_currentlyexpired', false, PARAM_TEXT);
        $certifstatus_nevercertified = optional_param('certifstatus_nevercertified', false, PARAM_TEXT);

        $certifassignmentstatus_assigned = optional_param('certifassignmentstatus_assigned', false, PARAM_TEXT);
        $certifassignmentstatus_unassigned = optional_param('certifassignmentstatus_unassigned', false, PARAM_TEXT);

        $status = array();

        if ($certifstatus_currentlycertified) {
            $status[] = cohort_rule_sqlhandler_certification_status::CERTIFIED;
        }

        if ($certifstatus_currentlyexpired) {
            $status[] = cohort_rule_sqlhandler_certification_status::EXPIRED;
        }

        if ($certifstatus_nevercertified) {
            $status[] = cohort_rule_sqlhandler_certification_status::NEVER_CERTIFIED;
        }

        if (empty($status)) {
            throw new \coding_exception('Dynamic audience certification rule has missing status');
        }

        $assignmentstatus = array();

        if ($certifassignmentstatus_assigned) {
            $assignmentstatus[] = cohort_rule_sqlhandler_certification_status::ASSIGNED;
        }

        if ($certifassignmentstatus_unassigned) {
            $assignmentstatus[] = cohort_rule_sqlhandler_certification_status::UNASSIGNED;
        }

        if (empty($assignmentstatus)) {
            throw new \coding_exception('Dynamic audience certification rule has missing assignment status');;
        }

        $this->listofids = $sqlhandler->listofids = explode(',', required_param('selected', PARAM_SEQUENCE));
        $this->status = $sqlhandler->status = implode(',', $status);
        $this->assignmentstatus = $sqlhandler->assignmentstatus = implode(',', $assignmentstatus);

        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;

        if (!isset($this->status) || !isset($this->assignmentstatus) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        $strvar->desc = $this->description;

        // Status.
        $status = explode(',', $this->status);
        array_walk($status, function(&$item) {
            $item = "'" . get_string(cohort_rule_sqlhandler_certification_status::get_status($item), 'totara_cohort') . "'";
        });
        $strvar->status = implode(', ', $status);

        // Assignment status.
        $assignmentstatus = explode(',', $this->assignmentstatus);
        array_walk($assignmentstatus, function(&$item) {
            $item = "'" . get_string(cohort_rule_sqlhandler_certification_status::get_assignment_status($item), 'totara_cohort') . "'";
        });
        $strvar->assignmentstatus = implode(', ', $assignmentstatus);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;

        $sql = "SELECT p.id, p.fullname, crp.id AS paramid
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";

        $courseprogramlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($courseprogramlist as $i => $c) {
            $value = '"' . format_string($c->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $courselist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($courselist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $courselist);

        return get_string('ruleformat-certificationstatus', 'totara_cohort', $strvar);
    }


    /**
     * @param array $ruledescriptions
     * @param int $ruleinstanceid
     * @param bool $static
     * @return void
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($ruledescriptions) < count($this->listofids)) {
            // Detected there are missing certifications
            $fullparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name' => 'listofids'
            ),  "", "value AS certid, id AS paramid");

            foreach ($this->listofids as $certid) {
                if (!isset($ruledescriptions[$certid])) {
                    $item = isset($fullparams[$certid]) ? $fullparams[$certid] : null;
                    if (!$item) {
                        debugging("Missing certification {$certid} in rule's params");
                        continue;
                    }

                    $a = (object) array('id' => $item->certid);
                    $value = "\"" . get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$certid] = html_writer::tag('span', $value, array(
                        'class' => 'ruleparamcontainer cohortdeletedparam'
                    ));
                }
            }
        }
    }
}

// todo: Refactor to remove the shameful amount of code duplication between courses & programs
require_once($CFG->dirroot.'/totara/core/dialogs/dialog_content_programs.class.php');
class totara_dialog_content_cohort_rules_programs extends totara_dialog_content_programs {

    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param   $elements    array elements to be created in the pane
    * @return  $html
    */
    public function populate_selected_items_pane($elements) {
        $html = $this->cohort_rule_ui->getExtraSelectedItemsPaneWidgets();
        return $html .= parent::populate_selected_items_pane($elements);
    }
}

class totara_dialog_content_cohort_rules_certifications extends totara_dialog_content_certifications {

    /**
     * Returns markup to be used in the selected pane of a multi-select dialog
     *
     * @param   $elements    array elements to be created in the pane
     * @return  $html
     */
    public function populate_selected_items_pane($elements) {
        $html = $this->cohort_rule_ui->getExtraSelectedItemsPaneWidgets();
        return $html .= parent::populate_selected_items_pane($elements);
    }
}

class cohort_rule_ui_picker_program_allanynotallnone extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets(){
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_ALL] = get_string('completionmenuall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_ANY] = get_string('completionmenuany', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NOTALL] = get_string('completionmenunotall', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_NONE] = get_string('completionmenunotany', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';
        return html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));
    }

    public function handleDialogUpdate($sqlhandler){
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = $listofids;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_ALL:
                $getstr = 'pcdescall';
                break;
            case COHORT_RULE_COMPLETION_OP_ANY:
                $getstr = 'pcdescany';
                break;
            case COHORT_RULE_COMPLETION_OP_NOTALL:
                $getstr = 'pcdescnotall';
                break;
            default:
                $getstr = 'pcdescnotany';
        }

        $strvar = new stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort');

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT p.id, p.fullname, crp.id AS paramid
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $proglist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($proglist as $i => $p) {
            $value = '"' . format_string($p->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($p->paramid);
            }
            $proglist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($proglist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $proglist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array     $ruledescriptions
     * @param int       $ruleinstanceid
     * @param bool      $static
     * @throws coding_exception
     * @throws dml_exception
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static=true) {
        global $DB;
        if (count($ruledescriptions) < count($this->listofids)) {
            // There are missing records, might be a posibility of deleted records, therefore, add some helper message
            // here for user to update. For retrieving what parameter of the rule is invalid, we need to know that
            // which $value (this reference to the program's) is missing in database
            $ruleparams = $DB->get_records("cohort_rule_params", array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids',
            ), "", "value, id AS paramid");

            foreach ($this->listofids as $id) {
                if (!isset($ruledescriptions[$id])) {
                    // So this $id is missing from the tracker, which indicate that it has been removed
                    // therefore, add the message here.
                    $item = isset($ruleparams[$id]) ? $ruleparams[$id] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for program id $id");
                        continue;
                    }

                    $a = (object) array('id' => $id);
                    $value = "\"". get_string('deleteditem', 'totara_cohort', $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$id] =
                        html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}

class cohort_rule_ui_picker_program_duration extends cohort_rule_ui_picker_course_program {
    public $params = array(
        'operator' => 0,
        'date' => 0,
        'listofids' => 1
    );

    public function getExtraSelectedItemsPaneWidgets(){
        $html = '<div class="mform cohort-treeview-dialog-extrafields">';
        $operatormenu = array();
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN] =    get_string('completiondurationmenulessthan', 'totara_cohort');
        $operatormenu[COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN] = get_string('completiondurationmenumorethan', 'totara_cohort');
        $selected = isset($this->operator) ? $this->operator : '';
        $html .= html_writer::select($operatormenu, 'operator', $selected, array(),
            array('id' => 'id_operator', 'class' => 'cohorttreeviewsubmitfield'));
        $html .= '<fieldset>';
        $html .= '<input class="cohorttreeviewsubmitfield" id="completionduration" name="date" value="';
        if (isset($this->date)) {
            $html .= htmlspecialchars($this->date);
        }
        $html .= '" /> day(s)';
        $html .= '</fieldset>';
        $html .= '</div>';
        $badduration = get_string('error:badduration', 'totara_cohort');
        $html .= <<<JS

<script type="text/javascript">
$(function() {
    var valfunc = function(element){
        element = $(element);
        var parent = element.parent();
        if (!element.val().match(/[1-9]+[0-9]*/)){
            parent.addClass('error');
            if ( $('#id_error_completionduration').length == 0 ) {
                parent.prepend('<span id="id_error_completionduration" class="error">{$badduration}</span>');
            }
            return false;
        } else {
            $('#id_error_completionduration').remove();
            parent.removeClass('error');
            return true;
        }
    };
    $('#completionduration').get(0).cohort_validation_func = valfunc;
    $('#completionduration').change(
        function(){
            valfunc(this);
        }
    );
});
</script>

JS;
        return $html;
    }

    public function handleDialogUpdate($sqlhandler){
        $date = required_param('date', PARAM_INT);
        $operator = required_param('operator', PARAM_INT);
        $listofids = required_param('selected', PARAM_SEQUENCE);
        $listofids = explode(',',$listofids);
        $this->date = $sqlhandler->date = $date;
        $this->operator = $sqlhandler->operator = $operator;
        $this->listofids = $sqlhandler->listofids = $listofids;
        $sqlhandler->write();
    }

    /**
     * Get the description of this rule for the list of rules
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;
        if (!isset($this->operator) || !isset($this->listofids)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }
        switch ($this->operator) {
            case COHORT_RULE_COMPLETION_OP_DATE_LESSTHAN:
                $getstr = 'pcdurationdesclessthan';
                break;
            case COHORT_RULE_COMPLETION_OP_DATE_GREATERTHAN:
                $getstr = 'pcdurationdescmorethan';
                break;
        }

        $strvar = new stdClass();
        $strvar->desc = get_string($getstr, 'totara_cohort', $this->date);

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->listofids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT p.id, p.fullname, crp.id AS paramid
            FROM {prog} p
            INNER JOIN {cohort_rule_params} crp ON p.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE p.id {$sqlin}
            AND crp.name = 'listofids' AND crp.ruleid = ?
            ORDER BY sortorder, fullname";
        $proglist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($proglist as $i => $p) {
            $value = '"' . format_string($p->fullname) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($p->paramid);
            }
            $proglist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($proglist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $proglist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $ruledescriptions
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @throws coding_exception
     * @throws dml_exception
     * @inheritdoc
     */
    protected function add_missing_rule_params(array &$ruledescriptions, $ruleinstanceid, $static = true) {
        global $DB;
        if (count($ruledescriptions) < count($this->listofids)) {
            // Detected that there are invalid records. Therefore, this method will automatically state which
            // recorded was deleted.
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name'   => 'listofids'
            ), "", "value AS programid, id AS paramid");

            foreach($this->listofids as $programid) {
                if (!isset($ruledescriptions[$programid])) {
                    // If the program id was not found in the $ruledescriptionlists, then it means that the
                    // record/instance was deleted

                    $item = isset($fullparams[$programid]) ? $fullparams[$programid] : null;
                    if (!$item) {
                        debugging("Missing the rule parameter for program id {$programid}");
                        continue;
                    }
                    $a = (object) array('id' => $programid);
                    $value = "\"". get_string("deleteditem", "totara_cohort", $a) . "\"";
                    if (!$static) {
                        $value .= $this->param_delete_action_icon($item->paramid);
                    }

                    $ruledescriptions[$programid] =
                        html_writer::tag('span', $value, array('class' => 'ruleparamcontainer cohortdeletedparam'));
                }
            }
        }
    }
}

require_once($CFG->dirroot . '/totara/core/dialogs/dialog_content_users.class.php');
class totara_dialog_content_manager_cohort extends totara_dialog_content_users {
    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param   $elements    array elements to be created in the pane
    * @return  $html
    */
    public function populate_selected_items_pane($elements) {
        $operatormenu = array();
        $operatormenu[0] = get_string('reportsto', 'totara_cohort');
        $operatormenu[1] = get_string('reportsdirectlyto', 'totara_cohort');
        $selected = isset($this->isdirectreport) ? $this->isdirectreport : '';
        $html = html_writer::select($operatormenu, 'isdirectreport', $selected, array(),
            array('id' => 'id_isdirectreport', 'class' => 'cohorttreeviewsubmitfield'));
        return $html . parent::populate_selected_items_pane($elements);
    }
}

class cohort_rule_ui_reportsto extends cohort_rule_ui {
    public $handlertype = 'treeview';
    public $params = array(
        'isdirectreport' => 0,
        'managerid' => 1
    );

    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $CFG, $DB;

        // Parent id
        $parentid = optional_param('parentid', 0, PARAM_INT);

        // Only return generated tree html
        $treeonly = optional_param('treeonly', false, PARAM_BOOL);

        $dialog = new totara_dialog_content_manager_cohort();

        // Toggle treeview only display
        $dialog->show_treeview_only = $treeonly;

        // Load items to display
        $dialog->load_items($parentid);

        // Set selected items
        $alreadyselected = array();
        if ($ruleinstanceid) {
            $sql = "SELECT u.id, " . get_all_user_name_fields(true, 'u') . "
                FROM {user} u
                INNER JOIN {cohort_rule_params} crp
                    ON u.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE crp.ruleid = ? AND crp.name='managerid'
                ORDER BY u.firstname, u.lastname
                ";
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
            foreach ($alreadyselected as $k => $v) {
                $alreadyselected[$k]->fullname = fullname($v);
            }
        }
        $dialog->selected_items = $alreadyselected;
        $dialog->isdirectreport = isset($this->isdirectreport) ? $this->isdirectreport : '';

        $dialog->urlparams = $hidden;

        // Display page
        // Display
        $markup = $dialog->generate_markup();
        // Hack to get around the hack that prevents deleting items via dialogs
        $markup = str_replace('<td class="selected" ', '<td class="selected selected-shown" ', $markup);
        echo $markup;
    }

    public function handleDialogUpdate($sqlhandler) {
        $isdirectreport = required_param('isdirectreport', PARAM_BOOL);
        $managerid = required_param('selected', PARAM_SEQUENCE);
        $managerid = explode(',', $managerid);
        $this->isdirectreport = $sqlhandler->isdirectreport = (int) $isdirectreport;
        $this->managerid = $sqlhandler->managerid = $managerid;
        $sqlhandler->write();
    }

    /**
     * Get the description of the rule, to be printed on the cohort's rules list page
     * @param int $ruleid
     * @param boolean $static only display static description, without action controls
     * @return string
     */
    public function getRuleDescription($ruleid, $static=true) {
        global $DB;

        if (!isset($this->isdirectreport) || !isset($this->managerid)) {
            return get_string('error:rulemissingparams', 'totara_cohort');
        }

        $strvar = new stdClass();
        if ($this->isdirectreport) {
            $strvar->desc = get_string('userreportsdirectlyto', 'totara_cohort');
        } else {
            $strvar->desc = get_string('userreportsto', 'totara_cohort');
        }

        $usernamefields = get_all_user_name_fields(true, 'u');
        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->managerid);
        $sqlparams[] = $ruleid;
        $sql = "SELECT u.id, {$usernamefields}, crp.id AS paramid
            FROM {user} u
            INNER JOIN {cohort_rule_params} crp ON u.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE u.id {$sqlin}
            AND crp.name = 'managerid' AND crp.ruleid = ?";
        $userlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($userlist as $i => $u) {
            $value = '"' . fullname($u) . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($u->paramid);
            }
            $userlist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };
        // Sort by fullname
        sort($userlist);

        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $userlist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }
}

class totara_dialog_content_manager_cohortmember extends totara_dialog_content {
    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param array $elements elements to be created in the pane
    * @return string $html
    */
    public function populate_selected_items_pane($elements) {

        $operatormenu = array();
        $operatormenu[1] = get_string('incohort', 'totara_cohort');
        $operatormenu[0] = get_string('notincohort', 'totara_cohort');
        $selected = isset($this->incohort) ? $this->incohort : '';
        $html = html_writer::select($operatormenu, 'incohort', $selected, array(),
            array('id' => 'id_incohort', 'class' => 'cohorttreeviewsubmitfield'));
        return $html . parent::populate_selected_items_pane($elements);
    }
}

class cohort_rule_ui_cohortmember extends cohort_rule_ui {
    public $handlertype = 'treeview';
    public $params = array(
        'cohortids' => 1,
        'incohort' => 0
    );

    public function printDialogContent($hidden=array(), $ruleinstanceid=false) {
        global $CFG, $DB;

        $type = !empty($hidden['type']) ? $hidden['type'] : '';
        $id = !empty($hidden['id']) ? $hidden['id'] : 0;
        $rule = !empty($hidden['rule']) ? $hidden['rule'] : '';
        // Get sql to exclude current cohort
        switch ($type) {
            case 'rule':
                $sql = "SELECT DISTINCT crc.cohortid
                    FROM {cohort_rules} cr
                    INNER JOIN {cohort_rulesets} crs ON crs.id = cr.rulesetid
                    INNER JOIN {cohort_rule_collections} crc ON crc.id = crs.rulecollectionid
                    WHERE cr.id = ? ";
                $currentcohortid = $DB->get_field_sql($sql, array($id), IGNORE_MULTIPLE);
                break;
            case 'ruleset':
                $sql = "SELECT DISTINCT crc.cohortid
                    FROM {cohort_rulesets} crs
                    INNER JOIN {cohort_rule_collections} crc ON crc.id = crs.rulecollectionid
                    WHERE crs.id = ? ";
                $currentcohortid = $DB->get_field_sql($sql, array($id), IGNORE_MULTIPLE);
                break;
            case 'cohort':
                $currentcohortid = $id;
                break;
            default:
                $currentcohortid =  0;
                break;
        }

        // Get cohorts
        $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
                "END AS fullname
            FROM {cohort} c";
        if (!empty($currentcohortid)) {
            $sql .= ' WHERE c.id != ? ';
        }
        $sql .= ' ORDER BY c.name, c.idnumber';
        $items = $DB->get_records_sql($sql, array($currentcohortid));

        // Set up dialog
        $dialog = new totara_dialog_content_manager_cohortmember();
        $dialog->type = totara_dialog_content::TYPE_CHOICE_MULTI;
        $dialog->items = $items;
        $dialog->selected_title = 'itemstoadd';
        $dialog->searchtype = 'cohort';
        $dialog->urlparams = array('id' => $id, 'type' => $type, 'rule' => $rule);
        if (!empty($currentcohortid)) {
            $dialog->disabled_items = array($currentcohortid);
            $dialog->customdata['current_cohort_id'] = $currentcohortid;
        }

        // Set selected items
        if ($ruleinstanceid) {
            $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
                "END AS fullname
                FROM {cohort} c
                INNER JOIN {cohort_rule_params} crp
                    ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
                WHERE crp.ruleid = ? AND crp.name = 'cohortids'
                ORDER BY c.name, c.idnumber
                ";
            $alreadyselected = $DB->get_records_sql($sql, array($ruleinstanceid));
        } else {
            $alreadyselected = array();
        }
        $dialog->selected_items = $alreadyselected;
        $dialog->unremovable_items = $alreadyselected;
        $dialog->incohort = isset($this->incohort) ? $this->incohort : '';

        // Display
        $markup = $dialog->generate_markup();
        echo $markup;
    }

    public function handleDialogUpdate($sqlhandler) {
        $cohortids = required_param('selected', PARAM_SEQUENCE);
        $cohortids = explode(',', $cohortids);
        $this->cohortids = $sqlhandler->cohortids = $cohortids;

        $incohort = required_param('incohort', PARAM_BOOL);
        $this->incohort = $sqlhandler->incohort = $incohort;

        $sqlhandler->write();
    }

    public function getRuleDescription($ruleid, $static=true) {
        global $DB;

        $strvar = new stdClass();
        if ($this->incohort) {
            $strvar->desc = get_string('useriscohortmember', 'totara_cohort');
        } else {
            $strvar->desc = get_string('userisnotcohortmember', 'totara_cohort');
        }

        list($sqlin, $sqlparams) = $DB->get_in_or_equal($this->cohortids);
        $sqlparams[] = $ruleid;
        $sql = "SELECT c.id,
                CASE WHEN c.idnumber IS NULL OR c.idnumber = '' OR c.idnumber = '0'
                    THEN c.name
                    ELSE " . $DB->sql_concat("c.name", "' ('", "c.idnumber", "')'") .
                "END AS fullname, crp.id AS paramid
            FROM {cohort} c
            INNER JOIN {cohort_rule_params} crp ON c.id = " . $DB->sql_cast_char2int('crp.value') . "
            WHERE c.id {$sqlin}
            AND crp.name = 'cohortids' AND crp.ruleid = ?
            ORDER BY c.name, c.idnumber";
        $cohortlist = $DB->get_records_sql($sql, $sqlparams);

        foreach ($cohortlist as $i => $c) {
            $value = '"' . $c->fullname . '"';
            if (!$static) {
                $value .= $this->param_delete_action_icon($c->paramid);
            }
            $cohortlist[$i] = html_writer::tag('span', $value, array('class' => 'ruleparamcontainer'));
        };

        $this->add_missing_rule_params($cohortlist, $ruleid, $static);
        $paramseparator = html_writer::tag('span', ', ', array('class' => 'ruleparamseparator'));
        $strvar->vars = implode($paramseparator, $cohortlist);

        return get_string('ruleformat-descvars', 'totara_cohort', $strvar);
    }

    /**
     * @param array $cohortlist
     * @param int   $ruleinstanceid
     * @param bool  $static
     * @return void
     */
    protected function add_missing_rule_params(array &$cohortlist, $ruleinstanceid, $static = true) {
        global $DB;

        if (count($cohortlist) < count($this->cohortids)) {
            // Detected that there is a missing cohort
            $fullparams = $DB->get_records('cohort_rule_params', array(
                'ruleid' => $ruleinstanceid,
                'name' => 'cohortids'
            ), "", "value AS cohortid, id AS paramid");
        }

        foreach ($this->cohortids as $cohortid) {
            if (!isset($cohortlist[$cohortid])) {
                // So, the missing $cohortid that does not existing in $cohortlist array_keys. Which
                // we have to notify the users that it is missing.
                $item = isset($fullparams[$cohortid]) ? $fullparams[$cohortid] : null;
                if (!$item) {
                    debugging("Missing the rule parameter for cohort id {$cohortid}");
                    continue;
                }

                $a = (object) array('id' => $cohortid);
                $value = "\"" . get_string("deleteditem", "totara_cohort", $a) . "\"";
                if (!$static) {
                    $value .= $this->param_delete_action_icon($item->paramid);
                }

                $cohortlist[$cohortid] = html_writer::tag('span', $value, array(
                    'class' => 'ruleparamcontainer cohortdeletedparam'
                ));
            }
        }
    }
}

/**
 * Class cohort_rule_ui_authentication_type
 * @property array $options Array<value, label> where 'value' is mixed type and 'label' is a string
 */
class cohort_rule_ui_authentication_type extends cohort_rule_ui_menu {
    /**
     * cohort_rule_ui_authentication_type constructor.
     * @param string $description
     */
    public function __construct($description) {
        parent::__construct($description, array());
        $this->init();
    }

    /**
     * @return void
     */
    private function init(): void {
        if (empty($this->options)) {
            $authavailables = core_component::get_plugin_list('auth');
            foreach ($authavailables as $auth => $dirpath) {
                $authplugin = get_auth_plugin($auth);
                $this->options[$auth] = $authplugin->get_title();
            }
        }
    }

    /**
     * A method for validating the form submitted data
     * @return bool
     */
    public function validateResponse() {
        /** @var core_renderer $OUTPUT */
        global $OUTPUT;
        $form = $this->constructForm();
        if  ($data = $form->get_submitted_data()) {
            $success = !empty($data->listofvalues);
            // Checking whether the listofvalues being passed is empty or not. If it is empty, error should be returned
            if (!$success) {
                $form->_form->addElement('html',
                    $OUTPUT->notification(get_string('msg:missing_auth_type', 'totara_cohort'), \core\output\notification::NOTIFY_ERROR)
                );
            }
            return $success;
        }

        // If the form is not submitted at all, then there is no point to validate and false should be returned here
        return false;
    }
}
