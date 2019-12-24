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
 * @package tabexport_excel
 */

namespace tabexport_excel;

use \totara_core\tabexport_source;
use \totara_core\tabexport_writer;

/**
 * Export data in Excel 2007 format.
 *
 * @package tabexport_excel
 */
class writer extends tabexport_writer {
    /**
     * Constructor.
     *
     * @param tabexport_source $source
     */
    public function __construct(tabexport_source $source) {
        $source->set_format('excel');
        parent::__construct($source);

        // Increasing the execution time and available memory.
        \core_php_time_limit::raise(60 * 60 * 2);
        raise_memory_limit(MEMORY_HUGE);
    }

    /**
     * Add all data to the workbook.
     *
     * @param \MoodleExcelWorkbook $workbook
     */
    protected function add_all_data(\MoodleExcelWorkbook $workbook) {
        $worksheet = array();

        $worksheet[0] = $workbook->add_worksheet('');
        $row = 0;
        $col = 0;

        $customheader = $this->source->get_custom_header();
        if ($customheader === null) {
            $extras = $this->source->get_extra_information();
            if ($extras) {
                foreach ($extras as $extra) {
                    $worksheet[0]->write($row, 0, $extra);
                    $row++;
                }
            }
        } else {
            foreach ((array)$customheader as $extra) {
                $i = 0;
                foreach ((array)$extra as $cell) {
                    $worksheet[0]->write($row, $i, $cell);
                    $i++;
                }
                $row++;
            }
        }

        // Leave an empty row between any initial info and the header row.
        if ($row != 0) {
            $row++;
        }

        foreach ($this->source->get_headings() as $heading) {
            $worksheet[0]->write($row, $col, $heading);
            $col++;
        }
        $row++;

        foreach ($this->source as $record_data) {
            $col = 0;
            foreach ($record_data as $value) {
                if (is_array($value)) {
                    if (method_exists($worksheet[0], 'write_' . $value[0])) {
                        $worksheet[0]->{'write_' . $value[0]}($row, $col++, $value[1], $value[2]);
                    } else {
                        $worksheet[0]->write($row, $col++, $value[1]);
                    }
                } else {
                    $worksheet[0]->write($row, $col++, $value);
                }
            }
            $row++;
        }

        $this->source->close();
    }

    /**
     * Send the file to browser.
     *
     * @param string $filename without extension
     * @return void serves the file and exits.
     */
    public function send_file($filename) {
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");

        $workbook = new \MoodleExcelWorkbook($filename . '.' . self::get_file_extension());
        $this->add_all_data($workbook);
        $workbook->close();
        die;
    }

    /**
     * Save to file.
     *
     * @param string $file full file path
     * @return bool success
     */
    public function save_file($file) {
        global $CFG;
        require_once("$CFG->libdir/excellib.class.php");

        @unlink($file);

        $workbook = new \MoodleExcelWorkbook($file, 'Excel2007', true);
        $this->add_all_data($workbook);
        $workbook->close();

        @chmod($file, (fileperms(dirname($file)) & 0666));
        return file_exists($file);
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public static function get_file_extension() {
        return 'xlsx';
    }
}
