<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package tabexport_csv
 */

namespace tabexport_csv;

use \totara_core\tabexport_source;
use \totara_core\tabexport_writer;

/**
 * Export data in CSV format.
 *
 * @package tabexport_csv
 */
class writer extends tabexport_writer {
    /** @var string */
    protected $delimiter;

    /** @var string */
    protected $enclosure;

    /**
     * Constructor.
     *
     * @param tabexport_source $source
     */
    public function __construct(tabexport_source $source) {
        $source->set_format('csv');
        parent::__construct($source);

        // For now there are no settings for these.
        $this->delimiter = ',';
        $this->enclosure = '"';

        // Increasing the execution time, no need for more memory any more.
        \core_php_time_limit::raise(60 * 60 * 2);
    }

    protected function add_row($handle, $row) {
        fputcsv($handle, $row, $this->delimiter, $this->enclosure);
    }

    /**
     * Add all data to export.
     *
     * @param resource $handle
     */
    protected function add_all_data($handle) {
        foreach ($this->source->get_headings() as $heading) {
            $row[] = $heading;
        }
        $this->add_row($handle, $row);

        foreach ($this->source as $row) {
            $this->add_row($handle, $row);
        }

        fclose($handle);
        $this->source->close();
    }

    /**
     * Output file headers to initialise the download of the file.
     * @param string $filename wiithout extension
     */
    protected function send_headers($filename) {
        $filename = $filename . '.' . self::get_file_extension();

        // Always force download here.
        if (is_https()) { // HTTPS sites - watch out for IE! KB812935 and KB316431.
            header('Cache-Control: private, max-age=10, no-transform');
            header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0, no-transform');
            header('Pragma: no-cache');
        }
        header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
        header('X-Content-Type-Options: nosniff');

        if (defined('BEHAT_SITE_RUNNING')) {
            // Behat cannot deal with force-downloaded files,
            // let's open the file in browser instead so that behat may assert the contents.
            header('Content-Type: text/plain');
            header('Content-Disposition: inline; filename="'.$filename.'"');
            return;
        }

        header('Content-Type: application/x-forcedownload');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
    }

    /**
     * Send the file to browser.
     *
     * @param string $filename without extension
     * @return void serves the file and exits.
     */
    public function send_file($filename) {
        // Send errors to log file!
        @ini_set('display_errors', '0');
        @ini_set('log_errors', '1');

        $this->send_headers($filename);

        // Make sure there is no other buffering or compression active.
        disable_output_buffering();
        ob_implicit_flush(false);

        // Buffer the output and compress if possible.
        if (!ob_start("ob_gzhandler", 1)) {
            ob_start(null, 1);
        }

        $handle = fopen('php://output', 'w');
        $this->add_all_data($handle);

        die;
    }

    /**
     * Save to file.
     *
     * @param string $file full file path
     * @return bool success
     */
    public function save_file($file) {
        @unlink($file);
        $handle = fopen($file, "w");
        $this->add_all_data($handle);

        @chmod($file, (fileperms(dirname($file)) & 0666));
        return file_exists($file);
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public static function get_file_extension() {
        return 'csv';
    }
}
