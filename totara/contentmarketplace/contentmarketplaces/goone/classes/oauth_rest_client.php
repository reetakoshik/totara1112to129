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

final class oauth_rest_client extends rest_client {

    /** @var oauth $oauth */
    private $oauth = null;

    public function __construct($endpoint, $oauth, $curl = null) {
        $this->oauth = $oauth;
        parent::__construct($endpoint, $curl);
    }

    /**
     * @param string $resourcename
     * @param array $params
     * @param array $headers
     * @param array $options
     * @return mixed
     */
    public function get(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        try {
            return parent::get($resourcename, $params, $this->headers_with_auth($headers), $options);
        } catch (invalid_token_exception $e) {
            $this->oauth->token_refresh();
            return parent::get($resourcename, $params, $this->headers_with_auth($headers), $options);
        }
    }

    public function post(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        try {
            return parent::post($resourcename, $params, $this->headers_with_auth($headers), $options);
        } catch (invalid_token_exception $e) {
            $this->oauth->token_refresh();
            return parent::post($resourcename, $params, $this->headers_with_auth($headers), $options);
        }
    }

    public function put(string $resourcename, array $params = [], array $headers = [], array $options = []) {
        try {
            return parent::put($resourcename, $params, $this->headers_with_auth($headers), $options);
        } catch (invalid_token_exception $e) {
            $this->oauth->token_refresh();
            return parent::put($resourcename, $params, $this->headers_with_auth($headers), $options);
        }
    }

    public function headers_with_auth($headers) {
        $headers[] = 'Authorization: Bearer ' . $this->oauth->config->get('oauth_access_token');
        return $headers;
    }

}
