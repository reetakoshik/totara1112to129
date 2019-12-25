<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/carrousel/lib.php');
require_once($CFG->dirroot.'/blocks/carrousel/editform.php');


function block_carrousel_render($blockid){
    global $CFG, $OUTPUT;
    
    $slides = block_carrousel_get_slides($blockid);
    if(empty($slides)){
        return '';
    }
    $numslides = count($slides);

    global $DB;
    $settings = $DB->get_record_sql("
        SELECT * FROM {block_carrousel_settings} 
        WHERE block_id = {$blockid}"
    );

    $html = '
        <script type="text/javascript" src="' . $CFG->wwwroot . '/blocks/carrousel/jquery.carouFredSel-6.2.1-packed.js"></script>
        <div class="carousel">
        <div id="Info-carousel">';
        
            foreach ($slides as $slide) { 
                if (!isset($slide->textcolor)) {
                    $slide->textcolor = 'white';
                }

                $html .= '<div class="carousel__item" style="background-image: url(' . $CFG->wwwroot.$slide->imageurl .');">
                            <div class="carousel__text">
                                <div class="carousel__text-inner text-center">
                                    <div style="color: '.$slide->textcolor.'" class="slider-title">'. $slide->title .'</div>
                                    <a href="'. $slide->buttonurl .'" style="color: '.$slide->textcolor.'" class="slider-text"> '. $slide->buttontext .' </a>
                                </div>
                            </div>'
                            . ($slide->buttonurl
                                ? '<a href="'. $slide->buttonurl .'" target="_blank"><span class="slider-link"></span></a>'
                                : '') .
                         '</div>';
            }
          
            $html .= '</div><div class="clearfix"></div>
        <a class="carousel__prev" id="Info-carousel-prev" href="#"><span></span></a>
        <a class="carousel__next" id="Info-carousel-next" href="#"><span></span></a>

        <div class="carousel__pagination" id="Info-carousel-pagination"></div>
    </div>';

    //duration in milliseconds but carouFredSel multiplies it by 5
    $duration = $settings ? $settings->scroll_duration * 1000 / 5 : 2000 / 5; 

    $html .= ' <script>
        $(\'#Info-carousel\').carouFredSel({
            items: 1,
            auto: true,
            direction: "right",
            scroll: {
                items: 1,
                pauseOnHover: false,
                duration: '.$duration.'
            },
            prev: {
                button: "#Info-carousel-next",
                key: "left"
            },
            next: {
                button: "#Info-carousel-prev",
                key: "right"
            },
            responsive: true,
            pagination: "#Info-carousel-pagination"
        });
    </script>';
    return $html;
}

function block_carrousel_edit_form($block) 
{
    global $DB;

    $settings = $DB->get_record_sql("
        SELECT * FROM {block_carrousel_settings} 
        WHERE block_id = {$block->id}"
    );

    if (!$settings) {
        $settings = new stdClass;
        $settings->block_id = $block->id;
        $settings->scroll_duration = 2.0;
        $settings->id = $DB->insert_record('block_carrousel_settings', $settings, true);
    }

    $returnurl = new moodle_url('/blocks/carrousel/index.php', ['blockid' => $block->id]);
    $mform = new carrousel_block_edit_form(null, ['settings' => $settings]);

    if ($mform->is_cancelled()) {
        $context = context::instance_by_id($block->parentcontextid);
        redirect($context->get_url());
    }

    if ($fromform = $mform->get_data()) {
        if (empty($fromform->submitbutton)) {
            totara_set_notification(get_string('error:unknownbuttonclicked', 'block_carrousel'), $returnurl);
        }

        if (!confirm_sesskey()) {
            print_error('confirmsesskeybad', 'error');
        }

        $settings->scroll_duration = isset($fromform->scroll_duration) ? $fromform->scroll_duration : '2' ;

        if (!$DB->update_record('block_carrousel_settings', $settings)) {
            print_error('Failed to update block');
            die;
        }

        totara_set_notification(
            get_string('success', 'moodle'),
            $returnurl, 
            array('class' => 'notifysuccess')
        );
    }

    return $mform;
}

/**
 * Return a button that when clicked, takes the user to new slide editor
 *
 * @return string HTML to display the button
 */
function block_carrousel_create_slide_button() {
    global $OUTPUT;
    
    $blockid = required_param('blockid', PARAM_INT);
    $url = new moodle_url('/blocks/carrousel/edit.php', array('action' => 'new', 'adminedit' => 1, 'blockid' => $blockid));
    return $OUTPUT->single_button($url, get_string('add', 'block_carrousel'), 'get');
}

/**
 * Renders a table containing dashboard list
 *
 * @param array $slides array of totara_dashboard object
 * @return string HTML table
 */
function block_carrousel_manage_table($blockid) {
    global $OUTPUT;
    
    $slides = block_carrousel_get_slides($blockid, true);
    
    if (empty($slides)) {
        return get_string('noslides', 'block_carrousel');
    }

    $tableheader = array(get_string('name', 'block_carrousel'),
                         get_string('assignedcohorts', 'block_carrousel'),
                         get_string('options', 'block_carrousel'));

    $dashboardstable = new html_table();
    $dashboardstable->summary = '';
    $dashboardstable->head = $tableheader;
    $dashboardstable->data = array();
    $dashboardstable->attributes = array('class' => 'generaltable fullwidth');

    $strpublish = get_string('publish', 'totara_dashboard');
    $strunpublish = get_string('unpublish', 'totara_dashboard');
    $strdelete = get_string('delete', 'totara_dashboard');
    $stredit = get_string('editdashboard', 'totara_dashboard');

    $data = array();
    foreach ($slides as $slide) {
        $id = $slide->id;
       
        $name = format_string($slide->title);
        
        $urledit = new moodle_url('/blocks/carrousel/edit.php', array('id' => $id, 'blockid' => $blockid));
        $urlpublish = new moodle_url('/blocks/carrousel/manage.php', array('action' => 'publish', 'id' => $id, 'blockid' => $blockid, 'sesskey' => sesskey()));
        $urlunpublish = new moodle_url('/blocks/carrousel/manage.php', array('action' => 'unpublish', 'id' => $id, 'blockid' => $blockid,  'sesskey' => sesskey()));
        $urlup = new moodle_url('/blocks/carrousel/manage.php', array('action' => 'up', 'id' => $id, 'sesskey' => sesskey(), 'blockid' => $blockid));
        $urldown = new moodle_url('/blocks/carrousel/manage.php', array('action' => 'down', 'id' => $id, 'sesskey' => sesskey(), 'blockid' => $blockid));
        $deleteurl = new moodle_url('/blocks/carrousel/manage.php', array('action' => 'delete', 'id' => $id, 'blockid' => $blockid));

        $row = array();
        $row[] = html_writer::link($urledit, $name);
        //$row[] = count($slide->cohorts);
        
        $cohorts = block_carrousel_get_cohorts($slide->cohorts);
       
        $cohortsnames = [];
        foreach ($cohorts as $cohort) {
            $cohortsnames[] = $cohort->name;
        }
        $row[] = implode (' ,', $cohortsnames);

        $options = '';
        $options .= $OUTPUT->action_icon($urledit, new pix_icon('/t/edit', $stredit, 'moodle'), null,
                array('class' => 'action-icon edit'));

        if ($slide->hide == 0) {
           $options .= $OUTPUT->action_icon($urlunpublish, new pix_icon('/t/hide', $strunpublish, 'moodle'), null,
                   array('class' => 'action-icon publish'));
        } else {
            $options .= $OUTPUT->action_icon($urlpublish, new pix_icon('/t/show', $strpublish, 'moodle'), null,
                array('class' => 'action-icon unpublish'));
        }

        if (!block_carrousel_is_first($slide)) {
        $options .= $OUTPUT->action_icon($urlup, new pix_icon('/t/up', 'moveup', 'moodle'), null,
                  array('class' => 'action-icon up'));
        }
        if (!block_carrousel_is_last($slide)) {
        $options .= $OUTPUT->action_icon($urldown, new pix_icon('/t/down', 'movedown', 'moodle'), null,
                   array('class' => 'action-icon down'));
        }

        $options .= $OUTPUT->action_icon($deleteurl, new pix_icon('/t/delete', $strdelete, 'moodle'), null,
                   array('class' => 'action-icon delete'));  
        
        $row[] = $options;  

        $data[] = $row;
    }
    $dashboardstable->data = $data;

    return html_writer::table($dashboardstable);
}

function block_carrousel_include_totara_cohortdialog($blockid, $slide)
{
    global $PAGE;

    $PAGE->requires->strings_for_js(array('assignedcohorts'), 'totara_dashboard');

    $jsmodule = [
        'name'     => 'totara_cohortdialog',
        'fullpath' => '/blocks/carrousel/cohort.js',
        'requires' => ['json']
    ];
    
    $args = [
        'args' => json_encode([
            'selected'                   => $slide->cohorts,
            'blockid'                    => $blockid,
            'COHORT_ASSN_VALUE_ENROLLED' => COHORT_ASSN_VALUE_ENROLLED
        ])
    ];

    $PAGE->requires->js_init_call('M.carrousel_cohort.init', $args, true, $jsmodule);
}

function block_carrousel_include_carrousel_js()
{
    global $PAGE;

    $jsmodule = [
        'name'     => 'block_carrousel',
        'fullpath' => '/blocks/carrousel/carrousel.js',
        'requires' => []
    ];
    $PAGE->requires->js_init_call('M.block_carrousel.init', [], true, $jsmodule);
}
