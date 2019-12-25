<?php

namespace local_lz_extension\watcher;

require_once __DIR__.'/../../lib/program_message.php';
  
class prog_messages_manager_construct 
{
    public static function execute(\local_lz_extension\hook\prog_messages_manager_construct $hook)
    {
        $hook->manager_message_classnames = [
            MESSAGETYPE_ENROLMENT               => 'local_lz_extension\prog_enrolment_message',
            MESSAGETYPE_EXCEPTION_REPORT        => 'local_lz_extension\prog_exception_report_message',
            MESSAGETYPE_UNENROLMENT             => 'local_lz_extension\prog_unenrolment_message',
            MESSAGETYPE_PROGRAM_DUE             => 'local_lz_extension\prog_program_due_message',
            MESSAGETYPE_PROGRAM_OVERDUE         => 'local_lz_extension\prog_program_overdue_message',
            MESSAGETYPE_EXTENSION_REQUEST       => 'local_lz_extension\prog_extension_request_message',
            MESSAGETYPE_PROGRAM_COMPLETED       => 'local_lz_extension\prog_program_completed_message',
            MESSAGETYPE_COURSESET_DUE           => 'local_lz_extension\prog_courseset_due_message',
            MESSAGETYPE_COURSESET_OVERDUE       => 'local_lz_extension\prog_courseset_overdue_message',
            MESSAGETYPE_COURSESET_COMPLETED     => 'local_lz_extension\prog_courseset_completed_message',
            MESSAGETYPE_LEARNER_FOLLOWUP        => 'local_lz_extension\prog_learner_followup_message',
            MESSAGETYPE_RECERT_WINDOWOPEN       => 'local_lz_extension\prog_recert_windowopen_message',
            MESSAGETYPE_RECERT_WINDOWDUECLOSE   => 'local_lz_extension\prog_recert_windowdueclose_message',
            MESSAGETYPE_RECERT_FAILRECERT       => 'local_lz_extension\prog_recert_failrecert_message',
        ];
    }
}