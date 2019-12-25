<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 */

namespace totara_core\quickaccessmenu;

final class group {

    private const PREFERENCE = 'groups';
    private const __default = self::LEARN;

    public const LEARN = 'learn';
    public const PERFORM = 'perform';
    public const PLATFORM = 'platform';
    public const CONFIGURATION = 'configuration';

    /**
     * The key for this group
     * @var string
     */
    private $key;

    /**
     * The label for this group
     * @var string
     */
    private $label;

    /**
     * The weight for this group
     * @var string
     */
    private $weight;

    /**
     * Visibility status of this group
     * @var string
     */
    private $visible;

    /**
     * Private constructor. Only creates a group object.
     * Use {@link group:get()} to get group outside of this class.
     *
     * @param string      $key
     * @param null|string $label
     * @param int|null    $weight
     * @param bool|null   $visible
     */
    private function __construct(string $key, ?string $label = null, ?int $weight = null, ?bool $visible = null) {
        $this->key = $key;
        $this->label = $label;
        $this->weight = $weight;
        $this->visible = $visible;
    }

    /**
     * Factory method to get group object from already existing groups.
     *
     * @param string   $key
     * @param int|null $userid
     *
     * @return group
     */
    public static function get(string $key, ?int $userid = null): group {
        global $USER;
        if ($userid === null) {
            $userid = $USER->id;
        }
        $groups = self::get_groups($userid);
        if (!isset($groups[$key])) {
            debugging('Invalid group provided, reset to default', DEBUG_DEVELOPER);
            $key = self::__default;
        }

        return new group($key, $groups[$key]->get_label(), $groups[$key]->get_weight(), $groups[$key]->get_visible());
    }

    /**
     * Returns the key used to identify this group
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Returns the label for this group
     * @return string
     */
    public function get_label(): string {
        return (string)$this->label;
    }

    /**
     * Sets the label for this group
     *
     * @param string $newlabel
     */
    public function set_label(string $newlabel) {
        $this->label = $newlabel;
    }

    /**
     * Returns the sort weight of this group
     * @return int
     */
    public function get_weight(): int {
        return $this->weight;
    }

    /**
     * Sets the weight for this group
     *
     * @param int $newweight
     */
    public function set_weight(int $newweight) {
        $this->weight = $newweight;
    }

    /**
     * Returns true if this group is visible to the user
     * @return bool
     */
    public function get_visible(): bool {
        return $this->visible == false ? false : true;
    }

    /**
     * Makes the group hidden
     */
    public function make_hidden() {
        $this->visible = false;
    }

    /**
     * Makes the item visible
     */
    public function make_visible() {
        $this->visible = true;
    }

    /**
     * Returns an array of group keys
     *
     * @param int $userid
     *
     * @return array
     */
    public static function get_group_keys(int $userid): array {
        return array_keys(self::get_groups($userid));
    }

    /**
     * Return an array of key => [label, weight, visible] groups
     *
     * @param int $userid
     *
     * @return group[]
     */
    public static function get_groups(int $userid): array {
        $default = self::get_defaults();
        $preference = preference_helper::get_preference($userid, self::PREFERENCE, null);
        if ($preference === null) {
            return $default;
        }
        $pref_groups = (array)$preference;
        if (empty($pref_groups)) {
            preference_helper::unset_preference($userid, self::PREFERENCE);

            return $default;
        }

        // Get properties from the default groups.
        // In the preferences we will always have default groups + custom.
        $groups = [];
        foreach ($pref_groups as $key => $group) {
            $groups[$key] = new group($key, $group->label, $group->weight, $group->visible);
            if (isset($default[$key])) {
                if (is_null($group->label)) {
                    $groups[$key]->set_label($default[$key]->get_label());
                }
                if (is_null($group->weight)) {
                    $groups[$key]->set_weight($default[$key]->get_weight());
                }
                if (is_null($group->visible)) {
                    $groups[$key]->make_visible();
                } else if ($group->visible === false) {
                    $groups[$key]->make_hidden(); // Default deleted groups are hidden.
                }
            }
        }

        uasort($groups, [group::class, 'sort_groups']); // Maintain index association.

        return $groups;
    }

    /**
     * @param int $userid
     * @param array $groupkeys
     * @return group[]
     */
    public static function reorder_groups(int $userid, $groupkeys): array {
        $groups = group::get_groups($userid);
        if (count($groups) !== count($groupkeys)) {
            throw new \coding_exception('Invalid number of groups provided.', [count($groups), count($groupkeys)]);
        }

        $group_weights = [];
        foreach ($groups as $key => $group) {
            $group_weights[$key] = $group->get_weight();
        }
        sort($group_weights);

        $map = [];
        foreach ($groupkeys as $key) {
            $map[$key] = array_shift($group_weights);
        }

        $pref_groups = group::get_group_preference_array($userid);
        foreach ($pref_groups as $key => $group) {
            if (isset($map[$key])) {
                $pref_groups[$key]->weight = $map[$key];
                unset($map[$key]);
            }
        }

        if (!empty($map)) {
            throw new \coding_exception('Leftover mapping after re-ordering of groups');
        }

        preference_helper::set_preference($userid, group::PREFERENCE, $pref_groups);

        return group::get_groups($userid);
    }

    /**
     * Return an array of key => string groups
     *
     * @param int $userid
     * @param int $length Truncate each label to this length
     *
     * @return array
     */
    public static function get_group_strings(int $userid, int $length = null): array {
        $group_strings = [];

        $groups = self::get_groups($userid);
        if (!empty($length)) {
            foreach ($groups as $key => $group) {
                if ($group->get_visible() === true) {
                    $group_strings[$key] = shorten_text($group->get_label(), (int)$length, true);
                }
            }
        } else {
            foreach ($groups as $key => $group) {
                if ($group->get_visible() === true) {
                    $group_strings[$key] = $group->get_label();
                }
            }
        }


        return $group_strings;
    }

    /**
     * Return an array of defaults
     *
     * @return group[]
     */
    private static function get_defaults(): array {
        return [
            self::PLATFORM      => new group(self::PLATFORM, new \lang_string('quickaccessmenu_group-platform', 'admin'), 1000, true),
            self::LEARN         => new group(self::LEARN, new \lang_string('quickaccessmenu_group-learn', 'admin'), 2000, true),
            self::PERFORM       => new group(self::PERFORM, new \lang_string('quickaccessmenu_group-perform', 'admin'), 3000, true),
            self::CONFIGURATION => new group(self::CONFIGURATION, new \lang_string('quickaccessmenu_group-configuration', 'admin'), 5000, true),
        ];
    }

    /**
     * Sorts two groups
     *
     * Suitable for use with usort/uasort
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    public static function sort_groups(group $a, group $b) {
        $weight_a = $a->weight;
        $weight_b = $b->weight;
        if ($weight_a === $weight_b) {
            $label_a = $a->get_label();
            $label_b = $b->get_label();

            return strcmp($label_a, $label_b);
        }

        return ($weight_a > $weight_b) ? 1 : -1;
    }

    /**
     * Explain this group as a string
     *
     * @return string
     */
    public function __toString(): string {
        return $this->key;
    }

    /**
     * Create a user specified group and save to the preferences table
     *
     * @param string|null $name If null the default unnamed group label is used.
     * @param int $userid
     * @return group
     */
    public static function create_group(?string $name, int $userid): group {
        $groups = self::get_group_preference_array($userid);

        do {
            $key = uniqid();
        } while (isset($groups[$key]));

        if ($name === null) {
            // Group names can be empty, need to take into account only null values.
            $name = get_string('quickaccessmenu:untitledgroup', 'totara_core');
        }

        $group = new \stdClass();
        $group->label = (string)$name;
        $group->visible = true;
        $group->weight = group::get_max_weight($userid) + 100 ;// Always add to the bottom of the group list
        $groups[$key] = $group;

        preference_helper::set_preference($userid, self::PREFERENCE, $groups);

        return new group($key, $group->label, $group->weight, $group->visible);
    }

    /**
     * Returns max weight of the current groups the user has.
     *
     * @param int $userid
     *
     * @return int
     */
    private static function get_max_weight(int $userid): int {
        $groups = self::get_groups($userid);

        $group_weights = [];
        foreach ($groups as $key => $group) {
            $group_weights[$key] = $group->get_weight();
        }

        return max($group_weights);
    }

    /**
     * Removes a specified group and saves to the preferences table.
     * Note: This function doesn't make sure the group does't contain
     * any items linked to it. You have to perform your own checks or
     * use {@link helper::remove_group()}.
     *
     * @param string $key
     * @param int $userid
     * @return bool
     */
    public static function remove_group(string $key, int $userid): bool {
        $groups = self::get_group_preference_array($userid);
        $defaults = self::get_defaults();

        if (isset($defaults[$key])) {
            // Hide the default group.
            $groups[$key]->visible = false;
        } else {
            // Remove the custom group.
            unset($groups[$key]);
        }

        preference_helper::set_preference($userid, self::PREFERENCE, $groups);

        return true;
    }

    /**
     * Renames a specified group and saves to the preferences table.
     *
     * @param string $key
     * @param string $name
     * @param int $userid
     * @return group
     * @throws \coding_exception If the group key is invalid.
     */
    public static function rename_group(string $key, string $name, int $userid): group {
        $groups = self::get_group_preference_array($userid);

        if (!isset($groups[$key])) {
            // Exception here, you should have checked prior to calling this function.
            throw new \coding_exception('Invalid group requested.', $key);
        }

        // Rename group.
        $groups[$key]->label = $name;

        preference_helper::set_preference($userid, self::PREFERENCE, $groups);

        return new group($key, $name, $groups[$key]->weight, $groups[$key]->visible);
    }

    /**
     * Returns an array of groups with properties ready to be saved into the user preferences.
     *
     * @param int $userid
     * @return array
     */
    public static function get_group_preference_array(int $userid): array {
        $preference = preference_helper::get_preference($userid, self::PREFERENCE, null);
        if ($preference === null) {
            $defaultgroup = new \stdClass();
            $defaultgroup->label = null;
            $defaultgroup->weight = null;
            $defaultgroup->visible = null;
            $defaultgroups = array_keys(self::get_defaults());
            $groups = [];
            foreach ($defaultgroups as $key) {
                $groups[$key] = clone($defaultgroup);
            }
        } else {
            // Our preferences should contain correct values already.
            $groups = (array)$preference;
        }

        return $groups;
    }
}
