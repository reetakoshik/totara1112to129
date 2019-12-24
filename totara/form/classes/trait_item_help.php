<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2016 onwards Totara Learning Solutions LTD
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
 * @package totara_form
 */

namespace totara_form;

/**
 * Trait for item help.
 *
 * @package   totara_form
 * @copyright 2016 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Petr Skoda <petr.skoda@totaralms.com>
 */
trait trait_item_help {
    /**
     * @internal do not use directly!
     *
     * @var string help html stuff
     */
    private $helpbutton;

    /**
     * Add help button to item.
     *
     * @throws \coding_exception if the form structure has been finalised and help button cannot be added.
     * @param string $identifier help string identifier without _help suffix
     * @param string $component component name to look the help string in
     * @param string $linktext optional text to display next to the icon
     */
    public function add_help_button($identifier, $component = 'core', $linktext = '') {
        /** @var item $this */
        if ($this->is_finalised()) {
            throw new \coding_exception('Form structure cannot be changed any more!');
        }

        /** @var trait_item_help $this */
        $this->helpbutton = array('identifier' => $identifier, 'component' => $component, 'linktext' => $linktext);
    }

    /**
     * Add errors to template data and tweak attributes if necessary.
     *
     * @param array &$data
     * @param \renderer_base|\core_renderer $output
     * @return void $data argument is modified
     */
    protected function set_help_template_data(&$data, \renderer_base $output) {
        // TODO TL-9419: replace with proper help template!
        if ($this->helpbutton) {
            $identifier = $this->helpbutton['identifier'];
            $component = $this->helpbutton['component'];
            $linktext = $this->helpbutton['linktext'];
            if ($component === 'core') {
                // We do not want this TM in Totara APIs!
                $component = 'moodle';
            }
            $data['helphtml'] = $output->help_icon($identifier, $component, $linktext);
        } else {
            $data['helphtml'] = false;
        }
    }
}
