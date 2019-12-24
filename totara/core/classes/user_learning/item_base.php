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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package totara_core
 * @category user_learning
 */

namespace totara_core\user_learning;

/**
 * User Learning Item base class.
 *
 * All other user learning item classes the represent in product learning requirements must extend this class.
 *
 * @package totara_core
 * @category user_learning
 */
abstract class item_base implements item, designation {

    /**
     * The user this user learning item relates to.
     * @var \stdClass
     */
    public $user;

    /**
     * The id of this user learning ite,
     * @var int
     */
    public $id;
    public $shortname;
    public $fullname;
    public $description;
    public $description_format = null;

    public $url_view;
    public $progress;
    public $duedate;

    /**
     * The learning item record passed into the contructor.
     *
     * Kept as protected in case child classes require the entire object.
     * Noting they should not trust it!
     *
     * @var \stdClass
     */
    protected $learningitemrecord;

    private $context;
    private $owner;

    /**
     * Constructs a new user learning item instance given the user it relates to and the learning item.
     *
     * @param \stdClass|int $userorid
     * @param \stdClass $learningitemrecord
     */
    protected final function __construct($userorid, \stdClass $learningitemrecord) {
        $this->user = $this->resolve_user($userorid);
        $this->learningitemrecord = $learningitemrecord;
        $this->map_learning_item_record_data($learningitemrecord);
        $this->ensure_required_data_loaded();
    }

    /**
     * Ensure we have been able to load all the data required for the user's learning item.
     *
     * The {@see map_learning_item_record_data()} method must set at least the following properties:
     *   - id
     *   - shortname
     *   - fullname
     *   - url_view
     */
    protected function ensure_required_data_loaded() {
        $required = [
            'id',
            'shortname',
            'fullname',
            'url_view'
        ];
        foreach ($required as $field) {
            if ($this->$field === null) {
                // If you get here then please review your classes map_learning_item_record_data method and ensure it is setting all required fields.
                throw new \coding_exception('Method '.__CLASS__.'::map_learning_item_record_data() failed to load "'.$field.'" for the user learning item');
            }
        }
    }

    /**
     * Maps the from a learning item's data into the user learning item instance.
     *
     * This method must set at least the following properties:
     *   - id
     *   - shortname
     *   - fullname
     *   - url_view
     *
     * @param \stdClass $learningitemrecord The database record containing data that belongs to this user learning item.
     */
    abstract protected function map_learning_item_record_data(\stdClass $learningitemrecord);

    /**
     * Get the context for the item
     *
     * @return \context The context level for the item.
     */
    public function get_context() {
        if ($this->context === null) {
            /** @var \context $contextclass */
            $contextclass = \context_helper::get_class_for_level(static::get_context_level());
            if ($contextclass == 'context_system') {
                $this->context = \context_system::instance();
            } else {
                $this->context = $contextclass::instance($this->id);
            }
        }
        return $this->context;
    }

    /**
     * Sets the owner of an item.
     *
     * @throws \coding_exception if a circular reference has been encountered.
     * @param item_base $owner The parent of an item.
     */
    public function set_owner(item_base $owner) {
        if ($owner->has_owner() && $owner->get_owner() === $this) {
            throw new \coding_exception('Circular reference detected in user learning item ownership.');
        }
        $this->owner = $owner;
    }

    /**
     * Returns true if this user learning item has an owner.
     *
     * @return bool
     */
    public function has_owner() {
        return ($this->owner !== null);
    }

    /**
     * Gets the owner item of the learning item.
     *
     * @throws \coding_exception if the user learning item has no owner.
     * @return item_base The parent item.
     */
    public function get_owner() {
        if ($this->owner === null) {
            throw new \coding_exception('Attempting to get the owner of a user learning item that has no owner.');
        }
        return $this->owner;
    }

    /**
     * Returns the user record from the database given an id or the record.
     *
     * @param \stdClass|int $userorid
     * @return \stdClass
     */
    protected final static function resolve_user($userorid) {
        global $DB, $USER;
        if (is_object($userorid) && isset($userorid->id)) {
            $user = $userorid;
        } else if ($userorid == $USER->id) {
            $user = $USER;
        } else {
            $user = $DB->get_record('user', ['id' => (int)$userorid], '*', MUST_EXIST);
        }
        return $user;
    }


    /**
     * Exports data for rendering via a template.
     *
     * @return \stdClass
     */
    public function export_for_template() {
        $context = $this->get_context();
        $formatoptions = array('context' => $context);

        $record = new \stdClass;
        $record->component = $this->get_component();
        $record->type = $this->get_type();
        $record->id = $this->id;
        $record->shortname = format_string($this->shortname, true, $formatoptions);
        if ($this->shortname === $this->fullname) {
            $record->fullname = $record->shortname;
        } else {
            $record->fullname = format_string($this->fullname, true, $formatoptions);
        }
        if ($this->description !== null) {
            if ($this->description_format === null) {
                $record->description = format_string($this->description, true, $formatoptions);
            } else {
                $component  = ($record->component == 'totara_certification') ? 'totara_program' : $record->component;
                $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php',
                    $context->id, $component, 'summary', 0);

                $record->description = format_text($description, $this->description_format, $formatoptions);
            }
        }
        $record->url_view = (string)$this->url_view;

        if ($this instanceof item_has_progress) {
            $record->progress = $this->export_progress_for_template();
        }

        if ($this instanceof item_has_dueinfo) {
            $record->dueinfo = $this->export_dueinfo_for_template();
        }

        return $record;
    }
}
