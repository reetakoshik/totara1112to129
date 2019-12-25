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
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package contentmarketplace_goone
 */

namespace contentmarketplace_goone\workflow\totara_contentmarketplace\exploremarketplace;

defined('MOODLE_INTERNAL') || die();

/**
 * Go1 explore marketplace workflow implementation.
 */
class goone extends \totara_workflow\workflow\base {

    public function get_name(): string {
        return get_string('explorego1marketplace', 'contentmarketplace_goone');
    }

    public function get_description(): string {
        return get_string('explorego1marketplacedesc', 'contentmarketplace_goone');
    }

    public function get_image(): ?\moodle_url {
        return new \moodle_url('/totara/contentmarketplace/marketplaces/goone/pix/logo.png');
    }

    protected function get_workflow_url(): \moodle_url {
        return new \moodle_url('/totara/contentmarketplace/explorer.php', ['marketplace' => 'goone']);
    }

    public function can_access(): bool {
        // Check Go1 marketplace plugin is enabled.
        /** @var \totara_contentmarketplace\plugininfo\contentmarketplace $plugin */
        $plugin = \core_plugin_manager::instance()->get_plugin_info("contentmarketplace_goone");
        if ($plugin === null || !$plugin->is_enabled()) {
            return false;
        }
        return true;
    }
}
