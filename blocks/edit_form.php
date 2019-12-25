<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Defines the base class form used by blocks/edit.php to edit block instance configuration.
 *
 * It works with the {@link block_edit_form} class, or rather the particular
 * subclass defined by this block, to do the editing.
 *
 * @package    core_block
 * @copyright  2009 Tim Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/blocklib.php');

/**
 * The base class form used by blocks/edit.php to edit block instance configuration.
 *
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_edit_form extends moodleform {

    /**
     * Used to represent null as it passes through this form.
     * Needed as moodleform doesn't deal with nulls very well.
     */
    const NULL = '%@NULL@%';

    /**
     * The block instance we are editing.
     * @var block_base
     */
    public $block;
    /**
     * The page we are editing this block in association with.
     * @var moodle_page
     */
    public $page;

    /**
     * An array of pagetypelist options.
     * DO NOT ACCESS DIRECTLY, call self::get_pagetypelist_options().
     * @var array|null
     */
    private $pagetypelist_options = null;

    /**
     * True if the pagetypelist warning should be shown.
     * DO NOT ACCESS DIRECTLY, call self::get_pagetypelist_options().
     * @var bool|null
     */
    private $pagetypelist_warning = null;

    /**
     * Edit form constructor.
     *
     * @param moodle_url|string $actionurl
     * @param block_base $block
     * @param moodle_page $page
     */
    public function __construct($actionurl, $block, $page) {
        $this->block = $block;
        $this->page = $page;
        parent::__construct($actionurl);
    }

    /**
     * Returns the parent context.
     *
     * @return context
     */
    public function get_block_parent_context(): context {
        return context::instance_by_id($this->block->instance->parentcontextid);
    }

    /**
     * Returns true if the user is editing a frontpage.
     *
     * @return bool
     */
    public function is_editing_the_frontpage(): bool {
        // There are some conditions to check related to contexts
        $ctxconditions = $this->page->context->contextlevel == CONTEXT_COURSE && $this->page->context->instanceid == get_site()->id;
        $issiteindex = (strpos($this->page->pagetype, 'site-index') === 0);
        // So now we can be 100% sure if edition is happening at frontpage
        return ($ctxconditions && $issiteindex);
    }

    /**
     * Returns an array of pagetypelist options.
     *
     * @return array
     */
    private function get_pagetypelist_options(): array {
        if ($this->pagetypelist_options === null) {
            $this->pagetypelist_options = [];
            $this->pagetypelist_warning = false;
            if ($this->is_editing_the_frontpage()) {
                $this->pagetypelist_options['*'] = '*'; // This is not going to be shown ever, it's an unique option
            } else {
                // Generate pagetype patterns by callbacks if necessary (has not been set specifically)
                $currentpagetypepattern = $this->block->instance->pagetypepattern;
                $parentcontext = $this->get_block_parent_context();
                $this->pagetypelist_options = generate_page_type_patterns($this->page->pagetype, $parentcontext, $this->page->context);
                if (!array_key_exists($currentpagetypepattern, $this->pagetypelist_options)) {
                    // Pushing block's existing page type pattern
                    $pagetypestringname = 'page-' . str_replace('*', 'x', $currentpagetypepattern);
                    if (get_string_manager()->string_exists($pagetypestringname, 'pagetype')) {
                        $this->pagetypelist_options[$currentpagetypepattern] = get_string($pagetypestringname, 'pagetype');
                    } else {
                        // As a last resort we could put the page type pattern in the select box
                        // however this causes mod-data-view to be added if the only option available is mod-data-*
                        // so we are just showing a warning to users about their prev setting being reset.
                        $this->pagetypelist_warning = true;
                        $this->pagetypelist_options[$currentpagetypepattern] = $currentpagetypepattern;
                    }
                }
            }
        }
        return $this->pagetypelist_options;
    }

    /**
     * Returns true if the pagetypelist warning should be shown.
     * This should be true in situations the block is using an unrecognised pagetypelist.
     *
     * @return bool
     */
    private function display_pagetypelist_warning(): bool {
        $this->get_pagetypelist_options();
        return $this->pagetypelist_warning;
    }

    /**
     * Returns an array of subpagepattern options.
     *
     * @return array
     */
    private function get_subpagepattern_options() {
        $options = [];
        if ($this->page->subpage) {
            $parentcontext = $this->get_block_parent_context();
            $options[self::NULL] = get_string('anypagematchingtheabove', 'block');
            if ($parentcontext->contextlevel !== CONTEXT_USER) {
                $options[$this->page->subpage] = get_string('thisspecificpage', 'block', $this->page->subpage);
            }
        }
        $currentsubpagepattern = $this->block->instance->subpagepattern;
        if (empty($currentsubpagepattern)) {
            $currentsubpagepattern = self::NULL;
        }
        if (!isset($options[$currentsubpagepattern])) {
            $options[$currentsubpagepattern] = $currentsubpagepattern;
        }
        return $options;
    }

    /**
     * Returns an array of default block regions.
     *
     * @return array
     */
    private function get_default_region_options() {
        $options = $this->page->theme->get_all_block_regions();
        $defaultregion = $this->block->instance->defaultregion;
        if (!array_key_exists($defaultregion, $options)) {
            $options[$defaultregion] = $defaultregion;
        }
        return $options;
    }

    /**
     * Returns an array of default context options.
     *
     * @return array
     */
    private function get_context_options() {
        $options = [];
        // Front page, show the page-contexts element and set $pagetypelist to 'any page' (*)
        // as unique option. Processign the form will do any change if needed
        $parentcontext = $this->get_block_parent_context();
        if ($this->is_editing_the_frontpage()) {
            $options = array();
            $options[BUI_CONTEXTS_FRONTPAGE_ONLY] = get_string('showonfrontpageonly', 'block');
            $options[BUI_CONTEXTS_FRONTPAGE_SUBS] = get_string('showonfrontpageandsubs', 'block');
            $options[BUI_CONTEXTS_ENTIRE_SITE]    = get_string('showonentiresite', 'block');

            // Any other system context block, hide the page-contexts element,
            // it's always system-wide BUI_CONTEXTS_ENTIRE_SITE
        } else if ($parentcontext->contextlevel == CONTEXT_SYSTEM) {
            $options[BUI_CONTEXTS_ENTIRE_SITE] = get_string('showonentiresite', 'block');

        } else if ($parentcontext->contextlevel == CONTEXT_COURSE) {
            // 0 means display on current context only, not child contexts
            // but if course managers select mod-* as pagetype patterns, block system will overwrite this option
            // to 1 (display on current context and child contexts)
            $options[BUI_CONTEXTS_CURRENT] = BUI_CONTEXTS_CURRENT;
        } else if ($parentcontext->contextlevel == CONTEXT_MODULE or $parentcontext->contextlevel == CONTEXT_USER) {
            // module context doesn't have child contexts, so display in current context only
            $options[BUI_CONTEXTS_CURRENT] = BUI_CONTEXTS_CURRENT;
        } else {
            $parentcontextname = $parentcontext->get_context_name();
            $options[BUI_CONTEXTS_CURRENT]      = get_string('showoncontextonly', 'block', $parentcontextname);
            $options[BUI_CONTEXTS_CURRENT_SUBS] = get_string('showoncontextandsubs', 'block', $parentcontextname);
        }
        return $options;
    }

    /**
     * Returns an array of available region options.
     *
     * @return array
     */
    private function get_region_options() {
        $regionoptions = $this->page->theme->get_all_block_regions($this->page->pagelayout);
        foreach ($this->page->blocks->get_regions() as $region) {
            // Make sure to add all custom regions of this particular page too.
            if (!isset($regionoptions[$region])) {
                $regionoptions[$region] = $region;
            }
        }
        $defaultregionoptions = $this->get_default_region_options();
        $blockregion = $this->block->instance->region;
        if (!array_key_exists($blockregion, $regionoptions)) {
            if (array_key_exists($blockregion, $defaultregionoptions)) {
                $regionoptions[$blockregion] = $defaultregionoptions[$blockregion];
            } else {
                $regionoptions[$blockregion] = $blockregion;
            }
        }
        return $regionoptions;
    }

    /**
     * Returns an array of weight options.
     *
     * @return array
     */
    public function get_weight_options() {
        // If the current weight of the block is out-of-range, add that option in.
        $blockweight = $this->block->instance->weight;
        $options = array();
        if ($blockweight < -block_manager::MAX_WEIGHT) {
            $options[$blockweight] = $blockweight;
        }
        for ($i = -block_manager::MAX_WEIGHT; $i <= block_manager::MAX_WEIGHT; $i++) {
            $options[$i] = $i;
        }
        if ($blockweight > block_manager::MAX_WEIGHT) {
            $options[$blockweight] = $blockweight;
        }
        $first = reset($options);
        $options[$first] = get_string('bracketfirst', 'block', $first);
        $last = end($options);
        $options[$last] = get_string('bracketlast', 'block', $last);
        return $options;
    }

    /**
     * Defines the block edit form.
     *
     * Please DO NOT override this method. It is considered final.
     * If you want to add configuration please override specific_definition().
     */
    final public function definition() {
        $mform =& $this->_form;

        // First show fields common for all blocks.
        $this->common_definition($mform);

        // Specific definitions for blocks
        $this->specific_definition($mform);

        // TOTARA: This is a little hacky, we are going to force this as a config option for all blocks.
        // Because its prefixed with "config_" it will be collected from and stored in the block_instance.configdata field
        // automatically for us.

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'whereheader', get_string('wherethisblockappears', 'block'));

        $mform->addElement('static', 'bui_homecontext', get_string('createdat', 'block'), $this->get_block_parent_context()->get_context_name());
        $mform->addHelpButton('bui_homecontext', 'createdat', 'block');

        $options = $this->get_context_options();
        if (count($options) > 1) {
            $mform->addElement('select', 'bui_contexts', get_string('contexts', 'block'), $options);
            if ($this->is_editing_the_frontpage()) {
                $mform->addHelpButton('bui_contexts', 'contexts', 'block');
            }
        } else {
            $mform->addElement('hidden', 'bui_contexts', reset($options));
            $mform->setType('bui_contexts', PARAM_INT);
        }

        // For pre-calculated (fixed) pagetype lists
        $pagetypelist = $this->get_pagetypelist_options();
        if (count($pagetypelist) > 1) {
            if ($this->display_pagetypelist_warning()) {
                $mform->addElement('static', 'pagetypewarning', '', get_string('pagetypewarning','block'));
            }
            $mform->addElement('select', 'bui_pagetypepattern', get_string('restrictpagetypes', 'block'), $pagetypelist);
        } else {
            $value = reset($pagetypelist);
            $mform->addElement('hidden', 'bui_pagetypepattern', $value);
            $mform->setType('bui_pagetypepattern', PARAM_NOTAGS);
            // Now we are really hiding a lot (both page-contexts and page-type-patterns),
            // specially in some systemcontext pages having only one option (my/user...)
            // so, until it's decided if we are going to add the 'bring-back' pattern to
            // all those pages or no (see MDL-30574), we are going to show the unique
            // element statically
            // TODO: Revisit this once MDL-30574 has been decided and implemented, although
            // perhaps it's not bad to always show this statically when only one pattern is
            // available.
            if (!$this->is_editing_the_frontpage()) {
                // Try to beautify it
                $strvalue = $value;
                $strkey = 'page-'.str_replace('*', 'x', $strvalue);
                if (get_string_manager()->string_exists($strkey, 'pagetype')) {
                    $strvalue = get_string($strkey, 'pagetype');
                }
                // Show as static (hidden has been set already)
                $mform->addElement('static', 'bui_staticpagetypepattern',
                    get_string('restrictpagetypes','block'), $strvalue);
            }
        }

        $subpageoptions = $this->get_subpagepattern_options();
        if (count($subpageoptions) > 1) {
            $mform->addElement('select', 'bui_subpagepattern', get_string('subpages', 'block'), $subpageoptions);
        } else {
            $mform->addElement('hidden', 'bui_subpagepattern', reset($subpageoptions));
            $mform->setType('bui_subpagepattern', PARAM_NOTAGS);
        }

        $mform->addElement('select', 'bui_defaultregion', get_string('defaultregion', 'block'), $this->get_default_region_options());
        $mform->addHelpButton('bui_defaultregion', 'defaultregion', 'block');

        $mform->addElement('select', 'bui_defaultweight', get_string('defaultweight', 'block'), $this->get_weight_options());
        $mform->addHelpButton('bui_defaultweight', 'defaultweight', 'block');

        // Where this block is positioned on this page.
        $mform->addElement('header', 'onthispage', get_string('onthispage', 'block'));

        $mform->addElement('selectyesno', 'bui_visible', get_string('visible', 'block'));

        $mform->addElement('select', 'bui_region', get_string('region', 'block'), $this->get_region_options());

        $mform->addElement('select', 'bui_weight', get_string('weight', 'block'), $this->get_weight_options());

        $pagefields = array('bui_visible', 'bui_region', 'bui_weight');
        if (!$this->block->user_can_edit()) {
            $mform->hardFreezeAllVisibleExcept($pagefields);
        }
        if (!$this->page->user_can_edit_blocks()) {
            $mform->hardFreeze($pagefields);
        }

        $this->add_action_buttons();
    }

    /**
     * Set the data for this form instance.
     *
     * @param stdClass $defaults
     */
    public function set_data($defaults) {
        // Prefix bui_ on all the core field names.
        $blockfields = array('showinsubcontexts', 'pagetypepattern', 'subpagepattern', 'parentcontextid',
                'defaultregion', 'defaultweight', 'visible', 'region', 'weight');
        foreach ($blockfields as $field) {
            $newname = 'bui_' . $field;
            $defaults->$newname = $defaults->$field;
        }

        // Copy block config into config_ fields.
        if (!empty($this->block->config)) {
            foreach ($this->block->config as $field => $value) {
                $configfield = 'config_' . $field;
                $defaults->$configfield = $value;
            }
        }

        // Munge ->subpagepattern becuase HTML selects don't play nicely with NULLs.
        if (empty($defaults->bui_subpagepattern)) {
            $defaults->bui_subpagepattern = '%@NULL@%';
        }

        $systemcontext = context_system::instance();
        if ($defaults->parentcontextid == $systemcontext->id) {
            $defaults->bui_contexts = BUI_CONTEXTS_ENTIRE_SITE; // System-wide and sticky
        } else {
            $defaults->bui_contexts = $defaults->bui_showinsubcontexts;
        }
        // Default context may be set to value that not allowed anymore. For example when "All pages" page
        // pattern was previously selected, bui_contexts will be set to 1, while it is not allowed option for course page.
        // Default context must be set to allowed context.
        // Especially when user has no options (e.g. when field is hidden).
        $allowedbuicontexts = $this->get_context_options();
        if (count($allowedbuicontexts) == 1) {
            $defaults->bui_contexts = array_keys($allowedbuicontexts)[0];
        }

        parent::set_data($defaults);
    }

    /**
     * Validate that submit data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        // First up ask the form to do the basic stuff.
        $errors = parent::validation($data, $files);

        // pagetypepattern needs to be validated because it is sometimes a select and sometimes hidden.
        if (empty($errors['bui_pagetypepattern'])) {
            $options = $this->get_pagetypelist_options();
            if (!isset($options[$data['bui_pagetypepattern']]) && !isset($options['*'])) {
                // Literally they hacked it, no special message here. They won't see it as the element is hidden.
                debugging('Unexpected bui_pagetypepattern provided '.$data['bui_pagetypepattern'], DEBUG_DEVELOPER);
                $errors['bui_pagetypepattern'] = get_string('error', 'error');
            }
        }

        // subpagepattern needs to be validated because it is sometimes a select and sometimes hidden.
        if (empty($errors['bui_subpagepattern'])) {
            $options = $this->get_subpagepattern_options();
            if (!isset($options[$data['bui_subpagepattern']])) {
                // Literally they hacked it, no special message here. They won't see it as the element is hidden.
                debugging('Unexpected bui_subpagepattern provided '.$data['bui_subpagepattern'], DEBUG_DEVELOPER);
                $errors['bui_subpagepattern'] = get_string('error', 'error');
            }
        }

        // context needs to be validated because it is sometimes a select and sometimes hidden.
        if (empty($errors['bui_contexts'])) {
            $options = $this->get_context_options();
            if (!isset($options[$data['bui_contexts']])) {
                // Literally they hacked it, no special message here. They won't see it as the element is hidden.
                debugging('Unexpected bui_contexts provided '.$data['bui_contexts'], DEBUG_DEVELOPER);
                $errors['bui_contexts'] = get_string('error', 'error');
            }
        }

        return $errors;
    }

    /**
     * Override this to create any form fields specific to this type of block.
     *
     * @param \MoodleQuickForm $form
     */
    protected function specific_definition($form) {
        // Hi. Please override me if you want to add your own specific definitions to the block.
    }

    /**
     * Override this to create any form fields specific to this type of block.
     *
     * @param \MoodleQuickForm $form the form being built.
     */
    final protected function common_definition(\MoodleQuickForm $form) {

        if (!$this->has_common_settings()) {
            return;
        }

        $form->addElement('header', 'config_header', get_string('common_settings', 'block'));

        // Title
        $form->addElement('text', 'cs_title', get_string('cs_title', 'block'), [
            'placeholder' => $this->block->get_title(),
        ]);
        $form->setDefault('cs_title', $this->block->get_common_config_value('title', $this->block->get_title()));
        $form->setType('cs_title', PARAM_TEXT);
        $form->disabledIf('cs_title', 'cs_override_title', 'notchecked');

        // Override title
        $form->addElement(
            'advcheckbox',
            'cs_override_title',
            get_string('cs_override_title', 'block'),
            '',
            [],
            [
                false,
                true,
            ]
        );
        $form->setDefault('cs_override_title',
            $this->block->get_common_config_value('override_title', false));

        // Enable hiding
        $form->addElement(
            'advcheckbox',
            'cs_enable_hiding',
            get_string('cs_enable_hiding', 'block'),
            '',
            [],
            [
                false,
                true,
            ]
        );
        $form->setDefault('cs_enable_hiding',
            $this->block->get_common_config_value('enable_hiding', true));

        // Enable docking
        $form->addElement(
            'advcheckbox',
            'cs_enable_docking',
            get_string('cs_enable_docking', 'block'),
            '',
            [],
            [
                false,
                true,
            ]
        );
        $form->setDefault('cs_enable_docking',
            $this->block->get_common_config_value('enable_docking', true));

        // Block appearance heading
        $form->addElement('header', 'displayconfig', get_string('displayconfig', 'block'));

        // Show header
        $form->addElement(
            'advcheckbox',
            'cs_show_header',
            get_string('cs_show_header', 'block'),
            '',
            [],
            [
                false,
                true,
            ]
        );
        $form->setDefault('cs_show_header',
            $this->block->get_common_config_value('show_header', $this->block->display_with_header()));

        // Show border
        $form->addElement(
            'advcheckbox',
            'cs_show_border',
            get_string('cs_show_border', 'block'),
            '',
            [],
            [
                false,
                true,
            ]
        );

        $form->setDefault('cs_show_border',
            $this->block->get_common_config_value('show_border', $this->block->display_with_border()));
    }

    /**
     * Split common settings data from the form data
     *
     * @param stdClass $data Given form data
     * @param bool $unset Unset common settings from given data
     * @return array
     */
    final public function split_common_settings_data(\stdClass $data, bool $unset = false) {
        $cs = [];

        foreach ($data as $key => $datum) {
            if (strpos($key, 'cs_') === 0) {
                $cs[substr($key, 3)] = $datum;

                if ($unset) {
                    unset($data[$key]);
                }
            }
        }

        return $cs;
    }



    /**
     * Override this if your block as configurable as rock.
     *
     * @return bool
     */
    protected function has_common_settings() {
        return true;
    }
}
