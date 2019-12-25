<?php
$string['pluginname'] = 'מטלה משודרגת';

$string['settings:roles'] = 'Roles';
$string['settings:rolesdescription'] = 'Role should have ability \'mod/assign:manageallocations\'';

$string['messageprovider:submissioncreated'] = 'מטלה הוגשה';
$string['messageprovider:submissionupdated'] = 'מטלה עודכנה';

$string['submissioncreated:smallmessage'] = 'המשתמש {$a->username} הגיש את המטלה {$a->assignment}';
$string['submissioncreated:fullmessage'] = 'המשתמש {$a->username} הגיש את המטלה \'{$a->assignment}\' בתאריך {$a->date}';
$string['submissioncreated:fullmessagehtml'] = '<p>{$a->path}</p><hr><p>המשתמש {$a->username} הגיש את המטלה \'{$a->assignment}\' בתאריך {$a->date}</p><br><p>{$a->url}</p><hr>';

$string['submissionupdated:smallmessage'] = 'המשתמש {$a->username} עדכן את הגשת המטלה עבור {$a->assignment}';
$string['submissionupdated:fullmessage'] = 'המשתמש {$a->username} עדכן את הגשת המטלה עבור \'{$a->assignment}\' בתאריך {$a->date}';
$string['submissionupdated:fullmessagehtml'] = '<p>{$a->path}</p><hr><p>המשתמש {$a->username} עדכן את הגשת המטלה עבור \'{$a->assignment}\' בתאריך {$a->date}</p><br><p>{$a->url}</p><hr>';