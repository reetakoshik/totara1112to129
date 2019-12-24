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
 * @author Aleksandr Baishev <aleksandr.baishev@@totaralearning.com>
 * @package mod_chat
 */

namespace mod_chat\userdata;


/**
 * Class sessions_helper to perform purge of chat userdata user session
 *
 * @package mod_chat\userdata
 */
final class sessions_helper extends chat_userdata_helper {
    /**
     * Purge chat user sessions
     *
     * @return bool
     */
    public function purge() {

        [$where, $joins, $params] = $this->prepare_query('target.chatid');

        $sql = "SELECT target.id FROM {chat_users} target $joins WHERE $where";

        return $this->db->delete_records_list('chat_users', 'id',
            array_keys($this->db->get_records_sql($sql, $params)));
    }
}