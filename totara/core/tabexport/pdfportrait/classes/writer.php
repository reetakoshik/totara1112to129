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
 * @package tabexport_pdfportrait
 */

namespace tabexport_pdfportrait;

use \totara_core\tabexport_source;
use \totara_core\tabexport_writer;

/**
 * Export data in PDF format.
 *
 * @package tabexport_pdfportrait
 */
class writer extends tabexport_writer {
    /** @var bool is this portrait or landscape */
    protected $portrait = true;

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
     * Returns default or configured PDF font.
     *
     * @param string $language Language that is being used
     * @return string The appropriate font based on the language
     */
    protected function get_font($language) {
        $setting = $this->get_config('pdffont');
        if (empty($setting)) {
            // If the setting is empty we will select an appropriate default font.
            if (in_array($language, array('zh_cn', 'ja'))) {
                return 'droidsansfallback';
            } else if ($language === 'th') {
                return 'cordiaupc';
            }
            return 'freeserif';
        }
        return $setting;
    }

    /**
     * Create pdf object.
     *
     * @return \PDF
     */
    protected function create_pdf() {
        global $CFG;
        require_once $CFG->libdir . '/pdflib.php';

        // Table.
        $html = '';
        $colspan = 0;
        $html .= '<table border="1" cellpadding="2" cellspacing="0">
                        <thead>
                            <tr style="background-color: #CCC;">';
        foreach ($this->source->get_headings() as $heading) {
            $html .= '<th>' . s($heading) . '</th>';
            $colspan++;
        }
        $html .= '</tr></thead><tbody>';
        $count = 0;
        foreach ($this->source as $record_data) {
            $count++;
            $html .= '<tr>';
            foreach($record_data as $value) {
                $html .= '<td>' . str_replace("\n", '<br />', s($value)) . '</td>';
            }
            $html .= '</tr>';

            // Check memory limit.
            $mramuse = ceil(((memory_get_usage(true)/1024)/1024));
            if (1024 <= $mramuse and !PHPUNIT_TEST) {
                if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                    $html .= '<tr><td colspan="'.$colspan.'">';
                    $html .= get_string('exportpdf_mramlimitexceeded', 'totara_reportbuilder', 1024);
                    $html .= '</td></tr>';
                    break;
                } else {
                    // Notice message.
                    print_error('exportpdf_mramlimitexceeded', 'totara_reportbuilder', '', 1024);
                }
            }
        }
        $html .= '</tbody></table>';
        $this->source->close();

        $fullname = $this->source->get_fullname();

        // Layout options.
        if ($this->portrait) {
            $pdf = new \PDF('P', 'mm', 'A4', true, 'UTF-8');
        } else {
            $pdf = new \PDF('L', 'mm', 'A4', true, 'UTF-8');
        }

        // Check if language is RTL.
        $align = 'L';
        if (right_to_left()) {
            $pdf->setRTL(true);
            $align = 'R';
        }
        $pdf->setTitle($fullname);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->SetFooterMargin(REPORT_BUILDER_PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(true, REPORT_BUILDER_PDF_MARGIN_BOTTOM);
        $pdf->AddPage();

        // Get current language to set the font properly.
        $language = current_language();
        $font = $this->get_font($language);

        $customheader = $this->source->get_custom_header();
        if ($customheader === null) {
            $pdf->SetFont($font, 'B', REPORT_BUILDER_PDF_FONT_SIZE_TITLE);
            $pdf->Write(0, $fullname, '', 0, $align, true, 0, false, false, 0);

            $resultstr = $count == 1 ? 'record' : 'records';
            $recordscount = get_string('x' . $resultstr, 'totara_reportbuilder', $count);
            $pdf->SetFont($font, 'B', REPORT_BUILDER_PDF_FONT_SIZE_RECORD);
            $pdf->Write(0, $recordscount, '', 0, $align, true, 0, false, false, 0);
            $pdf->SetFont($font, '', REPORT_BUILDER_PDF_FONT_SIZE_DATA);

            $extras = $this->source->get_extra_information();
            if ($extras) {
                foreach ($extras as $extra) {
                    $pdf->Write(0, $extra, '', 0, $align, true, 0, false, false, 0);
                }
            }

        } else {
            $pdf->SetFont($font, '', REPORT_BUILDER_PDF_FONT_SIZE_DATA);
            foreach ((array)$customheader as $extra) {
                foreach ((array)$extra as $cell) {
                    $pdf->WriteHTML($cell, true, false, false, false, '');
                }
            }
        }

        $this->source->set_font($font);
        if ($this->portrait) {
            $svgdata = $this->source->get_svg_graph(800, 400);
            if ($svgdata) {
                $pdf->WriteHTML('<img src="@' . base64_encode($svgdata) . '" width="800" height="400"/>', true, false, false, false, '');
            }
        } else {
            $svgdata = $this->source->get_svg_graph(1200, 400);
            if ($svgdata) {
                $pdf->WriteHTML('<img src="@' . base64_encode($svgdata) . '" width="1200" height="400"/>', true, false, false, false, '');
            }
        }

        // Closing the pdf.
        $pdf->WriteHTML($html, true, false, false, false, '');

        return $pdf;
    }

    /**
     * Send the file to browser.
     *
     * @param string $filename without extension
     * @return void serves the file and exits.
     */
    public function send_file($filename) {
        $filename = $filename . '.' . self::get_file_extension();
        $pdf = $this->create_pdf();
        $pdf->Output($filename, 'D');
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
        $pdf = $this->create_pdf();
        $pdf->Output($file, 'F');

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
}
