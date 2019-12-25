<?php

namespace GoLearningZone\Pages;

use GoLearningZone\Traits\Theme as ThemeTrait;

class Admin extends Base
{
    use ThemeTrait;

    public function render()
    {
        global $CFG;
        $template = 'theme_golearningzone/admin';
        //echo $_REQUEST['section'];die('test123');
        if($this->renderer->blocks('side-pre') == null) {
            $pre = false;
        } else {
            $pre = true;
        }
       
        $params = $this->getDefaultPageValues() + [
             'side-pre'  => $this->renderer->blocks('side-pre'),
             'pre' => $pre,
             'side-post' => $this->renderer->blocks('side-post'),
             'siteurl'   => $CFG->wwwroot
        ];
        //echo "<pre>";print_r($params);echo "</pre>";die('test123');
         return $this->renderer->render_from_template($template, $params);  
    } 

    private function blocks($name, $width = 12)
    {
         $renderer = $this->renderer;
         $block = $renderer->blocks($name);

          if (!$width || !$block) {
              return '';
          }

        return $renderer->render_from_template(
            'theme_golearningzone/front_page_block_wrapper',
            [
                'size'  => $width,
                'name'  => $name,
                'block' => $block,
            ]
        );
    }   
}
