<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage totara_question
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once('lib.php');

/**
 * Output renderer for totara_question module
 */
class totara_question_renderer extends plugin_renderer_base {

    /**
     * Adds review items to the given form.
     *
     * @param MoodleQuickForm $form
     * @param array $items
     * @param review $review
     */
    public function add_review_items($form, $items, review $review) {
        foreach ($items as $scope) {
            foreach ($scope as $itemgroup) {
                $this->add_review_item($form, $itemgroup, $review);
            }
        }
    }

    /**
     * Adds review item to the given form.
     *
     * @param MoodleQuickForm $form
     * @param array $itemgroup
     * @param review $review
     */
    public function add_review_item($form, $itemgroup, review $review) {
        global $CFG, $DB;

        require_once($CFG->libdir.'/form/textarea.php');
        require_once($CFG->libdir.'/form/static.php');

        $text_area_options = array('cols' => '90', 'rows' => '5');
        $otherroles = $review->roleinfo;
        $form_prefix = $review->get_prefix_form();
        $prefix = $review->storage->prefix;

        if ($review->cananswer) {
            $currentuseritems = $itemgroup[$review->answerid];
        }

        // Item title.
        $anyitemset = reset($itemgroup);
        $anyitem = reset($anyitemset);
        if (isset($anyitem->planname)) {
            $a = new stdClass();
            $a->fullname = format_string($anyitem->fullname);
            $a->planname = format_string($anyitem->planname);
            $title = get_string('reviewnamewithplan', 'totara_question', $a);
        } else {
            $title = format_string($anyitem->fullname);
        }

        // Link to more info about the item.
        $details = '';
        if ($review->can_view_more_info($itemgroup)) {
            // We only include this if not printing (that includes adding to a snapshot pdf).
            // If this question is returned via ajax, we may not have an action element, we just need to assume
            // that it is not being printed if no element is present.
            if (!($form->elementExists('action') and ($form->getElementValue('action') === 'print'))) {

                $infourl = $review->get_more_info_url($itemgroup);
                $detailstext = get_string('viewdetails', 'totara_question');
                $detailstext .= html_writer::span(get_string('detailsof', 'totara_question', $title), 'sr-only');
                $detailstext .= $this->output->render(new \core\output\flex_icon('external-link-square',
                    array('alt' => get_string('opensinnewwindow', 'totara_question'))));

                $details .= html_writer::link($infourl, $detailstext, array('target' => '_blank'));
            }
        }

        // Delete button.
        $deletelink = '';
        if ($review->can_select_items() && $review->can_delete_item($itemgroup)) {
            $deleteurl = new moodle_url("/totara/$prefix/ajax/removeitem.php",
                array('id' => reset($currentuseritems)->id, 'sesskey' => sesskey()));
            $deletelink .= html_writer::start_span('totara-question-review-delete');

            $deletelinktext = get_string('remove', 'totara_question')
                . html_writer::span(get_string('removethis', 'totara_question', $title), 'sr-only');
            $deletelink .= html_writer::link($deleteurl, $deletelinktext, array('data-reviewitemid' => reset($currentuseritems)->id));

            $deletelink .= html_writer::end_span();
        }

        // Clearfix added as otherwise long item names will prevent the extra links from being clickable.
        $extralinks = html_writer::span( $details . $deletelink . html_writer::div('', 'clearfix'),
            $form_prefix . '_' . $prefix . '_review' . ' totara-question-review-extralinks');

        // Start a new div so that we can identify it for deletion.
        if ($review->cananswer) {
            $cssid_reviewitem = 'id_question-review-item-' . reset($currentuseritems)->id;
        } else {
            $cssid_reviewitem = 'id_question-review-item-0';
        }
        $form->addElement('html', html_writer::start_div('question-review-item',
            array('id' => $cssid_reviewitem)));
        $form->addElement('html', html_writer::div(html_writer::tag('h3', $title) . $extralinks, 'totara-question-review-item-title clearfix'));

        if ($review->cananswer) {
            $review->add_item_specific_edit_elements($form, reset($currentuseritems));
        } else {
            $review->add_item_specific_edit_elements($form, $anyitem);
        }

        // Prepare for multifield headers.
        $multifield = $review->param1;
        if ($multifield) {
            $scalevalues = $DB->get_records($prefix . '_scale_value',
                    array($prefix .'scaleid' => $review->param1), 'id');
            $form->addElement('html', '<div class="review-multifield">');
        }

        if ($review->cananswer) {
            if (!empty($review->viewers)) {
                $viewersstring = get_string('visibleto', 'totara_question', implode(', ', $review->viewers));
                $form->addElement('html', html_writer::tag('p', $viewersstring, array('class'=>'visibleto-review')));
            }

            $currentuseritems = $itemgroup[$review->answerid];
            if ($review->viewonly) {
                if ($multifield) {
                    $content = '';
                    foreach ($scalevalues as $scalevalue) {
                        if ($content != '') {
                            $content .= html_writer::empty_tag('br');
                        }
                        $content .= html_writer::tag('b', format_string($scalevalue->name));
                        $content .= html_writer::empty_tag('br');
                        if ($currentuseritems[$scalevalue->id]->content != '') {
                            $content .= format_string($currentuseritems[$scalevalue->id]->content);
                        } else {
                            $content .= html_writer::tag('em', get_string('notanswered', 'totara_question'));
                        }
                    }
                } else {
                    $content = format_string($currentuseritems[0]->content);
                }

                if (!empty(trim($content))) {
                    $form->addElement(new MoodleQuickForm_static('', get_string('youranswer', 'totara_question'), $content));
                }
            } else {
                if ($multifield) {
                    $youranswerlabel = get_string('youranswer', 'totara_question');
                    $count = 0;
                    foreach ($scalevalues as $scalevalue) {
                        $item_form_element_name = $form_prefix . '_reviewitem_' . $currentuseritems[$scalevalue->id]->id;
                        $form->addElement(new MoodleQuickForm_static($item_form_element_name . '_label', $youranswerlabel,
                                html_writer::tag('b', format_string($scalevalue->name))));
                        $formelement = $form->addElement(new MoodleQuickForm_textarea(
                                $item_form_element_name,
                                '',
                                $text_area_options + ['data-multifield' => $count])
                        );
                        $youranswerlabel = '';
                        $count ++;
                    }
                } else {
                    $formelement = $form->addElement(
                            new MoodleQuickForm_textarea($form_prefix . '_reviewitem_' . $currentuseritems[0]->id,
                            get_string('youranswer', 'totara_question'), $text_area_options));
                }
            }
        }

        if ($multifield) {
            $form->addElement('html', '</div>');
        }

        foreach ($otherroles as $role) {
            $content = '';
            if ($multifield) {
                foreach ($scalevalues as $scalevalue) {
                    if ($content != '') {
                        $content .= html_writer::empty_tag('br');
                    }
                    $content .= html_writer::tag('b', format_string($scalevalue->name));
                    $content .= html_writer::empty_tag('br');
                    if (isset($itemgroup[$role->answerid][$scalevalue->id]->content) &&
                             ($itemgroup[$role->answerid][$scalevalue->id]->content != '')) {
                        $content .= format_string($itemgroup[$role->answerid][$scalevalue->id]->content);
                    } else {
                        $content .= html_writer::tag('em', get_string('notanswered', 'totara_question'));
                    }
                }
            } else {
                if (isset($itemgroup[$role->answerid][0]->content) &&
                         ($itemgroup[$role->answerid][0]->content != '')) {
                    $content .= format_string($itemgroup[$role->answerid][0]->content);
                } else {
                    $content .= html_writer::tag('em', get_string('notanswered', 'totara_question'));
                }
            }
            $form->addElement('html', $role->userimage);
            $form->addElement('static', '', $role->label, $content);
        }

        // Close the div which contains all the stuff that needs to be deleted when the delete button is pushed.
        $form->addElement('html', html_writer::end_div());
    }

}
