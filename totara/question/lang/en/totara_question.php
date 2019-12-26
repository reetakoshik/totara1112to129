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
 * @author Simon Coggins <simon.coggins@totaralms.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralms.com>
 * @package totara
 * @subpackage totara_question
 */

$string['add'] = 'Add';
$string['addanotheroption'] = 'Add another option';
$string['aggregate'] = 'Aggregate rating questions';
$string['aggregate_help'] = 'Aggregate rating questions allow you to select ratings questions from previous pages and aggregate the scores.';
$string['aggregateaverage'] = 'Display average';
$string['aggregatedisplayavg'] = ' Average score: {$a}';
$string['aggregatedisplaymed'] = ' Median score: {$a}';
$string['aggregatedisplayscores'] = ' Display scores';
$string['aggregatemedian'] = 'Display median';
$string['aggregatenooptions'] = 'There are no selectable options for aggregation, options must be numerical or custom ratings questions and on an earlier page than the aggregate question.';
$string['aggregatequestions'] = 'Rating questions';
$string['aggregatetype'] = 'Aggregation Type';
$string['aggregateincludezeroscores'] = 'Include zero scores';
$string['aggregateincludescoresforunanswered'] = 'Include scores for unanswered questions';
$string['aggregateincludedvalues'] = 'Included values';
$string['aggregateincludedvalues_help'] = 'By default, only answered questions resulting in a non-zero score are included in the average and/or median calculations. The provided checkboxes can be used to also include unanswered and/or zero scored questions in the calculation.When you check the **Include scores for unanswered questions** option, unanswered questions will be given the default score specified by an administrator. If no default score exists then they will be given the minimum value (numeric scales) or 0 (custom scales). When you check the **Include zero scores**  option, questions with a zero score will be used in the calculation. When either/both options are enabled, the overall calculated result will be lower when zero scores and/or unanswered questions are present.';
$string['answerrange'] = 'Answer range';
$string['atleastonerequired'] = 'You must specify at least one item';
$string['availablechoices'] = 'Available choices';
$string['choice'] = 'Choice';
$string['choicemenu'] = 'Menu of choices';
$string['choicesmustbenamed'] = 'Saved choices must be given a name';
$string['choiceradio'] = 'Radio buttons';
$string['choosecompfromplanreview'] = 'Choose competencies to review';
$string['choosecoursefromplanreview'] = 'Choose courses to review';
$string['chooseevidencefromplanreview'] = 'Choose evidence to review';
$string['chooseexisting'] = 'Choose an existing question to redisplay...';
$string['choosegoalsreview'] = 'Choose goals to review';
$string['chooseobjfromplanreview'] = 'Choose objectives to review';
$string['chooseprogfromplanreview'] = 'Choose programs to review';
$string['chooserequiredlearningreview'] = 'Choose required learning to review';
$string['competencystatus'] = 'Current competency status';
$string['createnewchoices'] = 'Create new options';
$string['dateselection'] = 'Date selection';
$string['datefirstyear'] = 'First year available';
$string['datelastyear'] = 'Last year available';
$string['dateincludetime'] = 'Include time as well as date';
$string['dateincludetimezone'] = 'Include timezone as well as time';
$string['dateinvalid'] = 'Invalid date selection';
$string['defaultmake'] = 'Make selected by default';
$string['defaultselected'] = 'Selected by default';
$string['defaultunselect'] = 'unselect';
$string['defaultvalueoutrange'] = 'Default value out of range';
$string['delete'] = 'Delete';
$string['deletethis'] = 'Delete {$a}';
$string['deletedquestion'] = 'Question deleted';
$string['detailsof'] = 'Details of {$a}';
$string['displaysettings'] = 'Display settings';
$string['error:aggregatedisplayselect'] = 'You must select one (or both) of the average / median display options';
$string['error:aggregatequestionselect'] = 'You must select at least one (preferably more) question to aggregate';
$string['error:allowselectgoals'] = 'You must allow some goals to be added to the question';
$string['error:invalidfunctioncalledinredisplay'] = 'A call was made to a function in a redisplay question which should never be
called. This is caused by a programming error. This probably happened because a function in this module loaded a question
directly from the database, rather than using a provided function. Make sure that redirect questions are processed when
loading from the db - i.e. select the desired questions, look for any redirect questions and replace them with the linked
question, use the processed set of questions. There should be functions provided which perform these operations.';
$string['error:choosedatatype'] = 'Choose element to add';
$string['error:elementnotfound'] = 'Question not found';
$string['error:goalselectionmustallowsomething'] = 'At least one goal type must be allowed when adding goal review items';
$string['error:reviewmustselectitem'] = 'At least one item must be reviewed';
$string['error:scorenumeric'] = 'The value for score must be numeric';
$string['error:selectatleastone'] = 'You must select at least one';
$string['error:twooptions'] = 'At least two options required';
$string['error:userinfoatleastone'] = 'At least one item must be selected';
$string['fieldrequired'] = 'This field is required';
$string['fieldspercompfromplan'] = 'Fields per competency';
$string['fieldspercoursefromplan'] = 'Fields per course';
$string['fieldsperevidencefromplan'] = 'Fields per evidence';
$string['fieldspergoals'] = 'Fields per goal';
$string['fieldsperobjfromplan'] = 'Fields per objective';
$string['fieldsperprogfromplan'] = 'Fields per program';
$string['fieldsperrequiredlearning'] = 'Fields per required learning';
$string['goalhasnoscale'] = 'This goal has no scale';
$string['goalselection'] = 'Goal selection';
$string['goalscompany'] = 'Company goals';
$string['goalspersonal'] = 'Personal goals';
$string['goalstatus'] = 'Current goal status';
$string['groupquestion'] = 'Question';
$string['groupreview'] = 'Review question';
$string['groupother'] = 'Non-question element';
$string['image'] = 'Image';
$string['infotodisplay'] = 'Information to display';
$string['notanswered'] = 'Not yet answered';
$string['managernames'] = 'Managers';
$string['moveup'] = 'Move up';
$string['movedown'] = 'Move down';
$string['multichoicecheck'] = 'Checkboxes';
$string['multichoicemenu'] = 'Menu of choices';
$string['multichoicemultimenu'] = 'Multi-select menu of choices';
$string['multichoiceradio'] = 'Radio buttons';
$string['multiplefields'] = 'Multiple fields';
$string['multiplefields_help'] = 'By default, when multiple fields is disabled, one text box per review item
will be provided for the users to put their answers.

When multiple fields is enabled, you can create several text boxes for each review item, each with its own
title. Enter a title (such as a question relating to the review items) for each text box that you want to provide.';
$string['noanswer'] = '-';
$string['nolearnercompfromplan'] = '{$a}\'s learning plan(s) don\'t contain any competencies to review';
$string['nolearnercoursefromplan'] = '{$a}\'s learning plan(s) don\'t contain any courses to review';
$string['nolearnerevidencefromplan'] = '{$a}\'s learning plan(s) don\'t contain any evidence to review';
$string['nolearnergoals'] = '{$a} doesn\'t have any goals to review';
$string['nolearnerobjfromplan'] = '{$a}\'s learning plan(s) don\'t contain any objectives to review';
$string['nolearnerprogfromplan'] = '{$a}\'s learning plan(s) don\'t contain any programs to review';
$string['nolearnerrequiredlearning'] = '{$a}\ doesn\'t have any required learning to review';
$string['noresponse'] = 'No response';
$string['noselfcompfromplan'] = 'Your learning plan(s) don\'t contain any competencies to review';
$string['noselfcoursefromplan'] = 'Your learning plan(s) don\'t contain any courses to review';
$string['noselfevidencefromplan'] = 'Your learning plan(s) don\'t contain any evidence to review';
$string['noselfgoals'] = 'You don\'t have any goals to review';
$string['noselfobjfromplan'] = 'Your learning plan(s) don\'t contain any objectives to review';
$string['noselfprogfromplan'] = 'Your learning plan(s) don\'t contain any programs to review';
$string['noselfrequiredlearning'] = 'You don\'t have any required learning to review';
$string['nothingselected'] = 'No items selected';
$string['opensinnewwindow'] = 'Opens in new window';
$string['organisations'] = 'Organisations';
$string['pluginname'] = 'Question';
$string['positions'] = 'Positions';
$string['question'] = 'Question';
$string['question_help'] = 'Here you specify what information you want the user to provide when they answer this element.';
$string['questionandstage'] = '{$a->name} ({$a->stage})';
$string['questionandtype'] = '{$a->name} ({$a->type})';
$string['questionaddheader'] = 'Add {$a}';
$string['questioneditheader'] = 'Edit {$a}';
$string['questionviewheader'] = 'View {$a}';
$string['questionmanage'] = 'Manage question';
$string['questiontypeaggregate'] = 'Aggregate rating questions';
$string['questiontypetext'] = 'Short text';
$string['questiontypelongtext'] = 'Long text';
$string['questiontypemultichoice'] = 'Multiple choice (one answer)';
$string['questiontypemultichoicemulti'] = 'Multiple choice (several answers)';
$string['questiontyperatingcustom'] = 'Rating (custom scale)';
$string['questiontyperatingnum'] = 'Rating (numeric scale)';
$string['questiontyperedisplay'] = 'Redisplay previous question';
$string['questiontypedate'] = 'Date picker';
$string['questiontypefile'] = 'File upload';
$string['questiontypegoals'] = 'Goals';
$string['questiontypecompfromplan'] = 'Competencies from Learning plan';
$string['questiontypecoursefromplan'] = 'Courses from Learning plan';
$string['questiontypeevidencefromplan'] = 'Evidence from Learning plan';
$string['questiontypeobjfromplan'] = 'Objectives from Learning plan';
$string['questiontypeprogfromplan'] = 'Programs from Learning plan';
$string['questiontyperequiredlearning'] = 'Required Learning';
$string['questiontypefixedtext'] = 'Fixed text';
$string['questiontypefixedimage'] = 'Fixed image';
$string['questiontypereviewmulti'] = '{$a} (multiple fields)';
$string['questiontypeuserinfo'] = 'User profile information';
$string['rangefrom'] = 'From';
$string['rangelimit'] = 'The maximum range for the numeric scale is 1000';
$string['rangeto'] = 'To';
$string['rangeslider'] = 'Slider';
$string['rangeinput'] = 'Text input field';
$string['rangeinvalid'] = 'Invalid range parameters';
$string['ratingchoicemusthavescore'] = 'Every specified choice must have a score';
$string['ratingscoremusthavechoice'] = 'Every specified score must have a label';
$string['ratingrequiredrange'] = 'Valid answers are from {$a->from} to {$a->to} inclusive.';
$string['redisplay'] = 'Redisplay question';
$string['redisplay_help'] = 'This allows you to redisplay a previous question.
Redisplaying a future question or another redisplay question is not possible (they are greyed out in the select box).
Answers or information entered in the original question will be shown in the redisplayed question. Changes made
in the redisplayed question will be saved over the original answer. If the original question is locked for a particular
user then the redisplayed question will also be locked for that user.';
$string['remove'] = 'Remove';
$string['removeconfirm'] = 'Are you sure you want to remove this item?';
$string['removethis'] = 'Remove {$a}';
$string['reorder'] = 'Change order';
$string['reviewcompfromplanassignmissing'] = 'This competency has been removed from the learning plan';
$string['reviewcoursefromplanassignmissing'] = 'This course has been removed from the learning plan';
$string['reviewevidencefromplanassignmissing'] = 'This evidence has been removed from the learning plan';
$string['reviewgoalsassignmissing'] = 'This goal has been removed';
$string['reviewobjfromplanassignmissing'] = 'This objective has been removed from the learning plan';
$string['reviewprogfromplanassignmissing'] = 'This program has been removed from the learning plan';
$string['reviewrequiredlearningassignmissing'] = 'This program has been removed from required learning';
$string['reviewincluderating'] = 'Include rating';
$string['reviewincludepersonal'] = 'Include personal goal custom fields';
$string['reviewnamewithplan'] = '{$a->fullname} ({$a->planname})';
$string['required'] = 'User must provide answer to this question';
$string['savechoicesas'] = 'Save these options for other questions as ';
$string['score'] = 'Score';
$string['selectall'] = 'Select All';
$string['selectcompanyaddall'] = 'Automatically add all to review';
$string['selectcompanydonotreview'] = 'Do not review company goals';
$string['selectcompanyusercanchoose'] = 'Users can choose which to review';
$string['selectpersonaladdall'] = 'Automatically add all to review';
$string['selectpersonaldonotreview'] = 'Do not review personal goals';
$string['selectpersonalusercanchoose'] = 'Users can choose which to review';
$string['setdefault'] = 'Set default';
$string['settings'] = 'Settings';
$string['unavailableforguest'] = 'This question can only be answered by logged-in users';
$string['uploadoptions'] = 'Upload options';
$string['uploadmaxinvalid'] = 'Number of files must be at least one';
$string['uploadmaxnum'] = 'Maximum number of files';
$string['userselectednothing'] = 'User selected nothing';
$string['valueoutsiderange'] = 'Please enter a value within the valid range';
$string['viewdetails'] = 'View details';
$string['visibleto'] = 'Visible to: {$a}';
$string['youranswer'] = 'Your answer';

// Deprecated.
$string['managername'] = 'Manager\'s name';
