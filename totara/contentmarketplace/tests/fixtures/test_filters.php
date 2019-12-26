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
 * @package totara_contentmarketplace
 */

/**
 * This fixture facilitates testing filters.
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

$displaydebugging = false;
if (!defined('BEHAT_SITE_RUNNING') || !BEHAT_SITE_RUNNING) {
    if (debugging()) {
        $displaydebugging = true;
    } else {
        throw new coding_exception('Invalid access.');
    }
}

$data = [
    'filters' => [
        [
            'name' => 'availability',
            'label' => 'Availability',
            'module' => 'totara_contentmarketplace/filter_radios',
            'showcounts' => true,
        ], [
            'name' => 'language',
            'label' => 'Language',
            'template' => 'totara_contentmarketplace/filter_checkboxes_searchable_init',
            'module' => 'totara_contentmarketplace/filter_checkboxes_searchable',
            'showcounts' => true,
        ], [
            'name' => 'tags',
            'label' => 'Tags',
            'template' => 'totara_contentmarketplace/filter_checkboxes_searchable_init',
            'module' => 'totara_contentmarketplace/filter_checkboxes_searchable',
            'showcounts' => false,
        ]
    ]
];

$js = 'require(["jquery", "totara_contentmarketplace/filters"], function($, Filters) {

    var test = {};

    test.filters = null;

    test.init = function(selector) {
        var self = this;
        this.filters = new Filters(selector, this.onFitler.bind(this), "TEST");
        this.filters.fetchFilterSeeds = this.fetchFilterSeeds.bind(this);

        $("#set-counts").on("click", function() {
            var counts = [
                {
                    name: "language",
                    values: {
                        "alpha": self.randomCount().toLocaleString(),
                        "beta": self.randomCount().toLocaleString(),
                        "gamma": self.randomCount().toLocaleString()
                    }
                }, {
                    name: "availability",
                    values: {
                        "all": self.randomCount().toLocaleString(),
                        "subscribed": self.randomCount().toLocaleString()
                    }
                }

            ];
            self.filters.setCounts(counts);
        });

        $("#log-values").on("click", function() {
            window.console.log(self.filters.getValues());
        });
    };

    test.randomCount = function () {
        var length = Math.floor(Math.random() * Math.floor(6)) + 1;
        return Math.floor(Math.random() * Math.pow(10, length));
    };

    test.onFitler = function() {
        window.console.log("onFitler() was called");
    };

    test.fetchFilterSeeds = function() {
        var deferred = $.Deferred();
        setTimeout(function() {
            var seeds = [
                {
                    "name": "availability",
                    "options": [
                        {
                            "htmlid": "tcm-filter-availability-0",
                            "label": "All",
                            "value": "all",
                            "checked": false
                        },
                        {
                            "htmlid": "tcm-filter-availability-1",
                            "label": "Subscription (with label long enough that it will probably get truncated)",
                            "value": "subscribed",
                            "checked": true
                        },
                        {
                            "htmlid": "tcm-filter-availability-2",
                            "label": "Collection (always zero)",
                            "value": "collection",
                            "checked": false
                        }
                    ]
                },
                {
                    "name": "language",
                    "options": {
                        "alpha": {
                            "htmlid": "tcm-filter-language-0",
                            "value": "alpha",
                            "label": "Alpha",
                            "checked": false
                        },
                        "beta": {
                            "htmlid": "tcm-filter-language-1",
                            "value": "beta",
                            "label": "Beta (with label long enough that it will probably get truncated)",
                            "checked": false
                        },
                        "gamma": {
                            "htmlid": "tcm-filter-language-2",
                            "value": "gamma",
                            "label": "Gamma",
                            "checked": false
                        },
                        "delta": {
                            "htmlid": "tcm-filter-language-3",
                            "value": "delta",
                            "label": "Delta (always zero)",
                            "checked": false
                        }
                    }
                },
                {
                    "name": "tags",
                    "options": {
                        "1": {
                            "htmlid": "tcm-filter-tags-0",
                            "value": "1",
                            "label": "One",
                            "checked": false
                        },
                        "2": {
                            "htmlid": "tcm-filter-tags-1",
                            "value": "2",
                            "label": "Two (with label long enough that it will probably get truncated)",
                            "checked": false
                        },
                        "3": {
                            "htmlid": "tcm-filter-tags-2",
                            "value": "3",
                            "label": "Three",
                            "checked": false
                        },
                        "4": {
                            "htmlid": "tcm-filter-tags-3",
                            "value": "4",
                            "label": "Four",
                            "checked": false
                        },
                        "5": {
                            "htmlid": "tcm-filter-tags-4",
                            "value": "5",
                            "label": "Five",
                            "checked": false
                        },
                        "6": {
                            "htmlid": "tcm-filter-tags-5",
                            "value": "6",
                            "label": "Six",
                            "checked": false
                        }
                    }
                }
            ];
            deferred.resolve(seeds);
        }, 200);

        return deferred.promise();
    };

    test.init(".tcm-explorer");
});
';

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('noblocks');
$PAGE->set_url('/totara/contentmarketplace/tests/fixtures/test_filters.php');

$PAGE->set_title("Content Marketplace filter testing page");
$PAGE->set_heading("Content Marketplace filter testing page");

$PAGE->requires->js_amd_inline($js);

echo $OUTPUT->header();
echo $OUTPUT->heading('Content Marketplace filter testing page');

if ($displaydebugging) {
    // This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
    $msg = 'This page only exists to facilitate acceptance testing, if you are here for any other reason please file an improvement request.';
    echo $OUTPUT->notification($msg, \core\output\notification::NOTIFY_WARNING);
    // We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
    debugging('This is a developer resource, please contact your system admin if you have arrived here by mistake.', DEBUG_DEVELOPER);
}

echo html_writer::start_div('row tcm-explorer');
echo html_writer::start_div('col-md-3 col-sm-4 col-xs-12');
echo $OUTPUT->render_from_template('totara_contentmarketplace/filters', $data);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::tag('button', 'Set counts', ['id' => 'set-counts']) . ' ';
echo html_writer::tag('button', 'Log values', ['id' => 'log-values']);

echo $OUTPUT->footer();
