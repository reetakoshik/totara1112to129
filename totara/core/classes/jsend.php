<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

namespace totara_core;

/**
 * JSend protocol support code - see http://labs.omniti.com/labs/jsend
 */
class jsend {
    /**
     * @var array of fake test data.
     */
    protected static $testdata;

    /**
     * Fake the returned data when testing code.
     *
     * @internal
     * @param string[] $testdata json encoded fake request results, null means stop faking
     * @return void
     */
    public static function set_phpunit_testdata(array $testdata = null) {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('JSEND responses can be faked in PHPUnit tests only!');
        }
        self::$testdata = $testdata;
    }

    /**
     * Returns the remaining fake data for code testing.
     *
     * @internal
     * @return string[] testdata or false when not enabled
     */
    public static function get_phpunit_testdata() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('JSEND responses can be faked in PHPUnit tests only!');
        }
        return self::$testdata;
    }

    /**
     * Init JSend server scripts.
     */
    public static function init_output() {
        global $PAGE, $SCRIPT, $OUTPUT;
        if (!AJAX_SCRIPT) {
            throw new \coding_exception('JSend endpoint scripts require ajax flag!');
        }
        $PAGE->set_url($SCRIPT);
        $PAGE->set_context(\context_system::instance());
        echo $OUTPUT->header();
        set_exception_handler(array('totara_core\jsend', 'default_exception_handler'));

        // We do not want any interruptions here.
        ignore_user_abort(true); // Sloppy IIS FastCGI does not support this, bad luck.
        \core_php_time_limit::raise(300);
    }

    /**
     * Log problems.
     *
     * @param string $error
     */
    protected static function error_log($error) {
        if (PHPUNIT_TEST) {
            // Do not pollute the logs when testing.
            return;
        }
        error_log($error);
    }

    /**
     * Exception handler, do not use directly.
     *
     * @param \Exception $ex
     * @internal
     */
    public static function default_exception_handler($ex) {
        abort_all_db_transactions();

        $info = get_exception_info($ex);
        $logerrmsg = "JSend exception handler: {$info->message} Debug: {$info->debuginfo}\n" . format_backtrace($info->backtrace, true);

        self::error_log($logerrmsg);
        self::send_error($info->message);
    }

    /**
     * Send JSend result and stop execution.
     *
     * @param array $data
     */
    public static function send_success(array $data = null) {
        $result = array(
            'status' => 'success',
            'data' => $data,
        );
        self::send_result($result);
    }

    /**
     * Send JSend result and stop execution.
     *
     * @param string $message error message
     */
    public static function send_error($message) {
        $result = array(
            'status' => 'error',
            'message' => $message,
        );
        self::send_result($result);
    }

    /**
     * Send JSend result and stop execution.
     *
     * @param array $result
     */
    public static function send_result(array $result) {
        if (!isset($result['status'])) {
            debugging('invalid JSend result, missing status key', DEBUG_DEVELOPER);
            $result = array();
            $result['status'] = 'error';
            $result['message'] = 'invalid JSend result data';
        } else if ($result['status'] === 'success') {
            if (!array_key_exists('data', $result)) {
                $result['data'] = null;
                debugging('invalid JSend result, missing data key in success result', DEBUG_DEVELOPER);
            } else {
                if ($result['data'] != null and !is_array($result['data'])) {
                    if (!is_object($result['data']) or get_class($result['data']) !== 'stdClass') {
                        debugging('invalid JSend result, data value must be an array, stdClass or null in success result', DEBUG_DEVELOPER);
                    }
                    $result['data'] = (array)$result['data'];
                }
            }
        } else if ($result['status'] === 'fail') {
            if (!array_key_exists('data', $result)) {
                $result['data'] = null;
                debugging('invalid JSend result, missing data key in fail result', DEBUG_DEVELOPER);
            } else {
                if ($result['data'] != null and !is_array($result['data'])) {
                    if (!is_object($result['data']) or get_class($result['data']) !== 'stdClass') {
                        debugging('invalid JSend result, data value must be an array, stdClass or null in fail result', DEBUG_DEVELOPER);
                    }
                    $result['data'] = (array)$result['data'];
                }
            }
        } else if ($result['status'] === 'error') {
            if (!isset($result['message'])) {
                $result['message'] = 'unknown error';
                debugging('invalid JSend result, missing message key in error result', DEBUG_DEVELOPER);
            } else {
                $result['message'] = (string)$result['message'];
            }
        } else {
            debugging('invalid JSend result, unknown status key', DEBUG_DEVELOPER);
            $result = array();
            $result['status'] = 'error';
            $result['message'] = 'invalid JSend result data';
        }

        if ($result['status'] === 'error') {
            foreach ($result as $k => $v) {
                if ($k !== 'status' and $k !== 'message' and $k !== 'code' and $k !== 'data') {
                    debugging("invalid JSend result, '$k' is not a valid result index", DEBUG_DEVELOPER);
                    unset($result[$k]);
                }
            }
            self::error_log('JSend result error: ' . $result['message']);
        } else {
            foreach ($result as $k => $v) {
                if ($k !== 'status' and $k !== 'data') {
                    debugging("invalid JSend result, '$k' is not a valid result index", DEBUG_DEVELOPER);
                    unset($result[$k]);
                }
            }
            if ($result['status'] === 'fail') {
                self::error_log('JSend result fail: ' . var_export($result['data'], true));
            }
        }

        echo json_encode($result);
        if (PHPUNIT_TEST) {
            // No dying in tests.
            return;
        }
        die;
    }

    /**
     * Make JSend request to a remote server.
     *
     * NOTE: developers must sanitise the returned data before use!
     *
     * @param string|\moodle_url $url
     * @param array $params
     * @param int $timeout
     * @return array normalised JSend result array
     */
    public static function request($url, $params, $timeout = 60) {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        if ($url instanceof \moodle_url) {
            $url = $url->out(false);
        }

        if (PHPUNIT_TEST and self::$testdata !== null) {
            // We are faking the results.
            if (self::$testdata) {
                $result = json_encode(array_shift(self::$testdata));
            } else {
                $result = json_encode('');
            }
        } else {
            $result = download_file_content($url, null, $params, false, $timeout, 10);
        }

        if (!$result) {
            $result = array(
                'status' => 'error',
                'message' => 'Remote data request failed - cannot connect server.',
            );
            return $result;
        }

        // Note we cannot clean the $result before using json_decode because it does unicode magic.
        $result = @json_decode($result, true);

        if (!is_array($result) or empty($result['status'])) {
            $result = array(
                'status' => 'error',
                'message' => 'Remote data request failed - invalid response format.',
            );
            return $result;
        }

        // Perform basic utf8 and null byte cleanup because we cannot trust plugin developers to do it.
        self::clean_result($result);

        if ($result['status'] === 'success') {
            if (!isset($result['data'])) {
                $result['data'] = null;
            } else {
                $result['data'] = (array)$result['data'];
            }
            return array('status' => 'success', 'data' => $result['data']);
        }

        if ($result['status'] === 'error') {
            if (!isset($result['message'])) {
                $result['message'] = 'unknown error';
            }
            $return = array('status' => 'error', 'message' => $result['message']);
            if (isset($result['data']) and is_array($result['data'])) {
                $return['data'] = $result['data'];
            }
            if (isset($result['code'])) {
                $return['code'] = $result['code'];
            }
            return $return;
        }

        if ($result['status'] === 'fail') {
            if (!isset($result['data'])) {
                $result['data'] = null;
            } else {
                $result['data'] = (array)$result['data'];
            }
            return array('status' => 'fail', 'data' => $result['data']);
        }

        $return = array(
            'status' => 'error',
            'message' => 'Remote data request failed - invalid response format.',
        );
        return $return;
    }

    /**
     * Fix all strings in array to contain only valid utf-8 bytes and strip null bytes,
     * compared to fix_utf8() this method is optimised for performance and memory use.
     *
     * @param array $data
     */
    public static function clean_result(array &$data) {
        foreach ($data as $key => $value) {
            // First deal with key cleaning.
            if (is_string($key)) {
                $cleaned = fix_utf8($key);
                if ($key !== $cleaned) {
                    // This should never happen, so we do not really care here about array order.
                    unset($data[$key]);
                    $key = $cleaned;
                    $data[$key] = $value;
                }
            }
            // Then clean string values.
            if (is_string($value)) {
                $cleaned = fix_utf8($value);
                if ($value !== $cleaned) {
                    $data[$key] = $cleaned;
                }
                // Continue to next item.
                continue;
            }
            if (is_object($value)) {
                // This should not happen, conversion to array is fine.
                $value = (array)$value;
                $data[$key] = $value;
                // The next if does the recursive cleaning.
            }
            if (is_array($value)) {
                self::clean_result($data[$key]);
                continue;
            }
            // There is no need to clean other scalars here.
        }
    }
}
