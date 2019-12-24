<?php
      // This function fetches math. images from the data directory
      // If not, it obtains the corresponding TeX expression from the cache_tex db table
      // and uses mimeTeX to create the image file

// disable moodle specific debug messages and any errors in output
define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true); // Because it interferes with caching

    require_once('../../config.php');

    if (!filter_is_enabled('algebra')) {
        print_error('filternotenabled');
    }

    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/filter/tex/lib.php');

    $cmd    = '';               // Initialise these variables
    $status = '';

    $relativepath = get_file_argument();

    $args = explode('/', trim($relativepath, '/'));

    if (count($args) == 1) {
        $image    = $args[0];
        $pathname = $CFG->dataroot.'/filter/algebra/'.$image;
    } else {
        print_error('invalidarguments', 'error');
    }

    if (!file_exists($pathname)) {
        $md5 = str_replace('.gif','',$image);
        if ($texcache = $DB->get_record('cache_filters', array('filter'=>'algebra', 'md5key'=>$md5))) {
            if (!file_exists($CFG->dataroot.'/filter/algebra')) {
                make_upload_directory('filter/algebra');
            }

            $texexp = $texcache->rawtext;
            $texexp = str_replace('&lt;','<',$texexp);
            $texexp = str_replace('&gt;','>',$texexp);
            $texexp = preg_replace('!\r\n?!',' ',$texexp);
            $texexp = '\Large ' . $texexp;

            $commandpath = filter_tex_get_executable(true);
            $texcommand = new \core\command\executable($commandpath);
            if (core\command\executable::is_windows()) {
                $texcommand->add_switch('++');
            }
            $texcommand->add_switch('-e');
            $texcommand->add_value($pathname, \core\command\argument::PARAM_FULLFILEPATH);

            $texexp = filter_tex_sanitize_formula($texexp);
            $texcommand->add_argument('--', $texexp,PARAM_TEXT);

            $texcommand->execute();
        }
    }

    if (file_exists($pathname)) {
        send_file($pathname, $image);
    } else {
        debugging('External command for algebra filter failed. Go to filter/algebra/algebradebug.php to debug', DEBUG_DEVELOPER);
    }

