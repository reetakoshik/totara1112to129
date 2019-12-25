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

    private static $mock_requests = null;

    private static function url($method, $path, $params = null) {
        if (is_null($params)) {
            return $method . ' ' . $path;
        } else {
            return $method . ' ' . $path . '?' . http_build_query($params, null, '&');
        }
    }

    private static function mock_requests() {
        if (is_null(self::$mock_requests)) {
            self::$mock_requests = [
                self::url('GET', '/account')
                => '/account/GET.json',

                self::url('GET', '/configuration')
                => '/configuration/GET.json',

                self::url('PUT', '/configuration')
                => '/configuration/PUT.json',

                self::url('GET', '/learning-objects/1873868/scorm')
                => '/learning-objects/1873868/scorm/GET.zip',

                self::url('GET', '/learning-objects/29271/scorm')
                => '/learning-objects/29271/scorm/GET.zip',

                self::url('GET', '/learning-objects/1916572/scorm')
                => '/learning-objects/1916572/scorm/GET.zip',

                self::url('GET', '/learning-objects/1881379/scorm')
                => '/learning-objects/1881379/scorm/GET.zip',

                self::url('GET', '/learning-objects/1868492/scorm')
                => '/learning-objects/1868492/scorm/GET.zip',

                self::url('GET', '/learning-objects', [
                    'limit' => '0',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-all.json',

                self::url('GET',  '/learning-objects', [
                    'collection' => 'default',
                    'limit' => '0',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-collection.json',

                self::url('GET', '/learning-objects', [
                    'subscribed' => 'true',
                    'limit' => '0',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-subscription.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-subscription.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-all.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-all.json',

                self::url('GET', '/learning-objects', [
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-all.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-subscription.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-tag-technology.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-subscription-tag-technology.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection-tag-technology.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'tags[1]' => 'Communication',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-tag-technology-communication.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'tags[1]' => 'Communication',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-subscription-tag-technology-communication.json',

                self::url('GET',  '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'tags[1]' => 'Communication',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection-tag-technology-communication.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Communication',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-tag-communication.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Communication',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-subscription-tag-communication.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Communication',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection-tag-communication.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-tag-technology.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-subscription-tag-technology.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-subscription-tag-technology-language-ja.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-tag-technology-language-ja.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection-tag-technology-language-ja.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '48',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'language[1]' => 'en',
                    'subscribed' => 'true',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-listing-subscription-tag-technology-language-ja-en.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'language[1]' => 'en',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-tag-technology-language-ja-en.json',

                self::url('GET', '/learning-objects', [
                    'sort' => 'created:desc',
                    'offset' => '0',
                    'limit' => '0',
                    'facets' => 'tag,language,instance',
                    'tags[0]' => 'Technology',
                    'language[0]' => 'ja',
                    'language[1]' => 'en',
                    'collection' => 'default',
                    'event' => 'false',
                ])
                => '/learning-objects/GET-count-with-facets-collection-tag-technology-language-ja-en.json',

                self::url('POST', '/oauth/token')
                => '/oauth/token/POST.json',
            ];
        }
        return self::$mock_requests;
    }

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
        if (array_key_exists($request, self::mock_requests())) {
            return self::mock_requests()[$request];
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
        $path = "/totara/contentmarketplace/contentmarketplaces/goone/tests/behat/fixtures$basename";
        $path = $CFG->dirroot . clean_param($path, PARAM_PATH);
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
