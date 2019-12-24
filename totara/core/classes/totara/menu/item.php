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
 * Totara navigation edit page.
 *
 * @package    totara
 * @subpackage navigation
 * @author     Oleg Demeshev <oleg.demeshev@totaralms.com>
 * @author     Chris Wharton <chris.wharton@catalyst-eu.net>
 */
namespace totara_core\totara\menu;

use \totara_core\totara\menu\menu as menu;

class item {

    // @var properties of menu node.
    protected $id, $parentid, $title, $url, $classname, $sortorder;
    protected $depth, $path, $custom, $customtitle, $visibility, $targetattr;
    protected $name, $parent;

    /**
     * Set values for node's properties.
     *
     * @param object $node
     */
    public function __construct($node) {
        foreach ((object)$node as $key => $value) {
            $this->{$key} = $value;
        }
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
     * Set node parent id.
     */
    public function set_parentid($parentid = 0) {
        $this->parentid = $parentid;
    }

    /**
     * Returns node formatted title.
     * Check for customtitle flag, if it is not set then returns default title.
     * Otherwise returns modified title by client.
     *
     * @return string node title
     */
    public function get_title() {
        if (empty($this->customtitle)) {
            $this->title = $this->get_default_title();
        }
        return format_string($this->title);
    }

    /**
     * Check if get_default_title() method exists, if not throw exception.
     *
     * @return string
     */
    protected function get_default_title() {
        throw new \coding_exception('Menu item get_default_title() method is missing', get_called_class());
    }

    /**
     * Returns node url.
     * Check for custom flag, if it is not set then returns default url.
     * Otherwise returns modified url by client.
     *
     * @param bool $replaceparams replace ##params##
     * @return string node url
     */
    public function get_url($replaceparams = true) {
        if ((int)$this->custom == 0) {
            $this->url = $this->get_default_url();
        }
        if (!$replaceparams) {
            return $this->url;
        }
        return menu::replace_url_parameter_placeholders($this->url);
    }

    /**
     * Check if get_default_url() method exists, if not throw exception.
     *
     * @return string
     */
    protected function get_default_url() {
        throw new \coding_exception('Menu item get_default_url() method is missing', get_called_class());
    }

    /**
     * Returns node classname.
     *
     * @return string node classname
     */
    public function get_classname() {
        return $this->classname;
    }

    /**
     * Returns the visibility of a particular node.
     *
     * If $calculated is true, this method calls {@link check_visibility()} to assess
     * the visibility and always returns menu::SHOW_ALWAYS or menu::HIDE_ALWAYS.
     *
     * If $calculated is false, this method returns the raw visibility, which could
     * also be menu::SHOW_WHEN_REQUIRED.
     *
     * @param bool $calculated Whether or not to convert SHOW_WHEN_REQUIRED to an actual state.
     * @return int One of menu::SHOW_WHEN_REQUIRED, menu::SHOW_ALWAYS or menu::HIDE_ALWAYS
     */
    public function get_visibility($calculated = true) {
        if ($this->is_disabled()) {
            // Disabled features are always hidden!
            return menu::HIDE_ALWAYS;
        }
        if (!isset($this->visibility)) {
            $this->visibility = $this->get_default_visibility();
        }
        if ($calculated && $this->visibility == menu::SHOW_WHEN_REQUIRED) {
            return $this->check_visibility();
        }
        if ($calculated && $this->visibility == menu::SHOW_CUSTOM) {
            return $this->get_visibility_custom();
        }
        return $this->visibility;
    }

    /**
     * Real-time check of visibility for SHOW_WHEN_REQUIRED. Override
     * in subclasses with specific visibility rules for the class.
     *
     * @return int Either menu::SHOW_ALWAYS or menu::HIDE_ALWAYS.
     */
    protected function check_visibility() {
        return menu::SHOW_ALWAYS;
    }

    /**
     * Check if get_default_visibility() method exists, if not throw exception.
     *
     * @return bool
     */
    public function get_default_visibility() {
        throw new \coding_exception('Menu item get_default_visibility() method is missing', get_called_class());
    }

    /**
     * Is this menu item completely disabled?
     * If yes it will not be visible in admin UI and also for end users.
     *
     * @return bool
     */
    public function is_disabled() {
        // Note: override with true if feature disable.
        return false;
    }

    /**
     * Returns node original class name, wihtout namespace string.
     *
     * @return string node class name
     */
    public function get_name() {
        if ((int)$this->custom == 1) {
            $this->name = 'item' . $this->id;
        } else {
            $this->name = $this->get_original_classname($this->classname);
        }
        return $this->name;
    }

    /**
     * Returns node original parent class name, wihtout namespace string.
     *
     * @return string node parent class name
     */
    public function get_parent() {
        // If parent is empty then it is database record or new item from class.
        if ((int)$this->id == 0) {
            $this->parent = $this->get_default_parent();
        } else {
            if ((int)$this->parentid > 0) {
                $this->parent = $this->get_original_classname($this->parent);
                // Check if menu item created through UI
                if ($this->parent == 'item') {
                    $this->parent .= $this->parentid;
                }
            }
        }
        return $this->parent;
    }

    /*
     * Returns the default parent of this type of menu item. Defaults to top level ('root')
     * unless overridden in a subclass.
     *
     * @return string Name of parent classname or 'root' for top level.
     */
    protected function get_default_parent() {
        return 'root';
    }

    /*
     * Returns the default sort order of this type of menu item. Defaults to null (no preference)
     * unless overridden in a subclass.
     *
     * @return int|null Preferred sort order when this item is first added, or null for no preference.
     */
    public function get_default_sortorder() {
        return null;
    }

    /**
     * Returns node html target attribute.
     * Values for target attributes are _blank|_parent|_top|framename
     *
     * @return string node html target attribute.
     */
    public function get_targetattr() {
        if ((int)$this->id == 0) {
            $this->targetattr = $this->get_default_targetattr();
        }
        return $this->targetattr;
    }

    /*
     * Return value for url link target attribute.
     * Default value _self, <a href="http://mydomain.com">My page</a>.
     * Customer value _blank|_parent|_top|framename, <a target="_blank" href="http://mydomain.com">My page</a>.
     *
     * @return string node target attribute
     */
    protected function get_default_targetattr() {
        return menu::TARGET_ATTR_SELF;
    }

    /**
     * Parse namespace classname and returns original class name.
     * Coming string is "totara_core\totara\menu\myclassname".
     * Returns "myclassname".
     *
     * @param string $classname
     * @return string
     */
    private function get_original_classname($classname) {
        $path = \core_text::strrchr($classname, "\\");
        return \core_text::substr($path, 1);
    }

    /**
     * Menu items that have their visibility set to use custom access rules use this function to check
     * their visibility.
     *
     * @return bool true if item is visible to current user.
     */
    protected function get_visibility_custom() {
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

        if ($visibility == menu::AGGREGATION_ANY) {
            return in_array(true, $result); // Any true result.
        } else if ($visibility == menu::AGGREGATION_ALL) {
            return !in_array(false, $result); // None false results.
        } else {
            return false;
        }
    }

    /**
     * Checks the preset rules for this menu item.
     *
     * To add another rule, just add to the switch statement, and {@link menu::get_preset_rule_choices()}.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $visibility Logical operator for combining multiple results - one of the menu::AGGREGATION_* constants.
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

        if ($visibility == menu::AGGREGATION_ANY) {
            return in_array(true, $result); // Any true result.
        } else if ($visibility == menu::AGGREGATION_ALL) {
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
        /** @var \totara_core\totara\menu\item $menuinstance */
        $menuinstance = new $menuclass(array());
        $visibility = $menuinstance->get_visibility();
        return (bool) $visibility == menu::SHOW_ALWAYS;
    }

    /**
     * Checks the role rules for this menu item.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $aggregation Logical operator for combining multiple results - one of the menu::AGGREGATION_* constants.
     * @param string $contextsetting If 'site', check for role in the system context. If 'any' check for role in any context.
     *
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
        if ($aggregation == menu::AGGREGATION_ANY) {
            return (count(array_intersect($allowedroles, $userroles)) != 0);
        } else if ($aggregation == menu::AGGREGATION_ALL) {
            return (count(array_intersect($allowedroles, $userroles)) == count($allowedroles));
        } else {
            return false;
        }
    }

    /**
     * Checks the audience rules for this menu item.
     *
     * @param object $rule The rule that applies to the particular menu item.
     * @param int $visibility Logical operator for combining multiple results - one of the menu::AGGREGATION_* constants.
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

        if ($visibility == menu::AGGREGATION_ANY) {
            return (count(array_intersect($allowedaudiences, $useraudiences)) != 0);
        } else if ($visibility == menu::AGGREGATION_ALL) {
            return (count(array_intersect($allowedaudiences, $useraudiences)) == count($allowedaudiences));
        } else {
            return false;
        }
    }
}
