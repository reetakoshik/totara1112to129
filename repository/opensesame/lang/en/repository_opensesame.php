<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package repository_opensesame
 */

$string['about'] = 'About';
$string['aboutcontent'] = '<h3>What does this plugin do?</h3>
<p>The OpenSesame plugin in Totara allows you to easily register, browse the course catalogue and purchase an OpenSesame Plus subscription.
<br />Any courses purchased via the plugin will be downloaded to your site.
They can then be turned directly into single activity courses within your site with the click of a button
or manually added to existing courses by adding new SCORM activities and using the OpenSesame repository shown when selecting the package.</p>

<h3>Your OpenSesame account</h3>
<p>Registering with OpenSesame is a two stage process, first you register your server and then your name and email are used to create an OpenSesame account.
Server registration details can be viewed at any time after registration and may be required when communicating with OpenSesame.</p>

<h3>Purchasing and support</h3>
<p>All purchases made with OpenSesame through this plugin are made solely with OpenSesame and any enquiries should be directed to their support department.<br />The support website is at <a href="http://support.opensesame.com/" target="_blank">support.opensesame.com</a>.</p>';
$string['aboutopensesame'] = 'About OpenSesame';
$string['aboutopensesamedesc'] = 'OpenSesame is an online training course provider for businesses.
OpenSesame makes buying and selling elearning courses as easy as downloading a song from iTunes.
Through the OpenSesame plugin you are able to register directly with OpenSesame, search and download course packages.
Any purchased courses will be downloaded to your Totara site as SCORM packages where you can either turn them into a single activity course with the click of a button or include them within an existing course.';
$string['addhelp'] = 'Please click "Save" button and return to settings to configure OpenSesame integration';
$string['configplugin'] = 'Configure OpenSesame';
$string['confirmbrowse'] = 'I agree';
$string['confirmbrowsewarning'] = 'You are about to browse the OpenSesame Plus course catalogue.

This action requires an OpenSesame account, if an account already exists with your email address this will be used, otherwise your name "{$a->FirstName} {$a->LastName}" and email address "{$a->Email}" will be used to create a new account for you.

Please agree to continue.';
$string['browsecatalogue'] = 'Online course catalogue';
$string['browsepackages'] = 'View downloaded courses';
$string['catalogueheading'] = 'OpenSesame catalogue: {$a->firstname} {$a->lastname} ({$a->email})';
$string['coursefetcherror'] = 'Error downloading courses from OpenSesame';
$string['coursefetchsuccess'] = '{$a} course packages downloaded';
$string['coursefetchsuccessnocourse'] = 'No courses downloaded';
$string['createcourse'] = 'Create course';
$string['errorcannotaccesstotaralms'] = 'Unable to connect to {$a}, please make sure your server is able to access this site.';
$string['errorcannotregister'] = 'OpenSesame registration failure';
$string['erroropensesameconnection'] = 'Error communicating with the OpenSesame server, please retry later.';
$string['eventcatalogueaccessed'] = 'OpenSesame catalogue accessed';
$string['eventpackagefetched'] = 'Course package fetched';
$string['eventpackagehid'] = 'Package made hidden';
$string['eventpackageunhid'] = 'Package made visible';
$string['eventtenantregistered'] = 'Server registered';
$string['eventtenantunregistered'] = 'Server unregistered';
$string['fetchall'] = 'Download courses';
$string['metabundlename'] = 'Course category';
$string['metadescription'] = 'Course description';
$string['metaduration'] = 'Duration';
$string['metaexpirationdate'] = 'License valid until';
$string['metaexternalid'] = 'External ID';
$string['metamobilecompatibility'] = 'Mobile compatibility';
$string['metamobilecompatibilityall'] = 'All devices';
$string['metamobilecompatibilityandroid'] = 'Android';
$string['metamobilecompatibilityios'] = 'Apple';
$string['metamobilecompatibilitynone'] = 'None / unknown';
$string['metatitle'] = 'Course title';
$string['nobundle'] = 'No bundle';
$string['opensesame:managepackages'] = 'Manage OpenSesame content packages';
$string['opensesame:view'] = 'View OpenSesame content packages';
$string['packagevisible'] = 'Visibility';
$string['pluginname_help'] = 'Create local storage for OpenSesame content.';
$string['pluginname'] = 'OpenSesame';
$string['registration'] = 'Registration details';
$string['registrationdetails'] = 'The following are your registration details with OpenSesame. When dealing directly with OpenSesame they may require this information from you.';
$string['registrationlink'] = 'Configure OpenSesame marketplace integration';
$string['register'] = 'Sign up for access';
$string['registerlink'] = 'Set up OpenSesame marketplace integration';
$string['repositoryname'] = 'Repository name';
$string['root'] = 'Top';
$string['tenantid'] = 'Company ID';
$string['tenantname'] = 'Company name';
$string['tenanttype'] = 'Account type';
$string['tenanttypedemo'] = 'Demo';
$string['tenanttypeprod'] = 'Production';
$string['timeadded'] = 'Date added';
$string['unregister'] = 'Delete registration';
$string['unregisterconfirm'] = 'Do you really want to delete the server registration?

* All downloaded course packages will be deleted.
* Existing SCORM activities will have to be deleted manually.
* It will not be possible to undo this action.

Type your _Company ID_ to confirm that you really want to delete the OpenSesame account.';
