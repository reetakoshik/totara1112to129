<?php

class block_image_link_edit_form extends block_edit_form 
{
    protected function specific_definition($mform) 
    {
        global $CFG;

        $mform->addElement(
            'filemanager', 
            'config_image', 
            get_string('image', 'block_image_link'),
            null,
            [
                'subdirs'        => 0, 
                'maxbytes'       => 5000000, 
                'maxfiles'       => 2,
                'accepted_types' => ['.png', '.jpg', '.gif'] 
            ]
        );
        $mform->addHelpButton('config_image', 'image', 'block_image_link');

       $mform->addElement(
            'hidden', 
            'config_image_caption_update', 
            get_string('image-caption', 'block_image_link')
        );
       $mform->setDefault('config_image_caption_update', 'update');
        //$mform->setType('config_image_caption', PARAM_RAW);
        //$mform->addHelpButton('config_image_caption', 'image-caption', 'block_image_link');*/

        $mform->addElement(
            'text', 
            'config_image_link', 
            get_string('image-link', 'block_image_link')
        );
        $mform->setType('config_image_link', PARAM_RAW);
        $mform->addHelpButton('config_image_link', 'image-link', 'block_image_link');

        $mform->addElement(
            'advcheckbox', 
            'config_new_window', 
            get_string('open-in-new-window', 'block_image_link')
        );
        $mform->setType('config_new_window', PARAM_INT);
        $mform->addHelpButton('config_new_window', 'open-in-new-window', 'block_image_link');

        $mform->addElement(
            'select', 
            'config_color', 
            get_string('header-color', 'block_image_link'), 
            [
                '-1'      => get_string('color-default', 'block_image_link'),
                '#d0463c' => get_string('color-red',     'block_image_link'), 
                '#37465d' => get_string('color-blue',    'block_image_link'),
                '#2d2d37' => get_string('color-dark',    'block_image_link')
            ]
        );
        $mform->addHelpButton('config_color', 'header-color', 'block_image_link');
    }

    function set_data($defaults) 
    {
        if (empty($entry->id)) {
            $entry = new stdClass;
            $entry->id = null;
        }

        $draftitemid = file_get_submitted_draft_itemid('config_image');

        file_prepare_draft_area(
            $draftitemid, 
            $this->block->context->id, 
            'block_image_link', 
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
                'block_image_link', 
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
                'block_image_link', 
                'content',
                 0
             );
        if($files){
           $fs = get_file_storage();
           $fs->delete_area_files(
            $this->block->context->id, 
                'block_image_link', 
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
