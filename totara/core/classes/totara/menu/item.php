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

class item {
    /** @var int maximum visible depth of menu items */
    const MAX_DEPTH = 3;

    /** @var int menu item is always hidden */
    const VISIBILITY_HIDE = 0;

    /** @var int menu item is visible (default items may have extra checks) */
    const VISIBILITY_SHOW = 1;

    /** @var int use complex visibility rules - this may very slow */
    const VISIBILITY_CUSTOM = 3;

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

    /** @var int item id */
    protected $id;

    /** @var int parent recod id, 0 means top item */
    protected $parentid;

    /** @var string for custom items it is the title, for default items it is overridden title if customtitle is 1 */
    protected $title;

    /** @var string url for custom item, not used for containers or default items */
    protected $url;

    /** @var string PHP classname prefixed with backslash */
    protected $classname;

    /** @var int sort order */
    protected $sortorder;

    /** @var int 1 means this is a custom item or container, 0 is a default item or container */
    protected $custom;

    /** @var int always 1 for custom items or containers, 0 means use lang pack for default items and containers */
    protected $customtitle;

    /** @var int self::VISIBILITY_HIDE, self::VISIBILITY_SHOW or self::VISIBILITY_CUSTOM */
    protected $visibility;

    /** @var int used for show/hide UI toggle so that the custom visibility is not lost */
    protected $visibilityold;

    /** @var string either '_self, '_blank' or '' */
    protected $targetattr;

    /** @var int last modification timestamp */
    protected $timemodified;

    /**
     * Internal flag for detection of incorrect constructor use,
     * this will be removed after we make constructor private.
     * @var bool
     */
    private static $preventpublicconstructor = true;

    /**
     * Private constructor, use item::create_instance() instead.
     *
     * @param \stdClass|array $record
     */
    public function __construct($record) {
        if (self::$preventpublicconstructor) {
            debugging('Deprecated item::__construct() call, use item::create_instance() instead.', DEBUG_DEVELOPER);
        }
        self::$preventpublicconstructor = true;

        $this->classname = '\\' . static::class;
        $record = (object)(array)$record;

        // Extra tests until we make this constructor private.
        if (empty($record->id)) {
            debugging('Incorrect item::__construct() call, fake data is not allowed any more, use real database record', DEBUG_DEVELOPER);
        }
        if ($record->classname !== $record->classname) {
            debugging('Incorrect item::__construct() call, classname mismatch', DEBUG_DEVELOPER);
        }

        // NOTE: These assignments intentionally trigger notices when invalid/incomplete database record submitted.
        $this->id = $record->id;
        $this->parentid = $record->parentid;
        $this->title = $record->title;
        $this->url = $record->url;
        $this->sortorder = $record->sortorder;
        $this->customtitle = $record->customtitle;
        $this->visibility = $record->visibility;
        $this->visibilityold = $record->visibilityold;
        $this->targetattr = $record->targetattr;
        $this->timemodified = $record->timemodified;

        // Make sure the important fields are consistent with item type.
        if ($this->classname === '\totara_core\totara\menu\item' or $this->classname === '\totara_core\totara\menu\container') {
            $this->custom = '1';
            $this->customtitle = '1';
        } else {
            $this->custom = '0';
        }
    }

    /**
     * Create instance of item from db record.
     *
     * @param \stdClass|array $record
     * @return null|item
     */
    final public static function create_instance($record) {
        $record = (object)(array)$record;

        if (empty($record->id)) {
            debugging('Incorrect constructor call, fake data is not allowed any more, use real database record', DEBUG_DEVELOPER);
            return null;
        }

        if (!$record->classname) { // This will trigger notice if invalid data supplied.
            return null;
        }

        if ($record->custom) {
            if ($record->classname !== '\totara_core\totara\menu\item' and $record->classname !== '\totara_core\totara\menu\container') {
                return null;
            }
        } else {
            if ($record->classname === '\totara_core\totara\menu\item' or $record->classname === '\totara_core\totara\menu\container') {
                return null;
            }
        }

        $classname = $record->classname;
        if (!class_exists($classname)) {
            return null;
        }

        try {
            self::$preventpublicconstructor = false;
            $instance = new $classname($record);
        } catch (\Throwable $e) {
            return null;
        }

        if (!($instance instanceof item)) {
            return null;
        }

        return $instance;
    }


    /**
     * Is this item a custom item/container?
     *
     * @return bool false if default item/container
     */
    final public function is_custom() {
        return (bool)$this->custom;
    }

    /**
     * Is this item a container?
     *
     * @return bool
     */
    final public function is_container() {
        return ($this instanceof container);
    }

    /**
     * Returns node id.
     *
     * @return int id
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Returns node parent id.
     *
     * @return int parent id
     */
    public function get_parentid() {
        return $this->parentid;
    }

    /**
     * @deprecated since Totara 12.0
     */
    public function set_parentid($parentid = 0) {
        debugging('item::set_parentid() was deprecated, do not use it');
    }

    /**
     * Returns node formatted title.
     *
     * @return string node title
     */
    public function get_title() {
        if ($this->custom) {
            return format_string($this->title);
        }
        if (!$this->customtitle) {
            $this->title = $this->get_default_title();
        }
        return format_string($this->title);
    }

    /**
     * Returns localised title for default items and containers.
     *
     * This can be overridden via admin interface.
     *
     * @return string
     */
    protected function get_default_title() {
        // NOTE: this must be overridden in all default item and container classes.
        throw new \coding_exception('Menu item get_default_title() method is missing', get_called_class());
    }

    /**
     * Returns info to create help icon for this item
     * in Main menu administration UI.
     *
     * @return null|array help information in array: string name and component
     */
    public function get_default_admin_help() {
       // NOTE: Override if you want to provide a help icon in admin interface.
        return null;
    }

    /**
     * Returns node url.
     * Check for custom flag, if it is not set then returns default url.
     * Otherwise returns modified url by client.
     *
     * @param bool $replaceparams replace ##params##
     * @return string node url, &s are not encoded
     */
    public function get_url($replaceparams = true) {
        if (!$this->custom) {
            // URLs of default items cannot be altered.
            return $this->get_default_url();
        }
        if (!$replaceparams) {
            return $this->url;
        }
        return self::replace_url_parameter_placeholders($this->url);
    }

    /**
     * Replace url placeholders in menu items.
     *
     * @param string $url
     * @return string final node URL, &s are not encoded
     */
    final public static function replace_url_parameter_placeholders($url) {
        global $USER, $COURSE;

        $search = array(
            '##userid##',
            '##username##',
            '##useremail##',
            '##courseid##',
        );
        $replace = array(
            isset($USER->id) ? $USER->id : 0,
            isset($USER->username) ? urlencode($USER->username) : '',
            isset($USER->email) ? urlencode($USER->email) : '',
            isset($COURSE->id) ? $COURSE->id : SITEID,
        );

        $url =  str_replace($search, $replace, $url);

        // Make sure there are no nasty surprises.
        $url = purify_uri($url, false, false);
        if ($url === '') {
            $url = '#';
        }

        return $url;
    }

    /**
     * Return URL for default item.
     *
     * @return string
     */
    protected function get_default_url() {
        // NOTE: this must be overridden in all default item classes.
        throw new \coding_exception('Menu item get_default_url() method is missing', get_called_class());
    }

    /**
     * Returns node classname.
     *
     * @return string node classname prefix with backslash
     */
    final public function get_classname() {
        return $this->classname;
    }

    /**
     * Returns the visibility of a particular node.
     *
     * NOTE: parameter $calculate was removed in Totara 12.0,
     *       this now returns the visibility selected by admin
     *       in main menu administration interface.
     *
     * @return int
     */
    final public function get_visibility() {
        return $this->visibility;
    }

    /**
     * Returns localised visibility name.
     *
     * NOTE: this can be overridden to better explain
     *       why something is disabled.
     *
     * @return string
     */
    public function get_visibility_description() {
        if ($this->visibility == self::VISIBILITY_HIDE) {
            return get_string('menuitem:hide', 'totara_core');
        }
        if ($this->visibility == self::VISIBILITY_CUSTOM) {
            return get_string('menuitem:showcustom', 'totara_core');
        }
        if ($this->custom or $this instanceof container) {
            // Access control for container makes little sense, so use simple Show string.
            return get_string('menuitem:show', 'totara_core');
        } else {
            return get_string('menuitem:showwhenrequired', 'totara_core');
        }
    }

    /**
     * Is this item visible for current user?
     *
     * @return bool
     */
    final public function is_visible() {
        if ($this->visibility == self::VISIBILITY_HIDE) {
            return false;
        }
        if (!$this->check_visibility()) {
            return false;
        }
        if ($this->visibility == self::VISIBILITY_CUSTOM) {
            return $this->get_visibility_custom();
        }
        return true;
    }

    /**
     * Real-time check of visibility.
     *
     * NOTE: Since Totara 12.0 this is now checked even if custom visibility is selected by admin,
     *       the reason is that only the item knows for sure if the link works or not
     *       and it improves performance too.
     *
     * @return bool true means URL will work for current user if item is not disabled
     */
    protected function check_visibility() {
        // NOTE: Override in subclasses with specific visibility rules for the class,
        //       do not check is_disabled() because this method will not be called if item is disabled.
        return true;
    }

    /**
     * Returns initial visibility setting for new default items and containers.
     *
     * @return bool
     */
    public function get_default_visibility() {
        return true;
    }

    /**
     * Is this menu item completely disabled?
     * If yes it will not be visible for end users.
     *
     * @return bool
     */
    public function is_disabled() {
        // NOTE: override with true if feature is disabled.
        return false;
    }

    /**
     * Returns node original class name.
     *
     * @return string node name
     */
    final public function get_name() {
        return 'totaramenuitem' . $this->id;
    }

    /**
     * Returns node parent class name
     *
     * @return string parent node name
     */
    final public function get_parent() {
        if ($this->parentid) {
            return 'totaramenuitem' . $this->parentid;
        }
        return '';
    }

    /*
     * Returns the initial parent container for new default item or container.
     *
     * @return string Name of parent classname or 'root' for top level.
     */
    protected function get_default_parent() {
        return 'root';
    }

    /*
     * Returns the initial sort order for new default item or container.
     *
     * @return int|null Preferred sort order when this item is first added, null means at to the end of parent container.
     */
    public function get_default_sortorder() {
        return null;
    }

    /**
     * Returns node html target attribute.
     * Values for target attributes are nothing or _blank
     *
     * @return string node html target attribute.
     */
    public function get_targetattr() {
        if ($this->is_container()) {
            return '';
        }
        if (!$this->custom) {
            // New windows should be limited to external content, prevent them for default items,
            // in the work case developers may override this method in their default item.
            return '';
        }
        if ($this->targetattr === '_blank') {
            return '_blank';
        }
        return '';
    }

    /**
     * Method for obtaining a item setting.
     *
     * @param string $type Identifies the class using the setting.
     * @param string $name Identifies the particular setting.
     * @return string|null The value of the setting $name or null if it doesn't exist.
     */
    public function get_setting($type, $name) {
        global $DB;
        return $DB->get_field('totara_navigation_settings', 'value',
            array('itemid' => $this->id, 'type' => $type, 'name' => $name));
    }

    /**
     * Menu items that have their visibility set to use custom access rules use this function to check
     * their visibility.
     *
     * @return bool true if item is visible to current user.
     */
    final protected function get_visibility_custom() {
        global $DB;

        // The set of rules for this menu item.
        $ruleset = $DB->get_records('totara_navigation_settings', array('itemid' => $this->id));

        $visibility = true; // Default to being visible.
        $activerules = array();
        $ruleaggregations = array();
        $context = null;
        foreach ($ruleset as $rule) {
            if ($rule->name === 'enable' && $rule->value === '1') {
                $activerules[] = $rule->type;
                unset($ruleset[$rule->id]);
            }
            if ($rule->name === 'aggregation') {
                $ruleaggregations[$rule->type] = $rule->value;
                unset($ruleset[$rule->id]);
            }
            if ($rule->type === 'visibility_restriction' && $rule->name === 'item_visibility') {
                $visibility = $rule->value;
                unset($ruleset[$rule->id]);
            }
            if ($rule->type === 'role_access' && $rule->name === 'context') {
                $context = $rule->value;
                unset($ruleset[$rule->id]);
            }
        }
        $result = array();
        foreach ($ruleset as $rule) {
            if (in_array($rule->type, $activerules)) {
                $aggregation =  $visibility;
                if (isset($ruleaggregations[$rule->type])) {
                    $aggregation = $ruleaggregations[$rule->type];
                }
                switch ($rule->name) {
                    case 'active_roles':
                        $result[] = $this->get_visibility_by_role($rule, $aggregation, $context);
                        break;
                    case 'active_presets':
                        $result[] = $this->get_visibility_by_preset_rule($rule, $aggregation);
                        break;
                    case 'active_audiences':
                        $result[] = $this->get_visibility_by_audience($rule, $aggregation);
                        break;
                    default:
                        continue 2;
                }
            } else {
                continue;
            }
        }

        if ($visibility == self::AGGREGATION_ANY) {
            return in_array(true, $result); // Any true result.
        } else if ($visibility == self::AGGREGATION_ALL) {
            return !in_array(false, $result); // None false results.
        } else {
            return false;
        }
    }

    /**
     * The list of available preset rules to select from.
     *
     * @return string[] $choices Rules to select from.
     */
    final public static function get_visibility_preset_rule_choices() {
        $choices = array(
            'is_logged_in'              => get_string('menuitem:rulepreset_is_logged_in', 'totara_core'),
            'is_not_logged_in'          => get_string('menuitem:rulepreset_is_not_logged_in', 'totara_core'),
            'is_guest'                  => get_string('menuitem:rulepreset_is_guest', 'totara_core'),
            'is_not_guest'              => get_string('menuitem:rulepreset_is_not_guest', 'totara_core'),
            'is_site_admin'             => get_string('menuitem:rulepreset_is_site_admin', 'totara_core'),
            'can_view_required_learning'=> get_string('menuitem:rulepreset_can_view_required_learning', 'totara_core'),
            'can_view_my_team'          => get_string('menuitem:rulepreset_can_view_my_team', 'totara_core'),
            'can_view_my_reports'       => get_string('menuitem:rulepreset_can_view_my_reports', 'totara_core'),
            'can_view_certifications'   => get_string('menuitem:rulepreset_can_view_certifications', 'totara_core'),
            'can_view_programs'         => get_string('menuitem:rulepreset_can_view_programs', 'totara_core'),
            'can_view_allappraisals'    => get_string('menuitem:rulepreset_can_view_allappraisals', 'totara_core'),
            'can_view_latestappraisal'  => get_string('menuitem:rulepreset_can_view_latest_appraisal', 'totara_core'),
            'can_view_appraisal'        => get_string('menuitem:rulepreset_can_view_appraisal', 'totara_core'),
            'can_view_feedback_360s'    => get_string('menuitem:rulepreset_can_view_feedback_360s', 'totara_core'),
            'can_view_my_goals'         => get_string('menuitem:rulepreset_can_view_my_goals', 'totara_core'),
            'can_view_learning_plans'   => get_string('menuitem:rulepreset_can_view_learning_plans', 'totara_core'),
        );

        return $choices;
    }

    /**
     * Preset rules not compatible with the current menu item. For example, setting a rule that to be able
     * to view reports page one should be able to view report page leads to an infinite loop and should not
     * be an option users can select from.
     *
     * @return string[] A list of rules to exclude when configuring a menu item
     */
    public function get_incompatible_preset_rules(): array {
        return [];
    }

    /**
     * Checks the preset rules for this menu item.
     *
     * To add another rule, just add to the switch statement, and {@link item::get_visibility_preset_rule_choices()}.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $visibility Logical operator for combining multiple results - one of the item::AGGREGATION_* constants.
     * @return bool $result True if the item is visible.
     */
    private function get_visibility_by_preset_rule($rule, $visibility) {
        global $USER;

        $result = array();
        $presets = explode(',', $rule->value);
        foreach ($presets as $preset) {
            switch ($preset) {
                case 'is_logged_in':
                    $result[] = isloggedin();
                    break;
                case 'is_not_logged_in':
                    $result[] = !(isloggedin());
                    break;
                case 'is_guest':
                    $result[] = isloggedin() && isguestuser();
                    break;
                case 'is_not_guest':
                    $result[] = !isloggedin() || !isguestuser();
                    break;
                case 'is_site_admin':
                    $result[] = is_siteadmin($USER);
                    break;
                case 'can_view_required_learning':
                    $result[] = $this->check_menu_item_visibility('\totara_program\totara\menu\requiredlearning');
                    break;
                case 'can_view_my_team':
                    $result[] = $this->check_menu_item_visibility('\totara_core\totara\menu\myteam');
                    break;
                case 'can_view_my_reports':
                    $result[] = $this->check_menu_item_visibility('\totara_core\totara\menu\myreports');
                    break;
                case 'can_view_certifications':
                    $result[] = $this->check_menu_item_visibility('\totara_coursecatalog\totara\menu\certifications');
                    break;
                case 'can_view_programs':
                    $result[] = $this->check_menu_item_visibility('\totara_coursecatalog\totara\menu\programs');
                    break;
                case 'can_view_allappraisals':
                    $result[] = $this->check_menu_item_visibility('\totara_appraisal\totara\menu\allappraisals');
                    break;
                case 'can_view_latestappraisal':
                    $result[] = $this->check_menu_item_visibility('\totara_appraisal\totara\menu\latestappraisal');
                    break;
                case 'can_view_appraisal':
                    $result[] = $this->check_menu_item_visibility('\totara_appraisal\totara\menu\appraisal');
                    break;
                case 'can_view_feedback_360s':
                    $result[] = $this->check_menu_item_visibility('\totara_feedback360\totara\menu\feedback360');
                    break;
                case 'can_view_my_goals':
                    $result[] = $this->check_menu_item_visibility('\totara_hierarchy\totara\menu\mygoals');
                    break;
                case 'can_view_learning_plans':
                    $result[] = $this->check_menu_item_visibility('\totara_plan\totara\menu\learningplans');
                    break;
                default:
                    debugging('The preset rule: ' . $preset . ' is not defined.', DEBUG_DEVELOPER);
                    $result[] = true;
                    break;
            }
        }

        if ($visibility == self::AGGREGATION_ANY) {
            return in_array(true, $result); // Any true result.
        } else if ($visibility == self::AGGREGATION_ALL) {
            return !in_array(false, $result); // None false results.
        } else {
            return false;
        }
    }

    /**
     * Instantiate a menu item instance of the specified class and
     * use it to determine that menu item's visibility to the current
     * user.
     *
     * @param string $menuclass Class name for a menu item.
     * @return bool True if the current user can see that type of menu item.
     */
    private function check_menu_item_visibility($menuclass) {
        global $DB;

        $record = $DB->get_record('totara_navigation', array('classname' => $menuclass, 'custom' => 0));
        if (!$record) {
            return false;
        }

        $item = self::create_instance($record);
        if (!$item) {
            return false;
        }

        if ($item->is_disabled()) {
            return false;
        }

        return $item->is_visible();
    }

    /**
     * Checks the role rules for this menu item.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $aggregation Logical operator for combining multiple results - one of the item::AGGREGATION_* constants.
     * @param string $contextsetting If 'site', check for role in the system context. If 'any' check for role in any context.
     * @return bool $result True if the item is visible.
     */
    private function get_visibility_by_role($rule, $aggregation, $contextsetting) {
        global $DB, $USER, $CFG;

        if (!$rule->value) {
            return false;
        }

        $userroles = array();
        $allowedroles = explode('|', $rule->value);

        if (!isloggedin()) {
            // Not logged in users cannot have any real roles.
            if (!empty($CFG->notloggedinroleid)) {
                $userroles[$CFG->notloggedinroleid] = $CFG->notloggedinroleid;
            }

        } else if (isguestuser()) {
            // Guests should not have any role assigned, UI prevents it.
            // For perf reasons do not look for any other roles.
            if (!empty($CFG->guestroleid)) {
                $userroles[$CFG->guestroleid] = $CFG->guestroleid;
            }

        } else {
            // This is a real user.
            // First get all system roles the user has been assigned.
            $ras = get_user_roles_with_special(\context_system::instance(), $USER->id);
            foreach ($ras as $ra) {
                $userroles[$ra->roleid] = $ra->roleid;
            }

            // Add roles in all other contexts if specified.
            if ($contextsetting === 'any') {
                // First the frontpage.
                if (!empty($CFG->defaultfrontpageroleid)) {
                    $userroles[$CFG->defaultfrontpageroleid] = $CFG->defaultfrontpageroleid;
                }
                // Then all other contexts.
                $allroles = $DB->get_fieldset_sql(
                    "SELECT DISTINCT roleid
                       FROM {role_assignments}
                      WHERE userid = ?", array($USER->id));
                foreach ($allroles as $role) {
                    $userroles[$role] = $role;
                }
            }
        }

        $userroles = array_values($userroles);
        if ($aggregation == self::AGGREGATION_ANY) {
            return (count(array_intersect($allowedroles, $userroles)) != 0);
        } else if ($aggregation == self::AGGREGATION_ALL) {
            return (count(array_intersect($allowedroles, $userroles)) == count($allowedroles));
        } else {
            return false;
        }
    }

    /**
     * Checks the audience rules for this menu item.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $visibility Logical operator for combining multiple results - one of the item::AGGREGATION_* constants.
     * @return bool True if user has access rights.
     */
    private function get_visibility_by_audience($rule, $visibility) {
        global $DB, $USER;

        if (!isloggedin() || isguestuser()) {
            return false;
        }

        $allowedaudiences = explode(',', $rule->value);

        $sql = "SELECT cohortid
                  FROM {cohort_members}
                 WHERE userid = ?";
        $useraudiences = $DB->get_fieldset_sql($sql, array($USER->id));

        if ($visibility == self::AGGREGATION_ANY) {
            return (count(array_intersect($allowedaudiences, $useraudiences)) != 0);
        } else if ($visibility == self::AGGREGATION_ALL) {
            return (count(array_intersect($allowedaudiences, $useraudiences)) == count($allowedaudiences));
        } else {
            return false;
        }
    }
}
