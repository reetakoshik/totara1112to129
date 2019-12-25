<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @package totara_reportbuilder
 */

namespace totara_reportbuilder\local;

/**
 * Class describing report graphs.
 */
class graph {
    /** @var \stdClass record from report_builder_graph table */
    protected $graphrecord;
    /** @var \reportbuilder the relevant reportbuilder instance */
    protected $report;
    /** @var array category and data series */
    protected $values;
    /** @var int count of records processed - count() in PHP may be very slow */
    protected $processedcount;
    /** @var int index of category, -1 means simple counter, -2 means category in column */
    protected $category;
    /** @var array indexes of series columns */
    protected $series;
    /** @var int legend column index when headings used as category */
    protected $legendcolumn;
    /** @var array SVGGraph settings */
    protected $svggraphsettings;
    /** @var array SVGGraph settings supplied by user */
    protected $usersettings;
    /** @var string SVGGraph type */
    protected $svggraphtype;
    /** @var string SVGGraph colours */
    protected $svggraphcolours;

    public function __construct(\reportbuilder $report) {

        $this->load($report);

        if (!empty($this->graphrecord->type)) {
            $this->report = $report;
            $this->init();
        }
    }

    /**
     * Object initialisation.
     */
    private function init() {

        $this->svggraphsettings = array(
            'preserve_aspect_ratio' => 'xMidYMid meet',
            'auto_fit' => true,
            'axis_font' => 'sans-serif',
            'pad_right' => 20,
            'pad_left' => 20,
            'pad_bottom' => 20,
            'axis_stroke_width' => 1,
            'axis_font_size' => 12,
            'axis_text_space' => 6,
            'show_grid' => false,
            'division_size' => 6,
            'stroke_width' => 0,
            'back_colour' => '#fff',
            'back_stroke_width' => 0,
            'marker_size' => 3,
            'line_stroke_width' => 2,
            'repeated_keys' => 'accept', // Bad luck, we cannot prevent repeated values.
            'label_font_size' => 14,
            // Custom Totara hacks.
            'label_shorten' => 40,
            'legend_shorten' => 80,
        );

        // Load user settings.
        if (isset($this->graphrecord->settings)) {
            $this->usersettings = parse_ini_string($this->graphrecord->settings, false);
        } else {
            $this->usersettings = array();
        }

        $this->processedcount = 0;
        $this->values = array();
        $this->series = array();
        $this->svggraphsettings['legend_entries'] = array();

        $columns = array();
        $columnsmap = array();
        $i = 0;
        foreach ($this->report->columns as $colkey => $column) {
            if (!$column->display_column(true)) {
                continue;
            }
            $columns[$colkey] = $column;
            $columnsmap[$colkey] = $i++;
        }
        $rawseries = json_decode($this->graphrecord->series, true);
        $series = array();
        foreach ($rawseries as $colkey) {
            $series[$colkey] = $colkey;
        }

        if ($this->graphrecord->category === 'columnheadings') {
            $this->category = -2;

            $legendcolumn = $this->graphrecord->legend;
            if ($legendcolumn and isset($columns[$legendcolumn])) {
                $this->legendcolumn = $columnsmap[$legendcolumn];
            }

            foreach ($columns as $colkey => $column) {
                if (!isset($series[$colkey])) {
                    continue;
                }
                $i = $columnsmap[$colkey];
                $this->values[$i][-2] = $this->report->format_column_heading($this->report->columns[$colkey], true);
            }

        } else {
            if (isset($columns[$this->graphrecord->category])) {
                $this->category = $columnsmap[$this->graphrecord->category];
                unset($series[$this->graphrecord->category]);

            } else { // Category value 'none' or problem detected.
                $this->category = -1;
            }

            foreach ($series as $colkey) {
                if (!isset($columns[$colkey])) {
                    continue;
                }
                $i = $columnsmap[$colkey];
                $this->series[$i] = $colkey;
            }
        }
    }

    /**
     * Load graph record.

     * @param \reportbuilder $report eportbuilder the relevant reportbuilder instance
     */
    private function load($report) {
        global $DB;

        $this->graphrecord = $DB->get_record('report_builder_graph', array('reportid' => $report->_id));
        if (!$this->graphrecord) {
            $this->graphrecord = new \stdClass();
            $this->graphrecord->type = '';
        }
    }

    /**
     * @deprecated since Totara 11
     */
    public function reset_records() {
        debugging('do not reset graph records, create a new graph instead', DEBUG_DEVELOPER);

        $this->processedcount = 0;

        if ($this->category == -2) {
            $this->series = array();
            $this->svggraphsettings['legend_entries'] = array();
            foreach ($this->values as $i => $unused) {
                $prev = $this->values[$i][-2];
                $this->values[$i] = array(-2 => $prev);
            }
        } else {
            $this->values = array();
        }
    }

    public function add_record($record) {
        $recorddata = $this->report->src->process_data_row($record, 'graph', $this->report);

        if ($this->category == -2) {
            $this->series[] = $this->processedcount;
            foreach ($recorddata as $k => $val) {
                if (isset($this->legendcolumn) and $k === $this->legendcolumn) {
                    $this->svggraphsettings['legend_entries'][] = (string)$val;
                    continue;
                }
                if (!isset($this->values[$k])) {
                    continue;
                }
                $this->values[$k][$this->processedcount] = self::normalize_numeric_value($val);
            }
            $this->processedcount++;
            return;
        }

        $value = array();
        if ($this->category == -1) {
            $value[-1] = $this->processedcount + 1;
        } else {
            $value[$this->category] = $recorddata[$this->category];
        }

        foreach ($this->series as $i => $key) {
            $val = $recorddata[$i];
            $value[$i] = self::normalize_numeric_value($val);
        }

        $this->values[] = $value;
        $this->processedcount++;
    }

    /**
     * Normalise the value before sending to SVGGraph for display.
     *
     * Note: There is a lot of guessing in here.
     *
     * @param mixed $val
     * @return int|float|string
     */
    public static function normalize_numeric_value($val) {
        // Strip the percentage sign, the SVGGraph is not compatible with it.
        if (substr($val, -1) === '%') {
            $val = substr($val, 0, -1);
        }

        // Trim spaces, they might be before the % for example, keep newlines though.
        if (is_string($val)) {
            $val = trim($val, ' ');
        }

        // Normalise decimal values to PHP format, SVGGraph needs to localise the numbers itself.
        if (substr_count($val, ',') === 1 and substr_count($val, '.') === 0) {
            $val = str_replace(',', '.', $val);
        }

        if ($val === null or $val === '' or !is_numeric($val)) {
            // There is no way to plot non-numeric data, sorry,
            // we need to use '0' because SVGGraph does not support nulls.
            $val = 0;
        } else if (is_string($val)) {
            if ($val === (string)(int)$val) {
                $val = (int)$val;
            } else {
                $val = (float)$val;
            }
        }

        return $val;
    }

    public function count_records() {
        return $this->processedcount;
    }

    public function get_max_records() {
        return $this->graphrecord->maxrecords;
    }

    protected function init_svggraph() {
        global $CFG;
        require_once($CFG->dirroot.'/totara/core/lib/SVGGraph/SVGGraph.php');

        $this->svggraphtype = null;

        if ($this->count_records() == 0) {
            return;
        }

        if ($this->graphrecord->type === 'pie') {
            // Rework the structure because Pie graph may use only one series.
            $legend = array();
            foreach ($this->values as $value) {
                $legend[] = $value[$this->category];
            }
            $this->svggraphsettings['legend_entries'] = $legend;
            $this->svggraphsettings['show_labels'] = true;
            $this->svggraphsettings['show_label_key'] = false;
            $this->svggraphsettings['show_label_amount'] = false;
            $this->svggraphsettings['show_label_percent'] = true;

        } else {
            // Optionally remove empty series.
            if (!empty($this->usersettings['remove_empty_series'])) {
                if ($this->category >= 0) { // Normal category setup only!
                    foreach ($this->series as $i => $colkey) {
                        if ($i == $this->category) {
                            // Always keep te category item!
                            continue;
                        }
                        $nonzero = false;
                        foreach ($this->values as $j => $value) {
                            if ($value[$i] != 0) {
                                $nonzero = true;
                                break;
                            }
                        }
                        if ($nonzero) {
                            continue;
                        }
                        unset($this->series[$i]);
                        foreach ($this->values as $j => $value) {
                            unset($this->values[$j][$i]);
                        }
                    }
                }
            }

            if (empty($this->series)) {
                // Nothing to plot.
                return;
            }

            // Create legend items.
            if ($this->category != -2) {
                $legend = array();
                foreach ($this->series as $i => $colkey) {
                    $legend[] = $this->report->format_column_heading($this->report->columns[$colkey], true);
                }
                $this->svggraphsettings['legend_entries'] = $legend;
            }
        }
        unset($this->usersettings['remove_empty_series']);

        $this->svggraphsettings['structured_data'] = true;
        $this->svggraphsettings['structure'] = array('key' => $this->category, 'value' => array_keys($this->series));
        $seriescount = count($this->series);
        $singleseries = ($seriescount === 1);

        if ($this->category == -1) {
            // Row number as category - start with 1 instead of automatic 0.
            if ($this->graphrecord->type === 'bar') {
                $this->svggraphsettings['axis_min_v'] = 1;
            } else {
                $this->svggraphsettings['axis_min_h'] = 1;
            }
        }

        if ($this->graphrecord->type === 'bar') {
            if ($seriescount <= 2) {
                $this->svggraphsettings['bar_space'] = 40;
            } else if ($seriescount <= 4) {
                $this->svggraphsettings['bar_space'] = 20;
            } else {
                $this->svggraphsettings['bar_space'] = 10;
            }
            if ($singleseries) {
                $this->svggraphtype = 'HorizontalBarGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'HorizontalStackedBarGraph' : 'HorizontalGroupedBarGraph';
            }

        } else if ($this->graphrecord->type === 'line') {
            if ($singleseries) {
                $this->svggraphtype = 'MultiLineGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedLineGraph' : 'MultiLineGraph';
            }

        } else if ($this->graphrecord->type === 'scatter') {
            if ($singleseries) {
                $this->svggraphtype = 'ScatterGraph';
            } else {
                $this->svggraphtype = 'MultiScatterGraph';
            }

        } else if ($this->graphrecord->type === 'area') {
            $this->svggraphsettings['fill_under'] = true;
            $this->svggraphsettings['marker_size'] = 2;

            if ($singleseries) {
                $this->svggraphtype = 'MultiLineGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedLineGraph' : 'MultiLineGraph';
            }

        } else if ($this->graphrecord->type === 'pie') {
            $this->svggraphtype = 'PieGraph';

        } else { // Type 'column' or unknown.
            $this->graphrecord->type = 'column';
            if ($seriescount <= 2) {
                $this->svggraphsettings['bar_space'] = 80;
            } else if ($seriescount <= 5) {
                $this->svggraphsettings['bar_space'] = 50;
            } else if ($seriescount <= 10) {
                $this->svggraphsettings['bar_space'] = 20;
            } else {
                $this->svggraphsettings['bar_space'] = 10;
            }
            if ($singleseries) {
                $this->svggraphtype = 'BarGraph';
            } else {
                $this->svggraphtype = $this->graphrecord->stacked ? 'StackedBarGraph' : 'GroupedBarGraph';
            }
        }

        // Rotate data labels if necessary.
        if ($this->count_records() > 5 and $this->graphrecord->type !== 'pie') {
            if (get_string('thisdirectionvertical', 'core_langconfig') === 'btt') {
                $this->svggraphsettings['axis_text_angle_h'] = 90;
            } else {
                $this->svggraphsettings['axis_text_angle_h'] = -90;
            }
        }

        // Colors are copied from D3 that used http://colorbrewer2.org by Cynthia Brewer, Mark Harrower and The Pennsylvania State University
        if ($seriescount == 1 and $this->graphrecord->type !== 'pie') {
            $this->svggraphcolours = array('#2ca02c'); // Green is the best colour!

        } else if ($seriescount <= 10) {
            $this->svggraphcolours = array(
                '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728', '#9467bd',
                '#8c564b', '#e377c2', '#7f7f7f', '#bcbd22', '#17becf');

        } else {
            $this->svggraphcolours = array(
                '#1f77b4', '#aec7e8', '#ff7f0e', '#ffbb78', '#2ca02c',
                '#98df8a', '#d62728', '#ff9896', '#9467bd', '#c5b0d5',
                '#8c564b', '#c49c94', '#e377c2', '#f7b6d2', '#7f7f7f',
                '#c7c7c7', '#bcbd22', '#dbdb8d', '#17becf', '#9edae5');
        }
    }

    /**
     * Apply user specified colours.
     *
     * @param array $settings the parsed settings
     * @return array settings with removed 'colours' option
     */
    protected function apply_custom_colours($settings) {
        if (!empty($settings['colours'])) {
            if (is_array($settings['colours'])) {
                $colours = array_values($settings['colours']);
            } else {
                $colours = explode(',', $settings['colours']);
            }
            $colours = array_map('trim', $colours);
            $this->svggraphcolours = $colours;
        }
        unset($settings['colours']);
        return $settings;
    }

    protected function get_final_settings() {
        $settings = $this->svggraphsettings;

        foreach ($this->usersettings as $k => $v) {
            $settings[$k] = $v;
        }

        if (right_to_left()) {
            if (!isset($settings['legend_text_side'])) {
                $settings['legend_text_side'] = 'left';
            }
        }

        // Set up legend defaults and shorten entries if requested.
        $settings = $this->shorten_legend($settings);

        return $this->apply_custom_colours($settings);
    }

    /**
     * Try to fix the SVG data somehow to make it work with RTL languages.
     *
     * @param string $data
     * @param null|bool $rtl apply RTL hacks, NULL means detect RTL from current language
     * @param null|bool $msrtlhack true means hack text anchors, NULL means true if IE/Edge detected
     * @return string SVG markup
     */
    public static function fix_svg_rtl($data, $rtl = null, $msrtlhack = null) {
        if ($rtl === null) {
            $rtl = right_to_left();
        }
        if (!$rtl) {
            return $data;
        }

        $data = str_replace('<svg ', '<svg direction="rtl" ', $data);

        if ($msrtlhack === null) {
            // NOTE: Silly MS devs always read the standards in a different way, oh well...
            //       Ignore lower IE versions because they do not support SVG,
            //       we fallback to PDF rendering that does not support RTL anyway.
            $msrtlhack = (\core_useragent::check_ie_version(9) || \core_useragent::is_edge());
        }

        if (!$msrtlhack) {
            $data = str_replace('text-anchor="end"', 'text-anchor="xxx"', $data);
            $data = str_replace('text-anchor="start"', 'text-anchor="end"', $data);
            $data = str_replace('text-anchor="xxx"', 'text-anchor="start"', $data);
        }

        return $data;
    }

    /**
     * Shorten the label texts in graph.
     *
     * @param array $values
     * @param array $settings
     * @return array modified $values
     */
    protected function shorten_labels(array $values, array $settings) {
        $labelshorten = (int)$settings['label_shorten'];

        if ($labelshorten <= 0) {
            return $values;
        }

        $legendkey = $settings['structure']['key'];

        foreach ($values as $k => $v) {
            if (!isset($v[$legendkey])) {
                continue;
            }
            $values[$k][$legendkey] = shorten_text($v[$legendkey], $labelshorten);
        }

        return $values;
    }

    /**
     * Set up legend defaults and shorten entries if requested.
     *
     * By default if there are many entries the default settings are
     * adjusted to fix as much as possible into 3 columns max.
     *
     * @param array $settings
     * @return array modified $settings
     */
    protected function shorten_legend(array $settings) {
        if (empty($settings['legend_entries'])) {
            return $settings;
        }

        // If there are many legend entries make everything smaller and use more columns by default.
        if (!isset($settings['legend_entry_width']) and !isset($settings['legend_entry_height']) and !isset($settings['legend_columns'])) {
            $legendcount = count($settings['legend_entries']);
            if ($legendcount > 84) {
                $settings['legend_columns'] = 3;
                $settings['legend_entry_width'] = 6;
                $settings['legend_entry_height'] = 6;
                $settings['legend_font_size'] = 6;
            } else if ($legendcount > 28) {
                $settings['legend_columns'] = ceil($legendcount / 28);
                $settings['legend_entry_width'] = 7;
                $settings['legend_entry_height'] = 7;
                $settings['legend_font_size'] = 7;
            } else if ($legendcount > 21) {
                $settings['legend_columns'] = 1;
                $settings['legend_entry_width'] = 8;
                $settings['legend_entry_height'] = 8;
                $settings['legend_font_size'] = 8;
            } else if ($legendcount > 14) {
                $settings['legend_columns'] = 1;
                $settings['legend_entry_width'] = 10;
                $settings['legend_entry_height'] = 10;
                $settings['legend_font_size'] = 10;
            }
        }

        if (!empty($settings['legend_shorten'])) {
            $legendshorten = (int)$settings['legend_shorten'];
            if ($legendshorten > 0) {
                foreach ($settings['legend_entries'] as $k => $v) {
                    $settings['legend_entries'][$k] = shorten_text($v, $legendshorten);
                }
            }
        }

        return $settings;
    }

    /**
     * Generate SVG markup using the SVGGraph library.
     *
     * @param int $width
     * @param int $height
     * @param array $settings
     * @return string SVG markup
     */
    protected function get_svggraph_data($width, $height, array $settings) {
        $svggraph = new \SVGGraph($width, $height, $settings);
        $svggraph->Colours($this->svggraphcolours);
        $svggraph->Values($this->shorten_labels($this->values, $settings));
        $data = $svggraph->Fetch($this->svggraphtype, false, false);

        if (strpos($data, 'Zero length axis (min >= max)') === false) {
            return $data;
        }

        // Use a workaround to prevent axis problems caused by zero only values.
        $dir = ($this->graphrecord->type === 'bar') ? 'h' : 'v';
        if (!isset($settings['axis_min_' . $dir])) {
            $settings['axis_min_' . $dir] = 0;
        }
        if (!isset($settings['axis_max_' . $dir]) or $settings['axis_max_' . $dir] <= $settings['axis_min_' . $dir]) {
            $settings['axis_max_' . $dir] = $settings['axis_min_' . $dir] + 1;
        }
        $svggraph = new \SVGGraph($width, $height, $settings);
        $svggraph->Colours($this->svggraphcolours);
        $svggraph->Values($this->shorten_labels($this->values, $settings));
        $data = $svggraph->Fetch($this->svggraphtype, false, false);
        return $data;
    }

    /**
     * Get SVG image markup suitable for embedding in report page.
     *
     * @return string SVG markup
     */
    public function fetch_svg() {
        $this->init_svggraph();
        if (!$this->svggraphtype) {
            // Nothing to do.
            return null;
        }
        $settings = $this->get_final_settings();
        $data = $this->get_svggraph_data(1000, 400, $settings);
        $data = self::fix_svg_rtl($data, null, null);
        return $data;
    }

    /**
     * Get SVG image markup intended for graph block.
     *
     * NOTE: the RTL fixes are not applied because we need to cache the results.
     *
     * @return string SVG markup without RTL hacks
     */
    public function fetch_block_svg() {
        $this->init_svggraph();
        if (!$this->svggraphtype) {
            // Nothing to do.
            return null;
        }

        // Hack the settings a bit, but keep the originals so that we can render more svgs.
        $settings = $this->get_final_settings();

        if ($this->graphrecord->type === 'column') {
            $settings['bar_space'] = $settings['bar_space'] / 3;
            if ($settings['bar_space'] < 10) {
                $settings['bar_space'] = 10;
            }
        }

        $data = $this->get_svggraph_data(400, 400, $settings);
        return $data;
    }

    /**
     * Get SVG image markup suitable for general export.
     *
     * Note: the result is NOT intended for displaying in MS browsers!
     *
     * @param int $w width of the SVG
     * @param int $h height of SVG
     * @return string SVG markup
     */
    public function fetch_export_svg($w, $h) {
        $this->init_svggraph();
        if (!$this->svggraphtype) {
            // Nothing to do.
            return null;
        }
        $settings = $this->get_final_settings();
        $data = $this->get_svggraph_data($w, $h, $settings);
        $data = self::fix_svg_rtl($data, null, false);
        return $data;
    }

    public function is_valid() {

        if (empty($this->graphrecord->type)) {
            return false;
        }

        return (bool)$this->series;
    }

    /**
     * Set all fonts used in svg graph to the specified font
     *
     * @param string $font Name of the font to use
     */
    public function set_font($font) {
        // this place require set all font settings for pdf svg graph, see svggraph.ini file
        $svgfonts = ['axis_font', 'tooltip_font', 'graph_title_font', 'legend_font', 'legend_title_font', 'data_label_font',
                     'label_font', 'guideline_font', 'crosshairs_text_font', 'bar_total_font', 'inner_text_font'];
        foreach ($svgfonts as $svgfont) {
            $this->svggraphsettings[$svgfont] = $font;
        }
    }

}
