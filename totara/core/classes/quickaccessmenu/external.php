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
 */

namespace totara_core\quickaccessmenu;

use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;

global $CFG;
require_once("$CFG->libdir/externallib.php");

/**
 * External servcies for the user's quick access menu.
 */
final class external extends \external_api {

    /**
     * Ensures that the user can access their quick access menu.
     *
     * @param int $userid
     * @throws \coding_exception If the user cannot access their quick access menu.
     */
    private static function ensure_user_can_access_menu(int $userid) {
        if (!isloggedin() || isguestuser($userid)) {
            throw new \coding_exception('Unable to access menu.');
        }
        self::ensure_current_user_only($userid);

        $context = \context_user::instance($userid);
        self::validate_context($context);
    }

    /**
     * Ensures that the user can customise their quick access menu.
     *
     * @param int $userid
     * @throws \coding_exception If the user cannot customise their quick access menu.
     */
    private static function ensure_user_can_customise_menu(int $userid) {
        self::ensure_user_can_access_menu($userid);
        $context = \context_user::instance($userid);
        require_capability('totara/core:editownquickaccessmenu', $context);
    }

    /**
     * Ensures that the given user is the current user.
     * @param int $userid
     * @throws \coding_exception
     */
    private static function ensure_current_user_only(int $userid) {
        global $USER;
        if ((int)$USER->id !== $userid) {
            throw new \coding_exception('Currently the quick access menu only works for the current user.');
        }
    }

    /**
     * Normalises the userid parameter, setting it to the current user if it is empty.
     *
     * @param int|string|null $userid
     * @return int
     */
    private static function normalise_userid_parameter($userid): int {
        global $USER;
        if (empty($userid)) {
            $userid = $USER->id;
        }
        return (int)$userid;
    }

    /**
     * Returns the user's quick access menu.
     *
     * @param int|null $userid
     * @return array
     */
    public static function get_user_menu($userid = null): array {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_access_menu($userid);

        $params = self::validate_parameters(
            self::get_user_menu_parameters(),
            ['userid' => $userid]
        );
        $userid = $params['userid'];

        $menu = helper::get_user_menu($userid);
        $output = \totara_core\output\quickaccessmenu::create_from_menu($menu);

        return $output->get_template_data();
    }
    public static function get_user_menu_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function get_user_menu_returns() {
        return new external_single_structure([
            'can_edit'      => new external_value(PARAM_BOOL, 'True if menu can be edited'),
            'can_search'    => new external_value(PARAM_BOOL, 'True if menu can be searched'),
            'empty_message' => new external_value(PARAM_CLEANHTML, 'The message when the menu is empty'),
            'groups'        => new external_multiple_structure(
                new external_single_structure([
                    'title' => new external_value(PARAM_TEXT, 'The group title'),
                    'items' => new external_multiple_structure(
                        new external_single_structure([
                            'label' => new external_value(PARAM_TEXT, 'The item\'s label'),
                            'url'   => new external_value(PARAM_URL, 'The item\'s URL'),
                        ])
                    ),
                    'item_count' => new external_value(PARAM_INT, 'The number of items in this group'),
                    'has_items' => new external_value(PARAM_INT, 'True if this group has items'),
                ])
            ),
            'group_count' => new external_value(PARAM_INT, 'The number of groups'),
            'has_groups' => new external_value(PARAM_BOOL, 'True if there are groups'),
        ]);
    }

    /**
     * Reorders the items within a group within the user's quick access menu.
     *
     * @param string $group
     * @param array $itemkeys
     * @param int|null $userid
     * @return bool
     */
    public static function reorder_items_in_group(string $group, array $itemkeys, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::reorder_items_in_group_parameters(),
            ['group' => $group, 'itemkeys' => $itemkeys, 'userid' => $userid]
        );
        $userid = $params['userid'];
        $group = $params['group'];
        $itemkeys = $params['itemkeys'];

        $keys = [];
        foreach ($itemkeys as $itemkey) {
            $keys[] = $itemkey['key'];
        }

        return helper::reorder_items_in_group($userid, group::get($group), $keys);
    }
    public static function reorder_items_in_group_parameters() {
        return new external_function_parameters([
            'group' => new external_value(PARAM_ALPHANUM, 'The group key'),
            'itemkeys' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_ALPHANUMEXT, 'The item key')
                ])
            ),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function reorder_items_in_group_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Rename an item in the users quick access menu.
     *
     * @param string $key
     * @param string $label
     * @param int|null $userid
     * @return bool
     */
    public static function rename_item(string $key, string $label, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::rename_item_parameters(),
            ['key' => $key, 'label' => $label, 'userid' => $userid]
        );
        $userid = $params['userid'];
        $key = $params['key'];
        $label = $params['label'];

        if (empty($label)) {
            return false;
        }

        return helper::rename_item($userid, $key, $label);
    }
    public static function rename_item_parameters() {
        return new external_function_parameters([
            'key' => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'label' => new external_value(PARAM_TEXT, 'The label for the item'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function rename_item_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Add an item to the users quick access menu.
     * @param string $key
     * @param string $group
     * @param int|null $userid
     * @return array
     */
    public static function add_item(string $key, string $group, $userid = null): array {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::add_item_parameters(),
            ['key' => $key, 'group' => $group, 'userid' => $userid]
        );

        $userid = $params['userid'];
        $key = $params['key'];
        $group = $params['group'];

        if (!in_array($group, group::get_group_keys($userid))) {
            throw new \coding_exception("The requested group '{$group}' does not exist");
        }

        if (!helper::add_item($userid, $key, group::get($group))) {
            throw new \coding_exception("The requested item '{$key}' cannot be added to the group '{$group}'");
        }

        return \totara_core\output\quickaccesssettings::get_item_data($key);
    }
    public static function add_item_parameters() {
        return new external_function_parameters([
            'key'    => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'group'  => new external_value(PARAM_ALPHANUM, 'The group to add the item to'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function add_item_returns() {
        return new external_single_structure([
            'key'   => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'page'  => new external_value(PARAM_TEXT, 'The display name for the item page'),
            'label' => new external_value(PARAM_TEXT, 'The label displayed in the menu'),
            'url'   => new external_value(PARAM_URL, 'URL of referred page')
        ], 'The context object for the newly created item');
    }

    /**
     * Removes an item from the user's quick access menu.
     *
     * @param string $key
     * @param int|null $userid
     * @return bool
     */
    public static function remove_item(string $key, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::remove_item_parameters(),
            ['userid' => $userid, 'key' => $key]
        );
        $userid = $params['userid'];
        $key = $params['key'];

        return helper::remove_item($userid, $key);
    }
    public static function remove_item_parameters() {
        return new external_function_parameters([
            'key'    => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function remove_item_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Changes an item from one group to another in the user's quick access menu.
     *
     * @param string $key
     * @param string $newgroup
     * @param int|null $userid
     * @return bool
     */
    public static function change_item_group(string $key, string $newgroup, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::change_item_group_parameters(),
            ['userid' => $userid, 'key' => $key, 'newgroup' => $newgroup]
        );
        $userid = $params['userid'];
        $key = $params['key'];
        $newgroup = $params['newgroup'];

        return helper::change_item_group($userid, $key, $newgroup);
    }
    public static function change_item_group_parameters() {
        return new external_function_parameters([
            'key'      => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'newgroup' => new external_value(PARAM_ALPHANUM, 'The group key of new group for the item'),
            'userid'   => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function change_item_group_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Moves an item before another item in the user's quick access menu.
     *
     * @param string $key
     * @param string $beforekey
     * @param int|null $userid
     * @return bool
     */
    public static function move_item_before(string $key, string $beforekey, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::move_item_before_parameters(),
            ['userid' => $userid, 'key' => $key, 'beforekey' => $beforekey]
        );
        $userid = $params['userid'];
        $key = $params['key'];
        $beforekey = $params['beforekey'];

        return helper::move_item_before($userid, $key, $beforekey);
    }
    public static function move_item_before_parameters() {
        return new external_function_parameters([
            'key'       => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
            'beforekey' => new external_value(PARAM_ALPHANUMEXT, 'The key of the item to move the given item before'),
            'userid'    => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function move_item_before_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Returns all of the items that can be added to the user's quick access menu.
     *
     * @param int|null $userid
     * @return array
     */
    public static function get_addable_items($userid = null): array {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::get_addable_items_parameters(),
            ['userid' => $userid]
        );
        $userid = $params['userid'];

        $items = helper::get_addable_items($userid);
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'key'    => $item->get_key(),
                'label'  => $item->get_label(),
                'weight' => $item->get_weight()
            ];
        }

        return $data;
    }
    public static function get_addable_items_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function get_addable_items_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'key'    => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
                'label'  => new external_value(PARAM_TEXT, 'The item label'),
                'weight' => new external_value(PARAM_INT, 'The item weight'),
            ])
        );
    }

    /**
     * Adds a group to the user's quick access menu.
     *
     * @param string|null $groupname
     * @param int|null $userid
     * @return array
     */
    public static function add_group(string $groupname = null, $userid = null): array {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::add_group_parameters(),
            ['userid' => $userid, 'groupname' => $groupname]
        );

        $userid = $params['userid'];
        $groupname = $params['groupname'];

        // Let's create new group as untitled and rename it later.
        if (empty($groupname)) {
            $groupname = get_string('quickaccessmenu:untitledgroup', 'totara_core');
        }

        $result = helper::add_group($userid, $groupname);
        return \totara_core\output\quickaccesssettings::get_group_data($result->get_key());
    }
    public static function add_group_parameters() {
        return new external_function_parameters([
            'groupname' => new external_value(PARAM_TEXT, 'The group to add to the menu, if not provided a default name will be used'),
            'userid'    => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function add_group_returns() {
        //We need to set up a recursive description for the tree selector before adding it to the final description
        $tree_node_data = new external_single_structure(
            [
                'key'   => new external_value(PARAM_TEXT, 'The node key'),
                'name'  => new external_value(PARAM_TEXT, 'The display name for the node'),
                'active' => new external_value(PARAM_BOOL, 'Whether the node is active'),
                'default' => new external_value(PARAM_BOOL, 'Whether this is the default node'),
                'has_children' => new external_value(PARAM_BOOL, 'Whether the node has any children'),
            ],
            ''
        );
        $tree_node = new external_multiple_structure($tree_node_data, 'A node for the admin navigation tree', VALUE_OPTIONAL);
        $tree_node_data->keys['children'] = $tree_node; //set recursive property

        return new external_single_structure(
            [
                'key'   => new external_value(PARAM_ALPHANUMEXT, 'The group key'),
                'title'  => new external_value(PARAM_TEXT, 'The display name for the group'),
                'has_items'  => new external_value(PARAM_BOOL, 'Whether the group has any items'),
                'item_count' => new external_value(PARAM_INT, 'Number of items in the group'),
                'items' => new external_multiple_structure(new external_single_structure(
                    [
                        'key'   => new external_value(PARAM_ALPHANUMEXT, 'The item key'),
                        'page'  => new external_value(PARAM_TEXT, 'The display name for the item page'),
                        'label' => new external_value(PARAM_TEXT, 'The label displayed in the menu'),
                        'url'   => new external_value(PARAM_URL, 'URL of referred page')
                    ],
                    'The context object for the newly created item'),
                    ''
                ),
                'tree_selector'   => new external_single_structure(
                    [
                        'key'   => new external_value(PARAM_TEXT, 'The item key'),
                        'title' => new external_value(PARAM_TEXT, 'The display name for the group'),
                        'title_hidden' => new external_value(PARAM_BOOL, 'Whether the group has any items'),
                        'call_to_action' => new external_value(PARAM_TEXT, 'The display name for the group', VALUE_OPTIONAL),
                        'options' => $tree_node,
                    ],
                    'The context object for the tree selector for new items'
                ),
            ],
            'The context object for the newly created item'
        );
    }

    /**
     * Renames a group in the user's quick access menu.
     *
     * @param string $groupkey
     * @param string $groupname
     * @param int|null $userid
     * @return bool
     */
    public static function rename_group(string $groupkey, string $groupname, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::rename_group_parameters(),
            ['userid' => $userid, 'groupkey' => $groupkey, 'groupname' => $groupname]
        );
        $userid = $params['userid'];
        $groupkey = $params['groupkey'];
        $groupname = $params['groupname'];

        if (!in_array($groupkey, group::get_group_keys($userid))) {
            return false;
        }

        return helper::rename_group($userid, $groupkey, $groupname);
    }
    public static function rename_group_parameters() {
        return new external_function_parameters([
            'groupkey'  => new external_value(PARAM_ALPHANUM, 'The group key'),
            'groupname' => new external_value(PARAM_TEXT, 'The label for the group'),
            'userid'    => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function rename_group_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Removes a group from the user's quick access menu.
     *
     * @param string $groupkey
     * @param int|null $userid
     * @return bool
     */
    public static function remove_group(string $groupkey, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::remove_group_parameters(),
            ['groupkey' => $groupkey, 'userid' => $userid]
        );
        $userid = $params['userid'];
        $groupkey = $params['groupkey'];

        if (!in_array($groupkey, group::get_group_keys($userid))) {
            return false;
        }

        return helper::remove_group($userid, $groupkey);
    }
    public static function remove_group_parameters() {
        return new external_function_parameters([
            'groupkey'  => new external_value(PARAM_ALPHANUM, 'The group key'),
            'userid'    => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function remove_group_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Moves the given group before another group in the user's quick access menu.
     * @param string $key
     * @param string $beforekey
     * @param int|null $userid
     * @return bool
     */
    public static function move_group_before(string $key, string $beforekey, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::move_group_before_parameters(),
            ['userid' => $userid, 'key' => $key, 'beforekey' => $beforekey]
        );
        $userid = $params['userid'];
        $key = $params['key'];
        $beforekey = $params['beforekey'];

        return helper::move_group_before($userid, $key, $beforekey);
    }
    public static function move_group_before_parameters() {
        return new external_function_parameters([
            'key'       => new external_value(PARAM_ALPHANUM, 'The group key'),
            'beforekey' => new external_value(PARAM_ALPHANUM, 'The key of the group to move the given group before'),
            'userid'    => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function move_group_before_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }

    /**
     * Reorders the groups in the user's quick access menu.
     *
     * @param array $itemkeys
     * @param int|null $userid
     * @return bool
     */
    public static function reorder_groups(array $itemkeys, $userid = null): bool {
        $userid = self::normalise_userid_parameter($userid);
        self::ensure_user_can_customise_menu($userid);

        $params = self::validate_parameters(
            self::reorder_groups_parameters(),
            ['userid' => $userid, 'itemkeys' => $itemkeys]
        );
        $userid = $params['userid'];
        $itemkeys = $params['itemkeys'];

        $keys = [];
        foreach ($itemkeys as $itemkey) {
            $keys[] = $itemkey['key'];
        }

        return helper::reorder_groups($userid, $keys);
    }
    public static function reorder_groups_parameters() {
        return new external_function_parameters([
            'itemkeys' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_ALPHANUM, 'The group key')
                ])
            ),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }
    public static function reorder_groups_returns() {
        return new external_value(PARAM_BOOL, 'Success or failure');
    }
}
