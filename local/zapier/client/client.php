<?php
// This client for local_wstemplate is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//

/**
 * XMLRPC client for Moodle 2 - local_wstemplate
 *
 * This script does not depend of any Moodle code,
 * and it can be called from a browser.
 *
 * @authorr Jerome Mouneyrac
 */

//http://golearningzone.dev/webservice/rest/server.php?wstoken=cec30d19f91b868e2e2d9019c4413e54&wsfunction=local_zapier_course_completion&moodlewsrestformat=json

/// MOODLE ADMINISTRATION SETUP STEPS
// 1- Install the plugin
// 2- Enable web service advance feature (Admin > Advanced features)
// 3- Enable XMLRPC protocol (Admin > Plugins > Web services > Manage protocols)
// 4- Create a token for a specific user (Admin > Plugins > Web services > Manage tokens)
// 5- Run this script directly from your browser: you should see 'Hello, FIRSTNAME'

/// SETUP - NEED TO BE CHANGED
$token = 'bb5d15a1a4bafdd3c10002bed5eed568';
$domainname = 'http://localhost/golearningzone';

/// FUNCTION NAME
$functionname = 'local_zapier_course_completion';

/// PARAMETERS
$welcomemsg = 'Hello, ';

///// XML-RPC CALL
header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/xmlrpc/server.php'. '?wstoken=' . $token;
require_once('./curl.php');
$curl = new curl;


$post = xmlrpc_encode_request($functionname, array());

$resp = xmlrpc_decode($curl->post($serverurl, $post));
print_r($resp);
