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
 * @author Michael Dunstan <michael.dunstan@androgogic.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone;

defined('MOODLE_INTERNAL') || die();

final class mock_playback_curl {

    private $recordings;
    private $testcase;

    public function __construct($testcase) {
        $this->recordings = [];
        $this->testcase = $testcase;
    }

    public function record($url, $options, $info, $body) {
        $this->recordings[] = [
            'url' => $url,
            'options' => $options,
            'info' => $info,
            'body' => $body,
        ];
    }

    private function request($url, $options = []) {
        $recording = array_shift($this->recordings);
        $this->testcase->assertEquals($recording['url'], $url);
        $this->testcase->assertEquals($recording['options'], $options);
        $this->info = $recording['info'];
        $this->errno = CURLE_OK;
        return $recording['body'];
    }

    public function get_info() {
        return $this->info;
    }

    public function get($url, $params = array(), $options = array()) {
        $options['CURLOPT_HTTPGET'] = 1;
        return $this->request($url, $options);
    }

    public function post($url, $params = '', $options = array()) {
        $options['CURLOPT_POST'] = 1;
        $options['CURLOPT_POSTFIELDS'] = $params;
        return $this->request($url, $options);
    }

}
