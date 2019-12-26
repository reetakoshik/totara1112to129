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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package totara_catalog
 */

namespace totara_catalog\dataformatter;

defined('MOODLE_INTERNAL') || die();

class textarea extends formatter {

    /**
     * @param string $textfield the database field containing the text
     * @param string $contextidfield the database field containing the contextid
     * @param string $componentfield the database field containing the component
     * @param string $fileareafield the database field containing the filearea
     * @param string $itemidfield the database field containing the itemid
     */
    public function __construct(
        string $textfield,
        string $contextidfield,
        string $componentfield,
        string $fileareafield,
        string $itemidfield
    ) {
        $this->add_required_field('text', $textfield);
        $this->add_required_field('contextid', $contextidfield);
        $this->add_required_field('component', $componentfield);
        $this->add_required_field('filearea', $fileareafield);
        $this->add_required_field('itemid', $itemidfield);
    }

    public function get_suitable_types(): array {
        return [
            formatter::TYPE_PLACEHOLDER_RICH_TEXT,
        ];
    }

    /**
     * Textarea data formatter.
     *
     * Expects $data to contain keys 'text', 'contextid', 'component', 'filearea' and 'itemid'.
     *
     * @param array $data
     * @param \context $context
     * @return string
     */
    public function get_formatted_value(array $data, \context $context): string {

        if (!array_key_exists('text', $data)) {
            throw new \coding_exception("Text area data formatter expects 'text'");
        }

        if (!array_key_exists('contextid', $data)) {
            throw new \coding_exception("Text area data formatter expects 'contextid'");
        }

        if (!array_key_exists('component', $data)) {
            throw new \coding_exception("Text area data formatter expects 'component'");
        }

        if (!array_key_exists('filearea', $data)) {
            throw new \coding_exception("Text area data formatter expects 'filearea'");
        }

        if (!array_key_exists('itemid', $data)) {
            throw new \coding_exception("Text area data formatter expects 'itemid'");
        }

        return format_text(
            file_rewrite_pluginfile_urls(
                $data['text'],
                'pluginfile.php',
                $data['contextid'],
                $data['component'],
                $data['filearea'],
                $data['itemid']
            ),
            FORMAT_MOODLE,
            ['context' => $context]
        );
    }
}
