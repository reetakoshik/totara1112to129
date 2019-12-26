<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings block
 *
 * @package    block_settings
 * @copyright  2010 Sam Hemelryk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_settings_renderer extends plugin_renderer_base {

    public function settings_tree(settings_navigation $navigation) {
        global $CFG;

        $count = 0;

        // Totara: check if admin navigation needs to be included.
        if (empty($CFG->legacyadminsettingsmenu)) {
            // We're going to create a new node, we can't use clone (it's not deep) and we can't remove children
            // as they are all handled by objects presently, so modifying one is modifying them all.
            $blocknav = navigation_node::create('Unexpected coding error', null, navigation_node::TYPE_ROOTNODE);
            // Copy the children that we want across to the new node.
            foreach ($navigation->children as $child) {
                if ($child->key === 'siteadministration' || $child->key === 'root') {
                    continue; // Bye now!
                }
                $blocknav->add_node($child);
            }
        } else {
            // Easy as, nothing to change here, just map navigation to blocknav.
            $blocknav = $navigation;
        }

        foreach ($blocknav->children as &$child) {
            $child->preceedwithhr = ($count!==0);
            if ($child->display) {
                $count++;
            }
        }
        $navigationattrs = array(
            'class' => 'block_tree list',
            'role' => 'tree',
            'data-ajax-loader' => 'block_navigation/site_admin_loader');
        $content = $this->navigation_node($blocknav, $navigationattrs);
        if (isset($blocknav->id) && !is_numeric($blocknav->id) && !empty($content)) {
            $content = $this->output->box($content, 'block_tree_box', $blocknav->id);
        }
        return $content;
    }

    /**
     * Build the navigation node.
     *
     * @param navigation_node $node the navigation node object.
     * @param array $attrs list of attributes.
     * @param int $depth the depth, default to 1.
     * @return string the navigation node code.
     */
    protected function navigation_node(navigation_node $node, $attrs=array(), $depth = 1) {
        $items = $node->children;

        // exit if empty, we don't want an empty ul element
        if ($items->count()==0) {
            return '';
        }

        // array of nested li elements
        $lis = array();
        $number = 0;
        foreach ($items as $item) {
            $number++;
            if (!$item->display) {
                continue;
            }
            $collapsed = false;
            $icon = '';

            $isbranch = ($item->children->count()>0  || $item->nodetype==navigation_node::NODETYPE_BRANCH);

            if ($isbranch) {
                $item->hideicon = true;
            }

            $content = $this->output->render($item);
            $id = $item->id ? $item->id : html_writer::random_id();
            $ulattr = ['id' => $id . '_group', 'role' => 'group'];
            $liattr = ['class' => [$item->get_css_type(), 'depth_'.$depth], 'tabindex' => '-1'];
            $pattr = ['class' => ['tree_item'], 'role' => 'treeitem'];
            $pattr += !empty($item->id) ? ['id' => $item->id] : [];
            $hasicon = (!$isbranch && $item->icon instanceof renderable);

            if ($isbranch) {
                $liattr['class'][] = 'contains_branch';
                if (!$item->forceopen || (!$item->forceopen && $item->collapse) || ($item->children->count() == 0
                        && $item->nodetype == navigation_node::NODETYPE_BRANCH)) {
                    $pattr += ['aria-expanded' => 'false'];

                    $icon = new \core\output\flex_icon('collapsed');
                    $icon = $this->render($icon);
                } else {
                    $pattr += ['aria-expanded' => 'true'];

                    $icon = new \core\output\flex_icon('expanded');
                    $icon = $this->render($icon);
                }
                if ($item->requiresajaxloading) {
                    $pattr['data-requires-ajax'] = 'true';
                    $pattr['data-loaded'] = 'false';
                } else {
                    $pattr += ['aria-owns' => $id . '_group'];
                }
            } else if ($hasicon) {
                $liattr['class'][] = 'item_with_icon';
                $pattr['class'][] = 'hasicon';
            }
            if ($item->isactive === true) {
                $liattr['class'][] = 'current_branch';
            }
            if (!empty($item->classes) && count($item->classes) > 0) {
                $pattr['class'] = array_merge($pattr['class'], $item->classes);
            }

            // class attribute on the div item which only contains the item content
            $pattr['class'][] = 'tree_item';
            if ($isbranch) {
                $pattr['class'][] = 'branch';
            } else {
                $pattr['class'][] = 'leaf';
            }

            $liattr['class'] = join(' ', $liattr['class']);
            $pattr['class'] = join(' ', $pattr['class']);

            if (isset($pattr['aria-expanded']) && $pattr['aria-expanded'] === 'false') {
                $ulattr += ['aria-hidden' => 'true'];
            }

            $content = html_writer::tag('p', $icon . $content, $pattr) . $this->navigation_node($item, $ulattr, $depth + 1);
            if (!empty($item->preceedwithhr) && $item->preceedwithhr===true) {
                $content = html_writer::empty_tag('hr') . $content;
            }
            $content = html_writer::tag('li', $content, $liattr);
            $lis[] = $content;
        }

        if (count($lis)) {
            if (empty($attrs['role'])) {
                $attrs['role'] = 'group';
            }
            return html_writer::tag('ul', implode("\n", $lis), $attrs);
        } else {
            return '';
        }
    }

    /**
     * Render search form for Bootstrap 3 based Totara themes.
     *
     * @param moodle_url $formtarget
     * @param string $searchvalue
     * @return string
     */
    public function search_form(moodle_url $formtarget, $searchvalue) {
        $content = html_writer::start_tag('form',
            array(
                'class' => 'adminsearchform',
                'method' => 'get',
                'action' => $formtarget,
                'role' => 'search',
            )
        );
        $content .= html_writer::start_div('input-group');
        $content .= html_writer::tag('label', s(get_string('searchinsettings', 'admin')), array('for'=>'adminsearchquery', 'class'=>'sr-only'));
        $content .= html_writer::empty_tag('input',
            array(
                'id' => 'adminsearchquery',
                'type' => 'text',
                'name' => 'query',
                'class' => 'form-control',
                'placeholder' => s(get_string('searchinsettings', 'admin')),
                'value' => s($searchvalue),
            )
        );
        $content .= html_writer::start_span('input-group-btn');
        $content .= html_writer::tag('button', s(get_string('go')), array('type' => 'submit', 'class' => 'btn btn-default'));
        $content .= html_writer::end_span();
        $content .= html_writer::end_div();
        $content .= html_writer::end_tag('form');
        return $content;
    }

}
