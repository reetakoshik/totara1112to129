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
 * @author Alastair Munro <alastair.munro@totaralearning.com>
 * @package block_admin_subnav
 */
define(['core/yui', 'core/tree'], function(Y, Tree) {
    return {
        init: function(instanceid) {
            var subnavTree = new Tree(".block_admin_subnav .block_tree");
            subnavTree.finishExpandingGroup = function(item) {
                Tree.prototype.finishExpandingGroup.call(this, item);
                Y.use('moodle-core-event', function() {
                    Y.Global.fire(M.core.globalEvents.BLOCK_CONTENT_UPDATED, {
                        instanceid: instanceid
                    });
                });
            };
            subnavTree.collapseGroup = function(item) {
                Tree.prototype.collapseGroup.call(this, item);
                Y.use('moodle-core-event', function() {
                    Y.Global.fire(M.core.globalEvents.BLOCK_CONTENT_UPDATED, {
                        instanceid: instanceid
                    });
                });
            };
        }
    };
});
