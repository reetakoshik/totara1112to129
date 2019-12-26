<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide a platform-independent method to white-list file paths.
 * - On Windows, DIRECTORY_SEPARATOR is '\' even though it can understand both '/' and '\'
 * - On Unix and Unix-compatible systems, DIRECTORY_SEPARATOR is '/' and they do *not* understand '\' at all
 */
final class path_whitelist {
    /**
     * An array of file path strings that always use a platform-native directory separator.
     * @var string[]
     */
    private $whitelist = array();

    /**
     * @var integer
     */
    private $lastkeyid = 0;

    /**
     * Constructor.
     *
     * @param string|string[] $paths file path(s) to be added to the white list.
     */
    public function __construct($paths) {
        $this->add($paths);
    }

    /**
     * Return true if the whitelist is empty.
     *
     * @return boolean
     */
    public function is_empty() {
        return empty($this->whitelist);
    }

    /**
     * Join white-listed items with a string.
     *
     * @param string $glue the delimiter
     * @return string
     */
    public function join($glue = '') {
        return implode($glue, array_values($this->whitelist));
    }

    /**
     * Look up a file path.
     *
     * @param string $path a file path to search
     * @return integer|false - an **opaque** whitelist key if the file path is whitelisted
     *                       - false if the file path is not whitelisted
     */
    public function search($path) {
        $path = self::normalise_path($path);
        $keys = array_keys($this->whitelist, $path, true);
        return reset($keys);
    }

    /**
     * Add path string(s) to the whitelist.
     *
     * @param string|string[] $paths file path(s) to be added to the white list.
     * @return void
     */
    public function add($paths) {
        if (empty($paths)) {
            return;
        }
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        foreach ($paths as $path) {
            $path = self::normalise_path($path);
            if ($this->search($path) === false) {
                ++$this->lastkeyid;
                $this->whitelist[$this->lastkeyid] = $path;
            }
        }
    }

    /**
     * Remove the item from the whitelist.
     *
     * @param integer|false $key the whitelist key returned by search()
     * @return void
     */
    public function remove($key) {
        if ($key !== false) {
            unset($this->whitelist[$key]);
        }
    }

    /**
     * Translate a file path string so it always uses a platform-native directory separator.
     * On Windows, "foo/bar/qux.php" is *normalised* as "foo\bar\qux.php".
     *
     * @param string $path the file path
     * @return string
     */
    private static function normalise_path($path) {
        if (DIRECTORY_SEPARATOR === '/') {
            // Nothing to do.
            return $path;
        }
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
