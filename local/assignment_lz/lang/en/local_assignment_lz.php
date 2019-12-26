<?php
$string['pluginname'] = 'Assignment enhanced';

$string['settings:roles'] = 'Roles';
$string['settings:rolesdescription'] = 'Role should have ability \'mod/assign:manageallocations\'';

$string['messageprovider:submissioncreated'] = 'Assignment submitted';
$string['messageprovider:submissionupdated'] = 'Assignment updated';

$string['submissioncreated:smallmessage'] = '{$a->username} has created submission for assignment {$a->assignment}';
$string['submissioncreated:fullmessage'] = '{$a->username} has created submission for assignment \'{$a->assignment}\' at {$a->date}';
$string['submissioncreated:fullmessagehtml'] = '<p>{$a->path}</p><hr><p>{$a->username} has created submission for assignment \'{$a->assignment}\' at {$a->date}</p><br><p>{$a->url}</p><hr>';

$string['submissionupdated:smallmessage'] = '{$a->username} has updated their submission for assignment {$a->assignment}';
$string['submissionupdated:fullmessage'] = '{$a->username} has updated their submission for assignment \'{$a->assignment}\' at {$a->date}';
$string['submissionupdated:fullmessagehtml'] = '<p>{$a->path}</p><hr><p>{$a->username} has updated their submission for assignment \'{$a->assignment}\' at {$a->date}</p><br><p>{$a->url}</p><hr>';