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
 * @package    totara_core
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @author     Chris Wharton <chris.wharton@catalyst-eu.net>
 */
namespace totara_core\totara\menu;

defined('MOODLE_INTERNAL') || die();

/**
 * This class was originally used to update edit menu items,
 * existing references to class constants will have to be updated
 * to use matching constants in item class.
 *
 * @deprecated since Totara 12.0
 */
class menu implements \renderable, \IteratorAggregate {

    // Custom field values.
    // Totara menu default item - delete is forbidden
    const DEFAULT_ITEM = 0;
    // Totaramenu default classname - add sting to database
    const DEFAULT_CLASSNAME = '\totara_core\totara\menu\item';
    // Database menu item - delete is allowed
    const DB_ITEM = 1;

    /**
     * Use item::VISIBILITY_HIDE instead
     * @deprecated since Totara 12.0
     * @var int
     */
    const HIDE_ALWAYS = 0;
    /**
     * Use item::VISIBILITY_SHOW instead
     * @deprecated since Totara 12.0
     * @var int
     */
    const SHOW_ALWAYS = 1;
    /**
     * Use item::VISIBILITY_SHOW instead
     * @deprecated since Totara 12.0
     * @var int
     */
    const SHOW_WHEN_REQUIRED = 2; // Use
    /**
     * Use item::VISIBILITY_CUSTOM instead
     * @deprecated since Totara 12.0
     * @var int
     */
    const SHOW_CUSTOM = 3;

    // Maximum number of levels of menu items.
    // If increasing this number, additional .totara_item_depthX css styles need to be implemented to ensure
    // that the top navigation menu editor table is formatted correctly. Also, the "path" db column has max
    // length of 50, so keep an eye on that. Make sure to extend test_update_descendant_paths.
    const MAX_DEPTH = 3;

    /**
     * Any access rule operator.
     * @const AGGREGATION_ANY One or more are required.
     */
    const AGGREGATION_ANY = 0;

    /**
     * All access rule operator.
     * @const AGGREGATION_ALL All are required.
     */
    const AGGREGATION_ALL = 1;

    /**
     * Use '' or '_self' string instead.
     * @deprecated since Totara 12.0
     * @var string
     */
    const TARGET_ATTR_SELF = '_self';
    /**
     * Use '_blank' string instead.
     * @deprecated since Totara 12.0
     * @var string
     */
    const TARGET_ATTR_BLANK = '_blank';

    /**
    * @var \totara_core\totara\menu\menu stores pseudo category with id=0.
    * Use totara_core_menu::get(0) to retrieve.
    */
    protected static $menucat0;

    /**
    * @var array list of all fields and their short name and reserve value.
    */
    protected static $menufields = array(
        'id' => array('id', 0),
        'parentid' => array('pa', 0),
        'title' => array('ti', ''),
        'url' => array('ur', null),
        'classname' => array('cl', ''),
        'sortorder' => array('so', 0),
        'depth' => array('dh', 1),
        'path' => array('ph', null),
        'custom' => array('de', self::DB_ITEM),
        'customtitle' => array('ct', 0),
        'visibility' => array('vi', self::SHOW_ALWAYS),
        'visibilityold' => array('vo', self::SHOW_ALWAYS),
        'targetattr' => array('ta', self::TARGET_ATTR_SELF),
        'timemodified' => null, // Not cached.
    );

    /** @var int */
    protected $id = 0;

    /** @var int */
    protected $parentid = 0;

    /** @var string */
    protected $title = '';

    /** @var string */
    protected $url = '';

    /** @var string */
    protected $classname = '';

    /** @var int */
    protected $sortorder = 0;

    /** @var int */
    protected $depth = 0;

    /** @var string */
    protected $path = '';

    /** @var bool */
    protected $custom = self::DB_ITEM;

    /** @var int */
    protected $customtitle = 1;

    /** @var int */
    protected $visibility = self::SHOW_ALWAYS;

    /** @var int stores the last visibility state */
    protected $visibilityold = null;

    /** @var int */
    protected $targetattr = self::TARGET_ATTR_SELF;

    /** @var int */
    protected $timemodified = null;

    /**
     * Magic method getter, redirects to read values. Queries from DB the fields that were not cached
     *
     * @global \moodle_database $DB
     * @param string $name
     * @return mixed
     */
    public function __get($name) {
        global $DB;
        if (array_key_exists($name, self::$menufields)) {
            if ($this->$name === false) {
                // Property was not retrieved from DB, retrieve all not retrieved fields.
                $notretrievedfields = array_diff_key(self::$menufields, array_filter(self::$menufields));
                $rs = $DB->get_record('totara_navigation', array('id' => $this->id), join(',', array_keys($notretrievedfields)), MUST_EXIST);
                foreach ($rs as $key => $value) {
                    $this->$key = $value;
                }
            }
            return $this->$name;
        }
        debugging('Invalid totara_core_menu property accessed! ' . $name, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Full support for isset on magic read properties.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
        if (array_key_exists($name, self::$menufields)) {
            return isset($this->$name);
        }
        return false;
    }

    /**
     * Create an iterator because magic vars can't be seen by 'foreach'.
     * Implementing method from interface IteratorAggregate.
     *
     * @return \ArrayIterator
     */
    public function getiterator() {
        $ret = array();
        foreach (self::$menufields as $property => $unused) {
            if ($this->$property !== false) {
                $ret[$property] = $this->$property;
            }
        }
        return new \ArrayIterator($ret);
    }

    public function get_property() {
        return (array)$this->getiterator();
    }

    /**
     * Use item::create_instance() instead.
     *
     * @deprecated since Totara 12.0
     *
     * @param \stdClass $record from DB (may not contain all fields)
     */
    protected function __construct(\stdClass $record) {
        debugging('menu class was deprecated, use helper or item class instead', DEBUG_DEVELOPER);
        foreach ($record as $key => $val) {
            if (array_key_exists($key, self::$menufields)) {
                $this->$key = $val;
            }
        }
    }

    /**
     * Use item and helper classes instead.
     *
     * @deprecated since Totara 12.0
     *
     * @param int $id category id
     * @return null|menu
     */
    public static function get($id = 0) {
        debugging('menu class was deprecated, use helper or item class instead', DEBUG_DEVELOPER);
        if (!$id) {
            if (!isset(self::$menucat0)) {
                $record = new \stdClass();
                $record->id = 0;
                $record->parentid = 0;
                $record->title = '';
                $record->url = '';
                $record->classname = '';
                $record->sortorder = 0;
                $record->depth = 0;
                $record->path = '';
                $record->custom = self::DB_ITEM;
                $record->customtitle = 1;
                $record->visibility = self::SHOW_ALWAYS;
                $record->visibilityold = null;
                $record->targetattr = self::TARGET_ATTR_SELF;
                $record->timemodified = 0;
                self::$menucat0 = new menu($record);
            }
            return self::$menucat0;
        }

        if ($rs = self::get_records('tn.id = :id', array('id' => (int)$id))) {
            $record = reset($rs);
            return new menu($record);
        } else {
            throw new \moodle_exception('unknowcategory');
        }
    }

    /**
     * Creates a new category from raw data.
     *
     * @deprecated since Totara 12.0
     *
     * @return bool
     */
    public static function sync($data) {
        debugging('menu::sync() method was deprecated, use \totara_core\totara\menu\helper instead', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Creates a new category either from form data or from raw data
     *
     * @deprecated since Totara 12.0
     *
     * @param array|\stdClass $data
     * @return bool
     */
    public function create($data) {
       debugging('menu::create() method was deprecated, use \totara_core\totara\menu\helper::create_item() instead', DEBUG_DEVELOPER);
       return false;
    }

    /**
     * Returns the maximum depth of all of this node's children, relative to this node's depth.
     *
     * @deprecated since Totara 12.0
     *
     * @return int
     */
    public function max_relative_descendant_depth() {
        debugging('menu::max_relative_descendant_depth() method was deprecated', DEBUG_DEVELOPER);
        return 0;
    }

    /**
     * Determines if the specified depth is valid, given the desired depth, the depth of descendants and the
     * absolute maximum depth supported by the tree.
     *
     * @deprecated since Totara 12.0
     *
     * @param int $depth
     * @return bool
     */
    public function can_set_depth(int $depth) {
        debugging('menu::can_set_depth() method was deprecated', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Updates the record with either form data or raw data
     *
     * @deprecated since Totara 12.0
     *
     * @param array|\stdClass $data
     * @return bool
     */
    public function update($data) {
        debugging('menu::udpate() method was deprecated', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Validate node data
     *
     * @deprecated since Totara 12.0
     *
     * @param object $data
     * @return array $errors
     */
    public static function validation($data) {
        debugging('menu::validation() method was deprecated', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Replace url placeholders in custom menu items.
     *
     * @deprecated since Totara 12.0
     *
     * @param string $url
     * @return string
     */
    public static function replace_url_parameter_placeholders($url) {
        debugging('menu::udpate() method was deprecated, use item::replace_url_parameter_placeholders() instead', DEBUG_DEVELOPER);
        return item::replace_url_parameter_placeholders($url);
    }

    /**
     * Retrieves number of records from totara_navigation table
     *
     * @deprecated since Totara 12.0
     *
     * @param string $whereclause
     * @param array $params
     * @return array of stdClass objects
     */
    public static function get_records($whereclause, $params = array()) {
        global $DB;

        $fields = array_keys(array_filter(self::$menufields));
        $sql = "SELECT tn.". join(',tn.', $fields). " FROM {totara_navigation} tn WHERE ". $whereclause." ORDER BY tn.sortorder";
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retrieves number of records from totara_navigation table where visibility true
     *
     * @deprecated since Totara 12.0
     *
     * @return array of stdClass objects
     */
    public static function get_nodes() {
        debugging('menu::get_nodes() was deprecated', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Returns array of children categories
     *
     * @deprecated since Totara 12.0
     *
     * @return \totara_core\totara\menu\menu[] Array of totara_core_menu objects indexed by category id
     */
    public function get_children() {
        debugging('menu::get_children() was deprecated', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Deletes a category.
     *
     * @deprecated since Totara 12.0
     *
     * @return boolean
     */
    public function delete() {
        debugging('menu::delete() was deprecated, use helper::delete_item() instead', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Returns default visibility list.
     *
     * @deprecated since Totara 12.0
     *
     * @return array array(HIDE_ALWAYS, SHOW_ALWAYS, SHOW_WHEN_REQUIRED)
     */
    public static function get_visibility_list() {
        debugging('menu::get_visibility_list() was deprecated', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Returns node visibility if exists in visibility list or empty value.
     *
     * @deprecated since Totara 12.0
     *
     * @param string $visibility
     * @return string empty|visibility
     */
    public static function get_visibility($visibility) {
        debugging('menu::get_visibility() was deprecated', DEBUG_DEVELOPER);
        return '';
    }

    /**
     * Set child node as a parent during totara_upgrade_menu().
     *
     * @deprecated since Totara 12.0
     */
    public function set_parent() {
        debugging('menu::set_parent() was deprecated', DEBUG_DEVELOPER);
    }

    /*
     * Change $custom property to delete a totara menu item during totara_upgrade_menu().
     *
     * @deprecated since Totara 12.0
     */
    public function set_custom($custom = menu::DEFAULT_ITEM) {
        debugging('menu::set_parent() was deprecated', DEBUG_DEVELOPER);
    }

    /**
     * This function returns a list representing category parent tree
     * for display or to use in a form <select> element
     *
     * @deprecated since Totara 12.0
     *
     * @param integer $excludeid Exclude this category and its children from the lists built.
     * @param integer $parentid The node to add to the result, and all children to be called recursively.
     * @return array of strings
     */
    public static function make_menu_list($excludeid = 0, int $parentid = 0) {
        debugging('menu::make_menu_list() was deprecated, use helper::create_parentid_form_element() instead', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Changes the sort order of this categories parent shifting this category up or down one.
     *
     * @deprecated since Totara 12.0
     *
     * @param int $id category
     * @param bool $up If set to true the category is shifted up one spot, else its moved down.
     * @return bool true on success, false otherwise.
     */
    public static function change_sortorder($id = 0, $up = false) {
        debugging('menu::change_sortorder() was deprecated, use helper::change_sortorder() instead', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Changes menu item visibility.
     *
     * @deprecated since Totara 12.0
     *
     * @global \moodle_database $DB
     * @param int menu item id
     * @param bool $hide
     * @return bool True on success, false otherwise.
     */
    public static function change_visibility($id = 0, $hide = false) {
        debugging('menu::change_visibility() was deprecated, use helper::change_visibility() instead', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Resets the menu to the default state as determined by the code.
     *
     * @deprecated since Totara 12.0
     */
    public static function reset_menu() {
        debugging('menu::reset_menu() is deprecated, use helper::reset_menu() instead.', DEBUG_DEVELOPER);
        helper::reset_menu(false);
    }

    /**
     * Load new node class if exists. For custom items this will be
     * the class \totara_core\totara\menu\item, for other items it is
     * the item classname.
     *
     * Returns false if the classname provided is not found.
     *
     * @deprecated since Totara 12.0
     *
     * @param object item
     * @return \totara_core\totara\menu\item|bool - new instance or false if not found.
     */
    public static function node_instance($item) {
        debugging('menu::node_instance() is deprecated, use item::create_instance() instead.', DEBUG_DEVELOPER);
        $item = item::create_instance($item);
        if ($item) {
            return $item;
        } else {
            return false;
        }
    }

    /**
     * Method for obtaining a item setting.
     *
     * @deprecated since Totara 12.0
     *
     * @param string $type Identifies the class using the setting.
     * @param string $name Identifies the particular setting.
     * @return mixed The value of the setting $name or null if it doesn't exist.
     */
    public function get_setting($type, $name) {
        debugging('menu::get_setting() is deprecated, use item::get_setting() instead.', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Returns all of the settings associated with this item.
     *
     * @deprecated since Totara 12.0
     *
     * @return array
     */
    public function get_settings() {
        debugging('menu::get_settings() is deprecated', DEBUG_DEVELOPER);
        return array();
    }

    /**
     * Insert or update a single rule for a menu item.
     *
     * @deprecated since Totara 12.0
     *
     * @param string $type Identifies the class using the setting.
     * @param string $name Identifies the particular setting.
     * @param string $value New value for the setting.
     */
    public function update_setting($type, $name, $value) {
        debugging('menu::update_setting() is deprecated', DEBUG_DEVELOPER);
    }

    /**
     * Update several settings at once.
     *
     * @deprecated since Totara 12.0
     *
     * The $settings array is expected to be an array of arrays.
     * Each sub array should associative with three keys: type, name, value.
     *
     * @param array[] $settings
     */
    public function update_settings(array $settings) {
        debugging('menu::update_settings() is deprecated, use helper::update_custom_visibility_settings() instead.', DEBUG_DEVELOPER);
        helper::update_custom_visibility_settings($this->id, $settings);
    }

    /**
     * The list of available preset rules to select from.
     *
     * @deprecated since Totara 12.0
     *
     * @return array $choices Rules to select from.
     */
    public static function get_preset_rule_choices() {
        debugging('menu::get_preset_rule_choices() is deprecated, use helper::update_custom_visibility_settings() instead.', DEBUG_DEVELOPER);
        return item::get_visibility_preset_rule_choices();
    }

}
