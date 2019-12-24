<?php
/**
 * This file is part of Totara LMS
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
 * @author Andrew McGhie <andrew.mcghie@totaralearning.com>
 * @package block_totara_featured_links
 */

namespace block_totara_featured_links\tile;

use core\output\flex_icon;

defined('MOODLE_INTERNAL') || die();

/**
 * Class base
 * @package block_totara_featured_links
 */
abstract class base {
    /** Defines the value for the tile to be visible to everyone.*/
    const VISIBILITY_SHOW = 0;
    /** Defines the value for the tile to be hidden from everyone.*/
    const VISIBILITY_HIDE = 1;
    /** Defines the values for the visibility of the tile to be defined by the custom rules.*/
    const VISIBILITY_CUSTOM = 2;
    /** Defines the value for the aggregation option that the user meets all of the defined rules.*/
    const AGGREGATION_ALL = 1;
    /** Defines the value for the aggregation option where the user only has to match one of the rules.*/
    const AGGREGATION_ANY = 0;

    /** Location of headings in the tiles. */
    const HEADING_TOP = 'top';
    const HEADING_BOTTOM = 'bottom';


    /** @var int id of the tile */
    public $id = '';
    /** @var int the order that the tile appears in the block */
    public $sortorder = '';
    /** @var int the id of the block that the tile is in */
    public $blockid = '';
    /** @var string the type of tile that it is */
    public $type = '';
    /** @var int the time that the tile was created */
    public $timecreated = '';
    /** @var int the last time that the tile was modified */
    public $timemodified = '';
    /** @var int the id of user who created the tile */
    public $userid = '';
    /** @var string the raw version of the data */
    public $dataraw = '';
    /** @var \stdClass the unfiltered and parsed version of the data */
    public $data;
    /** @var string has the modified version of the url so that if the www root changes the url will to */
    public $url_mod = '';
    /** @var \stdClass the filtered and parsed version of the data */
    public $data_filtered;
    /** @var string determines the basic visibility of the tile */
    public $visibility = '';
    /** @var boolean this holds whether or not to apply audience rules */
    public $audienceshowing = '';
    /** @var string what type of aggregation does the audiences use and whether to display the form values*/
    public $audienceaggregation = '';
    /** @var boolean this hold whether or not to apply preset rules and whether to display the form */
    public $presetshowing = '';
    /** @var array the presets that apply to the tile  */
    public $presets = [];
    /** @var string the raw version of $presets */
    public $presetsraw = '';
    /** @var string what type of aggregation do the presets use */
    public $presetsaggregation = '';
    /** @var string the overall aggregation for the audiences presets and custom rules */
    public $overallaggregation = '';
    /** @var boolean this holds whether the custom tile rules are showing */
    public $tilerulesshowing = '';
    /** @var string the custom rules that are saved and managed by the tile classes */
    public $tilerules = '';
    /** @var array created from exploding audience_raw */
    public $audiences = [];
    /** @var string comer separated values of the audience that the tile is visible to */
    public $audiences_raw = '';

    /** @var string The name of the content template */
    protected $content_template = 'block_totara_featured_links/content';
    /** @var string the name of the content wrapper template */
    protected $content_wrapper_template = 'block_totara_featured_links/content_wrapper';
    /** @var array The fields of the data object that the tile uses */
    protected $used_fields;
    /** @var string contains the classes to set usually on the content div */
    protected $content_class = '';
    /** @var string This is the name of the class that contains the definition for the content form for this tile */
    protected $content_form = '\block_totara_featured_links\tile\default_form_content';
    /** @var string This is the name of the class which defines the visibility form */
    protected $visibility_form = '\block_totara_featured_links\tile\default_form_visibility';

    /**
     * makes an empty tile if the tile id is null
     * if the tile id is not null then it will check if the tile contains the data
     * if not it will query the database to find the data
     * base constructor.
     * @param \stdClass|null $tile
     * @internal param int $tileid
     */
    public function __construct($tile = null) {
        global $DB, $USER;

        if (is_null($tile)) {
            // Even if the tile is not created yet we can still know the type.
            $type_arr = explode('\\', get_called_class());
            $this->type = $type_arr[0].'-'.$type_arr[count($type_arr) - 1];
            $this->userid = $USER->id;
            return;
        } else if (is_object($tile)) {
            $tile_data = $tile;
        } else {
            $tile_data = $DB->get_record('block_totara_featured_links_tiles', ['id' => (int)$tile], '*', MUST_EXIST);
        }

        foreach ($tile_data as $key => $value) {
            $this->$key = $value;
        }

        $this->audiences_raw = '';
        $results = $DB->get_records('cohort_visibility',
            [
                'instanceid' => $this->id,
                'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS
            ],
            '',
            'cohortid'
        );

        if (!empty($results)) {
            foreach ($results as $cohort_vis) {
                $this->audiences[] = $cohort_vis->cohortid;
            }
            $this->audiences_raw = implode(',', $this->audiences);
        }

        $this->decode_data();
    }

    /**
     * This makes a new tile.
     * The tile class must be passed
     * The block id must exist
     * @param int $blockinstanceid
     * @return \block_totara_featured_links\tile\base
     * @throws \coding_exception if the block instance does not exist.
     */
    public static function add($blockinstanceid) {
        global $DB, $USER;
        $blockinstanceid = (int)$blockinstanceid;
        if (!$DB->record_exists('block_instances', ['id' => $blockinstanceid])) {
            throw new \coding_exception('The Block instance id was not found');
        }

        $class_name = get_called_class();
        /** @var base $tile_instance */
        $tile_instance = new $class_name();
        $tile_instance->blockid = (string)$blockinstanceid;

        if (!isset($tile_instance->data)) {
            $tile_instance->data = new \stdClass();
        }
        // Finds the id for the row.
        $tile_instance->id = (string)$DB->insert_record('block_totara_featured_links_tiles', $tile_instance, true);

        $tile_instance->timecreated = (string)time();
        $tile_instance->userid = (string)$USER->id;
        $tile_instance->timemodified = (string)time();

        // Get the ordering for the new tile.
        $order_values = $DB->get_fieldset_select('block_totara_featured_links_tiles', 'sortorder', "blockid = $blockinstanceid");
        $tile_instance->sortorder = (string)($order_values ? max($order_values) + 1 : 1); // Sets the minimum position to 1.

        $tile_instance->visibility = (string)self::VISIBILITY_SHOW;
        $tile_instance->set_default_visibility();

        $tile_instance->add_tile();

        $tile_instance->encode_data();
        $tile_instance->filter_data_values();
        $DB->update_record('block_totara_featured_links_tiles', $tile_instance);
        return $tile_instance;
    }

    /**
     * This does the tile defined add
     * Ie instantiates objects so they can be referenced later
     * @return null
     */
    public abstract function add_tile();

    /**
     * Deletes the current tile.
     * @return bool whether or not the tile was successfully removed
     */
    final public function remove_tile() {
        global $DB;
        // Delete the tile.
        if (!$DB->get_record('block_totara_featured_links_tiles', ['id' => $this->id])) {
            return false;
        }
        $transaction = $DB->start_delegated_transaction();
        try {
            // Remove the row form the tiles table.
            $DB->delete_records('block_totara_featured_links_tiles', ['id' => $this->id]);
            // Remove cohort_visibility records if there are any.
            $DB->delete_records(
                'cohort_visibility',
                ['instanceid' => $this->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS]
            );
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
        self::squash_ordering($this->blockid);
        return true;
    }

    /**
     * Copy the files for the tile to the new location for the new tile
     * @param base $new_tile the object of the new tile
     * @return void
     */
    public function copy_files(base &$new_tile) {
    }

    /**
     * Gets the name of the tile to display in the edit form
     *
     * @throws \coding_exception You must override this function.
     * @return string
     */
    public static function get_name() {
        throw new \coding_exception('Please Override this function');
    }

    /**
     * Gets the class object for the tile that was specified with the id of the tile or the row of the tile
     *
     * @param int|\stdClass $tile_data
     * @return base
     */
    final public static function get_tile_instance($tile_data) {
        global $DB;
        if (is_int($tile_data) || is_numeric($tile_data)) {
            $tile = $DB->get_record('block_totara_featured_links_tiles', ['id' => $tile_data], '*', MUST_EXIST);
        } else {
            $tile = $tile_data;
        }
        list($plugin_name, $class_name) = explode('-', $tile->type, 2);
        $type = "\\$plugin_name\\tile\\$class_name";
        return new $type($tile);
    }

    /**
     * This will return an instance of the edit content form for the tile
     * the edit tile object must extend base_form_content
     * @param array $parameters This is the parameters for the form
     * @return base_form_content
     */
    public function get_content_form(array $parameters) {
        if ($parameters['blockinstanceid'] != $this->blockid) {
            throw new \coding_exception('The block id in parameters did not match the block id for the tile');
        }
        $data_obj = $this->get_content_form_data();
        $parameters['type'] = $data_obj->type;
        return new $this->content_form($data_obj, $parameters);
    }

    /**
     * Gets the data for the content form
     * @return \stdClass
     * @throws \coding_exception If the block instance does not exist.
     */
    public function get_content_form_data() {
        global $DB;
        if ($DB->record_exists('block_totara_featured_links_tiles', ['id' => !empty($this->id) ? $this->id : -1])) {
            if (!$DB->record_exists('block_instances', ['id' => $this->blockid])) {
                throw new \coding_exception('The block for the tile was not found');
            }
            $data_obj = $this->data_filtered;
            $data_obj->sortorder = $this->sortorder;
        } else { // Is new tile.
            $data_obj = new \stdClass();
            $data_obj->sortorder = self::get_next_sortorder($this->blockid);
        }
        $class_arr = explode('\\', get_class($this));
        $plugin_name = $class_arr[0];
        $class_name = $class_arr[count($class_arr) - 1];
        $data_obj->type = $plugin_name.'-'.$class_name;
        return $data_obj;
    }

    /**
     * Checks whether it makes sense for the tile to have visibility options
     * for the visibility to be hidden the block must be on a users dashboard so the page pattern has to match totara-dashboard and
     * have a parent context level of user
     * @return bool whether the tile should have visibility options
     */
    public function is_visibility_applicable() {
        global $DB;
        $blockinstance = $DB->get_record('block_instances', ['id' => $this->blockid], 'pagetypepattern,parentcontextid', MUST_EXIST);
        $parent_context = \context::instance_by_id($blockinstance->parentcontextid);
        return (!(preg_match('/^totara-dashboard/', $blockinstance->pagetypepattern) || preg_match('/^user-profile/', $blockinstance->pagetypepattern)) ||
            $parent_context->contextlevel != CONTEXT_USER);
    }

    /**
     * Similar to the edit_content_form but gets the visibility form object instead
     *
     * @throws \coding_exception
     * @param array $parameters ['blockinstanceid' => 1, 'tileid' => 2]
     * @return base_form_visibility
     */
    public function get_visibility_form(array $parameters) {
        global $DB;

        if (!isset($parameters['blockinstanceid']) || !isset($parameters['tileid'])) {
            throw new \coding_exception('blockinstanceid and tileid must be provided via parameters.');
        }

        if (!$DB->record_exists('block_instances', ['id' => $parameters['blockinstanceid']])) {
            throw new \coding_exception('The block for the tile does not exists');
        }
        if (!$DB->record_exists('block_totara_featured_links_tiles', ['id' => $parameters['tileid']])) {
            throw new \coding_exception('The tile does not exist');
        }
        if ($parameters['blockinstanceid'] != $this->blockid) {
            throw new \coding_exception('The block id in parameters did not match the block id for the tile');
        }
        if ($parameters['tileid'] != $this->id) {
            throw new \coding_exception('The tile id in the parameters did not match the id of the tile');
        }
        if ($this->id != $parameters['tileid']) {
            throw new \Exception('The tileid passed and the tile id of the object do not match');
        }
        if (isset($parameters['tile'])) {
            debugging('Get visibility form $parameters[\'tile\'] is reserved and has been overridden', DEBUG_DEVELOPER);
        }
        // Set the tile to the parameters so that we can get it if need be.
        $parameters['tile'] = $this;

        return new $this->visibility_form($this->get_visibility_form_data(), $parameters);
    }

    /**
     * This gets the default data to pass to the auth form
     * @return array
     */
    public function get_visibility_form_data() {
        $data = [
            'visibility' => $this->visibility,
            'audiences_visible' => $this->audiences_raw,
            'audience_aggregation' => $this->audienceaggregation,
            'presets_aggregation' => $this->presetsaggregation,
            'presets_checkboxes' => $this->presets,
            'overall_aggregation' => $this->overallaggregation,
            'audience_showing' => $this->audienceshowing,
            'preset_showing' => $this->presetshowing,
            'tile_rules_showing' => $this->tilerulesshowing
        ];
        if (isset($data['presets_checkboxes'][0]) && $data['presets_checkboxes'][0] == '') {
            unset($data['presets_checkboxes']);
        }
        return $data;
    }

    /**
     * Returns an array that the template will uses to put in text to help with accessibility
     * example (for the default content wrapper)
     *      [ 'tile_title' (optional) => 'value',
     *          'sr-only' => 'value']
     * @return array
     */
    public abstract function get_accessibility_text();

    /**
     * This saves the tile object to the data base by calling tile_custom_save and encoding the data
     * @param \stdClass $data
     */
    final public function save_content($data) {
        global $DB;
        if (!empty($data->type)) {
            $this->type = $data->type;
            unset($data->type);
        }
        if (!empty($data->sortorder)) {
            $this->sortorder = $data->sortorder;
            unset($data->sortorder);
        }
        $this->save_content_tile($data);
        $this->save_ordering();
        $this->timemodified = time();
        $this->encode_data();
        $DB->update_record('block_totara_featured_links_tiles', $this);
    }

    /**
     * This defines the saving process for the custom tile fields
     * This should modify the data variable rather than chang directly saving to the database cause if you don't
     * what you save will get overridden when the tile is saved to the database.
     *
     * @param \stdClass $data
     * @return void
     */
    public abstract function save_content_tile($data);

    /**
     * This saves the visibility options
     * @param \stdClass $data
     */
    final public function save_visibility($data) {
        global $DB;
        $this->visibility = !isset($data->visibility) ? self::VISIBILITY_SHOW : $data->visibility;

        // Remove Values if its not custom.
        if ($data->visibility != self::VISIBILITY_CUSTOM) {
            $this->set_default_visibility();
        } else {
            $this->audienceaggregation = empty($data->audience_aggregation) ? (string)self::AGGREGATION_ANY : $data->audience_aggregation;
            $this->presetsraw = !isset($data->presets_checkboxes) ? '' : implode(',', $data->presets_checkboxes);
            $this->presetsaggregation = empty($data->presets_aggregation) ? (string)self::AGGREGATION_ANY : $data->presets_aggregation;
            $this->overallaggregation = empty($data->overall_aggregation) ? (string)self::AGGREGATION_ANY : $data->overall_aggregation;
            $this->tilerules = $this->save_visibility_tile($data);
            $this->audienceshowing = !isset($data->audience_showing) ? 0 : $data->audience_showing;
            $this->presetshowing = !isset($data->preset_showing) ? 0 : $data->preset_showing;
            $this->tilerulesshowing = !isset($data->tile_rules_showing) ? 0 : $data->tile_rules_showing;
        }

        // Update the Cohort Visibility table.
        $res = $DB->get_records(
            'cohort_visibility',
            ['instanceid' => $this->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS]
        );
        if (isset($data->audiences_visible)) {
            foreach ($res as $audience) {
                if ($data->visibility != self::VISIBILITY_CUSTOM
                    || !in_array($audience->cohortid, explode(',', $data->audiences_visible))
                ) {
                    $DB->delete_records('cohort_visibility',
                        ['instanceid' => $this->id,
                            'instancetype' => COHORT_ASSN_ITEMTYPE_FEATURED_LINKS,
                            'cohortid' => $audience->cohortid
                        ]
                    );
                }
            }
            if ($this->visibility == self::VISIBILITY_CUSTOM && $this->audienceshowing) {
                foreach (explode(',', $data->audiences_visible) as $audience_id) {
                    if (in_array($audience_id, explode(',', $this->audiences_raw)) || $audience_id == '') {
                        continue;
                    }
                    global $USER;
                    $save_data = new \stdClass();
                    $save_data->cohortid = $audience_id;
                    $save_data->instanceid = $this->id;
                    $save_data->instancetype = COHORT_ASSN_ITEMTYPE_FEATURED_LINKS;
                    $save_data->timemodified = time();
                    $save_data->timecreated = time();
                    $save_data->usermodified = $USER->id;
                    $DB->insert_record('cohort_visibility', $save_data);
                }
            }
        }
        $this->timemodified = time();

        $DB->update_record('block_totara_featured_links_tiles', $this);
        $this->decode_data();
    }

    /**
     * Saves the data for the custom visibility.
     * Should only modify the custom_rules variable so the reset of the visibility and tile options are left the same
     * when its saved to the database
     * @param \stdClass $data all the data from the form
     * @return string
     */
    public abstract function save_visibility_tile($data);

    /**
     * Gets the javascript and $PAGE requirements for the tile type
     */
    protected function get_requirements() {
    }

    /**
     * Gets the data to be passed to the render_content function
     * @return array
     */
    protected abstract function get_content_template_data();

    /**
     * renders the tile contents
     * should return a string of HTML
     * the div wrappers and buttons are added by the content_wrapper template
     * @param \renderer_base $renderer
     * @return string
     */
    public function render_content(\renderer_base $renderer) {
        $this->get_requirements();
        $data = $this->get_content_template_data();
        return $renderer->render_from_template($this->content_template, $data);
    }

    /**
     * Renders the content_wrapper template
     * to change the template set the content_wrapper_template variable at the top of the class.
     * Note that parts of the content_wrapper template should be in every tile no matter what like controls so the tile
     * can be edited. Also controls should only be visible when the user is editing the block.
     * @param \renderer_base $renderer
     * @param array $settings
     * @return mixed
     */
    public function render_content_wrapper(\renderer_base $renderer, array $settings) {
        $data = $this->get_content_wrapper_template_data($renderer);
        $data = array_merge($data, $settings);
        return $renderer->render_from_template($this->content_wrapper_template, $data);
    }

    /**
     * Gets whether the tile is visible to the user by the custom rules defined by the tile.
     * This should only be used by the is_visible() function.
     * @return int (-1 = hidden, 0 = no rule, 1 = showing)
     */
    public abstract function is_visible_tile();

    /**
     * Returns true if the user is allowed to view the content of this tile.
     *
     * This gives custom tile types a way of removing the tile if the user does not have permission to view the content of the tile.
     * If this returns true then the standard visibility checks are made by {@link self::is_visible()}.
     * If this returns false then the user is deemed to not be allowed to see the content of the tile, and consequently
     * other visibility checks are not made, the user is simply not checked.
     *
     * @return bool
     */
    protected function user_can_view_content() {
        return true;
    }

    /**
     * Calculates whether the tile is visible for the user
     * @return bool
     */
    final public function is_visible() {
        global $USER;

        // First up check that the user can view the tiles content.
        // This is only restricted by advanced file types that display content that is or potentially is restricted.
        if (!$this->user_can_view_content()) {
            return false;
        }

        if (empty($this->visibility)) {
            return true;
        }

        if ($this->visibility == self::VISIBILITY_SHOW) {
            return true;
        } else if ($this->visibility == self::VISIBILITY_HIDE) {
            return false;
        } else if ($this->visibility == self::VISIBILITY_CUSTOM) {
            $matches = 0;
            $restrictions = 0;
            // Presets.
            if ($this->presetshowing) {
                $preset_matches = 0;
                $preset_restrictions = 0;
                if (in_array('loggedin', $this->presets)) {
                    if (isloggedin()) {
                        $preset_matches++;
                    } else {
                        $preset_restrictions++;
                    }
                }
                if (in_array('notloggedin', $this->presets)) {
                    if (!isloggedin()) {
                        $preset_matches++;
                    } else {
                        $preset_restrictions++;
                    }
                }
                if (in_array('guest', $this->presets)) {
                    if (isguestuser()) {
                        $preset_matches++;
                    } else {
                        $preset_restrictions++;
                    }
                }
                if (in_array('notguest', $this->presets)) {
                    if (!isguestuser()) {
                        $preset_matches++;
                    } else {
                        $preset_restrictions++;
                    }
                }
                if (in_array('admin', $this->presets)) {
                    if (is_siteadmin()) {
                        $preset_matches++;
                    } else {
                        $preset_restrictions++;
                    }
                }
                if ($this->presetsaggregation == self::AGGREGATION_ANY) {
                    if ($preset_matches > 0) {
                        $matches++;
                    } else if ($preset_restrictions > 0) {
                        $restrictions++;
                    }
                } else if ($this->presetsaggregation == self::AGGREGATION_ALL) {
                    if ($preset_restrictions > 0) {
                        $restrictions++;
                    } else if ($preset_matches > 0) {
                        $matches++;
                    }
                }
            }
            // Audiences.
            if ($this->audienceshowing) {
                $audience_matches = 0;
                $audience_restrictions = 0;
                foreach ($this->audiences as $audience) {
                    if ($audience == '') {
                        continue;
                    }
                    if (in_array($audience, totara_cohort_get_user_cohorts($USER->id)) > 0) {
                        $audience_matches++;
                    } else {
                        $audience_restrictions++;
                    }
                }
                if ($this->audienceaggregation == self::AGGREGATION_ANY) {
                    if ($audience_matches > 0) {
                        $matches++;
                    } else if ($audience_restrictions > 0) {
                        $restrictions++;
                    }
                } else if ($this->audienceaggregation == self::AGGREGATION_ALL) {
                    if ($audience_restrictions > 0) {
                        $restrictions++;
                    } else if ($audience_matches > 0) {
                        $matches++;
                    }
                }
            }
            if ($this->tilerulesshowing) {
                // Custom.
                $custom_visibility = $this->is_visible_tile();
                if ($custom_visibility == 1) {
                    $matches++;
                } else if ($custom_visibility == -1) {
                    $restrictions++;
                }
            }
            // Overall Aggregation.
            if ($this->overallaggregation == self::AGGREGATION_ANY) {
                return $matches > 0 || $restrictions == 0; // Return true if there are no rules as well.
            } else if ($this->overallaggregation == self::AGGREGATION_ALL) {
                return $restrictions == 0;
            }
        }
        return true;
    }

    /**
     * Checks if the user has the capability to edit the tile
     * This is similar to the user_can_edit method in block_base class
     * @return boolean
     */
    public function can_edit_tile() {
        global $USER, $DB;
        $block_context = \context_block::instance($this->blockid);
        if (has_capability('moodle/block:edit', $block_context)) {
            return true;
        }

        $block_instance_data = $DB->get_record('block_instances', ['id' => $this->blockid]);
        $parent_context_data = $DB->get_record('context', ['id' => $block_instance_data->parentcontextid]);
        $page_context = \context::instance_by_id($parent_context_data->id);

        // The blocks in My Moodle are a special case.  We want them to inherit from the user context.
        if (!empty($USER->id)
            && $parent_context_data->contextlevel == CONTEXT_USER       // Page belongs to a user.
            && $parent_context_data->instanceid == $USER->id            // Page belongs to this user.
            && $USER->id == $this->userid) {                            // Tile belongs to the user.
            return has_capability('moodle/my:manageblocks', $page_context);
        }
        return false;
    }

    /**
     * Returns the array of data used to render the tile with the add tile button
     *
     * @param int $blockid the id of the block that the adder tile will be in
     * @return array
     */
    final public static function export_for_template_add_tile($blockid) {
        global $PAGE;
        return [
            'adder' => true,
            'url' => (string)new \moodle_url('/blocks/totara_featured_links/edit_tile_content.php',
                [
                    'blockinstanceid' => $blockid,
                    'return_url' => $PAGE->url->out_as_local_url()]
            )
        ];
    }

    /**
     * Shifts all the sortorder values down to the lowest positive values so -1,3,5 becomes 1,2,3
     * @param int $blockid
     */
    final public static function squash_ordering($blockid) {
        global $DB;
        $tiles = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $blockid], 'sortorder ASC');
        $i = 1;
        foreach ($tiles as $tile) {
            if ($i != $tile->sortorder) {
                $tile->sortorder = $i;
                $DB->update_record('block_totara_featured_links_tiles', $tile);
            }
            $i++;
        }
    }

    /**
     * Gets the next value for sortorder that a new tile should have
     * @param int $blockid
     * @return int
     */
    final protected static function get_next_sortorder($blockid) {
        global $DB;
        return $DB->count_records(
            'block_totara_featured_links_tiles',
            ['blockid' => $blockid]) + 1;
    }

    /**
     * saves the sort to the database
     * Needs to be public for ajax calls
     * @return null
     */
    final public function save_ordering() {
        global $DB;
        // Gets what the sort used to be.
        $current_tile = $DB->get_record('block_totara_featured_links_tiles', ['id' => $this->id], '*', MUST_EXIST);
        $old_sortorder = $current_tile->sortorder;

        if ($old_sortorder == $this->sortorder) {
            return;
        }

        // Shifts all the tiles between the new position and the old position to make room for the tile.
        $orders = $DB->get_records('block_totara_featured_links_tiles', ['blockid' => $this->blockid]);
        foreach ($orders as $tile) {
            if ($tile->sortorder >= $old_sortorder && $tile->sortorder <= $this->sortorder) {
                $tile->sortorder -= 1;
            } else if ($tile->sortorder <= $old_sortorder && $tile->sortorder >= $this->sortorder) {
                $tile->sortorder += 1;
            }
            $DB->update_record('block_totara_featured_links_tiles', $tile);
        }
        $current_tile->sortorder = $this->sortorder;
        $DB->update_record('block_totara_featured_links_tiles', $current_tile);
        self::squash_ordering($this->blockid);
        $this->sortorder = $DB->get_field('block_totara_featured_links_tiles', 'sortorder', ['id' => $this->id]);
        return;
    }

    /**
     * Sets the default Visibility values
     */
    protected function set_default_visibility() {
        $this->audiences_raw = '';
        $this->audiences = [''];
        $this->audienceaggregation = (string)self::AGGREGATION_ANY;
        $this->presets = [''];
        $this->presetsraw = '';
        $this->presetsaggregation = (string)self::AGGREGATION_ANY;
        $this->overallaggregation = (string)self::AGGREGATION_ANY;
        $this->tilerules = '';
        $this->audienceshowing = '0';
        $this->presetshowing = '0';
        $this->tilerulesshowing = '0';
    }

    /**
     * Gets the base data that should be passed to the content_wrapper
     * It also renders the tile content.
     * call this method if you are going to override this function
     * not doing so could result in things like tiles that are no editable
     * @param \renderer_base $renderer
     * @return array
     */
    protected function get_content_wrapper_template_data(\renderer_base $renderer) {
        global $PAGE;
        $action_menu_items = [];
        $action_menu_items[] = new \action_menu_link_secondary(
            new \moodle_url('/blocks/totara_featured_links/edit_tile_content.php',
                ['blockinstanceid' => $this->blockid, 'tileid' => $this->id, 'return_url' => $PAGE->url->out_as_local_url()]),
            \core\output\flex_icon::get_icon('edit'),
            get_string('content_menu_title', 'block_totara_featured_links').'<span class="sr-only">'.get_string('content_menu_title_sr-only', 'block_totara_featured_links',
        $this->get_accessibility_text()['sr-only']). '</span>',
            ['type' => 'edit']);
        if ($this->is_visibility_applicable()) {
            $action_menu_items[] = new \action_menu_link_secondary(
                new \moodle_url('/blocks/totara_featured_links/edit_tile_visibility.php',
                    ['blockinstanceid' => $this->blockid, 'tileid' => $this->id, 'return_url' => $PAGE->url->out_as_local_url()]),
                \core\output\flex_icon::get_icon('hide'),
                get_string('visibility_menu_title', 'block_totara_featured_links').'<span class="sr-only">'.get_string('visibility_menu_title_sr-only', 'block_totara_featured_links',
                $this->get_accessibility_text()['sr-only']).'</span>',
                ['type' => 'edit_vis']);
        }
        $action_menu_items[] = new \action_menu_link_secondary(
            new \moodle_url('/'),
            \core\output\flex_icon::get_icon('delete'),
            get_string('delete_menu_title', 'block_totara_featured_links').'<span class="sr-only">'.get_string('delete_menu_title_sr-only', 'block_totara_featured_links',
                $this->get_accessibility_text()['sr-only']).'</span>',
            ['type' => 'remove', 'blockid' => $this->blockid, 'tileid' => $this->id]);

        return [
            'tile_id' => $this->id,
            'content' => $this->render_content($renderer),
            'disabled' => (!$this->is_visible()),
            'controls' => $renderer->render(
                new \action_menu($action_menu_items)
            ),
            'hidden_text' => $this->get_hidden_text()
        ];
    }

    /**
     * decodes the raw data variables
     */
    protected function decode_data() {
        $this->data = json_decode($this->dataraw);
        $this->presets = explode(',', $this->presetsraw);
        $this->audiences = explode(',', $this->audiences_raw);
        $this->filter_data_values();
        $this->url_mod = isset($this->data->url) ? $this->data->url : '';
        if (substr($this->url_mod, 0, 1) == '/') {
            $this->url_mod = new \moodle_url($this->url_mod);
        }
    }

    /**
     * encodes the data ready to put into the database
     */
    protected function encode_data() {
        $this->dataraw = json_encode($this->data);
        $this->presetsraw = implode(',', $this->presets);
    }

    /**
     * Removes the unused values in the data object for the content form.
     * This means that only the value that the tile uses will be updated and supplied to the content form
     * and the tile template allows for values to be persistent when changing tile types.
     * @return void
     */
    protected function filter_data_values () {
        $this->data_filtered = new \stdClass();
        if (empty($this->data)) {
            return;
        }
        foreach ($this->data as $key => $value) {
            if (in_array($key, $this->used_fields)) {
                $this->data_filtered->$key = $value;
            }
        }
    }

    /**
     * This text is shown in the top corner if viewing a tile that will not be visible.
     *
     * You may override this if there could be a special reason that the tile could be hidden, e.g. a deleted course
     * for a course tile.
     *
     * @return string of text shown if a tile is hidden but being viewed in edit mode.
     */
    protected function get_hidden_text() {
        return get_string('hidden_text', 'block_totara_featured_links');
    }
}