<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $DB, $USER;
$loggedinuserid = $USER->id;
$msgtype = $_REQUEST['msgtype'];
$messageworkingempty = false;

if($msgtype == 'message') {
	$msgtypeid = $DB->get_record_sql("SELECT id FROM {message_processors} WHERE name = 'popup'");
} else {
	$msgtypeid = $DB->get_record_sql("SELECT id FROM {message_processors} WHERE name = 'airnotifier'");
}

$messages = $DB->get_records_sql("SELECT m.* FROM {message} m INNER JOIN {message_working} mw ON mw.unreadmessageid = m.id WHERE m.useridto = '".$loggedinuserid."' ");

foreach($messages as $message) {

$message->timeread = time();

    $messageid = $message->id;
    unset($message->id);//unset because it will get a new id on insert into message_read

    //If any processors have pending actions abort them
    if (!$messageworkingempty) {
        $DB->delete_records('message_working', array('unreadmessageid' => $messageid));
    }
    $messagereadid = $DB->insert_record('message_read', $message);

    $DB->delete_records('message', array('id' => $messageid));

    // Get the context for the user who received the message.
    $context = context_user::instance($message->useridto, IGNORE_MISSING);
    // If the user no longer exists the context value will be false, in this case use the system context.
    if ($context === false) {
        $context = context_system::instance();
    }

    // Trigger event for reading a message.
    $event = \core\event\message_viewed::create(array(
        'objectid' => $messagereadid,
        'userid' => $message->useridto, // Using the user who read the message as they are the ones performing the action.
        'context' => $context,
        'relateduserid' => $message->useridfrom,
        'other' => array(
            'messageid' => $messageid
        )
    ));
    $event->trigger();

    //return $messagereadid;
}