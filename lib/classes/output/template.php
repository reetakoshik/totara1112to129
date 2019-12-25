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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

namespace core\output;

/**
 * Base class for elements that use mustache templates instead of PHP renderers.
 *
 * To use this base class you need to do following:
 *
 *  1. add mustache template file: plugintype/pluginname/templates/newelement.mustache
 *  2. extend this class in your plugin in output namespace: plugintype/pluginname/classes/output/newelement.php
 *  3. create a new instance: $el = new \plugintype_pluginname\output\newelement($data);
 *  4. render it: echo $OUTPUT->render($el);
 *  5. optionally add static factory methods create_from_xxx()
 *
 * @since Totara 12
 */
abstract class template implements \renderable {
    /** @var array context data */
    protected $data;

    /**
     * Element constructor.
     *
     * @param array $data context data for mustache template, this should be the public API of template
     */
    public function __construct(array $data) {
        // NOTE: instead of overriding this method please consider adding static factory ->create_from_xxx() methods.
        $this->data = $data;
    }

    /**
     * Returns full template name.
     *
     * @return string template name
     */
    final public static function get_template_name() {
        // NOTE: this method is final because there should always be exactly one template with matching name
        //       for each component, use partials for dynamic components.
        $classname = get_called_class(); // Late static binding.
        if (!preg_match('/^([a-z0-9_]+)\\\\output\\\\([a-z0-9_]+)$/', $classname, $matches)) {
            throw new \coding_exception('Unexpected template class name or namespace, mustache template name cannot be determined: ' . $classname);
        }
        return $matches[1] . '/' . $matches[2];
    }

    /**
     * Export data for template.
     *
     * @return array
     */
    public function get_template_data() {
        // NOTE: if you need to use output do that in some static factory method create_xxx().
        return $this->data;
    }
}
