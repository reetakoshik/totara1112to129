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
 * @author Yuliya Bozhko <yuliya.bozhko@totaralearning.com>
 *
 * @package block_course_navigation
 */

namespace block_course_navigation\output;

class renderer extends \plugin_renderer_base {

    /**
     * Returns the content of the course navigation tree.
     *
     * @param \navigation_node_collection $navigation
     *
     * @return string $content
     */
    public function course_navigation(\navigation_node_collection $navigation): string {
        $navigationattrs = [
            ['key' => 'class', 'value' => 'block_tree list',],
            ['key' => 'role', 'value' => 'tree',],
            ['key' => 'data-ajax-loader', 'value' => 'block_course_navigation/nav_loader',],
        ];
        $content = $this->navigation_node($navigation, $navigationattrs);

        return $content;
    }

    /**
     * Produces a navigation node for the navigation tree
     *
     * @param \navigation_node_collection $items
     * @param array $attrs
     * @param int $depth
     *
     * @return string
     */
    protected function navigation_node($items, $attrs = [], $depth = 1) {
        // Exit if empty, we don't want an empty ul element.
        if (count($items) === 0) {
            return '';
        }

        // Turn our navigation items into list items.
        $lis = [];
        foreach ($items as $item) {
            if (!$item->display && !$item->contains_active_node()) {
                continue;
            }
            $icon = new \core\output\flex_icon('spacer');

            $id = $item->id ? $item->id : \html_writer::random_id();
            $content = $item->get_content();
            $title = $item->get_title();
            $ulattr = [
                ['key' => 'id', 'value' => $id . '_group'],
                ['key' => 'role', 'value' => 'group'],
            ];
            $liattr = ['class' => [$item->get_css_type(), 'depth_' . $depth]];
            $pclasses = ['tree_item'];
            $pattr = [
                ['key' => 'role', 'value' => 'treeitem'],
            ];
            if (!empty($item->id)) {
                $pattr[] = ['key' => 'id', 'value' => $item->id];
            }
            $isbranch = $item->children->count() > 0 || ($item->has_children() && isloggedin());
            $hasicon = ((!$isbranch || $item->type == \navigation_node::TYPE_ACTIVITY || $item->type == \navigation_node::TYPE_RESOURCE) && $item->icon instanceof \renderable);

            if ($hasicon) {
                $liattr['class'][] = 'item_with_icon';
                $pclasses[] = 'hasicon';
                $icon = $this->output->render($item->icon);
                // Because an icon is being used we're going to wrap the actual content in a span.
                // This will allow designers to create columns for the content, as we've done in styles.css.
                $content = $icon . \html_writer::span($content, 'item-content-wrap');
            }
            if ($item->helpbutton !== null) {
                $content = trim($item->helpbutton) . \html_writer::tag('span', $content, ['class' => 'clearhelpbutton']);
            }
            if (empty($content)) {
                continue;
            }

            $attributes = ['tabindex' => '-1'];
            if ($title !== '') {
                $attributes['title'] = $title;
            }
            if ($item->hidden) {
                $attributes['class'] = 'dimmed_text';
            }
            if (is_string($item->action) || empty($item->action) ||
                $item->type === \navigation_node::TYPE_CATEGORY) {
                $content = \html_writer::tag('span', $content, $attributes);
            } else if ($item->action instanceof \action_link) {
                $link = $item->action;
                $link->text = $icon . \html_writer::span($link->text, 'item-content-wrap');
                $link->attributes = array_merge($link->attributes, $attributes);
                $content = $this->output->render($link);
            } else if ($item->action instanceof \moodle_url) {
                $content = \html_writer::link($item->action, $content, $attributes);
            }

            if ($isbranch) {
                $pclasses[] = 'branch';
                $liattr['class'][] = 'contains_branch';
                $expanded = ($item->has_children() && (!$item->forceopen || $item->collapse)) ? "false" : "true";
                $pattr[] = ['key' => 'aria-expanded', 'value' => $expanded];
                $icon = new \core\output\flex_icon($expanded === "true" ? 'expanded' : 'collapsed');
                $icon = $this->render($icon);
                $content = $icon . $content;
                if ($item->requiresajaxloading) {
                    $pattr[] = ['key' => 'data-requires-ajax', 'value' => 'true',];
                    $pattr[] = ['key' => 'data-loaded', 'value' => 'false',];
                    $pattr[] = ['key' => 'data-node-id', 'value' => $item->id,];
                    $pattr[] = ['key' => 'data-node-key', 'value' => $item->key,];
                    $pattr[] = ['key' => 'data-node-type', 'value' => $item->type,];
                } else {
                    $pattr[] = ['key' => 'aria-owns', 'value' => $id . '_group'];
                }

                if ($expanded === 'false') {
                    $ulattr[] = ['key' => 'aria-hidden', 'value' => 'true'];
                }
            }

            if ($item->isactive === true) {
                $liattr['class'][] = 'current_branch';
            }
            if (!empty($item->classes) && count($item->classes) > 0) {
                $pclasses = array_merge($pclasses, $item->classes);
            }

            $pattr[] = ['key' => 'class', 'value' => join(' ', $pclasses)];

            // Create the structure.
            $li = new \stdClass();
            $li->licontent = $this->navigation_node($item->children, $ulattr, $depth + 1);
            $li->liattrs[] = ['key' => 'class', 'value' => join(' ', $liattr['class'])];
            $li->pcontent = $content;
            $li->pattrs = $pattr;

            $lis[] = $li;
        }

        if (count($lis) === 0) {
            // There is still a chance, despite having items, that nothing had content and no list items were created.
            return '';
        }

        // We used to separate using new lines, however we don't do that now, instead we'll save a few chars.
        // The source is complex already anyway.
        $data = new \stdClass();
        $data->items = $lis;
        $data->ulattrs = $attrs;

        return $this->render_from_template('block_course_navigation/content', $data);
    }
}
