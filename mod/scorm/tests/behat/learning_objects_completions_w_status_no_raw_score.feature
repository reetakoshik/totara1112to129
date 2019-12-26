@mod @mod_scorm @_file_upload @_switch_iframe @javascript
Feature: Activity completion status of multisco SCORM with status but no raw score.
  In order to track student progress
  As a teacher
  I need to see the completion status for the SCORM activity.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following config values are set as admin:
      | enablecompletion | 1 |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name           | Multi-sco SCORM package |
      | Description    | Multi-sco SCORM package |
      | Grading method | Learning objects        |
    And I upload "mod/scorm/tests/packages/multisco_w_status_no_raw_score.zip" file to "Package file" filemanager


  Scenario: multisco_0_0: self mark as complete, no other conditions
    When I set the following fields to these values:
      | Completion tracking | Learners can manually mark the activity as completed |
    And I click on "Save and display" "button"
    Then I should see "Multi-sco SCORM package"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    Then I should see "I have completed this activity"

    When I set the "I have completed this activity" Totara form field to "1"
    And I am on homepage
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_1: only require view condition
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 1                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 1                                                 |
      | Require all scos to return "completed" status | 0                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    Then I should not see "I have completed this activity"

    Given I press "Enter"
    And I switch to "scorm_object" iframe
    And I should see "Play of the game"
    And I switch to the main frame
    And I follow "Exit activity"
    Then I should see "Multi-sco SCORM package"

    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_2: only require minimum score conditions.
    # Minimum scores are computed from "cmi.score.raw" (or "cmi.core.score.raw")
    # attributes sent from the SCORM. However, the test SCORM DOES NOT SEND BACK
    # RAW SCORES - and thus the activity is always marked as incomplete even if
    # the student clicks through the entire SCORM. Confusing as hell, but that's
    # the way it works.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 80                                                |
      | Require all scos to return "completed" status | 0                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    And I should see "Play of the game"

    And I switch to the main frame
    And I click on "Par?" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Par"

    And I switch to the main frame
    And I click on "Keeping Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Scoring"

    And I switch to the main frame
    And I click on "Other Scoring Systems" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Other Scoring Systems"

    And I switch to the main frame
    And I click on "The Rules of Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "The Rules of Golf"

    And I switch to the main frame
    And I click on "Playing Golf Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Taking Care of the Course" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Care For the Course"

    And I switch to the main frame
    And I click on "Avoiding Distraction" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Avoiding Distraction"

    And I switch to the main frame
    And I click on "Playing Politely" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Playing the Game"

    And I switch to the main frame
    And I click on "Etiquette Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Handicapping Overview" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Handicapping"

    And I switch to the main frame
    And I click on "Calculating a Handicap" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Handicap"

    And I switch to the main frame
    And I click on "Calculating a Handicapped Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Example" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "How to Have Fun Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Have Fun Golfing"

    And I switch to the main frame
    And I click on "How to Make Friends Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Make Friends on the Golf Course"

    And I switch to the main frame
    And I click on "Having Fun Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_3: only completed status conditions with SCORM partially viewed.
    # The test SCORM sends back "cmi.core.lesson_status" (or similar) values for
    # *every* SCO. However, Totara marks the activity as long as *any* SCO has a
    # "cmi.core.lesson_status" value of "completed".
    #
    # This makes things especially confusing when the status condition is used
    # with a grading method of "Learning Objects" (ie multisco). However, the
    # "cmi.core.lesson_status" attribute has got nothing to do with the learning
    # objects *grading* method! The number of learning objects completed counts
    # in the activity *score* but the activity is considered complete provided
    # one of the SCOs returned a "cmi.core.lesson_status" value of "completed".
    #
    # It is very counterintuitive but all along, there has been no code in SCORM
    # module to compute a completion condition of the total no of "completed"
    # learning objects against an expected count. It is to address this problem
    # that the "Require all scos to return "completed" status" setting is there.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 1                                                 |
      | id_completionstatusrequired_2                 | 1                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 0                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "Play of the game"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_4: require minimum score and completed status conditions with SCORM fully viewed.
    # In this test, the completed status condition is fulfilled even when 1 sco
    # is done. However, the minimum score is never fulfilled (because the score
    # is based on the "cmi.score.raw" attribute which the test SCORM does not
    # send back. So the activity is never complete.
    #
    # Note this is different in Moodle; in Moodle as long as one SCO is complete
    # the activity is complete, even if the SCORM sends back "cmi.score.raw"
    # values. The code to *continue* checking for scores was present only in the
    # Totara code.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 80                                                |
      | id_completionstatusrequired_2                 | 1                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 0                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    And I should see "Play of the game"
    And I switch to the main frame
    And I click on "Par?" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Par"

    And I switch to the main frame
    And I click on "Keeping Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Scoring"

    And I switch to the main frame
    And I click on "Other Scoring Systems" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Other Scoring Systems"

    And I switch to the main frame
    And I click on "The Rules of Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "The Rules of Golf"

    And I switch to the main frame
    And I click on "Playing Golf Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Taking Care of the Course" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Care For the Course"

    And I switch to the main frame
    And I click on "Avoiding Distraction" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Avoiding Distraction"

    And I switch to the main frame
    And I click on "Playing Politely" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Playing the Game"

    And I switch to the main frame
    And I click on "Etiquette Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Handicapping Overview" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Handicapping"

    And I switch to the main frame
    And I click on "Calculating a Handicap" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Handicap"

    And I switch to the main frame
    And I click on "Calculating a Handicapped Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Example" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "How to Have Fun Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Have Fun Golfing"

    And I switch to the main frame
    And I click on "How to Make Friends Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Make Friends on the Golf Course"

    And I switch to the main frame
    And I click on "Having Fun Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_5: only all scos to return completion status conditions.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 1                                                 |
      | id_completionstatusrequired_2                 | 0                                                 |
      | id_completionstatusrequired_4                 | 0                                                 |
      | Require all scos to return "completed" status | 1                                                 |
    And I click on "Save and display" "button"
    Then I should see "You must select a status to require"

    When I set the following fields to these values:
      | id_completionstatusrequired_2 | 0 |
      | id_completionstatusrequired_4 | 1 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_6: require completed status and all scos to return completion status conditions with SCORM partially viewed.
    # In this test, the completed status condition is fulfilled because one sco
    # is done. However, the all scos condition is not fulfilled. So the activity
    # is never complete.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 1                                                 |
      | id_completionstatusrequired_2                 | 1                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 1                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "Play of the game"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_7: require completed status and all scos to return completion status conditions with SCORM fully viewed.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 1                                                 |
      | id_completionstatusrequired_2                 | 1                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 1                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    And I should see "Play of the game"
    And I switch to the main frame
    And I click on "Par?" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Par"

    And I switch to the main frame
    And I click on "Keeping Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Scoring"

    And I switch to the main frame
    And I click on "Other Scoring Systems" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Other Scoring Systems"

    And I switch to the main frame
    And I click on "The Rules of Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "The Rules of Golf"

    And I switch to the main frame
    And I click on "Playing Golf Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Taking Care of the Course" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Care For the Course"

    And I switch to the main frame
    And I click on "Avoiding Distraction" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Avoiding Distraction"

    And I switch to the main frame
    And I click on "Playing Politely" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Playing the Game"

    And I switch to the main frame
    And I click on "Etiquette Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Handicapping Overview" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Handicapping"

    And I switch to the main frame
    And I click on "Calculating a Handicap" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Handicap"

    And I switch to the main frame
    And I click on "Calculating a Handicapped Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Example" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "How to Have Fun Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Have Fun Golfing"

    And I switch to the main frame
    And I click on "How to Make Friends Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Make Friends on the Golf Course"

    And I switch to the main frame
    And I click on "Having Fun Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_0_8: require minimum score, completed status and all scos to return completion status conditions with SCORM fully viewed.
    # In this test, the minimum score is never fulfilled (because the score is
    # based on the "cmi.score.raw" attribute which the test SCORM does not send
    # back. So the activity is never complete.
    #
    # Note this is different in Moodle; in Moodle as long as all SCOs complete
    # the activity is complete, even if the SCORM sends back "cmi.score.raw"
    # values. The code to *continue* checking for scores was only changed in the
    # Totara code.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 80                                                |
      | id_completionstatusrequired_2                 | 1                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 1                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I follow "Course 1"
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity

    Given I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    And I should see "Play of the game"
    And I switch to the main frame
    And I click on "Par?" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Par"

    And I switch to the main frame
    And I click on "Keeping Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Scoring"

    And I switch to the main frame
    And I click on "Other Scoring Systems" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Other Scoring Systems"

    And I switch to the main frame
    And I click on "The Rules of Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "The Rules of Golf"

    And I switch to the main frame
    And I click on "Playing Golf Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Taking Care of the Course" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Care For the Course"

    And I switch to the main frame
    And I click on "Avoiding Distraction" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Avoiding Distraction"

    And I switch to the main frame
    And I click on "Playing Politely" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Etiquette - Playing the Game"

    And I switch to the main frame
    And I click on "Etiquette Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "Handicapping Overview" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Handicapping"

    And I switch to the main frame
    And I click on "Calculating a Handicap" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Handicap"

    And I switch to the main frame
    And I click on "Calculating a Handicapped Score" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Example" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Calculating a Score"

    And I switch to the main frame
    And I click on "Handicapping Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"

    And I switch to the main frame
    And I click on "How to Have Fun Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Have Fun Golfing"

    And I switch to the main frame
    And I click on "How to Make Friends Playing Golf" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "How to Make Friends on the Golf Course"

    And I switch to the main frame
    And I click on "Having Fun Quiz" "list_item"
    And I switch to "scorm_object" iframe
    And I should see "Knowledge Check"
    And I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity
