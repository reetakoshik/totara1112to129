@totara @totara_program @totara_plan @javascript
Feature: Access programs content as a learner
  In order to access a programs content
  As an learner
  I need to be assigned to the program

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | completionstartonenrol |
      | Course 1 | C1        | topics | 1                | 1                      |
      | Course 2 | C2        | topics | 1                | 1                      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname             | shortname |
      | Program Access Tests | accstest  |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user    |
      | accstest | user001 |
    And I log in as "admin"
    And I set self completion for "Course 1" in the "Miscellaneous" category
    And I set self completion for "Course 2" in the "Miscellaneous" category
    And I log out

  Scenario: Access a new course added to a completed program via program assignments without duedates
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Program Access Tests" "link"
    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I log out

    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    # Check the user is still complete and can see the new course.
    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I should see "Course 2"

    # Check the user can access the course
    When I click on "Course 2" "link"
    Then I should see "You have been enrolled in course Course 2 via required learning program Program Access Tests."

    # Complete the course and check nothing has changed.
    When I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress

  Scenario: Access a new course added to a completed program via program assignments with duedates
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I click on "Assignments" "link"
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I click on "Day(s)" "option" in the "#timeperiod" "css_element"
    And I click on "Program enrollment date" "option" in the "#eventtype" "css_element"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I click on "Save changes" "button"
    And I log out

    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Program Access Tests" "link"
    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I log out

    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    # Check the user is still complete and can see the new course.
    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I should see "Course 2"

    # Check the user can access the course
    When I click on "Course 2" "link"
    Then I should see "You have been enrolled in course Course 2 via required learning program Program Access Tests."

    # Complete the course and check nothing has changed.
    When I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress

  Scenario: Access a new course added to a completed program via learning plans
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_002 ln_002" "link"
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I press "Create plan"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link" in the "assignprograms" "totaradialogue"
    And I click on "Program Access Tests" "link" in the "assignprograms" "totaradialogue"
    And I click on "Save" "button" in the "assignprograms" "totaradialogue"
    And I wait "1" seconds
    And I press "Approve"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I click on "Assignments" "link"
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I click on "Day(s)" "option" in the "#timeperiod" "css_element"
    And I click on "Program enrollment date" "option" in the "#eventtype" "css_element"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I click on "Save changes" "button"
    And I log out

    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Program Access Tests" "link"
    And I click on "Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I log out

    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Access Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    # Check the user is still complete and can see the new course.
    Given I log in as "user001"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
    And I should see "Course 2"

    # Check the user can access the course
    When I click on "Course 2" "link"
    Then I should see "You have been enrolled in course Course 2 via required learning program Program Access Tests."

    # Complete the course and check nothing has changed.
    When I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program Access Tests" "link"
    Then I should see "100%" program progress
