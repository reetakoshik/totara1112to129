@mod @mod_scorm @_file_upload @_switch_iframe @javascript
Feature: Activity completion status of multisco SCORM with both status and raw score.
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
    And I upload "mod/scorm/tests/packages/multisco_w_status_w_raw_score.zip" file to "Package file" filemanager


  #==== Minimum score only
  Scenario: multisco_1_0: only require minimum score conditions, SCORM score less than required score.
    # Note: the test SCORM has been hacked to always return 75/100.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 100                                               |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    # Unfortunately, there are other buttons with ">" and "<" in the SCORM page;
    # hence the using of XPath to select exactly which button to press.
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_1_1: only require minimum score conditions, SCORM score more than required score.
    # Note: the test SCORM has been hacked to always return 75/100.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 50                                               |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  #==== Minimum score, completion status
  Scenario: multisco_2_0: require minimum score and completed status conditions with SCORM partially viewed.
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
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 50                                                |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_2_1: require minimum score and completed status conditions with SCORM fully viewed, SCORM score less than required score.
    # Note this is different in Moodle; in Moodle as long as one SCO is complete
    # the activity is complete, even if the SCORM sends back "cmi.score.raw"
    # values. The code to *continue* checking for scores is present only in the
    # Totara code.

    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 100                                               |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  #==== Minimum score, completed status, all scos return completion status
  Scenario: multisco_3_0: require minimum score and all scos to return completion status conditions with SCORM partially viewed, SCORM score more than required score.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 50                                                |
      | id_completionstatusrequired_2                 | 0                                                 |
      | id_completionstatusrequired_4                 | 1                                                 |
      | Require all scos to return "completed" status | 1                                                 |
    And I click on "Save and display" "button"
    Then I should see "Mode:"

    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Multi-sco SCORM package"
    And I should see "Normal"
    And I press "Enter"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_3_1: require minimum score and all scos to return completion status conditions with SCORM fully viewed, SCORM score more than required score.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 50                                                |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has completed "Multi-sco SCORM package" activity


  Scenario: multisco_3_2: require minimum score and completed status conditions with SCORM fully viewed, SCORM score less than required score.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 100                                               |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity


  Scenario: multisco_3_3: require minimum score and completed status conditions with SCORM partially viewed, SCORM score less than required score.
    When I set the following fields to these values:
      | Completion tracking                           | Show activity as complete when conditions are met |
      | Require view                                  | 0                                                 |
      | Require grade                                 | 0                                                 |
      | id_completionscoredisabled                    | 0                                                 |
      | completionscorerequired                       | 100                                               |
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
    Then I should see "This is at @Level 1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-1"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-2"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-4"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 4-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 2-3"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-5"

    When I switch to the main frame
    And I click on "//button[@id='nav_next']" "xpath_element"
    And I switch to "scorm_object" iframe
    Then I should see "This is at @Level 3-6"

    Given I switch to the main frame
    And I follow "Exit activity"
    And I log out

    Given  I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then "Student 1" user has not completed "Multi-sco SCORM package" activity
