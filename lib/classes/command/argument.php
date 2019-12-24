<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */

namespace core\command;

defined('MOODLE_INTERNAL') || die();

/**
 * Command argument class
 *
 * Implements the Serializable interface just to block serialisation of the executable class.
 * It would be theoretically possible to execute malicious code by corrupting a serialised executable.
 * For this reason we block it - don't ever change it!
 * See the comment on the value property.
 *
 * This class is final, you shouldn't need to extend it, if you do, and you have a valid reason please
 * request that we remove the final keyword.
 * Once the API has proven itself and is considered stable we would consider removing the final keyword.
 *
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package core
 */
final class argument implements \Serializable {

    /**
     * This will be either a key in a key/value pair or an individual switch.
     *
     * This value is not escaped. It should not be set with any user input.
     *
     * @var null|string
     */
    private $key;

    /**
     * Either a standalone value or the value in a key/value pair.
     *
     * The value is cleaned when it is set, before it is stored here.
     * As such this value is already safe to execute.
     * It is also the reason we don't allow the argument to be serialised.
     *
     * @var null|string
     */
    private $value;

    /**
     * What joins the key and value in a key/value pair.
     *
     * This value is not escaped. It should not be set with any user input.
     *
     * @var string
     */
    private $operator = ' ';

    /**
     * Alphanumeric characters and underscores are not inherently dangerous.
     */
    const REGEX_ALPHANUM_UNDERSCORE = '/^[A-Za-z0-9_]+$/';

    /**
     * Will be validated by {@link self::validate_full_filepath()}.
     */
    const PARAM_FULLFILEPATH = 'core_command_fullfilepath';

    public function __construct() {
        // Not used as we want a clear api for when a key, operator and/or value is being set.
        // This class is designed to include some or all of those things.
    }

    /**
     * Perform any processing on the desired key/switch and then store that value.
     *
     * @param string $key Not escaped. No user input should be supplied here.
     * @return argument
     */
    public function set_key($key) {
        $this->key = $key;

        return $this;
    }

    /**
     * Perform any processing on the desired operator and then store that value.
     *
     * @param string $operator Not escaped. No user input should be supplied here.
     * @return argument
     */
    public function set_operator($operator) {
        $this->operator = $operator;

        return $this;
    }

    /**
     * Perform any processing on the desired value and then store that value.
     *
     * @param string $value
     * @param string $paramtype A param type from moodlelib.php (e.g. PARAM_ALPHANUM),
     *   a regex (which must specify start and end, i.e. /^regex$/) or
     *   a param type defined in this class (such as self::PARAM_FULLFILEPATH).
     *   The looser this is the more dangerous the resulting command can be.
     *   Keep in mind that different characters may be riskier on different environments.
     * @param bool $escape_ifnopcntl Values are not escaped if using pcntl and this is ok. If they are instead
     *   being added to the command line, they should always be escaped.
     *   Set this value to false if this value cannot be escaped when added to the command line.
     *   Ensure the value being set will always be safe if setting to false.
     * @return argument
     * @throws exception
     */
    public function set_value($value, $paramtype = self::REGEX_ALPHANUM_UNDERSCORE, $escape_ifnopcntl = true) {

        if ($value === 0) {
            $value = '0';
        } else if (empty($value)) {
            throw new exception('Invalid value');
        }

        if ($paramtype === self::PARAM_FULLFILEPATH) {
            self::validate_full_filepath($value);
        } else if (preg_match('/^([^a-zA-Z0-9\s\\\\])\^.*?[^\\\\]\$\1[is]*$/', $paramtype)) {
            // The regex we allow can use any delimiter that adhere's to the rules (non-alphanumeric,
            // non-whitespace, non-backslash). It must check from start to end (i.e. use ^ and $) and
            // only allows 'i' and 's' modifiers. Be very careful about permitting other modifiers
            // in the future, e.g. allowing 'm' (multi-line) could mean one line is checked,
            // but other lines that don't match the pattern aren't validated.
            // If a regex was supplied, check if the pattern matches.
            if (!preg_match($paramtype, $value)) {
                throw new exception('Invalid value');
            }
        } else if (!preg_match('/^[A-Za-z0-9_]+$/', $paramtype)) {
            // If it hasn't matched the above conditions then it should be a paramtype that can
            // be supplied to clean_param. That should actually just be a string with lowercase letters,
            // but we'll be slightly more permissive to allow for new param types.
            // Invalid param types will still error out from within clean_param anyway, but if it hits
            // this exception it might be easier to debug things like an invalid regex.
            throw new exception('Invalid param type');
        } else {
            // If it wasn't a regex, we assume it's a param type to check against.
            $cleanedparam = clean_param($value, $paramtype);
            if ($cleanedparam !== $value) {
                throw new exception('Invalid Value');
            }
        }

        if (!executable::can_use_pcntl() and $escape_ifnopcntl) {
            $value = escapeshellarg($value);
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Get a string containing the key and/or value, joined by an operator if applicable.
     *
     * @return string
     */
    public function get_argument_string() {
        $argstring = '';

        if (isset($this->key)) {
            $argstring .= $this->key;
        }
        if (isset($this->key) and isset($this->value)) {
            // Only use the operator if there is both a key and operator.
            $argstring .= $this->operator;
        }
        if (isset($this->value)) {
            $argstring .= $this->value;
        }

        return $argstring;
    }

    /**
     * Get an array that would typically be merged into the args array for pcntl_exec().
     *
     * @return string[]
     */
    public function get_argument_array() {
        $argarray = array();

        if ($this->operator === ' ') {
            // The default operator means any key and value will
            // be separate elements when passed to pcntl_exec().
            if (isset($this->key)) {
                $argarray[] = $this->key;
            }
            if (isset($this->value)) {
                $argarray[] = $this->value;
            }
        } else {
            // When the operator is anything other than space, e.g. '=' or an empty string (with no space),
            // then the key and value are joined into one array element when passed to pcntl_exec().
            $argarray[] = $this->get_argument_string();
        }

        return $argarray;
    }

    /**
     * Allows for the start of the pathname to be one of the selected pathnames given in $CFG variables.
     * The remainder must not be altered after being cleaned by clean_param($value, PARAM_PATH),
     * except that Windows directory separators will have been converted prior to that.
     *
     * So this allows for just about any characters at the beginning, e.g. 'c:', spaces etc. as long
     * as they match one of the chosen $CFG variables.
     *
     * It also allows either '\' or '/' at any point in the pathname, regardless of OS.
     *
     * Be aware that there could be file paths in use that are not in one of the $CFG variables. For example
     * clamscan might be scanning files in $_FILES - an os temp directory (often /tmp/ in Ubuntu
     * and a range of different things in others). In those cases you may need to use PARAM_RAW and be extra
     * careful about where the value comes from.
     *
     * Does not return if valid and throws exception if invalid.
     *
     * @param string $value
     * @throws exception
     */
    public static function validate_full_filepath($value) {
        global $CFG;

        $allowedbasedirs = array(
            $CFG->dataroot,
            $CFG->localcachedir,
            $CFG->tempdir,
            $CFG->cachedir,
            $CFG->dirroot
        );

        $valid = false;

        // Windows rewrite rules may not have been applied to the full filename here.
        $value_unixstyle = str_replace('\\', '/', $value);

        foreach ($allowedbasedirs as $allowedbasedir) {
            $allowedbasedir_unixstyle = str_replace('\\', '/', $allowedbasedir);
            if (\core_text::strpos($value_unixstyle, $allowedbasedir_unixstyle) === 0) {
                $afterbasedir = \core_text::substr($value_unixstyle, \core_text::strlen($allowedbasedir_unixstyle));
                if ((\core_text::substr($afterbasedir, 0, 1) !== '/')
                    and (\core_text::substr($allowedbasedir_unixstyle, -1) !== '/')) {
                    // The final part of the base directory is not being treated as a directory.
                    throw new exception('Invalid Filepath Value');
                }
                $cleanedpath = clean_param($afterbasedir, PARAM_PATH);
                if ($cleanedpath === $afterbasedir) {
                    $valid = true;
                    break;
                }
            }
        }

        if (!$valid) {
            throw new exception('Invalid Filepath Value');
        }
    }


    /**
     * Instances of this class should never need to be serialized. Preventing it so that it can't be exploited.
     * @throws exception
     */
    public function serialize() {
        throw new exception('Operation prohibited. You cannot serialize an executable argument instance.');
    }

    /**
     * Instances of this class should never need to be serialized. Preventing it so that it can't be exploited.
     * @throws exception
     */
    public function unserialize($serialized) {
        throw new exception('Operation prohibited: You cannot serialize an executable argument instance.');
    }
}
