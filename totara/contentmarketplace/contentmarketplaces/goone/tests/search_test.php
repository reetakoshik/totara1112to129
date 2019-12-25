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

use contentmarketplace_goone\search;

defined('MOODLE_INTERNAL') || die();

/**
 * Test search class
 *
 * @group totara_contentmarketplace
 */
class contentmarketplace_goone_search_testcase extends basic_testcase {

    /**
     * @dataProvider pricing_provider
     */
    public function test_price($course, $expected) {
        $price = search::price($course);
        $this->assertSame($expected, $price);
    }

    public function pricing_provider() {
        return [
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": -1,
                        "package": "premium"
                    }
                }'),
                'Included'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": 10,
                        "package": "premium"
                    }
                }'),
                'Included'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 0,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": -1,
                        "package": "premium"
                    }
                }'),
                'Included'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": 0,
                        "package": "premium"
                    }
                }'),
                'A$1,234.50'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 0,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": 0,
                        "package": "premium"
                    }
                }'),
                'Free'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 0,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'Free'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 0,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'A$1,234.50'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 10,
                        "tax_included": true
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'A$1,234.50'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 10,
                        "tax_included": false
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'A$1,234.50 (+10% tax)'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 1234.5,
                        "tax": 0,
                        "tax_included": false
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'A$1,234.50'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": "AUD",
                        "price": 0,
                        "tax": 10,
                        "tax_included": false
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                'Free'
            ],
            [
                json_decode('{
                    "pricing": {
                        "currency": null,
                        "price": null,
                        "tax": null,
                        "tax_included": null
                    },
                    "subscription": {
                        "licenses": null,
                        "package": null
                    }
                }'),
                ''
            ],
        ];
    }




}
