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
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>
 * @package   core
 */

namespace core\output;

use Mustache_LambdaHelper;
use renderer_base;

/**
 * Mustache flex icon helper.
 *
 * @copyright 2016 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Joby Harding <joby.harding@totaralms.com>
 * @package   core
 */
class mustache_flex_icon_helper {

    /**
     * @var renderer_base
     */
    protected $renderer;

    /**
     * Constructor
     *
     * @param renderer_base $renderer
     */
    public function __construct(renderer_base $renderer) {

        $this->renderer = $renderer;

    }

    /**
     * Read a pix icon name from a template and get it from pix_icon.
     *
     * {{#flex_icon}}t/class, { "classes": "size" }{{/flex_icon}}
     *
     * The args are comma separated and only the first is required.
     *
     * @throws \coding_exception if the JSON cache could not be decoded.
     * @param string $string Content of flex_icon helper in template.
     * @param Mustache_LambdaHelper $helper Used to render nested mustache variables.
     * @return string
     */
    public function flex_icon($string, Mustache_LambdaHelper $helper) {

        $parameters = array_map(function($parameter) {
            return trim($parameter);
        }, explode(',', $string, 2));

        $identifier = array_shift($parameters);
        if (count($parameters)) {
            $customdata = array_shift($parameters);
        }

        // This applies the same logic as in {{@see \Mustache_Compiler::SECTION}}
        if (strpos($identifier, '{{') !== false) {
            $identifier = $helper->render($identifier);
        }
        if (empty($customdata)) {
            $flexicon = new flex_icon($identifier);
            return $this->renderer->render($flexicon);
        }

        $customdata = $helper->render($customdata);
        $customdata = @json_decode($customdata, true);

        if ($customdata === null) {
            throw new \coding_exception("flex_icon helper was unable to decode JSON");
        }

        $flexicon = new flex_icon($identifier, $customdata);

        return $this->renderer->render($flexicon);

    }

}
