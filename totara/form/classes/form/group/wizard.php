<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package totara_form
 */

namespace totara_form\form\group;

use totara_form\group;
use totara_form\item;

/**
 * Wizard Group stage wrapper.
 *
 * @author    Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   totara_form
 */
class wizard extends group {

    const FORM_CANCELLED = 'cancelled';
    const CHANGE_STAGE_NEXT = 'Next';
    const CHANGE_STAGE_PREV = 'Prev';

    /**
     * Currently visible stage.
     *
     * @var wizard_stage
     */
    private $currentstage;

    /**
     * Flag for preventing jumping ahead to later stages.
     *
     * @var bool
     */
    private $preventjumpahead = true;

    /**
     * Label of the submit button.
     *
     * @var string
     */
    private $submitbtnlabel = null;

    /**
     * Add wizard.
     *
     * @param item $item
     * @param int $position null means the end
     * @return object wizard
     * @throws \coding_exception
     */
    public function add(item $item, $position = null) {
        if ($item instanceof wizard_stage) {
            return $this->add_stage($item, $position);
        }
        throw new \coding_exception('Only wizard stages can be added to a wizard');
    }

    /**
     * Add stage to wizard.
     *
     * @param wizard_stage $stage
     * @param int $position
     * @return wizard_stage $stage
     */
    public function add_stage(wizard_stage $stage, int $position = null) {
        $stage = parent::add($stage, $position);
        return $stage;
    }

    /**
     * Finalise set up of the wizard. Call this at the end of wizard definition.
     */
    public function finalise() {
        $this->correct_selected_stage();
    }

    /**
     * Set currentstage to the requested stage
     * Hide all other stages
     */
    private function correct_selected_stage() {
        $requestedstage = $this->get_requested_current_stage();
        if ($requestedstage !== null) {
            $newstage = $this->get_stage_by_name($requestedstage);
            if ($newstage !== null) {
                $this->set_current_stage($newstage);
            }
        }

        $current = $this->get_current_stage_name();
        /** @var wizard_stage $stage */
        foreach ($this->get_items() as $stage) {
            if ($current === null) {
                $this->set_current_stage($stage);
                $current = $stage->get_name();
            }
            if ($stage->get_name() !== $current) {
                $stage->set_hidden(true);
            }
        }
    }

    /**
     * Find the first stage that has validation errors so we know
     * which stage to jump to when presenting the form again.
     *
     * @return wizard_stage|null
     */
    private function get_first_invalid_stage() {
        /** @var wizard_stage $stage */
        foreach ($this->get_items() as $stage) {
            if (!$stage->is_valid()) {
                return $stage;
            }
        }
        return null;
    }

    /**
     * Get requested stage name.
     *
     * @return string|null
     */
    public function get_requested_current_stage() {
        $data = $this->get_model()->get_raw_post_data();
        $changestagename = $this->get_changestage_name();
        if (isset($data[$changestagename])) {
            $newstage = $data[$changestagename];
            if ($newstage === self::CHANGE_STAGE_NEXT && isset($data[$this->get_name() . '__nextstage'])) {
                $newstage = $data[$this->get_name() . '__nextstage'];
            } else if ($newstage === self::CHANGE_STAGE_PREV && isset($data[$this->get_name() . '__prevstage'])) {
                $newstage = $data[$this->get_name() . '__prevstage'];
            }
            return $newstage;
        }
        return null;
    }

    /**
     * Get name of the changestage hidden field.
     *
     * @return string
     */
    private function get_changestage_name() {
        return $this->get_name() . '__changestage';
    }

    /**
     * Determines if the cancel button was clicked by checking a hidden field value.
     *
     * @return bool
     */
    public function is_form_cancelled() {
        $data = $this->get_model()->get_raw_post_data();
        $changestagename = $this->get_changestage_name();
        return (isset($data[$changestagename]) && $data[$changestagename] == self::FORM_CANCELLED);
    }

    /**
     * Get current stage name.
     *
     * @return string|null
     */
    public function get_current_stage_name() {
        if (!$this->currentstage) {
            return null;
        }
        return $this->currentstage->get_name();
    }

    /**
     * Set current stage.
     *
     * @param item $stage
     */
    public function set_current_stage(item $stage) {
        if ($this->currentstage !== null) {
            $this->currentstage->set_hidden(true);
        }
        $this->currentstage = $stage;
        $this->currentstage->set_hidden(false);
    }

    /**
     * Set submit button label .
     *
     * @param string $label
     */
    public function set_submit_label($label) {
        $this->submitbtnlabel = $label;
    }

    /**
     * Get stage.
     *
     * @param \string $name
     * @return wizard_stage|null
     */
    public function get_stage_by_name(string $name) {
        /** @var wizard_stage $item */
        foreach ($this->get_items() as $item) {
            if ($item->get_name() === $name) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Set prevent jump ahead.
     *
     * @param bool|null $value
     */
    public function prevent_jump_ahead(bool $value) {
        $this->preventjumpahead = $value;
    }

    /**
     * Get Mustache template data.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {
        $this->get_model()->require_finalised();

        // When invalid form submitted, jump to the first stage that has errors.
        if (!$this->get_model()->is_valid()) {
            $firstinvalidstage = $this->get_first_invalid_stage();
            if ($firstinvalidstage) {
                $this->set_current_stage($firstinvalidstage);
            }
        }

        $itemnumber = 0;

        $result = array(
            'form_item_template' => 'totara_form/group_wizard',
            'name' => $this->get_name(),
            'id' => $this->get_id(),
            'items' => array(),
            'frozen' => $this->is_frozen(),
            'isonfirststage' => false,
            'isonlaststage' => false,
            'currentstage' => $this->get_current_stage_name(),
            'previousstage' => null,
            'nextstage' => null,
            'next_stage_data_attr' => self::CHANGE_STAGE_NEXT,
            'preventjumpahead' => $this->preventjumpahead,
            'amdmodule' => 'totara_form/form_group_wizard',
            'numberofstages' => count($this->get_items()),
            'stagenumber' => null,
            'wizardnextbtn' => null,
            'wizardstageprogress' => null,
            'wizardsubmitbtn' => null,
            'wizardcancelbtn' => get_string('cancel', 'core'),
        );

        $submitbtnlabel = $this->submitbtnlabel;
        if ($submitbtnlabel === null) {
            $result['wizardsubmitbtn'] = get_string('submit');
        }
        else {
            $result['wizardsubmitbtn'] = $submitbtnlabel;
        }

        $currentstage = null;
        $previousstage = null;
        $nextstage = null;
        $items = $this->get_items();

        foreach ($items as $item) {
            $itemnumber++;
            $detail = $item->export_for_template($output);
            if ($currentstage === null && $item->get_name() === $this->get_current_stage_name()) {
                $result['stagenumber'] = $itemnumber;
                $result['wizardstageprogress'] = get_string(
                    'wizardstageprogress',
                    'totara_form',
                    [
                        'currentstage' => $result['stagenumber'],
                        'numberofstages' => $result['numberofstages'],
                        'stage' => get_string('stage', 'totara_form')
                    ]
                );

                $currentstage = $item;
                if (reset($items) === $item) {
                    $result['isonfirststage'] = true;
                }
                if (end($items) === $item) {
                    $result['isonlaststage'] = true;
                }
                if ($previousstage !== null) {
                    $result['previousstage'] = $previousstage;
                }
                $nextstage = true;
            } else {
                if ($nextstage !== null) {
                    $result['nextstage'] = $item->get_name();
                    $nextstage = null;
                    $result['wizardnextbtn'] = get_string('wizardnext', 'totara_form', $detail['title']);
                }
            }
            $previousstage = $item->get_name();
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

            $detail['isfirststage'] = reset($items) === $item;
            $detail['islaststage'] = end($items) === $item;
            $detail['isbeforecurrentstage'] = ($currentstage === null);
            $detail['iscurrentstage'] = ($currentstage !== null && $currentstage === $item);
            $detail['isaftercurrentstage'] = ($currentstage !== null && $currentstage !== $item);

            $result['items'][] = $detail;
        }

        // Add errors if found, tweak attributes by validators.
        $this->set_validator_template_data($result, $output);

        // Add help button data.
        $this->set_help_template_data($result, $output);

        return $result;
    }

}
