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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_form
 */

namespace totara_form;

use totara_form\form\group\buttons,
    totara_form\form\group\section,
    totara_form\form\element\action_button;

/**
 * Totara form model.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
class model implements item {
    use trait_item_find,
        trait_item_validation,
        trait_item_help;

    /** @var form */
    protected $form;

    /** @var bool $submitted was the form data posted? */
    protected $submitted;

    /** @var array $currentdata Current data usually in the same format as form::get_data(). This also include all defaults and original constant values. */
    protected $currentdata;

    /** @var array $rawpostdata data from _POST - stored here so that it can be tested easily and devs cannot hack it on the fly */
    protected $rawpostdata;

    /** @var string $idsuffix this makes the form submission and element ids unique when multiple forms present on one page */
    protected $idsuffix;

    /** @var bool $finalised true means no more changes are allowed */
    protected $finalised = false;

    /** @var \context $context Optional default form context, falls back to $PAGE->context if not set */
    protected $defaultcontext;

    /** @var item[] $items */
    protected $items = array();

    /** @var bool $frozen is this element frozen? */
    protected $frozen = false;

    /** @var section $lastsection */
    protected $lastsection;

    /**
     * Client side actions attached to this item.
     * @var clientaction[]
     */
    protected $clientactions = [];

    /**
     * Element comparison operators.
     *
     * If you add any here you must also add them in JS.
     *
     * Equals and not equals work exactly as their name suggests.
     * Empty and not empty work like php is_empty.
     * Filled and not filled consider 0 to be filled, vs empty which considers 0 to be empty.
     */
    const OP_EQUALS = 'equals';
    const OP_EMPTY = 'empty';
    const OP_FILLED = 'filled';
    const OP_NOT_EQUALS = 'notequals';
    const OP_NOT_EMPTY = 'notempty';
    const OP_NOT_FILLED = 'notfilled';

    /**
     * Use comparison operator on values.
     *
     * Null value is considered to be equal to an empty string,
     * the reason is that we need to make this work the same in PHP and JS.
     *
     * Array values are not supported much here.
     *
     * @param mixed $value1
     * @param string $operator
     * @param mixed $value2 might not be used by some operators
     * @return bool
     */
    public static function compare($value1, $operator, $value2 = null) {
        switch ($operator) {
            case self::OP_EQUALS:
                if (is_array($value1) or is_array($value2)) {
                    return false;
                }
                return ((string)$value1 === (string)$value2);

            case self::OP_EMPTY:
                return ($value1 === '' or $value1 === '0' or $value1 === false or $value1 === null or $value1 === array());

            case self::OP_FILLED:
                if (is_array($value1)) {
                    return false;
                }
                return ($value1 !== '' and $value1 !== null);

            case self::OP_NOT_EQUALS:
                if (is_array($value1) or is_array($value2)) {
                    return false;
                }
                return ((string)$value1 !== (string)$value2);

            case self::OP_NOT_EMPTY:
                return ($value1 !== '' and $value1 !== '0' and $value1 !== false and $value1 !== null and (!is_array($value1) or count($value1) > 0));

            case self::OP_NOT_FILLED:
                if (is_array($value1)) {
                    return false;
                }
                return ($value1 === '' or $value1 === null);
        }

        debugging('Unknown comparison operator: ' . $operator, DEBUG_DEVELOPER);
        return false;
    }

    /**
     * All names of things in forms must be compatible with json objects, PHP properties, _POST, etc.
     *
     * @param string $name
     * @return bool
     */
    public static function is_valid_name($name) {
        // Must be a string!
        if (!is_string($name)) {
            return false;
        }
        // Prevent use of reserved names.
        if ($name === 'sesskey' or $name === '') {
            return false;
        }
        // Anything after three underscores is custom stuff,
        // this prevents element naming collisions.
        // Feel free to use any ___xyz name or id suffix in templates.
        if (strpos($name, '___') !== false) {
            return false;
        }
        // We must not allow fancy stuff!
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $name)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Form model constructor.
     *
     * @param form $form
     * @param array $currentdata
     * @param array $rawpostdata
     * @param string $idsuffix
     */
    public function __construct(form $form, array $currentdata, array $rawpostdata, $idsuffix) {
        $this->form = $form;
        $this->currentdata = $currentdata;
        $this->rawpostdata = array();

        // We need to ensure the idsuffix is unique to the form.
        // This means that if you use the same form twice on the same page then you need to provide a unique
        // suffix when constructing the forms.
        // But when using two different forms on the same page this will work out of the box.
        if (empty($idsuffix)) {
            $idsuffix = str_replace('\\', '_', get_class($form));
        }

        $this->idsuffix = clean_param($idsuffix, PARAM_ALPHANUMEXT);

        if (!empty($rawpostdata['sesskey']) and confirm_sesskey($rawpostdata['sesskey'])) {
            if (isset($rawpostdata['___tf_formclass']) and get_class($form) === $rawpostdata['___tf_formclass']) {
                if (isset($rawpostdata['___tf_idsuffix']) and $this->idsuffix === $rawpostdata['___tf_idsuffix']) {
                    $this->submitted = true;
                    $this->rawpostdata = self::clean_rawpostdata($rawpostdata);
                }
            }
        }
    }

    /**
     * Set default form context.
     *
     * @param \context $context
     */
    public function set_default_context(\context $context = null) {
        $this->require_not_finalised();
        $this->defaultcontext = $context;
    }

    /**
     * Returns the default form context.
     *
     * This is expected to be used as default context in elements that
     * work with contexts.
     *
     * @return \context
     */
    public function get_default_context() {
        global $PAGE;

        if (isset($this->defaultcontext)) {
            return $this->defaultcontext;
        }

        return $PAGE->context;
    }

    /**
     * Returns item name.
     *
     * Note: Forms cannot have names.
     *
     * @return string always '' in case of the form model
     */
    public function get_name() {
        return '';
    }

    /**
     * Is the given name used by this element?
     *
     * This is intended mainly for elements that
     * use more entries in current and returned data.
     *
     * Please note that '___xxx' input name suffix is
     * usually better solution if you need to add data
     * to _POST only.
     *
     * @param string $name
     * @return bool
     */
    public function is_name_used($name) {
        if ($name === '' or $name === 'sesskey') {
            return true;
        }
        if (strpos($name, '___') === 0) {
            return true;
        }
        return false;
    }

    /**
     * Recursive function that applies PARAM_RAW to raw _POST data.
     *
     * This fixes UTF-8, \0 bytes, etc.
     *
     * @param array $rawpostdata
     * @return array
     */
    protected static function clean_rawpostdata(array $rawpostdata) {
        $data = array();
        foreach ($rawpostdata as $k => $v) {
            $k = clean_param($k, PARAM_RAW);
            if (is_array($v)) {
                $v = self::clean_rawpostdata($v);
            } else {
                $v = clean_param($v, PARAM_RAW);
            }
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * Is the form model finalised?
     *
     * @return bool
     */
    public function is_finalised() {
        return $this->finalised;
    }

    /**
     * Returns contained items.
     *
     * @return item[]
     */
    public function get_items() {
        return $this->items;
    }

    /**
     * Add item to this model.
     *
     * If position is not specified then the location is guessed:
     *  - section is added to the end of items list and used as default for following add() calls
     *  - item is added to the end of last section - general section is added if necessary
     *
     * @throws \coding_exception if the item already has a parent.
     * @param item $item
     * @param int $position null means guess, number is position index in model, -1 means last
     * @return item|element|group $item
     */
    public function add(item $item, $position = null) {
        $this->require_not_finalised();

        if ($item->get_parent()) {
            throw new \coding_exception('Item already has parent!');
        }

        // Make sure no element is using (or abusing) the same name.
        if ($this->find(true, 'is_name_used', 'totara_form\item', true, array($item->get_name(), false))) {
            throw new \coding_exception('Duplicate name "' . $item->get_name() . '" detected!');
        }

        if (isset($position) and $position >= 0 and $position < count($this->items)) {
            $item->set_parent($this);
            array_splice($this->items, $position, 0, array($item));
            if ($item instanceof section) {
                $this->lastsection = $item;
            }
            return $item;
        }

        if ($item instanceof section or $position == -1 or !$this->lastsection) {
            $item->set_parent($this);
            $this->items[] = $item;
            if ($item instanceof section) {
                $this->lastsection = $item;
            }
            return $item;
        }

        // By default always add stuff to the most recent section.
        $this->lastsection->add($item);

        return $item;
    }

    /**
     * Adds a clientside action to this form.
     *
     * @param clientaction $clientaction
     * @return clientaction|\totara_form\form\clientaction\hidden_if
     */
    public function add_clientaction(clientaction $clientaction) {
        $this->clientactions[] = $clientaction;
        return $clientaction;
    }

    /**
     * Remove item recursively.
     *
     * @param item $item
     * @return bool true on success, false if not found
     */
    public function remove(item $item) {
        $this->require_not_finalised();

        $key = array_search($item, $this->items, true);
        if ($key !== false) {
            unset($this->items[$key]);
            $this->items = array_merge($this->items); // Fix keys.
            $item->set_parent(null);

            // Set last section if necessary.
            if ($item === $this->lastsection) {
                $this->lastsection = null;
                foreach (array_reverse($this->items) as $i) {
                    if ($i instanceof section) {
                        $this->lastsection = $i;
                        break;
                    }
                }
            }

            return true;
        }

        foreach ($this->items as $i) {
            $result = $i->remove($item);
            if ($result === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get model.
     *
     * @return model
     */
    public function get_model() {
        return $this;
    }

    /**
     * Do not use!
     *
     * @internal Model cannot have a parent.
     *
     * @param item $parent
     *
     * @throws \coding_exception
     */
    public function set_parent(item $parent = null) {
        throw new \coding_exception('Model cannot have a parent!');
    }

    /**
     * Get parent of model.
     *
     * @internal Model cannot have a parent.
     *
     * @return item always null
     */
    public function get_parent() {
        return null;
    }

    /**
     * Freeze or unfreeze all elements in the model recursively.
     *
     * @param bool $state new state
     */
    public function set_frozen($state) {
        $this->require_not_finalised();

        $this->frozen = (bool)$state;
        foreach ($this->items as $item) {
            $item->set_frozen($state);
        }
    }

    /**
     * Is this item frozen?
     *
     * Frozen elements keep their current value from the form constructor,
     * data submitted via form is ignored.
     *
     * NOTE: this method is not recursive.
     *
     * @return bool
     */
    public function is_frozen() {
        return $this->frozen;
    }

    /**
     * Did anything cancel form submission?
     *
     * @return bool
     */
    public function is_form_cancelled() {
        $this->require_finalised();

        foreach ($this->items as $item) {
            if ($item->is_form_cancelled()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Does user submit the form with the intention to reload the form only?
     *
     * This is usually triggered by so called "no submit" buttons.
     *
     * @return bool
     */
    public function is_form_reloaded() {
        $this->require_finalised();

        if ($this->get_raw_post_data('___tf_reload')) {
            // This is intended for JS tricks that want to reload the form.
            return true;
        }

        foreach ($this->items as $item) {
            if ($item->is_form_reloaded()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the submitted data without validation.
     */
    public function get_data() {
        $this->require_finalised();

        if (!$this->is_form_submitted()) {
            // Nothing to do, there cannot be any data without the submission!
            return array();
        }

        $result = array();
        foreach ($this->items as $item) {
            $result = array_merge($result, $item->get_data());
        }

        return $result;
    }

    /**
     * Get submitted draft files.
     *
     * @return array
     */
    public function get_files() {
        $this->require_finalised();

        if (!$this->is_form_submitted()) {
            // Nothing to do, there cannot be any files without the submission!
            return array();
        }

        $result = array();
        foreach ($this->items as $item) {
            $result = array_merge($result, $item->get_files());
        }
        return $result;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->require_finalised();

        $result = array(
            'formid' => $this->get_id(),
            'action' => $this->form->get_action_url()->out(false),
            'idsuffix' => $this->get_id_suffix(),
            'phpclass' => get_class($this->form),
            'sesskey' => sesskey(),
            'cssclass' => str_replace('\\', '__', get_class($this->form)),
            'items' => $this->get_item_data_for_template($output),
            'failedsubmission' => false,
            'requiredpresent' => false,
            'actions' => [],
            'actionjson' => [],
        );

        // Populate the clientaction data.
        list(
            $result['actions'],
            $result['actionsjson']
        ) = $this->get_clientaction_data_for_template($output);

        if ($this->is_form_submitted() and !$this->is_valid()) {
            $result['failedsubmission'] = true;
            $this->add_error(get_string('submissionerror', 'totara_form'));
        }

        if ($this->find(true, 'get_attribute', 'totara_form\item', true, array('required'), false)) {
            $result['requiredpresent'] = true;
        }

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

    /**
     * Returns context information that can be used with the template to display this model.
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function get_item_data_for_template(\renderer_base $output) {
        $data = [];
        foreach ($this->items as $item) {
            $detail = $item->export_for_template($output);
            if (debugging()) {
                if (isset($detail['elementtype'])) {
                    debugging('Form item parameter clash, elementtype is reserved.', DEBUG_DEVELOPER);
                }
                if (isset($detail['elementid'])) {
                    debugging('Form item parameter clash, elementid is reserved.', DEBUG_DEVELOPER);
                }
            }
            $detail['elementtype'] = get_class($item);
            $detail['elementid'] = $item->get_id();
            $detail['elementclassification'] = ($item instanceof group) ? 'group' : 'element';
            $data[] = $detail;
        }
        return $data;
    }

    /**
     * Returns client action data for use in the model template.
     *
     * This information is used in by the forms JS to initialise client side actions.
     *
     * @param \renderer_base $output
     * @return array
     */
    protected function get_clientaction_data_for_template(\renderer_base $output) {
        $data = [];
        $json = [];
        foreach ($this->clientactions as $action) {
            $actiondata = $action->get_js_config_obj($output);
            if (debugging()) {
                if (isset($actiondata->actiontype)) {
                    debugging('Form action parameter clash, actiontype is reserved.', DEBUG_DEVELOPER);
                }
            }
            $actiondata->actiontype = get_class($action);
            $data[] = $actiondata;

            $actionjson_data = (array)$actiondata;
            // We need to camel case elementtype to elementType - horrid but must be done.
            unset($actionjson_data['actiontype']);
            $actionjson_data['actionType'] = $actiondata->actiontype;
            $json[] = $actionjson_data;
        }
        return array($data, json_encode($json));
    }

    /**
     * Was the form submitted?
     *
     * If yes we use the _POST data only, if not we use current values.
     *
     * @return bool
     */
    public function is_form_submitted() {
        return $this->submitted;
    }

    /**
     * Get current data for given element or all data if null.
     *
     * The format of the data should be the same as $form->get_data().
     *
     * @param string $elname
     * @return array empty when no data present
     */
    public function get_current_data($elname) {
        if (!isset($elname)) {
            return $this->currentdata;
        }
        if (array_key_exists($elname, $this->currentdata)) {
            return array($elname => $this->currentdata[$elname]);
        }
        return array();
    }

    /**
     * Get _POST data for given element or all data if null.
     *
     * The format may be very different from $form->get_data().
     *
     * @param string $elname
     * @return mixed
     */
    public function get_raw_post_data($elname = null) {
        if (!isset($elname)) {
            return $this->rawpostdata;
        }
        if (array_key_exists($elname, $this->rawpostdata)) {
            return $this->rawpostdata[$elname];
        }
        return null;
    }

    /**
     * Mark as finalised, no more changes to the structure are allowed!
     *
     * NOTE: This must be called only from the final form constructor!
     */
    public function finalise() {
        $this->require_not_finalised();
        $this->finalised = true;
    }

    /**
     * Returns element id suffix to prevent collisions when multiple forms on one page.
     *
     * @return string suffix
     */
    public function get_id_suffix() {
        return $this->idsuffix;
    }

    /**
     * Returns form id.
     *
     * @return string html element id
     */
    public function get_id() {
        return 'tf_fid_' . $this->get_id_suffix();
    }

    /**
     * Require model to be finalised, otherwise throw coding exception.
     */
    public function require_finalised() {
        if (!$this->finalised) {
            throw new \coding_exception('Invalid form model action, form is not finalised yet');
        }
    }

    /**
     * Require model to be finalised, otherwise throw coding exception.
     */
    public function require_not_finalised() {
        if ($this->finalised) {
            throw new \coding_exception('Cannot change form model, it is already finalise');
        }
    }

    /**
     * Use this method to add a submit and cancel buttons to the end of your form. Pass a param of false
     * if you don't want a cancel button in your form. If you have a cancel button make sure you
     * check for it being pressed using is_cancelled().
     *
     * NOTE: buttons are added to a buttons group with name 'actionbuttonsgroup'.
     *
     * @param bool $cancel whether to show cancel button
     * @param string $submitlabel custom label for submit button, null means get_string('savechanges')
     *
     * @return buttons
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {
        if ($submitlabel === null) {
            $submitlabel = get_string('savechanges');
        }

        // Crate a new group if not present yet.
        $buttongroup = $this->find('actionbuttonsgroup', 'get_name', 'totara_form\form\group\buttons');
        if (!$buttongroup) {
            $buttongroup = $this->add(new buttons('actionbuttonsgroup'), -1);
        }

        $buttongroup->add(new action_button('submitbutton', $submitlabel, action_button::TYPE_SUBMIT));
        if ($cancel) {
            $buttongroup->add(new action_button('cancelbutton', get_string('cancel'), action_button::TYPE_CANCEL));
        }

        return $buttongroup;
    }
}
