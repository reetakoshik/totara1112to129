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
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Totara flavour renderer.
 *
 * To get a new instance:
 *   $PAGE->get_renderer('totara_flavour');
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralms.com>
 * @package totara_flavour
 */
class totara_flavour_renderer extends plugin_renderer_base {

    /**
     * Displays the overview table.
     *
     * @param \totara_flavour\overview $overview
     * @return string
     */
    protected function render_overview(\totara_flavour\overview $overview) {
        $currentflavour = $overview->currentflavour;
        $flavours = $overview->flavours;

        $available = $this->output->pix_icon('tick', get_string('available', 'totara_flavour'), 'totara_flavour');
        $disabled = html_writer::span(get_string('unavailable', 'totara_flavour'), 'accesshide');

        $cols = array();
        $table = new html_table;
        $table->attributes['class'] = 'flavour-overview-table';
        $table->head = array(get_string('features', 'totara_flavour'));
        $table->colclasses = array('feature');
        foreach ($flavours as $flavour) {
            $cols[] = $flavour->get_component();
            $table->head[] = $flavour->get_name();
            $table->colclasses[] = 'flavour';
            if ($flavour->get_component() === $currentflavour) {
                $table->colclasses[count($cols)] .= ' current';
            }
        }
        $table->head[] = get_string('currentsetup', 'totara_flavour');
        $table->colclasses[] = 'setting';

        $table->data = array();
        foreach ($overview->settings as $setting) {
            $row = new html_table_row();
            $row->cells[] = new html_table_cell($this->render($setting));
            foreach ($cols as $col) {
                $prohibited = $setting->is_prohibited($col);
                $cell = new html_table_cell($prohibited ? $disabled : $available);
                $cell->attributes['class'] .= $prohibited ? 'prohibited' : 'enabled';
                $row->cells[] = $cell;
            }
            $ison = $setting->is_on();
            $currentvalue = new html_table_cell($ison ? $available : $disabled);
            $currentvalue->attributes['class'] = ($ison) ? 'state-on' : 'state-off';
            if ($setting->is_prohibited($currentflavour)) {
                // The setting is prohibited but has been turned on.
                // This happens if setting was not enforced properly or
                // when it is forced in config.php.
                $currentvalue->attributes['class'] .= ' prohibited';
            }
            $row->cells[] = $currentvalue;
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Displays overview setting details.
     *
     * @param \totara_flavour\overview_setting $setting
     * @return string
     */
    protected function render_overview_setting(\totara_flavour\overview_setting $setting) {
        $desc = $setting->get_description();
        $html = '<strong>'.$setting->get_name().'</strong>';
        if ($desc !== '') {
            $html = $this->collapsible_description($desc, $html);
        }
        return $html;
    }

    /**
     * Produces a collapsible region.
     *
     * @param string $content
     * @param string $caption
     * @param bool $collapsed
     * @return string
     */
    protected function collapsible_description($content, $caption, $collapsed = true) {
        $id = html_writer::random_id('collapsible');
        $this->page->requires->js_init_call('M.util.init_collapsible_region', array($id, false, get_string('clicktohideshow')));

        $classes = 'overview-setting';
        if ($collapsed) {
            $classes .= ' collapsed';
        }

        $output  = '<div id="' . $id . '" class="collapsibleregion ' . $classes . '">';
        $output .= '<div id="' . $id . '_sizer">';
        $output .= '<div id="' . $id . '_caption" class="collapsibleregioncaption">';
        $output .= $caption . ' ';
        $output .= '</div><div id="' . $id . '_inner" class="collapsibleregioninner">';
        $output .= $content;
        $output .= '</div></div></div>';
        return $output;
    }
}
