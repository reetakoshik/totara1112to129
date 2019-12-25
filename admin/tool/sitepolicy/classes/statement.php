<?php
/**
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Courteney Brownie <courteney.brownie@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Consent statement class used to transport data between the statement element and the working code.
 *
 * @package tool_sitepolicy
 */
class statement {

    /**
     * The instance of the form, should only be set by the form.
     * @var int
     */
    public $instance = 0;

    /**
     * The id of this statement in the database, used so that we can update/remove it if necessary.
     * @var int
     */
    public $dataid = 0;

    /**
     * The statement on primary language.
     * @var string
     */
    public $primarystatement = '';

    /**
     * The statement.
     * @var string
     */
    public $statement = '';

    /**
     * The provided text on primary language.
     * @var string
     */
    public $primaryprovided = '';
    /**
     * The provided text.
     * @var string
     */
    public $provided = '';

    /**
     * The withheld text on primary language
     * @var string
     */
    public $primarywithheld = '';

    /**
     * The withheld text
     * @var string
     */
    public $withheld = '';

    /**
     * Checkbox to say if consent is mandatory
     * @var bool
     */
    public $mandatory = '';

    /**
     * True if this statement exists, and should be removed.
     * @var bool
     */
    public $removedstatement = false;

    /**
     * Statement index in form
     * @var int
     */
    public $index = 0;

    /**
     * Magic setter. Ignore names not in the object
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) {
        $properties = get_object_vars($this);
        if (array_key_exists($name, $properties)) {
            $this->{$name} = $value;
        }
    }

    /**
     * Exports data belonging to this statement for use in a template.
     * @return array
     */
    public function export_for_template(): array {
        $data = [
            'instance' => $this->instance,
            'dataid' => $this->dataid,
            'primarystatement' => $this->primarystatement,
            'statement' => $this->statement,
            'primaryprovided' => $this->primaryprovided,
            'provided' => $this->provided,
            'primarywithheld' => $this->primarywithheld,
            'withheld' => $this->withheld,
            'mandatory' => $this->mandatory,
            'index' => $this->index,
            'removedstatement' => $this->removedstatement
        ];
        return $data;
    }

    /**
     * Returns the properties of a statement.
     * @return array
     */
    public static function get_properties(): array {
        return [
            // null means no input data expected for property
            'dataid' => PARAM_INT,
            'instance' => PARAM_INT,
            'statement' => PARAM_TEXT,
            'provided' => PARAM_TEXT,
            'withheld' => PARAM_TEXT,
            'mandatory' => PARAM_INT,
            'removedstatement' => PARAM_BOOL,
            'index' => PARAM_INT,
            // This output only
            'primarystatement' => null,
            'primaryprovided' => null,
            'primarywithheld' => null,
        ];
    }

    /**
     * Cleans this statement and then returns itself.
     *
     * @return statement $this
     * @throws \coding_exception
     */
    public function clean() {
        $properties = get_object_vars($this);
        foreach (self::get_properties() as $name => $type) {
            if (is_null($type)) {
                unset($properties[$name]);
                continue;
            }
            $this->{$name} = clean_param($properties[$name], $type);
            unset($properties[$name]);
        }
        if (!empty($properties)) {
            throw new \coding_exception('Unexpected properties on statement', \json_encode($properties));
        }
        return $this;
    }
}