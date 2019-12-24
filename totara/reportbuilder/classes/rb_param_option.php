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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @package totara
 * @subpackage reportbuilder
 */
/**
 * Class defining a report builder parameter option
 *
 * A parameter is a restriction that can be applied to a report
 * by adding a certain field to the URL. If a report has a
 * parameter option enabled, then adding this to the url:
 *
 * <code>report.php?id=X&name=[value]</code>
 *
 * will add a restriction that limits the results to those with
 * the field $field equal to [value].
 *
 * Values can be arrays, then (IN SQL) will be used to match.
 * Also scalar values can have special first character that will affect comparison of ruther value:
 * "!" - value will be compared as not equal
 * ">" - value will be compared as greater than
 * "<" - value will be comparted as less than
 * This behaviour defined in @see reportbuilder::get_param_restrictions
 */
class rb_param_option {
    /**
     * Name for the parameter
     *
     * This is the string that must be added to the URL to activate
     * the restriction
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * Database field to apply the restriction to
     *
     * This should include the join name as a prefix, e.g:
     *
     * <code>course.fullname</code>
     *
     * @access public
     * @var string
     */
    public $field;

    /**
     * One or more join names required to access $field
     *
     * Either a string or an array of strings containing
     * names of {@link rb_join} objects that are required
     * to access the $field field
     *
     * @access public
     * @var mixed
     */
    public $joins;

    /**
     * @deprecated
     */
    public $type;

    /**
     * Define new param option
     * @param string $name
     * @param string $field
     * @param mixed $joins
     * @param @deprecated $type
     */
    function __construct($name, $field, $joins=null, $type='int') {

        $this->name = $name;
        $this->field = $field;
        $this->joins = $joins;
        $this->type = $type;
    }

} // end of rb_param_option class


