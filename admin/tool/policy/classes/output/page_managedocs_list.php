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
 * Provides {@link tool_policy\output\page_managedocs_list} class.
 *
 * @package     tool_policy
 * @category    output
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_policy\output;

use html_writer;
use tool_policy\api;

defined('MOODLE_INTERNAL') || die();

use action_menu;
use action_menu_link;
use moodle_url;
use pix_icon;
use renderable;
use renderer_base;
use single_button;
use templatable;
use tool_policy\policy_version;

/**
 * Represents a management page with the list of policy documents.
 *
 * The page displays all policy documents in their sort order, together with draft future versions.
 *
 * @copyright 2018 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_managedocs_list implements renderable, templatable {

    /** @var int  */
    protected $policyid = null;
    /** @var moodle_url */
    protected $returnurl = null;

    /**
     * page_managedocs_list constructor.
     * @param int $policyid when specified only archived versions of this policy will be displayed.
     */
    public function __construct($policyid = null, $search = null,$filterby = null) {
        $this->policyid = $policyid;
        $this->search = $search;
        $this->filterby = $filterby;
       
  
        //$this->search; die();
        $this->returnurl = new moodle_url('/admin/tool/policy/managedocs.php');
        if ($this->policyid) {
            $this->returnurl->param('archived', $this->policyid);
        }
    }

    /**
     * Export the page data for the mustache template.
     *
     * @param renderer_base $output renderer to be used to render the page elements.
     * @return stdClass
     */
    // working for add new and heading 
    public function export_for_template(renderer_base $output) {
        $filterby='';
        $searchby='';
        

        $data = (object) [];
        $data->pluginbaseurl = (new moodle_url('/admin/tool/policy'))->out(false);
        $data->canmanage = has_capability('tool/policy:accept', \context_system::instance());
        $data->canaddnew = $data->canmanage && !$this->policyid;
        $data->canownermanage = has_capability('tool/policy:policyowner', \context_system::instance());
        $data->canviewacceptances = has_capability('tool/policy:viewacceptances', \context_system::instance());
        $data->title = get_string('policiesagreements', 'tool_policy');
        $filteroptions = array('date' => get_string('policydate', 'tool_policy') , 'revision' => get_string('policyrevision', 'tool_policy') ,'status' => get_string('policyfilterstatus', 'tool_policy'),'type' => get_string('policytype', 'tool_policy') , 'lang' => get_string('policylang', 'tool_policy'),'comp' => get_string('policycomp', 'tool_policy'));
        
        $data->policies = [];
        $searchby=$this->search; 
        $data->searchby=$searchby;
        $filterby = $this->filterby;
        $data->filterby=$filterby;

        // $data->filterheading = 'true';

        // if((!empty($this->policyid)) && (empty($this->search)) && (empty($this->filterby)))
        // {
        //     echo "<br>";
        //     print_r("working after calling method for search");
        //     die();
        // }

        if((!empty($this->search)) && (empty($this->filterby)) && (empty($this->policyid)))
        {
           // $searchby=$this->search; 
           // $data->searchby=$searchby;

          // print_r($searchby);
           $this->searchvalue = api::view_policy_search($this->search);
           // print_r("searchvalue result is");
          // print_r($this->searchvalue);


           foreach ($this->searchvalue as $key => $value) {

                $policies=api::list_policies(null,true);
               // print_r($policies);
                       //  echo "<br>";
                 foreach ($policies as $policy) {
                     //print_r($policy->archivedversions);
                    //$afirstpolicy = array_shift($policy->archivedversions);
                    
                    if(has_capability('tool/policy:accept',\context_system::instance())){
                       if ($policy->currentversionid == $value->id) {
                        // print_r($policy->currentversionid);
                         //echo "<br>";
               
                         //  return $policy->currentversion;
                           $data->versions[] = $this->export_version_for_template($output, $policy, $policy->currentversion,
                            false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
                       }
                    }
                     if(has_capability('tool/policy:managedocs',\context_system::instance())){
                       if ($policy->draftversions) {
                        $firstpolicy = array_shift($policy->draftversions);
                       
                         if($firstpolicy->id == $value->id){
                         //  return $policy->currentversion;
                       // print_r($policy->currentversionid);
                         //echo "<br>";
                           $data->versions[] = $this->export_version_for_template($output, $policy, $firstpolicy,
                            false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
                       }
                       }
                   }
                       if(has_capability('tool/policy:managedocs',\context_system::instance())){
                       if($policy->archivedversions){
                        $afirstpolicy = array_shift($policy->archivedversions);

	                    if($afirstpolicy->id==$value->id){    	
	                        	 	$data->versions[] = $this->export_version_for_template($output, $policy, $afirstpolicy,
	                 			false, false, false,$filterby,$searchby);
                         }
                        }    	 	 
                       }
                      }  
           }
          // $data->filteroptions=$filteroptions;
            //return $data;
        }
        if((!empty($this->filterby)) && (empty($this->search)) && (empty($this->policyid)))
        {
          // $filterby = $this->filterby;
          // $data->filterby=$filterby;

          $filteroptions = array('date' => get_string('policydate', 'tool_policy') , 'revision' => get_string('policyrevision', 'tool_policy') ,'status' => get_string('policyfilterstatus', 'tool_policy'),'type' => get_string('policytype', 'tool_policy') , 'lang' => get_string('policylang', 'tool_policy'),'comp' => get_string('policycomp', 'tool_policy'));

          $filterarray = array('date','revision','status','type','lang','comp');
           foreach ($filterarray as $filteraaraykey) {
            $filterselected[$filteraaraykey] =  get_string('policy'.$filteraaraykey, 'tool_policy');

            if($filterby == get_string('policy'.$filteraaraykey, 'tool_policy'))
            {
              $filterselected[$filteraaraykey.'selected'] = 'selected';
            }
           }
          // print_r($filterselected);

           $filteroptions = $filterselected;
         //echo $this->filterby ;
             // print_r("infilterby");
          // echo "<pre>"; 
         // print_r(api::apply_policy_filter($this->filterby));
          // die(); 
           $this->filtervaluechanged = api::apply_policy_filter($this->filterby);

           //print_r($this->filtervaluechanged);
           //die();

           foreach($this->filtervaluechanged as $filtervaluechangedkey => $filtervaluechangedvalue)
           {
            //print_r($filtervaluechangedvalue);
           // echo "<br>";
                   //echo "<pre>";
                         $policies=api::list_policies(null,true);
               // print_r($policies);
                //die();
                       //  echo "<br>";
                 foreach ($policies as $policy) {
                     //print_r($policy->archivedversions);
                    //$afirstpolicy = array_shift($policy->archivedversions);
                    
                    if(has_capability('tool/policy:accept',\context_system::instance())){
                       if ($policy->currentversionid == $filtervaluechangedvalue) {
                        // echo "currentversionid";
                        // print_r($policy->currentversionid);
                        //  echo "<br>";
               
                         //  return $policy->currentversion;
                           $data->versions[] = $this->export_version_for_template($output, $policy, $policy->currentversion,
                            false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
                       }
                    }
                     if(has_capability('tool/policy:managedocs',\context_system::instance())){
                       if ($policy->draftversions) {
                        $firstpolicy = array_shift($policy->draftversions);
                       
                         if($firstpolicy->id == $filtervaluechangedvalue){
                         //  return $policy->currentversion;
                       //      echo "draftversions";
                       // print_r($firstpolicy->id);
                       //   echo "<br>";
                           $data->versions[] = $this->export_version_for_template($output, $policy, $firstpolicy,
                            false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
                       }
                       }
                   }
                       if(has_capability('tool/policy:managedocs',\context_system::instance())){
                        //print_r($policy->archivedversions);
                       if($policy->archivedversions){
                      //  echo "archivedversions";
                        $afirstpolicy = array_shift($policy->archivedversions);
                        
                        // print_r($afirstpolicy->id);
                        // echo "<br>";
                        if($afirstpolicy->id==$filtervaluechangedvalue){      
                                    $data->versions[] = $this->export_version_for_template($output, $policy, $afirstpolicy,
                                false, false, false,$filterby,$searchby);
                         }
                        }            
                       }
                      }  

           }
         //  die();
           // print_r("I am working");
           // print_r($this->filtervaluechanged);
           // die();
           //return $data;
        }
        if((!empty($this->policyid)) && (empty($this->search)) && (empty($this->filterby))) {
            // We are only interested in the archived versions of the given policy.
            $data->backurl = (new moodle_url('/admin/tool/policy/managedocs.php'))->out(false);
            $policy = api::list_policies([$this->policyid], true)[0];
            if ($firstversion = $policy->currentversion ?: (reset($policy->draftversions) ?: reset($policy->archivedversions))) {
                $data->title = get_string('previousversions', 'tool_policy', format_string($firstversion->name));
            }

            foreach ($policy->archivedversions as $i => $version) {

                $data->versions[] = $this->export_version_for_template($output, $policy, $version,
                    false, false, false,$filterby,$searchby);
            }

            foreach ($policy->draftversions as $i => $version) {

                $data->versions[] = $this->export_version_for_template($output, $policy, $version,
                    false, false, false,$filterby,$searchby);
            }
            // $data->filteroptions=$filteroptions;

            // return $data;
        }

        // List all policies. Display current and all draft versions of each policy in this list.
        // If none found, then show only one archived version.

        if((empty($this->filterby)) && (empty($this->search)) && (empty($this->policyid)))
        {
        $policies = api::list_policies(null, true);
        foreach ($policies as $i => $policy) {

           if(has_capability('tool/policy:managedocs', \context_system::instance())){
            if (empty($policy->currentversion) && empty($policy->draftversions)) {
                // There is no current and no draft versions, display the first archived version.
                $firstpolicy = array_shift($policy->archivedversions);
                $data->versions[] = $this->export_version_for_template($output, $policy, $firstpolicy,
                    false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
            }}

            if (!empty($policy->currentversion)) {
                // print_r($policy); die('pc');
                // Current version of the policy.
                $data->versions[] = $this->export_version_for_template($output, $policy, $policy->currentversion,
                    false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);

            } else if ($policy->draftversions) {

                // There is no current version, display the first draft version as the current.
                if(has_capability('tool/policy:managedocs', \context_system::instance())){
                $firstpolicy = array_shift($policy->draftversions);
                $data->versions[] = $this->export_version_for_template($output, $policy, $firstpolicy,
                    false, $i > 0, $i < count($policies) - 1,$filterby,$searchby);
            }
            }

            // foreach ($policy->draftversions as $draft) {
            //     // Show all [other] draft policies indented.
            //     $data->versions[] = $this->export_version_for_template($output, $policy, $draft,
            //         true, false, false);
            // }
        }
        }
         $data->filteroptions=$filteroptions;

        return $data;
        //         echo "<pre>";
        // print_r($data);

        //return "add";
        
    }

    /**
     * Exports one version for the list of policies
     *
     * @param \renderer_base $output
     * @param \stdClass $policy
     * @param \stdClass $version
     * @param bool $isindented display indented (normally drafts of the current version)
     * @param bool $moveup can move up
     * @param bool $movedown can move down
     * @return \stdClass
     */
    protected function export_version_for_template($output, $policy, $version, $isindented, $moveup, $movedown,$filterby,$searchby) {
        
       // echo "<pre>";print_r($policy); 
      // if ($previousId !== '' && $previousId !== $version->type) {
      //   // put a border
      //   }
      //   $previousId = $version->type;
      //   echo "<pre>";print_r($version);
      //   die();
        $status = $version->status;
        $version->statustext = get_string('status' . $status, 'tool_policy');
        $version->sortorder=$policy->sortorder;
        if ($status == policy_version::STATUS_ACTIVE) {
            $version->statustext = html_writer::tag('button',$version->statustext,array("class"=>"btn btn-primay btn-green btnwidth"));
            $version->graph = 1;
        } else if ($status == policy_version::STATUS_DRAFT) {
            $version->statustext = html_writer::tag('button',$version->statustext,array("class"=>"btn btn-default btn-gray btnwidth"));
        } else {
            $version->statustext = html_writer::tag('button',$version->statustext,array("class"=>"btn  btn-danger btn-red  btnwidth"));
            $version->graph = 1;
        }

        if ($version->optional == policy_version::AGREEMENT_OPTIONAL) {
            $version->optionaltext = get_string('policydocoptionalyes', 'tool_policy');
        } else {
            $version->optionaltext = get_string('policydocoptionalno', 'tool_policy');
        }

        //$version->indented = $isindented;
        
        $editbaseurl = new moodle_url('/admin/tool/policy/editpolicydoc.php', [
            'sesskey' => sesskey(),
            'policyid' => $policy->id,
            'returnurl' => $this->returnurl->out_as_local_url(false),
        ]);
        if(has_capability('tool/policy:managedocs',\context_system::instance())){
        $viewurl = new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $policy->id,
            'versionid' => $version->id,
            'manage' => 1,
            'returnurl' => $this->returnurl->out_as_local_url(false),
        ]);
        $version->viewmainpolicy = $viewurl;
      }else{
        $viewurl = new moodle_url('/admin/tool/policy/view.php', [
            'policyid' => $policy->id,
            'versionid' => $version->id,
            'returnurl' => $this->returnurl->out_as_local_url(false),
        ]);
        $version->viewmainpolicy = $viewurl;
       }
        $commenturl = new moodle_url('/admin/tool/policy/editcommentview.php', [
            'policyid' => $policy->id,
            'versionid' => $version->id,
            'manage' => 1,
            'returnurl' => $this->returnurl->out_as_local_url(false),
        ]);

        

        $version->percentages = $version->acceptancescounttext;
        $val = explode("(",$version->percentages);
        $val_explode = explode(")",$val[1]);
        $version->percentages = $val_explode[0];
        $val1 = explode("%",$val_explode[0]);
        $version->percentagesVal=$val1[0];
        if($version->percentagesVal<=50){
         $version->graphcolor = 'bar-r';   
        }else if(($version->percentagesVal>50) && ($version->percentagesVal<=80)){
         $version->graphcolor = 'bar-y';
        }else if($version->percentagesVal > 80)
        {
          $version->graphcolor='bar-g';  
        } 

        $actionmenu = new action_menu();
        $actionmenu->set_menu_trigger(get_string('actions', 'tool_policy'));
        $actionmenu->set_alignment(action_menu::TL, action_menu::BL);
        $actionmenu->prioritise = true;
        // if ($moveup) {
        //     $actionmenu->add(new action_menu_link(
        //         new moodle_url($editbaseurl, ['moveup' => $policy->id]),
        //         new pix_icon('t/up', get_string('moveup', 'tool_policy')),
        //         get_string('moveup', 'tool_policy'),
        //         true
        //     ));
        // }
        // if ($movedown) {
        //     $actionmenu->add(new action_menu_link(
        //         new moodle_url($editbaseurl, ['movedown' => $policy->id]),
        //         new pix_icon('t/down', get_string('movedown', 'tool_policy')),
        //         get_string('movedown', 'tool_policy'),
        //         true
        //     ));
        // }
        $actionmenu->add(new action_menu_link(
            $viewurl,
            null,
            get_string('view'),
            false
        ));
        if ($status != policy_version::STATUS_ARCHIVED) {
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['versionid' => $version->id]),
                null,
                get_string('edit'),
                false
            ));
        }
        if ($status == policy_version::STATUS_ACTIVE) {
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['inactivate' => $policy->id]),
                null,
                get_string('inactivate', 'tool_policy'),
                false,
                ['data-action' => 'inactivate']
            ));
        }
        if(has_capability('tool/policy:policyowner',\context_system::instance())){
        if ($status == policy_version::STATUS_DRAFT) {
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['makecurrent' => $version->id]),
                null,
                get_string('activate', 'tool_policy'),
                false,
                ['data-action' => 'makecurrent']
            ));
        }}
         if(has_capability('tool/policy:policyowner',\context_system::instance())){
        if (api::can_delete_version($version)) {
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['delete' => $version->id]),
                null,
                get_string('delete'),
                false,
                ['data-action' => 'delete']
            ));
        }
        }
        if ($status == policy_version::STATUS_ACTIVE) {
        $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['clone' => $version->id]),
                null,
                get_string('clone', 'tool_policy'),
                false,
                ['data-action' => 'clone']
            ));
            }
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['sendnotification' => $version->id]),
                null,
                get_string('sendnotification', 'tool_policy'),
                false,
                ['data-action' => 'sendnotification']
            ));
        if ($status == policy_version::STATUS_ARCHIVED) {
            $actionmenu->add(new action_menu_link(
                new moodle_url($editbaseurl, ['versionid' => $version->id]),
                null,
                get_string('settodraft', 'tool_policy'),
                false
            ));
        }
        if (!$this->policyid && !$isindented && $policy->archivedversions &&
                ($status != policy_version::STATUS_ARCHIVED || count($policy->archivedversions) > 1)) {
            $actionmenu->add(new action_menu_link(
                new moodle_url('/admin/tool/policy/managedocs.php', ['archived' => $policy->id]),
                null,
                get_string('viewarchived', 'tool_policy'),
                false
            ));
        }
        if ($policy->draftversions && ($status != policy_version::STATUS_ARCHIVED || count($policy->draftversions) > 1)) {
            $actionmenu->add(new action_menu_link(
                new moodle_url('/admin/tool/policy/managedocs.php', ['draft' => $policy->id]),
                null,
                get_string('viewarchived', 'tool_policy'),
                false
            ));
        }
        
        $version->subpolicyparent  = $version->id;
        
          $policies1 = api::list_sub_policies($version->id, true);

     if(empty($policies1)){
        $version->subpolicychild = false;
     }else {
        $version->subpolicychild = true;
     }
        $parr = array();
        $lino = 1;
        foreach($policies1 as $pdata) {
            $subpercent= new \stdClass();
            if(!empty($pdata->currentversion)) {
                $subpercent->subpercentages = $pdata->currentversion->acceptancescounttext;
                $val = explode("(",$subpercent->subpercentages);
                $val_explode = explode(")",$val[1]);
                $subpercent->subpercentages = $val_explode[0];
                $val2 = explode("%",$val_explode[0]);
                $subpercent->subpercentageVal=$val2[0];
                if($subpercent->subpercentageVal<50){
                 $subpercent->grapcolor='red';   
                }else if(($subpercent->subpercentageVal>50) || ($subpercent->subpercentageVal<80)){
                 $subpercent->grapcolor='yellow';
                }else{
                  $subpercent->grapcolor='green';  
                }
                $status = $pdata->currentversion->status;
                $subname = $pdata->currentversion->name;
                $subrevision = $pdata->currentversion->revision;
                $sublang = $pdata->currentversion->primarylang;
                $vid = $pdata->currentversion->id;
            } else if(!empty($pdata->draftversions)) {
                $subpercent->subpercentages = $pdata->draftversions[0]->acceptancescounttext;
                $val = explode("(",$subpercent->subpercentages);
                $val_explode = explode(")",$val[1]);
                $subpercent->subpercentages = $val_explode[0];
                $val2 = explode("%",$val_explode[0]);
                $subpercent->subpercentageVal=$val2[0];
                $status = $pdata->draftversions[0]->status;
                $subname = $pdata->draftversions[0]->name;
                $subrevision = $pdata->draftversions[0]->revision;
                $sublang = $pdata->draftversions[0]->primarylang;
                $vid = $pdata->draftversions[0]->id;

            } else {
                
               $subpercent->subpercentages = $pdata->archivedversions->acceptancescounttext;
                $val = explode("(",$subpercent->subpercentages);
                $val_explode = explode(")",$val[1]);
                $subpercent->subpercentages = $val_explode[0];
                $val2 = explode("%",$val_explode[0]);
                $subpercent->subpercentageVal=$val2[0];
                $status = $pdata->archivedversions->status;
                $subname = $pdata->archivedversions->name;
                $subrevision = $pdata->archivedversions->revision;
                $sublang = $pdata->archivedversions->primarylang;
                $vid = $pdata->archivedversions->id;
                
            }
             
            $version->statustext1 = get_string('status' . $status, 'tool_policy');

            if ($status == policy_version::STATUS_ACTIVE) {
                $version->statustext1 = html_writer::tag('button',$version->statustext1,array("class"=>"btn btn-primay btn-green btnwidth"));
                  $version->graph1 = 1;
            } else if ($status == policy_version::STATUS_DRAFT) {
                $version->statustext1 = html_writer::tag('button',$version->statustext1,array("class"=>"btn btn-default btn-gray  btnwidth"));
            } else {
                $version->statustext1 = html_writer::tag('button',$version->statustext1,array("class"=>"btn btn-danger btn-red btnwidth"));
                $version->graph1 = 1;
            }
             $editbaseurl1 = new moodle_url('/admin/tool/policy/editpolicydoc.php', [
            'sesskey' => sesskey(),
            'policyid' => $pdata->id,
            'returnurl' => $this->returnurl->out_as_local_url(false),
            ]);

            $viewurl1 = new moodle_url('/admin/tool/policy/view.php', [
                'policyid' => $pdata->id,
                'versionid' => $vid,
                'manage' => 1,
                'returnurl' => $this->returnurl->out_as_local_url(false),
            ]);

            $commenturl1 = new moodle_url('/admin/tool/policy/editcommentview.php', [
                'policyid' => $pdata->id,
                'versionid' => $pdata->currentversion->id,
                'manage' => 1,
                'returnurl' => $this->returnurl->out_as_local_url(false),
            ]);
        $actionmenu1 = new action_menu();
        $actionmenu1->set_menu_trigger(get_string('actions', 'tool_policy'));
        $actionmenu1->set_alignment(action_menu::TL, action_menu::BL);
        $actionmenu1->prioritise = true;
        if ($moveup) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['moveup' => $pdata->id]),
                new pix_icon('t/up', get_string('moveup', 'tool_policy')),
                get_string('moveup', 'tool_policy'),
                true
            ));
        }
        if ($movedown) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['movedown' => $pdata->id]),
                new pix_icon('t/down', get_string('movedown', 'tool_policy')),
                get_string('movedown', 'tool_policy'),
                true
            ));
        }
        $actionmenu1->add(new action_menu_link(
            $viewurl1,
            null,
            get_string('view'),
            false
        ));
        if ($status != policy_version::STATUS_ARCHIVED) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['versionid' => $pdata->id]),
                null,
                get_string('edit'),
                false
            ));
        }
        if ($status == policy_version::STATUS_ACTIVE) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['inactivate' => $pdata->id]),
                null,
                get_string('inactivate', 'tool_policy'),
                false,
                ['data-action' => 'inactivate']
            ));
        }
        if(has_capability('tool/policy:policyowner',\context_system::instance())){
        if ($status == policy_version::STATUS_DRAFT) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['makecurrent' => $pdata->id]),
                null,
                get_string('activate', 'tool_policy'),
                false,
                ['data-action' => 'makecurrent']
            ));
        }}
         if(has_capability('tool/policy:policyowner',\context_system::instance())){
        if ($status == policy_version::STATUS_DRAFT) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['delete' => $pdata->id]),
                null,
                get_string('delete'),
                false,
                ['data-action' => 'delete']
            ));
        }}
           if ($status == policy_version::STATUS_ACTIVE) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['clone' => $pdata->id]),
                null,
                get_string('clone', 'tool_policy'),
                false,
                ['data-action' => 'clone']
            ));
        }
           
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['sendnotification' => $pdata->id]),
                null,
                get_string('sendnotification', 'tool_policy'),
                false,
                ['data-action' => 'sendnotification']
            ));
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['sendnotification' => $pdata->id]),
                null,
                get_string('sendnotification', 'tool_policy'),
                false,
                ['data-action' => 'sendnotification']
            ));
        if ($status == policy_version::STATUS_ARCHIVED) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url($editbaseurl1, ['versionid' => $pdata->id]),
                null,
                get_string('settodraft', 'tool_policy'),
                false
            ));
        }
        if ($pdata->archivedversions &&
                ($status != policy_version::STATUS_ARCHIVED || count($pdata->archivedversions) > 1)) {
            $actionmenu1->add(new action_menu_link(
                new moodle_url('/admin/tool/policy/managedocs.php', ['archived' => $pdata->id]),
                null,
                get_string('viewarchived', 'tool_policy'),
                false
            ));
        }  
            $action= new \stdClass();
            $action->actionmenu = $actionmenu1->export_for_template($output);
        
            $type="";
            if($pdata->type==0){
                $type='Site policy';
            }else if($pdata->type==1){
                $type='Privacy policy';
            }else if($pdata->type==2)
            {
                $type='Third parties policy';
            }else{
                $type='Other policy';
            }
            $audience="";
            if($pdata->audience==0){
               $audience= 'All users';
            }else if($pdata->audience==0){
                $audience= 'Authenticated users';
            }else{
                $audience= 'Guests';
            }
            $optional="";
            if($pdata->optional==1){
              $optional="Compulsory";  
            }else{
               $optional="Optional"; 
            }


            $parr[] = array('id'=> $pdata->id, 'name' => $subname, 'timemodified' => date('d F Y', $pdata->timemodified),'optional'=>$optional,'audience'=>$audience,'type'=>$type, 'revision'=>$subrevision,'primarylang1' => $sublang, 'timemodified1' => date('d F Y h:i A', $pdata->timemodified),'action2'=>$action->actionmenu,'subpercentages'=>$subpercent->subpercentages,'subpercentageVal'=>$subpercent->subpercentageVal,'viewsubpolicy'=>$viewurl1,'graphcolor1'=>$subpercent->grapcolor);
                
            }
       
        $version->subpolicyparent = $parr;
        
        $version->actionmenu = $actionmenu->export_for_template($output);
        // echo "<pre>";
        // print_r($version);

        
        return $version;
    }
}
