<?php

namespace local_lz_extension;

require_once __DIR__.'/../vendor/autoload.php';

use PHPHtmlParser\Dom;

trait program_message 
{
    public function __construct($programid, $messageob = null, $uniqueid = null) {
        parent::__construct($programid, $messageob, $uniqueid);

        $this->managermessage = str_replace("\r\n", '<br>', $this->managermessage);
        $this->mainmessage = str_replace("\r\n", '<br>', $this->mainmessage);
    }

    public function init_form_data($formnameprefix, $formdata)
    {
        $this->id = $formdata->{$formnameprefix.'id'};
        $this->programid = $formdata->id;
        $this->messagetype = $formdata->{$formnameprefix.'messagetype'};
        $this->sortorder = $formdata->{$formnameprefix.'sortorder'};
        $this->messagesubject = $formdata->{$formnameprefix.'messagesubject'};
        $this->mainmessage = $formdata->{$formnameprefix.'mainmessage'}['text'];
        $this->mainmessageformat = $formdata->{$formnameprefix.'mainmessage'}['format'];

        $this->notifymanager = isset($formdata->{$formnameprefix.'notifymanager'}) ? $formdata->{$formnameprefix.'notifymanager'} : false;
        $this->managermessage = isset($formdata->{$formnameprefix.'managermessage'}) ? $formdata->{$formnameprefix.'managermessage'}['text'] : '';
        $this->triggerperiod = isset($formdata->{$formnameprefix.'triggerperiod'}) ? $formdata->{$formnameprefix.'triggerperiod'} : 0;
        $this->triggernum = isset($formdata->{$formnameprefix.'triggernum'}) ? $formdata->{$formnameprefix.'triggernum'} : 0;
        $this->triggertime = \program_utilities::duration_implode($this->triggernum, $this->triggerperiod);
    }

    public function get_generic_basic_fields_template(
        &$mform,
        &$template_values,
        &$formdataobject,
        $updateform = true
    ) {
        global $OUTPUT;
        $prefix = $this->get_message_prefix();

        $templatehtml = '';

        // Add the message subject
        $safe_messagesubject = format_string($this->messagesubject);
        if ($updateform) {
            $mform->addElement('text', $prefix.'messagesubject', '', array('size'=>'50', 'maxlength'=>'255', 'id'=>$prefix.'messagesubject'));
            $mform->setType($prefix.'messagesubject', PARAM_TEXT);
            $template_values['%'.$prefix.'messagesubject%'] = array('name'=>$prefix.'messagesubject', 'value'=>null);
        }
        $helpbutton = $OUTPUT->help_icon('messagesubject', 'totara_program');
        $templatehtml .= \html_writer::start_tag('div', array('class' => 'fitem'));
        $templatehtml .= \html_writer::tag('div', \html_writer::tag('label', get_string('label:subject', 'totara_program') . ' ' . $helpbutton, array('for' => $prefix.'messagesubject')), array('class' => 'fitemtitle'));
        $templatehtml .= \html_writer::tag('div', '%'.$prefix.'messagesubject%', array('class' => 'felement'));
        $templatehtml .= \html_writer::end_tag('div');
        $formdataobject->{$prefix.'messagesubject'} = $safe_messagesubject;

        // Add the main message
        // $safe_mainmessage = format_string($this->mainmessage);
        if ($updateform) {
            $editoroptions = [
                'subdirs'      => 0,
                'maxbytes'     => 0,
                'maxfiles'     => -1,
                'changeformat' => 0,
                'context'      => \context_program::instance($this->programid),
                'noclean'      => 0,
                'trusttext'    => 0
            ];
            $mform->addElement('editor', $prefix.'mainmessage', '', ['id' => $prefix.'mainmessage'], $editoroptions);
            $mform->setType($prefix.'mainmessage', PARAM_RAW);
            $template_values['%'.$prefix.'mainmessage%'] = array('name'=>$prefix.'mainmessage', 'value'=>null);
        }
        $helpbutton = $OUTPUT->help_icon('mainmessage', 'totara_program');
        $templatehtml .= \html_writer::start_tag('div', array('class' => 'fitem'));
        $templatehtml .= \html_writer::tag('div', \html_writer::tag('label', get_string('label:message', 'totara_program') . ' ' . $helpbutton, array('for' => $prefix.'mainmessage')), array('class' => 'fitemtitle'));
        $templatehtml .= \html_writer::tag('div', '%'.$prefix.'mainmessage%', array('class' => 'felement'));
        $templatehtml .= \html_writer::end_tag('div');
        $formdataobject->{$prefix.'mainmessage'} = [];
        $formdataobject->{$prefix.'mainmessage'}['text'] = $this->mainmessage;
        $formdataobject->{$prefix.'mainmessage'}['format'] = 1;//$this->mainmessageformat;

        return $templatehtml;
    }

    public function get_generic_manager_fields_template(
        &$mform,
        &$template_values,
        &$formdataobject,
        $updateform = true
    ) {
        global $OUTPUT;
        $prefix = $this->get_message_prefix();

        $templatehtml = '';

        // Add the notify manager checkbox
        $attributes = array();
        if (isset($this->notifymanager) && $this->notifymanager == true) {
            $attributes['checked'] = "checked";
        }
        if ($updateform) {
            $mform->addElement('checkbox', $prefix.'notifymanager', '', '', $attributes);
            $mform->setType($prefix.'notifymanager', PARAM_BOOL);
            $template_values['%'.$prefix.'notifymanager%'] = array('name'=>$prefix.'notifymanager', 'value'=>null);
        }
        $helpbutton = $OUTPUT->help_icon('notifymanager', 'totara_program');
        $templatehtml .= \html_writer::start_tag('div', array('class' => 'fitem'));
        $templatehtml .= \html_writer::tag('div', \html_writer::tag('label', get_string('label:sendnoticetomanager', 'totara_program') . ' ' . $helpbutton, array('for' => 'id_' . $prefix . 'notifymanager')), array('class' => 'fitemtitle'));
        $templatehtml .= \html_writer::tag('div', '%'.$prefix.'notifymanager%', array('class' => 'felement'));
        $templatehtml .= \html_writer::end_tag('div');
        $formdataobject->{$prefix.'notifymanager'} = (bool)$this->notifymanager;

        // Add the manager message
        if ($updateform) {
            $editoroptions = [
                'subdirs'      => 0,
                'maxbytes'     => 0,
                'maxfiles'     => -1,
                'changeformat' => 0,
                'context'      => \context_program::instance($this->programid),
                'noclean'      => 0,
                'trusttext'    => 0
            ];
            $mform->addElement('editor', $prefix.'managermessage', '', ['id' => $prefix.'managermessage'], $editoroptions);
            $mform->setDefault($prefix.'managermessage', ['text' => $this->managermessage]);
            //$mform->disabledIf($prefix.'managermessage', $prefix.'notifymanager', 'notchecked');
            $mform->setType($prefix.'managermessage', PARAM_RAW);
            $template_values['%'.$prefix.'managermessage%'] = array('name'=>$prefix.'managermessage', 'value'=>null);
        }
        $helpbutton = $OUTPUT->help_icon('managermessage', 'totara_program');
        $templatehtml .= \html_writer::start_tag('div', array('class' => 'fitem'));
        $templatehtml .= \html_writer::tag('div', \html_writer::tag('label', get_string('label:noticeformanager', 'totara_program') . ' ' . $helpbutton, array('for' => $prefix . 'managermessage')), array('class' => 'fitemtitle'));
        $templatehtml .= \html_writer::tag('div', '%'.$prefix.'managermessage%', array('class' => 'felement'));
        $templatehtml .= \html_writer::end_tag('div');
        $formdataobject->{$prefix.'managermessage'}['text'] = $this->managermessage;
        $formdataobject->{$prefix.'managermessage'}['format'] = 1;

        return $templatehtml;
    }

    public function save_message()
    {
        $this->mainmessage = $this->mainmessage
            ? $this->prepareMessage($this->mainmessage)
            : $this->mainmessage;

        $this->managermessage = $this->managermessage
            ? $this->prepareMessage($this->managermessage)
            : $this->managermessage;

        return parent::save_message();
    }

    private function prepareMessage($message)
    {
        global $CFG;

        $dom = new Dom;

        $dom->load($message);

        $images = $dom->find('img');

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $src = str_replace('local/lz_extension/draftfile.php', 'draftfile.php', $src);
            $src = str_replace('draftfile.php', 'local/lz_extension/draftfile.php', $src);
            preg_match('/\.[a-z]*$/', $src, $matches);
            $extension = count($matches) ? $matches[0] : '';

            $file = file_get_contents($src);
            
            if (!$file) {
                continue;
            }

            $name = md5($file);

            if (!file_exists(__DIR__."/../files/$name$extension")) {
                file_put_contents(__DIR__."/../files/$name$extension", $file);
            }

            $img->setAttribute('src', "{$CFG->wwwroot}/local/lz_extension/files/$name$extension");
        }

        return $dom->outerHtml;
    }
}

class prog_enrolment_message             extends \prog_enrolment_message             { use program_message; }

class prog_exception_report_message      extends \prog_exception_report_message      { use program_message; }

class prog_unenrolment_message           extends \prog_unenrolment_message           { use program_message; }

class prog_program_completed_message     extends \prog_program_completed_message     { use program_message; }

class prog_courseset_completed_message   extends \prog_courseset_completed_message   { use program_message; }

class prog_program_due_message           extends \prog_program_due_message           { use program_message; }

class prog_courseset_due_message         extends \prog_courseset_due_message         { use program_message; }

class prog_program_overdue_message       extends \prog_program_overdue_message       { use program_message; }

class prog_courseset_overdue_message     extends \prog_courseset_overdue_message     { use program_message; }

class prog_learner_followup_message      extends \prog_learner_followup_message      { use program_message; }

class prog_extension_request_message     extends \prog_extension_request_message     { use program_message; }

class prog_recert_windowopen_message     extends \prog_recert_windowopen_message     { use program_message; }

class prog_recert_windowdueclose_message extends \prog_recert_windowdueclose_message { use program_message; }

class prog_recert_failrecert_message     extends \prog_recert_failrecert_message     { use program_message; }
