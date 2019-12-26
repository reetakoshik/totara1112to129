<?php
/*
 * This file is part of Totara LMS
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
 * @copyright 2018 onwards Totara Learning Solutions LTD
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package   core_output
 */

namespace core\output;

/**
 * Mustache helper to escape render execution and perform a simple replacement.
 *
 * @package core\output
 */
class mustache_escape_helper {

    /**
     * Performs a simple, safe, non-recursive replacement on the given text.
     *
     * @param string $text
     * @param \Mustache_LambdaHelper $helper
     * @return string
     */
    public function esc(string $text, \Mustache_LambdaHelper $helper): string {

        if (strpos($text, '{{') === false || strpos($text, '}}') === false ) {
            // Nothing Mustache here.
            return $text;
        }

        if (!preg_match('#^ *(\{{2,3})\s*([a-zA-Z0-9_]+)\s*\}{2,3} *$#', $text, $matches)) {
            // It's not a straight up variable but contained mustache processing tags. Don't trust it!
            $this->debugging('Escaped content contains unexpected mustache processing queues. It will be lost.');
            return '';
        }

        // Load a reflection class so that we can access the context object.
        // We want to see if the requested variable exists in the context object, and if so make use of it for the replacement.
        $context = new \ReflectionProperty($helper, 'context');
        $context->setAccessible(true);
        $context = $context->getValue($helper);
        /** @var \Mustache_Context $context */
        $answer = $context->find($matches[2]);

        // Ensure that any mustache processing tags are replaced with something safe.
        $cleaned_answer = str_replace(['{{{', '}}}', '{{', '}}'], ['[[[', ']]]', '[[', ']]'], $answer);
        if ($answer !== $cleaned_answer) {
            $this->debugging('Mustache processing quotes converted to square brackets for safety.');
        }
        $answer = $cleaned_answer;
        if ($matches[1] !== '{{{') {
            // It wasn't triple braces, we need to clean it.
            $answer = s($answer);
        }
        $result = str_replace($matches[0], $answer, $text);
        return $result;
    }

    /**
     * Sends a debugging message, but with the Mustache backtrace trimmed out.
     *
     * @codeCoverageIgnore
     * @param string $message
     */
    private function debugging(string $message) {
        if (debugging()) {
            // We want to strip out the Mustache entries from the backtrace to make this meaningful.
            $backtrace = debug_backtrace();
            $count = 0;
            foreach ($backtrace as $caller) {
                if (isset($caller['function']) && strpos($caller['function'], 'render_from_template') !== false) {
                    // Include this line and then stop.
                    break;
                }
                $count++;
            }
            if ($count) {
                $backtrace = array_slice($backtrace, $count);
            }
            debugging($message, DEBUG_DEVELOPER, $backtrace);
        }
    }
}
