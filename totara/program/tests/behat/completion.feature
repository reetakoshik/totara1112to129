@totara @totara_program @totara_courseprogressbar @totara_programprogressbar
Feature: Users completion of programs and coursesets
  In order to view a program
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
      | Course 2 | C2        | topics | 1                |
      | Course 3 | C3        | topics | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname  |
      | Completion Program Tests | comptest   |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user    |
      | comptest | user001 |
      | comptest | user002 |
      | comptest | user003 |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime | 0 |
    And I set self completion for "Course 1" in the "Miscellaneous" category
    And I set self completion for "Course 2" in the "Miscellaneous" category
    And I set self completion for "Course 3" in the "Miscellaneous" category
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out

  # Completion of a program with content like so:
  # Course set 1 [ Course 1 And Course 2]
  # Then
  # Course set 2 [ Course 3]
  @javascript
  Scenario: Test program completion with courseset "AND"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "33%" program progress
    And I should see "100%" in the "Course 1" "table_row"

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "66%" program progress
    And I should see "100%" in the "Course 1" "table_row"
    And I should see "100%" in the "Course 2" "table_row"

    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

    # Test assignments interface due date cannot be changed
    Given I log out
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Enrolled users" "link" in the "Completion Program Tests" "table_row"
    And I should see "Cannot change - user complete" in the "fn_001 ln_001" "table_row"
    And I should not see "Set due date" in the "fn_001 ln_001" "table_row"
    And I should not see "Remove due date" in the "fn_001 ln_001" "table_row"
    And I should not see "Cannot change - user complete" in the "fn_002 ln_002" "table_row"
    And I should see "Set due date" in the "fn_002 ln_002" "table_row"
    And I should not see "Remove due date" in the "fn_002 ln_002" "table_row"
    And I should not see "Cannot change - user complete" in the "fn_003 ln_003" "table_row"
    And I should see "Set due date" in the "fn_003 ln_003" "table_row"
    And I should not see "Remove due date" in the "fn_003 ln_003" "table_row"

  # Completion of a program with content like so:
  # Course set 1 [ Course 1 Or Course 2]
  # Or
  # Course set 2 [ Course 3]
  @javascript
  Scenario: Test program completion with courseset "OR"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "One course"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field with xpath "//div[@class='nextsetoperator-and']/child::select" to "or"
    And I press "Save changes"
    And I click on "Save all changes" "button"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"

    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with content like so:
  # Course set 1 [ Any 2 of Course 1, Course 2, Course 3]
  @javascript
  Scenario: Test program completion with courseset "XofY"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "Some courses"
    And I set "Minimum courses completed" for courseset "Untitled set" to "2"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "50%" program progress

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with completely optional content like so:
  # Course set 1 [ Optional Course 1, Course 2, Course 3]
  @javascript
  Scenario: Test program completion with courseset "optional"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "All courses are optional"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I log out
    And I run the "\totara_program\task\completions_task" task
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with some optional content like so:
  # Course set 1 [Optional Course 1, Course 2]
  # Then
  # Course set 2 [Course 3]
  @javascript
  Scenario: Test program completion with complex courseset containing "optional"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "All courses are optional"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I log out
    And I run the "\totara_program\task\completions_task" task
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    # Check progress is 0% because optional cousresets should not count towards progress.
    When I click on "Record of Learning" in the totara menu
    And I click on "Completion Program Tests" "link"
    Then I should see "0%" program progress

    # Check optional courseset courses are not marked as completed.
    When I click on "Record of Learning" in the totara menu
    Then I should not see "Course 2"
    And I should not see "Course 1"

    # Complete optional courses, the progress should still be 0%.
    When I click on "Required Learning" in the totara menu
    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 1" "table_row"

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 2" "table_row"

    When I click on "Record of Learning" in the totara menu
    And I click on "Completion Program Tests" "link"
    Then I should see "0%" program progress

    # Now check program completion when the only non-optional course is completed.
    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should see "Course 3"
    And I should see "Course 2"
    And I should see "Course 1"

    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

    # Check user access to optional courses in completed coursesets.
    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 1" "table_row"

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 2" "table_row"

    # Finally check completion for the user.
    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should see "Course 3"
    And I should see "Course 2"
    And I should see "Course 1"

    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with 'some 0' content like so:
  # Course set 1 [ At least 0 of Course 1, Course 2, Course 3]
  @javascript
  Scenario: Test program completion with courseset "some 0"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "Some courses"
    And I set the field "Minimum courses complete" to "0"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I log out
    And I run the "\totara_program\task\completions_task" task
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with 'some 0' content like so:
  # Course set 1 [At least 0 of Course 1, Course 2]
  # Then
  # Course set 2 [Course 3]
  @javascript
  Scenario: Test program completion with complex courseset containing "some 0"
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the field "Learner must complete" to "Some courses"
    And I set the field "Minimum courses completed" to "0"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    # Check progress.
    And I should see "50%" program progress

    When I log out
    And I run the "\totara_program\task\completions_task" task
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    # Check program completion when the only non-optional course is completed.
    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should see "Course 3"
    And I should not see "Course 2"
    And I should not see "Course 1"

    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

    # Check user access to optional courses in completed coursesets.
    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

    When I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 1" "table_row"

    When I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Required Learning" in the totara menu
    Then I should see "100%" in the "Course 2" "table_row"

    # Finally check completion for the user.
    When I click on "Course 3" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should see "Course 3"
    And I should see "Course 2"
    And I should see "Course 1"

    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress

  # Completion of a program with 'some 0' content in combination with minimum score:
  @totara_customfield @javascript
  Scenario: Test program completion with courseset "some 0" and minimum score
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name     | testcustomscore |
      | Short name    | testcustomscore |
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "testcustomscore" to "10"
    And I press "Save and display"

    And I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "testcustomscore" to "10"
    And I press "Save and display"

    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Completion Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the following fields to these values:
      | Learner must complete     | Some courses    |
      | Minimum courses completed | 0               |
      | Course score field        | testcustomscore |
      | Minimum score             | 15              |
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I run the "\totara_program\task\completions_task" task

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "0%" program progress

    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I press "Yes"
    And I click on "Required Learning" in the totara menu
    Then I should see "Completion Program Tests"
    And I should see "66%" program progress

    And I click on "Course 2" "link"
    And I click on "Complete course" "link"
    And I press "Yes"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Completion Program Tests" "link"
    Then I should see "100%" program progress
