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

final class mock_curl {

    const MOCK_REQUESTS = [
        'GET /account'                                                                                                                => '/account/GET.json',
        'GET /configuration'                                                                                                          => '/configuration/GET.json',
        'PUT /configuration'                                                                                                          => '/configuration/PUT.json',
        'GET /learning-objects/1873868/scorm'                                                                                         => '/learning-objects/1873868/scorm/GET.zip',
        'GET /learning-objects/29271/scorm'                                                                                           => '/learning-objects/29271/scorm/GET.zip',
        'GET /learning-objects/1916572/scorm'                                                                                         => '/learning-objects/1916572/scorm/GET.zip',
        'GET /learning-objects/1881379/scorm'                                                                                         => '/learning-objects/1881379/scorm/GET.zip',
        'GET /learning-objects/1868492/scorm'                                                                                         => '/learning-objects/1868492/scorm/GET.zip',
        'GET /learning-objects?limit=0&event=false'                                                                                   => '/learning-objects/GET-count-all.json',
        'GET /learning-objects?collection=default&limit=0&event=false'                                                                => '/learning-objects/GET-count-collection.json',
        'GET /learning-objects?subscribed=true&limit=0&event=false'                                                                   => '/learning-objects/GET-count-subscription.json',
        'GET /learning-objects?sort=created%3Adesc&offset=0&limit=0&facets=tag%2Clanguage%2Cinstance&collection=default&event=false'  => '/learning-objects/GET-count-with-facets-collection.json',
        'GET /learning-objects?sort=created%3Adesc&offset=0&limit=0&facets=tag%2Clanguage%2Cinstance&subscribed=true&event=false'     => '/learning-objects/GET-count-with-facets-subscription.json',
        'GET /learning-objects?sort=created%3Adesc&offset=0&limit=0&facets=tag%2Clanguage%2Cinstance&event=false'                     => '/learning-objects/GET-count-with-facets-all.json',
        'GET /learning-objects?sort=created%3Adesc&offset=0&limit=48&facets=tag%2Clanguage%2Cinstance&event=false'                    => '/learning-objects/GET-listing-all.json',
        'GET /learning-objects?offset=0&limit=0&facets=tag%2Clanguage%2Cinstance&event=false'                                         => '/learning-objects/GET-count-with-facets-all.json',
        'POST /oauth/token'                                                                                                           => '/oauth/token/POST.json',
    ];

    public static function get_base_filename($url, $options) {
        $method = self::get_method($options);
        if (\core_text::strpos($url, api::ENDPOINT) === 0) {
            $name = \core_text::substr($url, \core_text::strlen(api::ENDPOINT) + 1);
        } elseif (\core_text::strpos($url, oauth::ENDPOINT) === 0) {
            $name = \core_text::substr($url, \core_text::strlen(oauth::ENDPOINT) + 1);
        } else {
            throw new \Exception("Unknown host for: $url");
        }
        $request = $method . ' /' . $name;
        if (array_key_exists($request, self::MOCK_REQUESTS)) {
            return self::MOCK_REQUESTS[$request];
        } else {
            throw new \Exception("Missing mock curl response for request: $url");
        }
    }

    public static function get_method($options) {
        if (array_key_exists('CUSTOMREQUEST', $options)) {
            return $options['CUSTOMREQUEST'];
        } elseif (array_key_exists('CURLOPT_HTTPGET', $options)) {
            return "GET";
        } elseif (array_key_exists('CURLOPT_POST', $options)) {
            return "POST";
        } else {
            throw new \Exception("Unknown HTTP method for options: " . json_encode($options));
        }
    }

    public static function get_extension($options) {
        if (isset($options['HTTPHEADER'])) {
            foreach ($options['HTTPHEADER'] as $header) {
                if (\core_text::strpos(\core_text::strtolower($header), 'accept: ') === 0) {
                    return \core_text::strtolower(explode('/', $header)[1]);
                }
            }
        }
        return 'json';
    }

    public static function get_content_type($path) {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'json':
                return 'application/json';
            default:
                return 'application/octet-stream';
        }
    }

    public static function validate_oauth($url, $options) {
        if (\core_text::strpos($url, oauth::ENDPOINT) === 0) {
            return true;
        }
        if (array_key_exists('HTTPHEADER', $options)) {
            foreach ($options['HTTPHEADER'] as $header) {
                if ($header === 'Authorization: Bearer --ACCESS-TOKEN--') {
                    return true;
                }
            }
        }
        throw new \Exception("Missing OAuth Access Token");
    }

    private function request($url, $options = array()) {
        global $CFG;

        self::validate_oauth($url, $options);

        $basename = self::get_base_filename($url, $options);
        $path = $CFG->dirroot . "/totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures$basename";
        $path = clean_param($path, PARAM_PATH);
        if (!file_exists($path)) {
            throw new \Exception("File for mock curl response does not exist: $path");
        }

        $this->info = [
            'url' => $url,
            'http_code' => 200,
            'content_type' => self::get_content_type($path),
        ];
        $this->errno = CURLE_OK;
        return file_get_contents($path);
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

    public function get_info() {
        return $this->info;
    }

}
