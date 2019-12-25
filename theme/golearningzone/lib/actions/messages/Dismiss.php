<?php

namespace GoLearningZone\Actions\Messages;

use GoLearningZone\Actions\Base;

require_once __DIR__.'/../../../../../totara/message/messagelib.php';

class Dismiss extends Base
{
    protected function validate($params)
    {
        $rules = [
            'Id' => [ 'required', 'positive_integer'],
        ];

        return \GoLearningZone\Validator::validate($params, $rules);
    } 

    protected function execute($params)
    {
        global $DB;
        global $USER;

        $message = $DB->get_record('message', ['id' => $params['Id']]);

        if (!$message || $message->useridto != $USER->id || !confirm_sesskey()) {
            return [ 'Status' => 0 ];
        }

        tm_message_dismiss($params['Id']);

        return [ 'Status' => 1 ];
    }   
}
