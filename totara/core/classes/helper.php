<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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

namespace totara_core;

/**
 * Final helper class for occasionally needed helper functions.
 *
 * This class is autoloaded so it is only included when needed.
 *
 * @package totara_core
 */
final class helper {

    /**
     * Returns a list of files that have the executable flag incorrectly set on them.
     *
     * @param bool $excludefilesingitignore If set to true (default) files ignored in gitignore will also be ignored by this check.
     * @return \SplFileInfo[]
     */
    public static function get_incorrectly_executable_files($excludefilesingitignore = true) {
        global $CFG;

        $ignored_patterns = array(
            '/.git', // This needs to be manually excluded - don't parse the git directory!
            '/vendor/', // We know this is going to contain executables.
            '/filter/tex/mimetex.*', // Mimetex executables need to be excluded.
            '/lib/tcpdf/fonts/*.z', // TCPDF fonts need to be excluded.
            '/filter/algebra/algebra2tex.pl' // The algebra filter executable is excluded.
        );
        if ($excludefilesingitignore) {
            $gitignorefile = $CFG->dirroot . '/.gitignore';
            if (file_exists($gitignorefile)) {
                $contents = file_get_contents($gitignorefile);
                $lines = preg_split("#\r?\n#", $contents);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (strpos($line, '#') === 0 || empty($line)) {
                        continue;
                    }
                    $ignored_patterns[] = $line;
                }
            }
        }

        foreach ($ignored_patterns as &$ignored) {
            $ignored = preg_quote($ignored, '#');
            $ignored = preg_replace('#\\\\\*#', '.*', $ignored);
        }
        $ignored_regex = '#' . join("|", $ignored_patterns) . '#';

        $directory = new \RecursiveDirectoryIterator($CFG->dirroot);
        $iterator = new \RecursiveIteratorIterator($directory);
        $incorrectperms = [];
        $windows = DIRECTORY_SEPARATOR !== '/';
        foreach ($iterator as $info) {
            /** @var \SplFileInfo $info */
            if ($info->isLink()) {
                // This must be some unsupported custom hack,
                // standard distribution is not supposed to have any symlinks.
                continue;
            }
            $name = $info->getFilename();
            $fullpath = $info->getPathname();
            if ($windows) {
                $fullpath = str_replace(DIRECTORY_SEPARATOR, '/', $fullpath);
            }
            $relpath = substr($fullpath, strlen($CFG->dirroot) + 1);
            if ($name === '.' || $name === '..') {
                continue;
            }
            if (preg_match($ignored_regex, '/' . $relpath)) {
                continue;
            }
            if (!$info->isDir() && !$info->isFile()) {
                // This isn't the type of file we are looking for.
                continue;
            }
            if ($info->getPerms() & 0111) {
                $incorrectperms[$relpath] = $info;
            }
        }
        return $incorrectperms;
    }

}