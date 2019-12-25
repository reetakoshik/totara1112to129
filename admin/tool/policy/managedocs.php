<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage policy documents used on the site.
 *
 * Script arguments:
 * - archived=<int> Show only archived versions of the given policy document
 *
 * @package     tool_policy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$archived = optional_param('archived', 0, PARAM_INT);
$draft = optional_param('draft', 0, PARAM_INT);
//$search = optional_param('searchpolicy', null, PARAM_RAW);

//admin_externalpage_setup('tool_policy_managedocs', '', ['archived' => $archived]);

$PAGE->set_pagelayout('noblocks');

$output = $PAGE->get_renderer('tool_policy');
require_login();
if(!empty($archived)){
// print_r("I am in archived");
$manpage = new \tool_policy\output\page_managedocs_list($archived);
// print_r($manpage);

}
else if(!empty($draft)){
// print_r("I am in draft");
$manpage = new \tool_policy\output\page_managedocs_list($draft);
}
else{

	if(isset($_POST['searchpolicy']) && (empty($_POST['group'])) && (empty($archived))){
       $search= $_POST['searchpolicy']; 
       // print_r("I am in search");
	// $manpage = new \tool_policy\output\page_managedocs_list($search);
		$manpage = new \tool_policy\output\page_managedocs_list('',$search);
		
		//print_r($manpage);
	 }
	 if(isset($_POST['group']) && (empty($_POST['searchpolicy'])) && (empty($archived))){
	    $filterby= $_POST['group']; 
	    // print_r("I am in filter");
	 	$manpage = new \tool_policy\output\page_managedocs_list('','',$filterby);
	 	// print_r($manpage);
	 	

	}
	// if((!empty($_POST['group'])) && (!empty($_POST['searchpolicy'])))
	// {
	// 	$search= $_POST['searchpolicy']; 
 //       print_r("I am in search and filter");
	// 	$manpage = new \tool_policy\output\page_managedocs_list('',$search,$filterby);
	// }
	 elseif((empty($_POST['group'])) && (empty($_POST['searchpolicy'])) && (empty($archived)) ){
	 // print_r("I am in last condition");	
	$manpage = new \tool_policy\output\page_managedocs_list();
	}
}

echo $output->header();
echo $output->render($manpage);
echo $output->footer();

