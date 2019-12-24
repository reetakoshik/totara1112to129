<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2013 onwards Totara Learning Solutions LTD
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\hook;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook manager class.
 *
 * New watchers are added in any plugin via db/hooks.php file, for example:
 * <code>
 * $watchers = [
 *   'hookname' => 'mod_somemodule\hook\samplehook',
 *   'callback' => 'mod_thisplugin\sample_hook_watcher',
 *   'priority' => 100,
 * ];
 * </code>
 *
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */
abstract class manager {

    /**
     * An array of all watchers.
     *
     * This is multidimensional array with the following structure:
     *
     * array(
     *    classname => array(
     *        watcher_object,
     *        ...,
     *    )
     * )
     *
     * @var array[] cache of all watchers
     */
    protected static $allwatchers = null;

    /**
     * PHPUNIT directive
     *
     * @var bool should we reload watchers after the test?
     */
    protected static $reloadaftertest = false;

    /**
     * Execute all hook watchers.
     *
     * Please note that all exceptions thrown by hooks are captured and discarded.
     * A debugging message is printed in this case.
     *
     * @param base $hook
     */
    public static function execute(base $hook) {
        global $CFG;

        if (during_initial_install()) {
            return;
        }
        self::init_all_watchers();

        $hookname = get_class($hook);
        if (!isset(self::$allwatchers[$hookname])) {
            return;
        }

        foreach (self::$allwatchers[$hookname] as $watcher) {
            if (isset($watcher->includefile) and file_exists($watcher->includefile)) {
                include_once($watcher->includefile);
            }
            try {
                $result = call_user_func($watcher->callback, $hook);
                if ($result === false and !is_callable($watcher->callback)) {
                    $callback = var_export($watcher->callback, true);
                    debugging("Cannot execute hook watcher '$callback'", DEBUG_DEVELOPER);
                }
            } catch (\Exception $e) {
                // Watchers are executed before installation and upgrade, this may throw errors.
                if (empty($CFG->upgraderunning)) {
                    // Ignore errors during upgrade, otherwise warn developers.
                    $callback = var_export($watcher->callback, true);
                    debugging("Exception encountered in hook watcher '$callback': " .
                        $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
                }
            } catch (\Throwable $e) {
                // Watchers are executed before installation and upgrade, this may throw errors.
                if (empty($CFG->upgraderunning)) {
                    // Ignore errors during upgrade, otherwise warn developers.
                    $callback = var_export($watcher->callback, true);
                    debugging("Error encountered in hook watcher '$callback': " .
                        $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
                }
            }
        }

        // Note: there is no protection against infinite recursion, sorry.
    }

    /**
     * Initialise the list of watchers.
     */
    protected static function init_all_watchers() {
        global $CFG;

        if (is_array(self::$allwatchers)) {
            return;
        }

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache = \cache::make('totara_core', 'hookwatchers');
            $cached = $cache->get('all');
            $dirroot = $cache->get('dirroot');
            if ($dirroot === $CFG->dirroot and is_array($cached)) {
                self::$allwatchers = $cached;
                return;
            }
        }

        self::$allwatchers = array();

        $plugintypes = \core_component::get_plugin_types();
        $systemdone = false;
        foreach ($plugintypes as $plugintype => $ignored) {
            $plugins = \core_component::get_plugin_list($plugintype);
            if (!$systemdone) {
                $plugins[] = "$CFG->dirroot/lib";
                $systemdone = true;
            }

            foreach ($plugins as $fulldir) {
                if (!file_exists("$fulldir/db/hooks.php")) {
                    continue;
                }
                $watchers = null;
                include("$fulldir/db/hooks.php");
                if (!is_array($watchers)) {
                    continue;
                }
                self::add_watchers($watchers, "$fulldir/db/hooks.php");
            }
        }

        self::order_all_watchers();

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache->set('all', self::$allwatchers);
            $cache->set('dirroot', $CFG->dirroot);
        }
    }

    /**
     * Add watchers.
     *
     * @param array[] $watchers structure defined in db/hooks.php
     * @param string $file file name and relative path, used for debugging only
     */
    protected static function add_watchers(array $watchers, $file) {
        global $CFG;

        foreach ($watchers as $watcher) {
            if (empty($watcher['hookname']) or !is_string($watcher['hookname'])) {
                debugging("Invalid 'hookname' detected in $file watcher definition", DEBUG_DEVELOPER);
                continue;
            }
            if (strpos($watcher['hookname'], '\\') === 0) {
                // Normalise the class name.
                $watcher['hookname'] = ltrim($watcher['hookname'], '\\');
            }
            if (empty($watcher['callback'])) {
                debugging("Invalid 'callback' detected in $file watcher definition", DEBUG_DEVELOPER);
                continue;
            }
            $o = new \stdClass();
            $o->callback = $watcher['callback'];
            if (!isset($watcher['priority'])) {
                $o->priority = 100;
            } else {
                $o->priority = (int)$watcher['priority'];
            }
            if (empty($watcher['includefile'])) {
                $o->includefile = null;
            } else {
                if ($CFG->admin !== 'admin' and strpos($watcher['includefile'], '/admin/') === 0) {
                    $watcher['includefile'] = preg_replace('|^/admin/|', '/' . $CFG->admin . '/', $watcher['includefile']);
                }
                $watcher['includefile'] = $CFG->dirroot . '/' . ltrim($watcher['includefile'], '/');
                if (!file_exists($watcher['includefile'])) {
                    debugging("Invalid 'includefile' detected in $file watcher definition", DEBUG_DEVELOPER);
                    continue;
                }
                $o->includefile = $watcher['includefile'];
            }
            self::$allwatchers[$watcher['hookname']][] = $o;
        }
    }

    /**
     * Reorder watchers by priority to allow quick execution of watchers for each hook class.
     */
    protected static function order_all_watchers() {
        foreach (self::$allwatchers as $classname => $watchers) {
            \core_collator::asort_objects_by_property($watchers, 'priority', \core_collator::SORT_NUMERIC);
            self::$allwatchers[$classname] = array_reverse($watchers);
        }
    }

    /**
     * Replace all standard watchers and return the correctly reordered.
     *
     * @private
     *
     * @param array[] $watchers
     * @return array[]
     *
     * @throws \coding_exception Throws a coding_exception if used outside of unit tests.
     */
    public static function phpunit_replace_watchers(array $watchers) {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot override hook watchers outside of phpunit tests!');
        }

        self::phpunit_reset();
        self::$allwatchers = array();
        self::$reloadaftertest = true;

        self::add_watchers($watchers, 'phpunit');
        self::order_all_watchers();

        return self::$allwatchers;
    }

    /**
     * Replace all standard watchers.
     *
     * @private
     *
     * @return array[]
     *
     * @throws \coding_exception Throws a coding_exception  if used outside of unit tests.
     */
    public static function phpunit_get_watchers() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot get hook watchers outside of phpunit tests!');
        }

        self::init_all_watchers();
        return self::$allwatchers;
    }

    /**
     * Reset everything if necessary.
     *
     * @private
     *
     * @throws \coding_exception Throws a coding_exception if used outside of unit tests.
     */
    public static function phpunit_reset() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot reset hook manager outside of phpunit tests!');
        }
        if (!self::$reloadaftertest) {
            self::$allwatchers = null;
        }
        self::$reloadaftertest = false;
    }
}
