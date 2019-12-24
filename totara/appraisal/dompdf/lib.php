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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara_appraisal
 */

require_once(__DIR__ . '/autoload.inc.php');

/**
 * Dompdf wrapper class for Totara
 */
class totara_dompdf extends Dompdf\Dompdf {
    public function __construct() {
        $tempdir = make_temp_directory('dompdf');
        $cachedir = make_cache_directory('dompdf');

        $options = new Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', $tempdir);
        $options->set('logOutputFile', "tempdir/dompdf.log");
        $options->set('fontCache', $cachedir);
        $options->set('dpi', 125);
        $options->set('isRemoteEnabled', true); // Only pluginfile.php links and main CSS file are processed.
        $options->set('isHtml5ParserEnabled', true);

        parent::__construct($options);
    }

    /**
     * Attempt to fix problematic html, use only if normal rendering fails.
     *
     * @param string $html
     * @return string
     */
    public static function hack_html($html) {
        // Large tables are always problematic with DOMPDF, get rid of them.
        $html = self::replace_tag($html, 'table', 'div');
        $html = self::replace_tag($html, 'tr', 'div');
        $html = self::replace_tag($html, 'td');
        $html = self::replace_tag($html, 'th');
        $html = self::replace_tag($html, 'thead');
        $html = self::replace_tag($html, 'tbody');
        $html = self::replace_tag($html, 'tfoot');
        $html = self::replace_tag($html, 'caption');
        $html = self::replace_tag($html, 'colgroup');

        // Do more cleanup if tidy extension installed.
        if (class_exists('tidy')) {
            $tidy = new tidy();
            $html = $tidy->repairString($html);
        }

        return $html;
    }

    /**
     * Replace HTML element.
     *
     * @param string $html
     * @param string $tagname
     * @param string $replacement
     * @return string
     */
    protected static function replace_tag($html, $tagname, $replacement = '') {
        if ($replacement === '') {
            $html = preg_replace('|<' . $tagname . '[^>]*>|i', '', $html);
            $html = preg_replace('|</' . $tagname. '>|i', '', $html);
        } else {
            $html = preg_replace('|<' . $tagname . '[^>]*>|i', '<' . $replacement . '>', $html);
            $html = preg_replace('|</' . $tagname. '>|i', '</' . $replacement . '>', $html);
        }
        return $html;
    }
}
