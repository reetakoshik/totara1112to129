<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 */

defined('MOODLE_INTERNAL') || die();

// Must be declared global as this file is included from other files.
global $CFG, $PAGE;

$strsearch = get_string('search');
$strshowall = get_string('showall', 'moodle', '');
$strsearchresults = get_string('searchresults');

// For backwards compatibility email is always shown here.
// Otherwise those users without viewuseridentity would suddenly lose this information.
// In 9.0 this is fixed, and only users with viewuseridentity will see the users email address.
$extrafields = ['email'];
if (!empty($CFG->showuseridentity) && has_capability('moodle/site:viewuseridentity', $PAGE->context)) {
    $extrafields = explode(',', $CFG->showuseridentity);
}

?>
<form id="assignform" method="post" action="<?php echo $PAGE->url; ?>">
<div class="f2f-usersbox">
<input type="hidden" name="sesskey" value="<?php p(sesskey()) ?>" />
<?php
if (!empty($error)) {
    echo "<div class=\"notifyproblem\">$error</div>";
}
$idx = 0; // Iterator to put elements on their positions when adding/removing.
?>
<div class="row-fluid user-multiselect" >
    <div class="span5">
        <label for="removeselect"><?php echo $strusertochange ?></label>
        <select name="removeselect[]" size="20" id="removeselect" multiple="multiple">
        <?php
            if (!empty($attendees) && $action === 'add') {
                foreach ($attendees as $attendee) {
                    if (!array_key_exists($attendee->id, $userstoadd)) {
                        echo "<option disabled value=\"$attendee->id\">".facetoface_output_user_for_selection($attendee, $extrafields, true)."</option>\n";
                    }
                }
            }
            if (!empty($userstoadd)) {
                foreach ($userstoadd as $newuser) {
                    $fullname = facetoface_output_user_for_selection($newuser, $extrafields, true);

                    if ($session->cntdates && ($newuser->statuscode > MDL_F2F_STATUS_BOOKED)) {
                        $status = get_string('status_'.$MDL_F2F_STATUS[$newuser->statuscode], 'facetoface');
                        echo "<option value=\"$newuser->id\">".$fullname." - ". $status."</option>\n";
                    } else {
                        echo "<option value=\"$newuser->id\">".$fullname."</option>\n";
                    }
                }
            }
        ?>
        </select>
        <label for="searchtoremovetext" class="accesshide"><?php p($strsearch) ?></label>
        <input type="text" name="searchtoremovetext" id="searchtoremovetext" size="20" placeholder="<?php p($strsearch) ?>" value=""/>
        <button name="searchtoremovereset" id="searchtoremovereset" class="search noshow"><?php p($strshowall) ?></button>
    </div>
    <div class="span2 controls addremove">
        <button name="add" id="add"><?php echo $OUTPUT->larrow().'&nbsp;'.$strlarrow; ?></button>
        <button name="remove" id="remove"><?php echo $OUTPUT->rarrow().'&nbsp;'.$strrarrow; ?></button>
    </div>
    <div class="span5">
        <label for="addselect"><?php echo $stravailableusers ?></label>
        <select name="addselect[]" size="20" id="addselect" multiple="multiple">
        <?php
            if (!empty($searchtext)) {
                if ($usercount > MAX_USERS_PER_PAGE) {
                    $serchcount = new stdClass();
                    $serchcount->count = $usercount;
                    $serchcount->search = s($searchtext);
                    echo '<optgroup label="'.get_string('toomanyusersmatchsearch', 'moodle', $serchcount).'"><option></option></optgroup>'."\n"
                        .'<optgroup label="'.get_string('pleasesearchmore').'"><option></option></optgroup>'."\n";
                } else {
                    if (is_array($availableusers) || $availableusers->valid()) {
                        echo "<optgroup label=\"$strsearchresults (" . $usercount . ")\">\n";
                        foreach ($availableusers as $user) {
                            $idx++;
                            $fullname = facetoface_output_user_for_selection($user, $extrafields, true);
                            if ($session->cntdates && ($user->statuscode == MDL_F2F_STATUS_WAITLISTED)) {
                                $status = get_string('status_'.$MDL_F2F_STATUS[$user->statuscode], 'facetoface');
                                echo "<option data-idx=\"$idx\" value=\"$user->id\">".$fullname." - ". $status."</option>\n";
                            } else {
                                echo "<option data-idx=\"$idx\" value=\"$user->id\">".$fullname."</option>\n";
                            }
                        }
                    } else {
                        echo '<optgroup label="'.get_string('nomatchingusers', 'moodle', s($searchtext)).'"><option></option></optgroup>'."\n"
                            .'<optgroup label="'.get_string('pleasesearchmore').'"><option></option></optgroup>'."\n";
                    }
                    if (is_object($availableusers)) {
                        $availableusers->close();
                    }
                }
                echo "</optgroup>\n";
            } else {
                if ($usercount > MAX_USERS_PER_PAGE) {
                    echo '<optgroup label="'.get_string('toomanytoshow').'"><option></option></optgroup>'."\n"
                          .'<optgroup label="'.get_string('trysearching').'"><option></option></optgroup>'."\n";
                } else {
                    if (is_array($availableusers) || $availableusers->valid()) {
                        foreach ($availableusers as $user) {
                            $idx++;
                            $fullname = facetoface_output_user_for_selection($user, $extrafields, true);
                            if ($session->cntdates && ($user->statuscode == MDL_F2F_STATUS_WAITLISTED)) {
                                $status = get_string('status_'.$MDL_F2F_STATUS[$user->statuscode], 'facetoface');
                                echo "<option data-idx=\"$idx\" value=\"$user->id\">".$fullname." - ". $status."</option>\n";
                            } else {
                                echo "<option data-idx=\"$idx\" value=\"$user->id\">".$fullname."</option>\n";
                            }
                        }
                    } else {
                        echo '<optgroup label="'.get_string('nousersfound').'"><option></option></optgroup>';
                    }
                    if (is_object($availableusers)) {
                        $availableusers->close();
                    }
                }
           }
         ?>
        </select>
        <label for="searchtext" class="accesshide"><?php p($strsearch) ?></label>
        <input type="text" name="searchtext" id="searchtext" size="20" value="<?php p($searchtext) ?>"/>
        <input name="search" id="search" type="submit" class="search" value="<?php p($strsearch) ?>"/>
        <?php if (!empty($searchtext)) { ?>
        <input name="clearsearch" id="clearsearch" class="search" type="submit" value="<?php echo $strshowall ?>"/>
        <?php } ?>
        <?php
            $strinterested = get_string('declareinterestfiltercheckbox', 'mod_facetoface');
            $attrchecked = $interested ? 'checked="checked"' : '';
        ?>
        <?php if (empty($nointerestsearch)) { ?>
        <br/>
        <input name="interested" id="interested" type="checkbox" value="1" <?php echo $attrchecked;?>/>
        <label for="interested"><?php echo $strinterested; ?></label>
        <?php } ?>
    </div>
</div>
</div>
    <?php if ($action === 'add') {
        $strignoreconflicts = get_string('allowscheduleconflicts', 'mod_facetoface');
        $attrcheckedconflicts = $ignoreconflicts ? 'check="checked"' : '';
        ?>
        <div class="f2f-ignoreconflicts-checkbox">
            <input name="ignoreconflicts" type="hidden" value="0"/>
            <input name="ignoreconflicts" id="ignoreconflicts" type="checkbox" value="1" <?php echo $attrcheckedconflicts;?>/>
            <label for="ignoreconflicts"><?php echo $strignoreconflicts; ?></label>
        </div>
    <?php } ?>
    <input name="next" id="next" type="submit" value="<?php echo get_string('continue'); ?>" class="form-submit btn btn-primary"/>
    <input name="cancel" id="cancel" type="submit" value="<?php echo get_string('cancel'); ?>"  class="btn btn-default"/>

</form>
