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
 * Provides {@link tool_policy\output\renderer} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 Sara Arjona <sara@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

use moodle_exception;

defined('MOODLE_INTERNAL') || die();

use context_system;
use moodle_url;
use renderable;
use renderer_base;
use single_button;
use templatable;
use tool_policy\api;
use tool_policy\policy_version;
use filter_kaltura;

/**
 * Represents a page for showing the given policy document version.
 *
 * @copyright 2018 Sara Arjona <sara@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_viewdoc implements renderable, templatable {

    /** @var stdClass Exported {@link \tool_policy\policy_version_exporter} to display on this page. */
    protected $policy;

    /** @var string Return URL. */
    protected $returnurl = null;

    /** @var int User id who wants to view this page. */
    protected $behalfid = null;

    /**
     * Prepare the page for rendering.
     *
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show. Empty tries to load the current one.
     * @param string $returnurl URL of a page to continue after reading the policy text.
     * @param int $behalfid The userid to view this policy version as (such as child's id).
     * @param bool $manage View the policy as a part of the management UI.
     * @param int $numpolicy Position of the current policy with respect to the total of policy docs to display.
     * @param int $totalpolicies Total number of policy documents which the user has to agree to.
     */
    public function __construct($policyid, $versionid, $returnurl, $behalfid, $manage, $numpolicy = 0, $totalpolicies = 0) {
        
        $this->returnurl = $returnurl;
        $this->behalfid = $behalfid;
        $this->manage = $manage;
        $this->numpolicy = $numpolicy;
        $this->totalpolicies = $totalpolicies;
        $this->versionid = $versionid;
        $this->prepare_policy($policyid, $versionid);
        $this->prepare_global_page_access();
    }

    /**
     * Loads the policy version to display on the page.
     *
     * @param int $policyid The policy id for this page.
     * @param int $versionid The version id to show. Empty tries to load the current one.
     */
    protected function prepare_policy($policyid, $versionid) {

        if ($versionid) {
            $this->policy = api::get_policy_version($versionid);

        } else {
            $this->policy = array_reduce(api::list_current_versions(), function ($carry, $current) use ($policyid) {
                if ($current->policyid == $policyid) {
                    return $current;
                }
                return $carry;
            });
        }

        if (empty($this->policy)) {
            throw new \moodle_exception('errorpolicyversionnotfound', 'tool_policy');
        }
    }

    /**
     * Sets up the global $PAGE and performs the access checks.
     */
    protected function prepare_global_page_access() {
        global $CFG, $PAGE, $SITE, $USER;

        $myurl = new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $this->policy->policyid,
            'versionid' => $this->policy->id,
            'returnurl' => $this->returnurl,
            'behalfid' => $this->behalfid,
            'manage' => $this->manage,
            'numpolicy' => $this->numpolicy,
            'totalpolicies' => $this->totalpolicies,
        ]);
       //print_r($myurl); die();
        if ($this->manage) {
            //require_once($CFG->libdir.'/adminlib.php');
            //admin_externalpage_setup('tool_policy_managedocs', '', null, $myurl);
            //require_capability('tool/policy:managedocs', context_system::instance());
            $PAGE->navbar->add(format_string($this->policy->name),
                new moodle_url('/admin/tool/policy/managedocs.php', ['id' => $this->policy->policyid]));
        } else {
            if ($this->policy->status != policy_version::STATUS_ACTIVE) {
                require_login();
            } else if (isguestuser() || empty($USER->id) || !$USER->policyagreed) {
                // Disable notifications for new users, guests or users who haven't agreed to the policies.
                $PAGE->set_popup_notification_allowed(false);
            }
            $PAGE->set_url($myurl);
            $PAGE->set_heading($SITE->fullname);
            $PAGE->set_title(get_string('policiesagreements', 'tool_policy'));
            $PAGE->navbar->add(get_string('policiesagreements', 'tool_policy'), new moodle_url('/admin/tool/policy/index.php'));
            $PAGE->navbar->add(format_string($this->policy->name));
        }

        if (!api::can_user_view_policy_version($this->policy, $this->behalfid)) {
            throw new moodle_exception('accessdenied', 'tool_policy');
        }
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $DB, $CFG;
        $canownermanage = has_capability('tool/policy:managedocs', \context_system::instance());
        $policydata = $DB->get_record('tool_policy_versions', array('id' => $this->versionid));
        
         // echo "<pre>";
         // print_r($policydata);
         // echo "<br>";
        $policytype = '';
        $documentno = $policydata->docnumber;
        $versionrevision = $policydata->revision;
        if($policydata->type == 0) {
        	$policytype = 'Site Policy';
        } else if($policydata->type == 1) {
        	$policytype = 'Private Policy';
        } else if($policydata->type == 2) {
        	$policytype = 'Third Party Policyies';
        } else {
        	$policytype = 'Other Policyies';
        }
        $commentdata = $DB->get_records_sql("SELECT ev.*,ec.*,ec.assignto as userid FROM {tool_policy_comment} ec INNER JOIN {tool_policy_versions} ev ON ev.id = ec.policyversionid WHERE ev.policyid = '". $policydata->policyid."' AND ev.id = '".$this->versionid."' ORDER BY ev.id ASC");
        $approvels = $DB->get_records_sql("SELECT ev.*,ec.*,ec.assignto as userid FROM {tool_policy_comment} ec INNER JOIN {tool_policy_versions} ev ON ev.id = ec.policyversionid WHERE ev.policyid = '". $policydata->policyid."' AND ev.id = '".$this->versionid."' ORDER BY ev.id ASC");
        $commentarr = array();
        $apporvelarr = array();
        $sno = 1;
        foreach($commentdata as $comment) {
            $commentval = '';
            if($comment->cstatus == 0) {
                $commentval = 'Authering';
            } else if($comment->cstatus == 1) {
                $commentval = 'Reviewing';
            } else if($comment->cstatus == 2) {
                $commentval = 'Approving';
            } else {
                $commentval = 'Approved';
            }
            $user = $DB->get_record('user', array('id' => $comment->userid), 'id, firstname, lastname');
            $commentarr[] = array('sno' => $sno, 'id' => $comment->id, 'policyversionid' => $comment->policyversionid, 'commentext' => $comment->commentext, 'assignto' => $user->firstname. ' '. $user->lastname, 'cstatus' => $commentval, 'timemodified' => date('d-m-Y H:i:s',$comment->timemodified), 'commentversion' => "$comment->revision-V$sno");
            $sno++;
        }
        $sno = 1;
        
        foreach($approvels as $comment) {
            if(!empty($comment->userid)){
             $role=  $DB->get_record_sql("SELECT r.shortname FROM {role} AS r INNER JOIN {role_assignments} AS ra ON r.id=ra.roleid WHERE ra.userid =".$comment->userid." AND ra.contextid =1");
             //print_r($role); die('role');
            }

            $commentval = '';
            if($comment->cstatus == 0) {
                $commentval = 'Authering';
            } else if($comment->cstatus == 1) {
                $commentval = 'Reviewing';
            } else if($comment->cstatus == 2) {
                $commentval = 'Approving';
            } else {
                $commentval = 'Approved';
            }
            $user = $DB->get_record('user', array('id' => $comment->userid), 'id, firstname, lastname');
            $apporvelarr[] = array('sno' => $sno, 'id' => $comment->id, 'policyversionid' => $comment->policyversionid, 'commentext' => $comment->commentext, 'assignto' => $user->firstname. ' '. $user->lastname, 'cstatus' => $commentval, 'timemodified' => date('d-m-Y H:i:s',$comment->timemodified), 'commentversion' => "$comment->revision-V$sno",'role'=>$role->shortname);
            $sno++;
        }
        //print_r($apporvelarr);die();
        
        $messagetextcontent = file_rewrite_pluginfile_urls($policydata->content, 'pluginfile.php', 1, 'tool_policy', 'policydocumentcontent', $this->versionid);

        $messagetextsummary = file_rewrite_pluginfile_urls($policydata->summary, 'pluginfile.php', 1, 'tool_policy', 'policydocumentsummary', $this->versionid);
        
        require_once($CFG->dirroot.'/filter/kaltura/filter.php');
        $messagetextsummary = filter_kaltura::filter($messagetextsummary);
        $messagetextcontent = filter_kaltura::filter($messagetextcontent);
        $this->policy->summary = $messagetextsummary;
        $this->policy->content = $messagetextcontent;

        $relatedp = '';
        if(!empty($policydata->relatedpolicy)) {
         $relatedp = $DB->get_records_sql("SELECT ev.id as id,ev.name, e.id as policyid  FROM {tool_policy_versions} ev INNER JOIN {tool_policy} e ON ev.id = e.currentversionid WHERE ev.id IN (".$policydata->relatedpolicy.")");
        }
          $relatedparr = array();
          //print_r($relatedp);
          foreach ($relatedp as $rpolicy) {
             $relatedparr[] =array('policyid'=>$rpolicy->policyid,'versionid'=>$rpolicy->id,'policyname'=>$rpolicy->name);
          }

          $relatedc = '';
        if(!empty($policydata->relatedcourse)) {
         $relatedc=$DB->get_records_sql("SELECT * FROM {course}  WHERE id IN (".$policydata->relatedcourse.")");
        }
          $relatedcarr = array();
          //print_r($relatedp);
          foreach ($relatedc as $rcourse) {
             $relatedcarr[] =array('courseid'=>$rcourse->id,'fullname'=>$rcourse->fullname);
          }
        
         if(has_capability('tool/policy:managedocs',\context_system::instance())){
        $viewurl = '&manage=1';
      }else{
        $viewurl ='';
       }
        $data = (object) [
            'pluginbaseurl' => (new moodle_url('/admin/tool/policy'))->out(false),
            'returnurl' => $this->returnurl ? (new moodle_url($this->returnurl))->out(false) : null,
            'numpolicy' => $this->numpolicy ? : null,
            'totalpolicies' => $this->totalpolicies ? : null,
            'type' => $policytype,
            'documentno' => $documentno,
            'versionrevision' => $versionrevision." -V".$sno,
            'commentdata' => $commentarr,
            'approveldata' => $apporvelarr,
            'relatepolicy'=> $relatedparr,
            'relatecourse'=> $relatedcarr,
            'siteurl'=>$CFG->wwwroot,
            'managepolicy'=>$canownermanage,
            'viewreturn'=> (new moodle_url('/admin/tool/policy/managedocs.php'))
        ];

        if ($this->manage && $this->policy->status != policy_version::STATUS_ARCHIVED) {
            $paramsurl = ['policyid' => $this->policy->policyid, 'versionid' => $this->policy->id];
            $data->editurl = (new moodle_url('/admin/tool/policy/editpolicydoc.php', $paramsurl))->out(false);
        }

        if ($this->policy->agreementstyle == policy_version::AGREEMENTSTYLE_OWNPAGE) {
            if (!api::is_user_version_accepted($USER->id, $this->policy->id)) {
                unset($data->returnurl);
                $data->accepturl = (new moodle_url('/admin/tool/policy/index.php', [
                    'listdoc[]' => $this->policy->id,
                    'status'.$this->policy->id => 1,
                    'submit' => 'accept',
                    'sesskey' => sesskey(),
                ]))->out(false);
                if ($this->policy->optional == policy_version::AGREEMENT_OPTIONAL) {
                    $data->declineurl = (new moodle_url('/admin/tool/policy/index.php', [
                        'listdoc[]' => $this->policy->id,
                        'status'.$this->policy->id => 0,
                        'submit' => 'decline',
                        'sesskey' => sesskey(),
                    ]))->out(false);
                }
            }
        }

        $data->policy = clone($this->policy);
        // echo "<pre>";
        // print_r($data);
        // die();
        return $data;
    }
}
