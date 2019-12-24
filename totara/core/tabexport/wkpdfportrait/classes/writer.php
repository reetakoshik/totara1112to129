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
 * @package tabexport_wkpdfportrait
 */

namespace tabexport_wkpdfportrait;

use \totara_core\tabexport_source;
use \totara_core\tabexport_writer;

/**
 * Export data in PDF format.
 *
 * @package tabexport_wkpdfportrait
 */
class writer extends tabexport_writer {
    /** @var bool is this portrait or landscape */
    protected $portrait = true;
    /** @var  string  */
    protected $component = 'tabexport_wkpdfportrait';

    /**
     * Constructor.
     *
     * @param tabexport_source $source
     */
    public function __construct(tabexport_source $source) {
        $source->set_format('pdf');
        parent::__construct($source);

        // Increasing the execution time and available memory.
        \core_php_time_limit::raise(60 * 60 * 2);
        raise_memory_limit(MEMORY_HUGE);
    }

    /**
     * Create pdf object.
     *
     * @return string path to pdf file
     */
    protected function create_pdf() {
        global $CFG;

        $tempdir = make_request_directory(true); // Includes the main html file and images.
        $htmlfile = $tempdir . '/output.html';
        $pdffile = $tempdir . '/output.pdf';

        if (!static::is_ready()) {
            // We cannot continue, send at least something!
            copy("$CFG->dirroot/totara/core/tabexport/wkpdfportrait/failed.bin", $pdffile);
            @chmod($pdffile, (fileperms(dirname($pdffile)) & 0666));
            return $pdffile;
        }

        // Create table.
        $tablehtml = '';
        $tablehtml .= '<table border="1" cellpadding="2" cellspacing="0" width="100%">
                        <thead>
                            <tr style="background-color: #CCC;">';
        foreach ($this->source->get_headings() as $heading) {
            $tablehtml .= '<th>' . s($heading) . '</th>';
        }
        $tablehtml .= '</tr></thead><tbody>';
        $count = 0;
        foreach ($this->source as $record_data) {
            $count++;
            $tablehtml .= '<tr style="page-break-inside: avoid;">'; // Workaround for https://github.com/wkhtmltopdf/wkhtmltopdf/issues/1524
            foreach($record_data as $value) {
                $tablehtml .= '<td>' . str_replace("\n", '<br />', s($value)) . '</td>';
            }
            $tablehtml .= '</tr>';
        }
        $tablehtml .= '</tbody></table>';
        $this->source->close();


        $language = current_language();
        $dir = right_to_left() ? 'rtl' : 'ltr';

        $html = "<!DOCTYPE html>
<html dir=\"$dir\" lang=\"$language\">
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
</head>
<body>";

        $fullname = $this->source->get_fullname();

        $customheader = $this->source->get_custom_header();
        if ($customheader === null) {
            $html .= '<h1>' . $fullname . '</h1>';

            $resultstr = $count == 1 ? 'record' : 'records';
            $recordscount = get_string('x' . $resultstr, 'totara_reportbuilder', $count);
            $html .= '<h2>' . $recordscount . '</h2>';

            $extras = $this->source->get_extra_information();
            if ($extras) {
                foreach ($extras as $extra) {
                    $html .= '<p>' . $extra . '</p>';
                }
            }
        } else {
            foreach ((array)$customheader as $extra) {
                foreach ((array)$extra as $cell) {
                    $html .= $cell;
                }
            }
        }

        $svgdata = $this->source->get_svg_graph(1200, 400);
        if ($svgdata) {
            $svgfile = $tempdir . '/graph.svg';
            file_put_contents($svgfile, $svgdata);
            @chmod($svgfile, (fileperms(dirname($svgfile)) & 0666));
            $html .= '<div><img src="'.s($svgfile).'" width="1100" height="400" /></div>';
        }

        $html .= $tablehtml;
        $html .= "</body></html>";

        file_put_contents($htmlfile, $html);
        @chmod($htmlfile, (fileperms(dirname($htmlfile)) & 0666));

        // Release memory.
        unset($html);
        unset($svgdata);

        // Create the pdf file from html.
        $command = new \core\command\executable($CFG->pathtowkhtmltopdf);
        $command->add_switch('--footer-line')->add_argument('--footer-right','[page]/[frompage]','/^[a-z\[\]\/]+$/');

        if (\core\command\executable::can_use_pcntl()) {
            // This is user input and a wide range of characters are also possible,
            // so it's only safe to add when pcntl will be used.
            $command->add_argument('--title', $fullname, PARAM_TEXT);
        }

        $orientation = $this->portrait ? 'Portrait' : 'Landscape';
        $command->add_argument('-O', $orientation);
        $command->add_switch('--disable-local-file-access');
        $command->add_argument('--allow', $tempdir, \core\command\argument::PARAM_FULLFILEPATH);
        $command->add_value($htmlfile, \core\command\argument::PARAM_FULLFILEPATH);
        $command->add_value($pdffile, \core\command\argument::PARAM_FULLFILEPATH);

        $command->execute();

        @chmod($pdffile, (fileperms(dirname($pdffile)) & 0666));
        return $pdffile;
    }

    /**
     * Send the file to browser.
     *
     * @param string $filename without extension
     * @return void serves the file and exits.
     */
    public function send_file($filename) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $filename = $filename . '.' . self::get_file_extension();
        $pdffile = $this->create_pdf();
        send_temp_file($pdffile, $filename);
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
        $pdffile = $this->create_pdf();
        copy($pdffile, $file);
        @chmod($file, (fileperms(dirname($file)) & 0666));
        return file_exists($file);
    }

    /**
     * Returns the file extension.
     *
     * @return string
     */
    public static function get_file_extension() {
        return 'pdf';
    }

    /**
     * Is this plugin fully configured and ready to use?
     * @return bool
     */
    public static function is_ready() {
        global $CFG;
        if (empty($CFG->pathtowkhtmltopdf) or !file_exists($CFG->pathtowkhtmltopdf) or !is_executable($CFG->pathtowkhtmltopdf)) {
            return false;
        }
        return true;
    }
}
