<?php

class block_video extends block_base 
{
    function init() {
        $this->title = get_string('pluginname', 'block_video');
    }

    public function instance_allow_multiple() {
        return true;
    }

    // public function hide_header() {
    //     return $this->imageUrl() ? true : false;
    // }

    public function get_content() {
        //echo "content is";
        print_r($this->content->text);

        if ($this->content !== null) {
          return $this->content;
        }

        $link = isset($this->config->video) ? $this->config->video : '';
        if (strpos($link, 'http://') === false && strpos($link, 'https://') === false) {
            $link = "http://$link";
        }

        $caption = isset($this->config->image_caption) ? $this->config->image_caption : '';
        $color = isset($this->config->color) && $this->config->color != -1 ? $this->config->color : '';
        $target = isset($this->config->new_window) && $this->config->new_window ? 'target="_blank"' : '';
        $imageUrl = $this->imageUrl();

        $headerClass = $color ? 'colored' : '';
        $headerStyle = $color ? "style=\"background-color: $color; color: white;\"" : '';
        $topColor = $color ? $color : '#f3f3f3';
        $bodyStyle = $imageUrl ? "style=\"border-top: 3px solid $topColor; background-image: url($imageUrl); \"" : '';
        if($headerStyle){
            $id = '#inst'.$this->instance->id;
     
            echo '<style type="text/css">
              '.$id.' .header{
             background-color:'.$color.'; } 
            </style>';

         }

        $this->content =  new stdClass;

        if ($imageUrl) {
            $this->content->text = 
                "
                <div $bodyStyle class=\"image\">
                    <a href=\"$link\" $target></a>
                </div>";
        }

        else
        {
            // print_r($this->config->video['text']);
            // die("I am in video");
            $this->content->text =  $this->config->video['text'];

            $content = file_rewrite_pluginfile_urls($this->config->video['text'], 'pluginfile.php', $this->context->id, 'block_video', 'content', NULL);
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $formatoptions->overflowdiv = true;
            $formatoptions->context = $context;
            $this->content->text = format_text($content, $page->contentformat, $formatoptions);
        }
        /*<div class=\"header $headerClass\" $headerStyle>".
                    "<h2>
                        <a href=\"$link\" $target>
                            $caption
                        </a>
                    </h2>
                </div>
        */
        return $this->content;
    }

    private function imageUrl()
    {
        $url = '';        

        $files = get_file_storage()->get_area_files(
            $this->context->id, 
            'block_video', 
            'content'
        );
    
        foreach ($files as $file) {
            if ($file->get_filename() <> '.') {
                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(), 
                    $file->get_component(), 
                    $file->get_filearea(), 
                    null, 
                    $file->get_filepath(), 
                    $file->get_filename()
                );
            }
        }

        return $url;
    }
}