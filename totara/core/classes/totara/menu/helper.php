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
 * @package totara_core
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 */
namespace totara_core\totara\menu;

/**
 * Upgrade and reset related code for Main menu.
 *
 * This class replaces deprecated build class.
 */
final class helper {
    /**
     * Returns the id for 'Unused' category.
     * @return int Unused container record id
     */
    public static function get_unused_container_id() {
        global $DB;

        $unused = $DB->get_record('totara_navigation', array('classname' => '\totara_core\totara\menu\unused'));
        if ($unused) {
            // Make sure nobody hacked this special item.
            if ($unused->sortorder != 1) {
                $DB->set_field('totara_navigation', 'sortorder', 1, array('id' => $unused->id));
            }
            if ($unused->parentid != 0) {
                $DB->set_field('totara_navigation', 'parentid', 0, array('id' => $unused->id));
            }
            if ($unused->custom != 0) {
                $DB->set_field('totara_navigation', 'custom', 0, array('id' => $unused->id));
            }

            return (int)$unused->id;
        }

        $record = new \stdClass();
        $record->parentid = 0;
        $record->title = '';
        $record->customtitle = 0;
        $record->custom = 0;
        $record->classname = '\totara_core\totara\menu\unused';
        $record->sortorder = 1; // Always displayed last in Main menu administration.
        $record->visibility = item::VISIBILITY_HIDE;
        $record->targetattr = '';
        $record->timemodified = time();

        return (int)$DB->insert_record('totara_navigation', $record);
    }

    /**
     * Look up all missing default item and container classes and add them to {totara_navigation} db table.
     *
     * This is intended for upgrades and menu reset only.
     */
    public static function add_default_items() {
        global $DB;

        // Keep this method simple, there is absolutely no need for fancy API calls here!

        $classes = \core_component::get_namespace_classes('totara\menu', 'totara_core\totara\menu\item', null, true);


        // At least in Totara 12 look for incorrectly named classes in old db files.
        $finddeprecatedfiles = function() {
            $fakebuild = new class {
                public $classnames = array();
                public function add(string $classname) {
                    $this->classnames[] = ltrim($classname, '\\');
                }
            };
            $TOTARAMENU = new $fakebuild();
            $plugintypes = \core_component::get_plugin_types();
            foreach ($plugintypes as $plugin => $path) {
                $pluginfiles = \core_component::get_plugin_list_with_file($plugin, 'db/totaramenu.php');
                foreach ($pluginfiles as $name => $file) {
                    debugging("Obsolete file db/totaramenu.php detected in plugin {$plugin}_{$name}, Totara menu classes are now discovered via namespace lookup", DEBUG_DEVELOPER);
                    // This is NOT a library file!
                    require($file);
                }
            }
            return $TOTARAMENU->classnames;
        };
        $deprecatedclasses = $finddeprecatedfiles();
        foreach ($deprecatedclasses as $deprecatedclass) {
            if (in_array($deprecatedclass, $classes)) {
                continue;
            }
            if (!class_exists($deprecatedclass)) {
                // Ignore wrong classes.
                continue;
            }
            debugging("Incorrectly named class '{$deprecatedclass}' detected in db/totaramenu.php, it must be placed in 'totara\menu' namespace in some plugin", DEBUG_DEVELOPER);
            $classes[] = $deprecatedclass;
        }


        /** @var \stdClass[] $items */
        $items = array();

        /** @var string[] $addparents */
        $addparents = array();

        $now = time();

        $trans = $DB->start_delegated_transaction();

        $unusedcontainerid = self::get_unused_container_id();

        foreach ($classes as $class) {
            if ($class === 'totara_core\totara\menu\item' or $class === 'totara_core\totara\menu\container') {
                // This is a custom item or container, there may be multiple instances.
                continue;
            }

            // Prepend backlash to real class name for BC.
            $classname = '\\' . $class;

            if ($record = $DB->get_record('totara_navigation', array('classname' => $classname))) {
                $items[$classname] = $record;
                continue;
            }

            $record = new \stdClass();
            $record->parentid = 0; // Can be reliably set only after all items are added, for now add it to the Top level.
            $record->title = ''; // Storing localised title in database is nonsense.
            $record->customtitle = 0;
            $record->custom = 0;
            $record->classname = $classname;
            $record->sortorder = 0;
            $record->visibility = 0;
            $record->targetattr = '';
            $record->timemodified = $now;
            $record->id = $DB->insert_record('totara_navigation', $record);
            $record = $DB->get_record('totara_navigation', array('id' => $record->id));

            $item = item::create_instance($record);
            if (!$item) {
                // Something is wrong with the default class, ignore it.
                debugging('Invalid default menu item class detected: ' . $classname, DEBUG_DEVELOPER);
                continue;
            }

            $record->visibility = ($item->get_default_visibility() ? item::VISIBILITY_SHOW : item::VISIBILITY_HIDE);

            $record->sortorder = $item->get_default_sortorder();
            if (!$record->sortorder) {
                // Put at the end, make sure it is after all normal default items.
                $maxsortorder = 100 + $DB->get_field('totara_navigation', 'MAX(sortorder)', array());
                if ($maxsortorder < 90000) {
                    $maxsortorder = 99000;
                }
                $record->sortorder = $maxsortorder;
            }

            // Work around incorrect visibility for methods with initial values.
            $rc = new \ReflectionClass($class);
            $rcparent = $rc->getMethod('get_default_parent');
            $rcparent->setAccessible(true);
            $parent = $rcparent->invoke($item);
            if (!$parent or $parent === '\totara_core\totara\menu\unused') {
                // Missing value means item should not be visible, so add it to 'Unused' container.
                $record->parentid = $unusedcontainerid;
            } else if ($parent !== 'root') {
                // Fix the parent later.
                $addparents[$record->id] = $parent;
            }

            // Final update before getting real instance.
            $DB->update_record('totara_navigation', $record);
            $record = $DB->get_record('totara_navigation', array('id' => $record->id));
            $items[$classname] = $record;
        }

        // Now set parents for new items.

        foreach ($addparents as $itemid => $parentclassname) {
            if (isset($items[$parentclassname])) {
                $parentid = $items[$parentclassname]->id;
            } else {
                $parentid = $unusedcontainerid;
                $record = $DB->get_record('totara_navigation', array('id' => $itemid));
                debugging('Invalid parent detected on Totara menu class: ' . $record->classname, DEBUG_DEVELOPER);
            }
            $DB->set_field('totara_navigation', 'parentid', $parentid, array('id' => $itemid));
        }

        $trans->allow_commit();
        totara_menu_reset_all_caches();
    }

    /**
     * Add new custom item or container.
     *
     * This is intended for totara/core/menu/edit.php only.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function add_custom_menu_item(\stdClass $data) {
        global $DB;

        $data = (object)(array)$data; // Do not modify parameters.

        if ($data->type === 'item') {
            $data->classname = '\totara_core\totara\menu\item';
            if (trim($data->url) === '') {
                throw new \coding_exception('Menu item URL is missing');
            }
            if (!isset($data->targetattr) or $data->targetattr !== '_blank') {
                $data->targetattr = '_self';
            }
        } else {
            $data->classname = '\totara_core\totara\menu\container';
            $data->url = '';
            $data->targetattr = '';
        }
        if (trim($data->title) === '') {
            throw new \coding_exception('Menu item title is missing');
        }
        $data->custom = 1;
        $data->customtitle = 1;
        if (!isset($data->parentid)) {
            $data->parentid = self::get_unused_container_id();
        }
        unset($data->type);
        if (!$data->visibility) {
            $data->visibility = item::VISIBILITY_HIDE;
        }
        if ($data->visibility == item::VISIBILITY_CUSTOM) {
            $data->visibilityold = item::VISIBILITY_CUSTOM;
        } else {
            $data->visibilityold = item::VISIBILITY_SHOW;
        }
        if (!isset($data->sortorder)) {
            $data->sortorder = 10 + $DB->get_field('totara_navigation', 'MAX(sortorder)', array('parentid' => $data->parentid));
        }
        $data->timemodified = time();

        $data->id = $DB->insert_record('totara_navigation', $data);
        totara_menu_reset_all_caches();

        $record = $DB->get_record('totara_navigation', array('id' => $data->id), '*', MUST_EXIST);

        $event = \totara_core\event\menuitem_created::create_from_item($record->id);
        $event->add_record_snapshot('totara_navigation', $record);
        $event->trigger();

        return $record;
    }

    /**
     * Update existing item or container.
     *
     * This is intended for totara/core/menu/edit.php only.
     *
     * @param \stdClass $data
     * @return \stdClass
     */
    public static function update_menu_item(\stdClass $data) {
        global $DB;

        $data = (object)(array)$data; // Do not modify parameters.

        unset($data->classname); // Must not be modified.
        unset($data->custom); // Must not be modified.
        unset($data->sortorder); // Cannot be modified here.

        // Some basic anti-tampering measures.
        $oldrecord = $DB->get_record('totara_navigation', array('id' => $data->id), '*', MUST_EXIST);

        if ($oldrecord->classname === '\totara_core\totara\menu\unused') {
            throw new \coding_exception('Built-in Unused container cannot be modified');
        }

        // Make sure customtitle is ok and we do not store localised data in database unnecessarily.
        if ($oldrecord->custom) {
            $data->customtitle = 1;
        } else {
            if ($data->customtitle) {
                if (trim($data->title) === '') {
                    $data->title = '';
                    $data->customtitle = 0;
                }
            } else {
                unset($data->title);
            }
        }

        // Record the last visibility type so that we can use show/hide icons in UI.
        if (isset($data->visibility) and $oldrecord->visibility != $data->visibility) {
            if ($oldrecord->visibility == item::VISIBILITY_CUSTOM) {
                $data->visibilityold = item::VISIBILITY_CUSTOM;
            } else if ($oldrecord->visibility == item::VISIBILITY_SHOW) {
                $data->visibilityold = item::VISIBILITY_SHOW;
            }
        }

        // All looks ok, let's do the update.

        $data->timemodified = time();

        $DB->update_record('totara_navigation', $data);
        totara_menu_reset_all_caches();

        $record = $DB->get_record('totara_navigation', array('id' => $data->id), '*', MUST_EXIST);

        $event = \totara_core\event\menuitem_updated::create_from_item($record->id);
        $event->add_record_snapshot('totara_navigation', $record);
        $event->trigger();

        return $record;
    }

    /**
     * Is this item deletable?
     *
     * Deleting is not possible if children are present or if it is a valid default item.
     *
     * @param int $id
     * @return bool
     */
    public static function is_item_deletable(int $id) {
        global $DB;

        $record = $DB->get_record('totara_navigation', array('id' => $id));
        if (!$record) {
            return false;
        }

        $select = "parentid = :id1 AND id <> :id2"; // Ignore invalid self-links.
        $params = array('id1' => $record->id, 'id2' => $record->id);
        if ($DB->record_exists_select('totara_navigation', $select, $params)) {
            return false;
        }

        if ($record->classname === '\totara_core\totara\menu\unused') {
            return false;
        }

        if ($record->custom) {
            return true;
        }

        $item = item::create_instance($record);
        if (!$item) {
            // All broken items can be deleted manually.
            return true;
        }

        // Default items cannot be deleted.
        return false;
    }

    /**
     * Delete item if possible, fails if item is not deletable.
     *
     * @param int $id
     * @return bool success
     */
    public static function delete_item(int $id) {
        global $DB;

        $record = $DB->get_record('totara_navigation', array('id' => $id));
        if (!$record) {
            return false;
        }

        if (!self::is_item_deletable($id)) {
            return false;
        }

        $trans = $DB->start_delegated_transaction();

        $DB->delete_records('totara_navigation_settings', array('itemid' => $record->id));
        $DB->delete_records('totara_navigation', array('id' => $record->id));
        totara_menu_reset_all_caches();

        $trans->allow_commit();

        $event = \totara_core\event\menuitem_deleted::create_from_item($record->id);
        $event->add_record_snapshot('totara_navigation', $record);
        $event->trigger();

        return true;
    }

    /**
     * Changes the sort order of this item in parent container.
     *
     * @param int $id item id
     * @param bool $up If set to true the category is shifted up one spot, else its moved down.
     * @return bool true on success, false otherwise.
     */
    public static function change_sortorder(int $id, bool $up) {
        global $DB;

        $record = $DB->get_record('totara_navigation', array('id' => $id));
        if (!$record) {
            return false;
        }

        // Get list of items at the same level.
        $select = "parentid = :parentid AND classname <> :unused";
        $params = array('parentid' => $record->parentid, 'unused' => '\totara_core\totara\menu\unused');
        $items = $DB->get_records_select('totara_navigation', $select, $params, 'sortorder ASC, id ASC');

        if (!isset($items[$id])) {
            // Weird.
            return false;
        }
        if (count($items) === 1) {
            // Nothing to do, that was quick!
            return false;
        }

        // Make sure there are no sortorder duplicates.
        $prevsortorder = 1;
        foreach ($items as $item) {
            $item->oldsortorder = $item->sortorder;
            if ($item->sortorder <= $prevsortorder) {
                $item->sortorder = (string)((int)$prevsortorder + 1);
            }
            $prevsortorder = $item->sortorder;
        }

        // Now just swap items depending on the move direction.
        reset($items);
        while (key($items) != $record->id) {
            if (next($items) === false) {
                return false;
            }
        }
        if ($up) {
            $swap = next($items);
        } else {
            $swap = prev($items);
        }
        if ($swap === false) {
            return false;
        }

        $temp = $swap->sortorder;
        $items[$swap->id]->sortorder = $items[$id]->sortorder;
        $items[$id]->sortorder = $temp;

        // Save all changed sortorders to db.

        $trans = $DB->start_delegated_transaction();
        $now = time();

        $changed = false;
        foreach ($items as $item) {
            if ($item->oldsortorder != $item->sortorder) {
                $changed = true;

                $data = new \stdClass();
                $data->id = $item->id;
                $data->sortorder = $item->sortorder;
                $data->timemodified = $now;

                $DB->update_record('totara_navigation', $data);
            }
        }

        $trans->allow_commit();

        if ($changed) {
            totara_menu_reset_all_caches();

            \totara_core\event\menuitem_sortorder::create_from_item($id, $up)->trigger();
        }

        return true;
    }

    /**
     * Changes the visibility of this item or container.
     *
     * @param int $id item id
     * @param bool $visible true means let users see the item, false means always hide it
     * @return bool true on success, false otherwise.
     */
    public static function change_visibility(int $id, bool $visible) {
        global $DB;

        $record = $DB->get_record('totara_navigation', array('id' => $id));
        if (!$record) {
            return false;
        }

        if ($visible) {
            if ($record->visibility != item::VISIBILITY_HIDE) {
                return true;
            }
        } else {
            if ($record->visibility == item::VISIBILITY_HIDE) {
                return true;
            }
        }

        $update = new \stdClass();
        $update->id = $record->id;
        $update->timemodified = time();

        if ($visible) {
            if (isset($record->visibilityold) and $record->visibilityold == item::VISIBILITY_CUSTOM) {
                $update->visibility = item::VISIBILITY_CUSTOM;
            } else {
                $update->visibility = item::VISIBILITY_SHOW;
            }
            $update->visibilityold = $update->visibility;

        } else {
            $update->visibility = item::VISIBILITY_HIDE;
            if ($record->visibility == item::VISIBILITY_CUSTOM) {
                $update->visibilityold = item::VISIBILITY_CUSTOM;
            } else {
                $update->visibilityold = item::VISIBILITY_SHOW;
            }
        }

        $DB->update_record('totara_navigation', $update);
        totara_menu_reset_all_caches();

        \totara_core\event\menuitem_visibility::create_from_item($id, !$visible)->trigger();

        return true;
    }

    /**
     * Resets the menu to the default state as determined by the code.
     *
     * Optionally all custom items and containers may be moved to 'Unused'
     * container to prevent data loss during menu reset.
     *
     * @param bool $backupcustom true means move all custom items to Unused container
     */
    public static function reset_menu($backupcustom) {
        global $DB;

        $trans = $DB->start_delegated_transaction();

        if ($backupcustom) {
            $unusedcontainerid = self::get_unused_container_id();

            $DB->set_field('totara_navigation', 'parentid', $unusedcontainerid, array('custom' => 1));

            $defaults = $DB->get_records('totara_navigation', array('custom' => 0));
            foreach ($defaults as $default) {
                if ($default->id == $unusedcontainerid) {
                    continue;
                }
                $DB->delete_records('totara_navigation_settings', array('id' => $default->id));
                $DB->delete_records('totara_navigation', array('id' => $default->id));
            }

        } else {
            // Delete the menu settings.
            $DB->delete_records('totara_navigation_settings');
            // And the menu table.
            $DB->delete_records('totara_navigation');
        }

        // Then recreate the defaults.
        self::add_default_items();
        totara_menu_reset_all_caches();

        $trans->allow_commit();
    }


    /**
     * Create parent id selection options.
     *
     * This is intended for totara/core/menu/edit.php and
     * \totara_core\form\menu\* classes only.
     *
     * @param int $itemid
     * @param int $maxdepth maximum depth, intended for adding of new items only
     * @return string[]
     */
    public static function create_parentid_form_options(int $itemid, int $maxdepth = 0) {
        $unusedcontainerid = self::get_unused_container_id();

        $lookupcontainers = function(array &$list, $item, $depth, $currentid, $parentstr, $maxdepth) use (&$lookupcontainers, $unusedcontainerid) {
            global $DB;

            if ($item) {
                if ($item->id == $unusedcontainerid) {
                    return;
                }
                $node = \totara_core\totara\menu\item::create_instance($item);
                if (!$node) {
                    return;
                }
                if (!$node->is_container()) {
                    return;
                }
                if ($parentstr) {
                    $list[$item->id] = $parentstr . ' / ' . $node->get_title();
                } else {
                    $list[$item->id] = $node->get_title();
                }
                if ($currentid and $currentid == $item->id) {
                    // This prevents cycles in parent links,
                    //do not offer sub containers of current item.
                    return;
                }
                $parentid = $item->id;
                $parentstr = $list[$item->id];
            } else {
                $list[0] = get_string('top');
                $parentid = 0;
            }

            if ($maxdepth and $depth >= $maxdepth) {
                // We have reached the max depth.
                return;
            }

            if ($depth > 20) {
                // Break out of infinite recursion.
                return;
            }

            $children = $DB->get_records('totara_navigation', array('parentid' => $parentid), 'sortorder ASC, id ASC');

            foreach ($children as $child) {
                $lookupcontainers($list, $child, $depth + 1, $currentid, $parentstr, $maxdepth);
            }
        };

        $options = array();
        $lookupcontainers($options, null, 0, $itemid, '', $maxdepth);

        // Always add 'Unused' as the last item.
        $options[$unusedcontainerid] = get_string('unused', 'totara_core');

        return $options;
    }

    /**
     * Form validation method for custom item URL editing.
     *
     * @param string $url
     * @return string|null null means ok, string is error message
     */
    public static function validate_item_url(string $url) {
        global $CFG;

        if (trim($url) === '') {
            return get_string('required');
        }
        if (\core_text::strlen($url) > 255) {
            return get_string('error:menuitemurltoolong', 'totara_core');
        }
        if (substr($url, 0 , 1) === '/') {
            $url = $CFG->wwwroot . $url;
        }
        $url = item::replace_url_parameter_placeholders($url);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return get_string('error:menuitemurlinvalid', 'totara_core');
        }

        return null;
    }

    /**
     * Returns element id attribute on the Main menu edit page.
     *
     * This is intended mainly for anchors in return links.
     *
     * @param int $id
     * @return string
     */
    public static function get_admin_edit_rowid(int $id) {
        return 'totaramenuedititem' . $id;
    }

    /**
     * Returns element id attributes on the Main menu edit page.
     *
     * @param int $id
     * @return \moodle_url
     */
    public static function get_admin_edit_return_url(int $id) {
        global $DB;
        $url = new \moodle_url('/totara/core/menu/index.php');
        if ($id) {
            $parentid = $DB->get_field('totara_navigation', 'parentid', array('id' => $id));
            if ($parentid) {
                $url->set_anchor(self::get_admin_edit_rowid($parentid));
            }
        }
        return $url;
    }

    /**
     * Returns current Totara menu cache revision number.
     *
     * @return int
     */
    public static function get_cache_revision() {
        global $CFG;
        if (!empty($CFG->totaramenurev)) {
            return (int)$CFG->totaramenurev;
        }
        return self::bump_cache_revision();
    }

    /**
     * Increments Totara menu cache revision number.
     *
     * @return int new cache revision number
     */
    public static function bump_cache_revision() {
        global $CFG;

        $next = time();
        if (isset($CFG->totaramenurev) and $next <= $CFG->totaramenurev and $CFG->totaramenurev - $next < 60*60) {
            // This resolves problems when reset is requested repeatedly within 1s,
            // the < 1h condition prevents accidental switching to future dates
            // because we might not recover from it.
            $next = $CFG->totaramenurev + 1;
        }

        set_config('totaramenurev', $next);

        return (int)$next;
    }

    /**
     * Update all settings at once.
     *
     * The $settings array is expected to be an array of arrays.
     * Each sub array should associative with three keys: type, name, value.
     *
     * @param int $id item id
     * @param array[] $settings
     */
    public static function update_custom_visibility_settings(int $id, array $settings) {
        global $DB;

        // Build an array of existing settings so that we don't need to fetch each one individually as we look
        // at the setting data.
        $now = time();

        $existing = array();
        foreach ($DB->get_records('totara_navigation_settings', array('itemid' => $id)) as $setting) {
            if (!isset($existing[$setting->type])) {
                $existing[$setting->type] = array();
            }
            $existing[$setting->type][$setting->name] = $setting;
        }

        // Look at each setting and either update it, insert it, or skip it (if it has not changed).
        foreach ($settings as $setting) {
            $type = $setting['type'];
            $name = $setting['name'];
            $value = $setting['value'];
            if (isset($existing[$type][$name])) {
                // Its an existing setting, lets check if its actually changed.
                if ($value === $existing[$type][$name]->value) {
                    // Nothing to do here, the setting has not changed.
                    continue;
                }
                // The value has changed update the setting.
                $record = new \stdClass;
                $record->id = $existing[$type][$name]->id;
                $record->timemodified = $now;
                $record->value = $value;
                $DB->update_record('totara_navigation_settings', $record);
            } else {
                // Its the first time this setting has been set.
                $record = new \stdClass;
                $record->timemodified = $now;
                $record->itemid = $id;
                $record->type = $type;
                $record->name = $name;
                $record->value = $value;
                $DB->insert_record('totara_navigation_settings', $record);
            }
        }
    }
}
