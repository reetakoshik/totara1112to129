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

/**
 * Quick access menu factory class.
 */
final class factory {

    /**
     * The userid this factory belongs to.
     *
     * @var int
     */
    private $userid;

    /**
     * Singleton instance.
     *
     * @var factory
     */
    private static $instance;

    /**
     * Constructs a new factory instance
     *
     * @param int $userid
     * @param bool|null $reload If null then the factory will not be reloaded, unless you are running unit tests.
     * @return factory
     */
    public static function instance(int $userid, ?bool $reload = null): factory {
        global $USER;
        if ($userid != $USER->id) {
            // Explanation: This API facilitates tracking the user this menu is generated for.
            // HOWEVER the admin code in Totara presently can only be generated for the current user.
            // as such you can pass the any user here, but the admin tree will always reflect the current user.
            throw new \coding_exception('Quick access menu can only be used for the current user presently.');
        }
        if ($reload === null) {
            $reload = (defined('PHPUNIT_TEST') && PHPUNIT_TEST);
        }
        if (self::$instance === null || $reload || self::$instance->get_userid() !== $userid) {
            self::$instance = new self($userid);
        }
        return self::$instance;
    }

    /**
     * Returns true if the current user can have a quickaccessmenu.
     *
     * @return bool
     */
    public static function can_current_user_have_quickaccessmenu() {
        global $USER;
        return (isloggedin() && !isguestuser($USER) && !is_mnet_remote_user($USER));
    }

    /**
     * Factory constructor.
     *
     * @param int $userid
     */
    private function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Returns the userid.
     *
     * @return int
     */
    public function get_userid(): int {
        return $this->userid;
    }

    /**
     * Merges the items in the second menu with the items in the first.
     *
     * @param menu $primary
     * @param menu $secondary
     * @param bool $addmissing If set to true any items in the second menu that are not present in the primary
     *     will be added to the primary.
     */
    private static function merge(?menu $primary, ?menu $secondary, bool $addmissing = false): void {

        foreach ($primary->get_all_items() as $primaryitem) {
            $key = $primaryitem->get_key();
            $secondaryitem = $secondary->locate($key);
            if ($secondaryitem) {
                $newitem = item::merge($primaryitem, $secondaryitem);
                $primary->replace_item($newitem);
            }
        }

        if ($addmissing) {
            foreach ($secondary->get_all_items() as $secondaryitem) {
                $key = $secondaryitem->get_key();
                $primaryitem = $primary->locate($key);
                if ($primaryitem === null) {
                    $primary->add_item($secondaryitem);
                }
            }
        }

    }

    /**
     * Returns a menu that the user can see.
     *
     * @return menu
     */
    public function get_menu(): menu {
        $menu = $this->get_admin_menu();
        self::merge($menu, $this->get_default_menu());
        self::merge($menu, $this->get_user_preference_menu());
        return $menu;
    }

    /**
     * Returns the user preference menu.
     *
     * Please note that this is not a complete menu.
     * If you want the complete menu then call {@link get_menu()}
     *
     * @return menu\user_preference
     */
    public function get_user_preference_menu(): menu\user_preference {
        return menu\user_preference::get($this);
    }

    /**
     * Returns the admin menu.
     *
     * Please note that this is not a complete menu.
     * If you want the complete menu then call {@link get_menu()}
     *
     * @return menu\admin
     */
    private function get_admin_menu(): menu\admin {
        return menu\admin::get($this);
    }

    /**
     * Returns the default menu
     *
     * Please note that this is not a complete menu.
     * If you want the complete menu then call {@link get_menu()}
     *
     * @return menu\system_default
     */
    private function get_default_menu(): menu\system_default {
        return menu\system_default::get($this);
    }

    /**
     * Get all items that can be added to the menu.
     *
     * @return item[]
     */
    public function get_possible_items(): array {
        $adminmenu = $this->get_admin_menu();
        return $adminmenu->get_all_items();
    }

    /**
     * Returns the item with the given key, regardless of anything other than it being possible to add.
     *
     * @param string $key
     * @return item|null
     */
    public function get_possible_item(string $key) {
        $adminmenu = $this->get_admin_menu();
        $item = $adminmenu->locate($key);
        return $item;
    }

}
