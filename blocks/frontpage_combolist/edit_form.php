<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @package block_frontpage_combolist
 */

/**
 * Block configuration form.
 * @deprecated since Totara 12. See readme.txt.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/edit_form.php');

/**
 * Block configuration form class.
 *
 * @deprecated since Totara 12. See readme.txt.
 */
class block_frontpage_combolist_edit_form extends block_edit_form {

    /**
     * Enable general settings
     *
     * @return bool
     */
    protected function has_general_settings() {
        return true;
    }

    /**
     * Form definition for this specific block.
     *
     * @param MoodleQuickForm $mform
     */
    protected function specific_definition($mform) {
        $options = [
            block_frontpage_combolist::DISPLAY_ALL => get_string('display_all', 'block_frontpage_combolist'),
            block_frontpage_combolist::DISPLAY_ITEMS => get_string('display_itemsonly', 'block_frontpage_combolist'),
            block_frontpage_combolist::DISPLAY_CATEGORIES => get_string('display_categoriesonly', 'block_frontpage_combolist'),
        ];

        $mform->addElement('select', 'config_display', get_string('display', 'block_frontpage_combolist'), $options);
        $mform->setDefault('config_display', 'all');

        $mform->addElement('text', 'config_maxcategorydepth', get_string('config_maxcategorydepth', 'block_frontpage_combolist'));
        $mform->setType('config_maxcategorydepth', PARAM_INT);
        $mform->setDefault('config_maxcategorydepth', '2');

        $mform->addElement('text', 'config_itemlimit', get_string('config_itemlimit', 'block_frontpage_combolist'));
        $mform->setType('config_itemlimit', PARAM_INT);
        $mform->setDefault('config_itemlimit', '200');
    }
}
