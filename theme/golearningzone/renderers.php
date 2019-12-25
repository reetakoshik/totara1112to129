<?php

defined('MOODLE_INTERNAL') || die();

require_once "{$CFG->dirroot}/totara/core/renderer.php";
require_once ($CFG->dirroot.'/theme/golearningzone/lib/Traits.php');

use GoLearningZone\Traits\Renderer as Renderer;

class theme_golearningzone_core_renderer extends core_renderer 
{
    use Renderer;
     public function mastheadglz(bool $hasguestlangmenu = true, bool $nocustommenu = false) {
        global $USER;

          if ($nocustommenu || !empty($this->page->layout_options['nototaramenu']) || !empty($this->page->layout_options['nocustommenu'])) {
              // No totara menu, or the old legacy no custom menu, in which case DO NOT generate the totara menu, its costly.
              $mastheadmenudata = new stdClass;
          } else {
              $mastheadmenudata = new stdClass;
               $menudata = totara_build_menu();
             $mastheadmenu = new totara_core\output\masthead_menu($menudata);
             // $mastheadmenudata = $mastheadmenu;
          }

        // $mastheadlogo = new totara_core\output\masthead_logo();

        $mastheaddata = new stdClass();
         //$mastheaddata->masthead_lang = $hasguestlangmenu && (!isloggedin() || isguestuser()) ? $this->output->lang_menu() : '';
         //$mastheaddata->masthead_logo = $mastheadlogo->export_for_template($this->output);
         $mastheaddata->masthead_menu = $mastheadmenudata;
         //$mastheaddata->masthead_plugins = $this->output->navbar_plugin_output();
         //$mastheaddata->masthead_search = $this->output->search_box();
        // // Even if we don't have a "navbar" we need this option, due to the poor design of the nonavbar option in the past.
         //$mastheaddata->masthead_toggle = $this->output->navbar_button();
         //$mastheaddata->masthead_usermenu = $this->output->user_menu();

        if (totara_core\quickaccessmenu\factory::can_current_user_have_quickaccessmenu()) {
            $menuinstance = totara_core\quickaccessmenu\factory::instance($USER->id);

            if (!empty($menuinstance->get_possible_items())) {
                $adminmenu = $menuinstance->get_menu();
                $quickaccessmenu = totara_core\output\quickaccessmenu::create_from_menu($adminmenu);
                $mastheaddata->masthead_quickaccessmenu = $quickaccessmenu->get_template_data();
            }
        }
        // $mastheaddata = new \stdClass();
        // $mastheaddata->name = 'Avinash Pastor';
        return $this->render_from_template('theme_golearningzone/mastheadglz', $mastheaddata);
    }

     public function glztop_menu($menudata, $parent=null, $selected_items=array()) {
       global $CFG;

       $menudata = totara_build_menu();
       $mastheadmenu = new totara_core\output\glztop_menu($menudata, $parent, $selected_items);
       
       $mastheadmenudata = $mastheadmenu;
        
       return $this->render_from_template('theme_golearningzone/glztop_menu', $mastheadmenudata);
   }
}
//die('test111111111');