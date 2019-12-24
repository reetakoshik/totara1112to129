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
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @package totara
 * @subpackage totara_core/dialogs
 */

/**
 * Dialog content generator
 */

defined('MOODLE_INTERNAL') || die();

/**
 * How many search results to show before paginating
 *
 * @var integer
 */
define('DIALOG_SEARCH_NUM_PER_PAGE', 50);

/**
 * Maximum number of items to show at any one level of a dialog
 * before displaying the "use search instead" message
 */
define('TOTARA_DIALOG_MAXITEMS', 1000);

/**
 * Class for generating markup
 *
 * @access  public
 */
class totara_dialog_content {

    /**
     * Configuration constants
     */
    const TYPE_CHOICE_SINGLE    = 1;
    const TYPE_CHOICE_MULTI     = 2;



    /**
     * Configuration parameters
     */

    /**
     * Dialog overall type
     *
     * @access  public
     * @var     class constant
     */
    public $type = self::TYPE_CHOICE_SINGLE;


    /**
     * Language file to use for messages
     *
     * @access  public
     * @var     string
     */
    public $lang_file = 'totara_core';

    /**
     * PHP file to use for search tab content
     *
     * @access  public
     * @var     string
     */
    public $search_code = '/totara/core/dialogs/search.php';

    /**
     * Type of search to perform (generally relates to dialog type)
     *
     * @access  public
     * @var     string
     */
    public $searchtype = '';

    /**
     * Lang string to display when no items available
     *
     * @access  public
     * @var     string
     */
    public $string_nothingtodisplay = 'error:dialognotreeitems';

    /**
     * Select pane title lang string
     *
     * Set to an empty string if you do not want it to be printed
     *
     * @access  public
     * @var     string
     */
    public $select_title = '';


    /**
     * Selected pane title lang string
     *
     * Set to an empty string if you do not want it to be printed
     *
     * @access  public
     * @var     string
     */
    public $selected_title = '';


    /**
     * Selected pane html id
     *
     * @access  public
     * @var     string
     */
    public $selected_id = '';


    /**
     * Return markup for only the treeview, rather than the whole dialog
     *
     * @access  public
     * @var     boolean
     */
    public $show_treeview_only = false;


    /**
     * Items to display in the treeview
     *
     * @access  public
     * @var     array
     */
    public $items = array();


    /**
     * Place for storing custom data, potentially useful for sharing
     * with the search code
     */
    public $customdata = array();


    /**
     * Array of items that are parents (e.g. have children)
     *
     * Used for rendering the treeview
     *
     * @access  public
     * @var     array
     */
    public $parent_items = array();


    /**
     * Array of items that are disabled (e.g. unselectable)
     *
     * Used for rendering the treeview
     *
     * @access  public
     * @var     array
     */
    public $disabled_items = array();

    /**
     * Array of items that can be expanded, but can not be selected.
     *
     * However, sub items may be selectable so do not add 'unclickable' class which
     * fades them.
     *
     * These items must still be added to the $parent_items array for them to be expandable in the first place.
     *
     * @var array
     */
    public $expandonly_items = array();


    /**
     * Array of items that are already selected (e.g. appear in the selected pane)
     *
     * If set to null, use $disabled_items instead
     *
     * Used for rendering the treeview
     *
     * @access  public
     * @var     array
     */
    public $selected_items = null;


    /**
     * Array of items that are selected (e.g. appear in the selected pane) and cannot be removed
     *
     * Used for rendering the treeview
     *
     * @access  public
     * @var     array
     */
    public $unremovable_items = array();

    /**
     * Place for storing additional url parameters, for use with the search code
     */
    public $urlparams = array();

    /**
     * Keys to be added as data-$key attribute to items DOM. Values will be taken from provided items objects (if exist)
     * @var array
     */
    protected $datakeys = array();

    /**
     * @var bool When you have an expandable parent item, a folder icon will be shown if this is set to true.
     */
    protected $showfoldericons = true;

    /*
     * Used to set the context when certain permissions are checked for the current user.
     *
     * Many places in dialog code will not use this property and might, for example, only
     * use system_context.
     *
     * @var null|context
     */
    protected $context = null;

    /**
     * Generate markup from configuration and return
     *
     * @access  public
     * @return  string  $markup Markup to print
     */
    public function generate_markup() {
        global $OUTPUT;
        header('Content-Type: text/html; charset=UTF-8');

        // Skip container if only displaying search results
        if (optional_param('search', false, PARAM_BOOL)) {
            return $this->generate_search();
        }
        // Skip container if only displaying treeview
        if ($this->show_treeview_only) {
            return $this->generate_treeview();
        }

        $markup = html_writer::start_tag('div', array('class' => 'row-fluid'));

        // Open select container
        $width = ($this->type == self::TYPE_CHOICE_MULTI) ? 'span8' : 'span12';
        $markup .= html_writer::start_tag('div', array('class' => $width . ' select'));

        // Show select header
        if (!empty($this->select_title)) {
            $markup .= $OUTPUT->heading(get_string($this->select_title, $this->lang_file), 3);
        }

        $markup .= html_writer::start_tag('div', array('id' => 'dialog-tabs', 'class' => 'dialog-content-select'));

        $markup .= '<ul class="nav nav-tabs tabs dialog-nobind">';
        $markup .= '  <li><a href="#browse-tab">'.get_string('browse', 'totara_core').'</a></li>';
        if (!empty($this->search_code)) {
            $markup .= '  <li><a href="#search-tab">'.get_string('search').'</a></li>';
        }
        $markup .= '</ul>';

        // Display treeview
        $markup .= '<div id="browse-tab">';

        // Display any custom markup
        if (method_exists($this, '_prepend_markup')) {
            $markup .= $this->_prepend_markup();
        }

        $markup .= $this->generate_treeview();
        $markup .= '</div>';

        if (!empty($this->search_code)) {
            // Display searchview
            $markup .= '<div id="search-tab" class="dialog-load-within">';
            $markup .= $this->generate_search();
            $markup .= '<div id="search-results"></div>';
            $markup .= '</div>';
        }

        // Close select container
        $markup .= html_writer::end_tag('div');
        $markup .= html_writer::end_tag('div');

        // If multi-select, show selected pane
        if ($this->type === self::TYPE_CHOICE_MULTI) {
            $markup .= html_writer::start_tag('div', array('class' => 'span4 selected dialog-nobind', 'id' => $this->selected_id));

            // Show title
            if (!empty($this->selected_title)) {
                $markup .= $OUTPUT->heading(get_string($this->selected_title, $this->lang_file), 3);
            }

            // Populate pane
            $markup .= $this->populate_selected_items_pane($this->selected_items);

            $markup .= html_writer::end_tag('div');
        }

        // Close container for content
        $markup .= html_writer::end_tag('div');

        return $markup;
    }


    /**
     * Should we show the treeview root
     *
     * @access  protected
     * @return  boolean
     */
    protected function _show_treeview_root() {
        return !$this->show_treeview_only;
    }

    /**
     * Also add data-$key attributes to items.
     * It allows convenient addition of meta data added to element's DOM and accessible by JS dialog handlers
     * Keys must be alpha-numeric strings.
     * @param array $keys
     */
    public function proxy_dom_data(array $keys = null) {
        $this->datakeys = $keys;
    }

    /**
     * Generate treeview markup
     *
     * @access  public
     * @return  string  $html Markup for treeview
     */
    public function generate_treeview() {
        global $CFG;

        // Maximum number of items to load (at any one level)
        // before giving up and suggesting search instead
        $maxitems = TOTARA_DIALOG_MAXITEMS;

        if (isset($this->context)) {
            $context = $this->context;
        } else {
            $context = context_system::instance();
        }
        // Check if user has capability to view emails.
        $canviewemail = in_array('email', get_extra_user_fields($context));

        $html = '';

        $html .= !$this->show_treeview_only ? '<div class="treeview-wrapper dialog-nobind">' : '';
        $show_root = $this->_show_treeview_root();
        $html .= $show_root ? '<ul class="treeview filetree picker">' : '';

        if (is_array($this->items) && !empty($this->items)) {

            $total = count($this->items);
            $count = 0;

            if ($total > $maxitems) {
                $html .= '<li class="last"><span class="empty">';
                $html .= get_string('error:morethanxitemsatthislevel', 'totara_core', $maxitems);
                $html .= ' <a href="#search-tab" onclick="$(\'#dialog-tabs\').tabs(\'option\', \'active\', 1);return false;">';
                $html .= get_string('trysearchinginstead', 'totara_core');
                $html .= '</a>';
                $html .= '</span></li>'.PHP_EOL;
            }
            else {
                // Loop through elements
                foreach ($this->items as $element) {
                    ++$count;

                    // Initialise class vars
                    $li_class = '';
                    $div_class = '';
                    $span_class = '';

                    // If last element
                    if ($count == $total) {
                        $li_class .= ' last';
                    }

                    // If element has children
                    if (array_key_exists($element->id, $this->parent_items)) {
                        $li_class .= ' expandable';
                        $div_class .= ' hitarea expandable-hitarea';
                        if ($this->showfoldericons) {
                            $span_class .= ' folder';
                        }

                        if ($count == $total) {
                            $li_class .= ' lastExpandable';
                            $div_class .= ' lastExpandable-hitarea';
                        }
                    }

                    // Make disabled elements non-draggable and greyed out
                    if (array_key_exists($element->id, $this->disabled_items)) {
                        $span_class .= ' unclickable';
                    } else if (array_key_exists($element->id, $this->expandonly_items)) {
                        $span_class .= ' expandonly';
                    } else {
                        $span_class .= ' clickable';
                    }

                    $datalist = array();
                    foreach ($this->datakeys as $key) {
                        if (isset($element->$key)) {
                            $datalist[] = 'data-' . $key .'="' . htmlspecialchars($element->$key) . '"';
                        }
                    }
                    $datahtml = implode(' ', $datalist);

                    $html .= '<li class="'.trim($li_class).'" id="item_list_'.$element->id.'">';
                    $html .= '<div class="'.trim($div_class).'"></div>';
                    $html .= '<span id="item_'.$element->id.'" class="'.trim($span_class).'" ' . $datahtml . '>';

                    // Grab item display name
                    if (isset($element->fullname)) {
                        if (isset($element->email) && $canviewemail) {
                            $displayname = get_string('assignindividual', 'totara_program', $element);
                        } else {
                            $displayname = $element->fullname;
                        }
                    } elseif (isset($element->name)) {
                        $displayname = $element->name;
                    } else {
                        $displayname = '';
                    }

                    $html .= '<a href="#"';
                    if (!empty($element->hover)) {
                        $html .= ' title="'.format_string($element->hover).'"';
                    }
                    $html .= '>';
                    $html .= format_string($displayname);
                    $html .= '</a>';
                    $html .= '<span class="deletebutton">delete</span>';

                    $html .= '</span>';

                    if ($div_class !== '') {
                        $html .= '<ul style="display: none;"></ul>';
                    }
                    $html .= '</li>'.PHP_EOL;
                }
            }
        }
        else {
            $html .= '<li class="last"><span class="empty">';
            $html .= get_string($this->string_nothingtodisplay, $this->lang_file);
            $html .= '</span></li>'.PHP_EOL;
        }

        $html .= $show_root ? '</ul>' : '';
        $html .= !$this->show_treeview_only ? '</div>' : '';
        return $html;
    }


    /**
     * Default search interface, simply includes a url
     *
     * @access  public
     * @return  string  Markup
     */
    public function generate_search() {
        global $CFG;

        if (empty($this->search_code) || empty($this->searchtype)) {
            return '';
        }
        if (!defined('TOTARA_DIALOG_SEARCH')) {
            define('TOTARA_DIALOG_SEARCH', true);
        }
        ob_start();
        require("{$CFG->dirroot}{$this->search_code}");
        return ob_get_clean();
    }

    /**
    * Returns markup to be used in the selected pane of a multi-select dialog
    *
    * @param   $elements    array elements to be created in the pane
    * @return  $html
    */
    public function populate_selected_items_pane($elements) {

        if (empty($elements)) {
            return '';
        }

        $html = '';
        foreach ($elements as $element) {
            // Check if unremovable
            $class = '';
            if (in_array($element->id, array_keys($this->unremovable_items))) {
                $class .= 'unremovable ';
            }

            $datalist = array();
            foreach ($this->datakeys as $key) {
                if (isset($element->$key)) {
                    $datalist[] = 'data-' . $key .'="' . htmlspecialchars($element->$key) . '"';
                }
            }
            $datahtml = implode(' ', $datalist);

            $html .= '<div class="treeview-selected-item"><span id="item_'.$element->id.'" class="'.$class.'" ' . $datahtml .'>';
            $html .= '<a href="#">';
            $html .= format_string($element->fullname);
            $html .= '</a>';
            $html .= '<span class="deletebutton">delete</span>';
            $html .= '</span></div>';
        }
        return $html;
    }

    public function set_datakeys($datakeys) {
        $this->datakeys = $datakeys;
    }

    /**
     * Set the context for this dialog.
     *
     * Be aware that much of the code may still determine context in its own way. e.g. may only use
     * the system context.
     *
     * @param $context context to make permission checks against for current user.
     */
    public function set_context($context) {
        $this->context = $context;
    }
}


/**
 * Return markup for a simple picker in a dialog
 *
 * @param   array  $options  options/values
 * @param   mixed  $selected $options key for currently selected element
 * @param   string $class    CSS class of element
 * @param   array  $attrs    attributes to add into html ($class has precedence in case of conflict)
 * @return  $html
 */
function display_dialog_selector($options, $selected, $class, $attrs = array()) {
    $attrs['class'] = $class;
    $name = '';
    if (isset($attrs['name'])) {
        $name = $attrs['name'];
        unset($attrs['name']);
    }

    // Prepare id as it is done in html_writer::select.
    if (!isset($attrs['id']) && $name != '') {
            $id = 'menu' . $name;
            $id = str_replace('[', '', $id);
            $id = str_replace(']', '', $id);
            $attrs['id'] = $id;
    }

    $label = '';
    if (isset($attrs['label']) && isset($attrs['id'])) {
        $label .= html_writer::label($attrs['label'], $attrs['id'], false, array('class' => 'accesshide'));
        unset($attrs['label']);
    }

    return  $label . html_writer::select($options, $name, $selected, array('' => 'choosedots'), $attrs);
}
