<?php
require_once($CFG->dirroot.'/blocks/video/lib.php');

class block_video_edit_form extends block_edit_form 
{


    protected function specific_definition($mform) 
    {
        global $CFG;

        // $mform->addElement(
        //     'filemanager', 
        //     'config_video', 
        //     get_string('image', 'block_video'),
        //     null,
        //     [
        //         'subdirs'        => 0, 
        //         'maxbytes'       => 5000000, 
        //         'maxfiles'       => 2,
        //         'accepted_types' => ['.png', '.jpg', '.gif'] 
        //     ]
        // );

        $mform->addElement('editor', 'config_video', 'video', ['rows' => 7],
            video_summary_field_options());


        $mform->addHelpButton('config_video', 'image', 'block_video');

        /*$mform->addElement(
            'text', 
            'config_video_caption', 
            get_string('image-caption', 'block_video')
        );
        $mform->setType('config_video_caption', PARAM_RAW);
        $mform->addHelpButton('config_video_caption', 'image-caption', 'block_video');*/

        $mform->addElement(
            'text', 
            'config_video', 
            get_string('image-link', 'block_video')
        );
        $mform->setType('config_video', PARAM_RAW);
        $mform->addHelpButton('config_video', 'image-link', 'block_video');

        $mform->addElement(
            'advcheckbox', 
            'config_new_window', 
            get_string('open-in-new-window', 'block_video')
        );
        $mform->setType('config_new_window', PARAM_INT);
        $mform->addHelpButton('config_new_window', 'open-in-new-window', 'block_video');

        $mform->addElement(
            'select', 
            'config_color', 
            get_string('header-color', 'block_video'), 
            [
                '-1'      => get_string('color-default', 'block_video'),
                '#d0463c' => get_string('color-red',     'block_video'), 
                '#37465d' => get_string('color-blue',    'block_video'),
                '#2d2d37' => get_string('color-dark',    'block_video')
            ]
        );
        $mform->addHelpButton('config_color', 'header-color', 'block_video');
    }

    function set_data($defaults) 
    {
        if (empty($entry->id)) {
            $entry = new stdClass;
            $entry->id = null;
        }

        $draftitemid = file_get_submitted_draft_itemid('config_video');

        file_prepare_draft_area(
            $draftitemid, 
            $this->block->context->id, 
            'block_video', 
            'content', 
            0,
            ['subdirs' => true]
        );

        $defaults->image = $draftitemid;

        parent::set_data($defaults);

        if ($data = parent::get_data() && $this->draftfileExist($draftitemid)) {
            
            file_save_draft_area_files(
                $draftitemid, 
                $this->block->context->id, 
                'block_video', 
                'content', 
                0, 
                ['subdirs' => true]
            );
        }
    }

    private function draftfileExist($draftitemid)
    {
        global $USER;
        
        $usercontext = context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files(
               $this->block->context->id, 
                'block_video', 
                'content',
                 0
             );
        if($files){
           $fs = get_file_storage();
           $fs->delete_area_files(
            $this->block->context->id, 
                'block_video', 
                'content',
                 0
        );}
           
        $fs = get_file_storage();
        $draftfiles = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id');

        foreach ($draftfiles as $file) {
            if (!$file->is_directory()) {
                return true;
            }
        }

        return false;
    }


   
}
