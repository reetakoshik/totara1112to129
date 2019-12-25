@totara @totara_program
Feature: Generation of program assignment exceptions
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
      | user004  | fn_004    | ln_004   | user004@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname |
      | Program Exception Tests  | exctest   |
    # Get back the removed dashboard item for now.
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Time allowance exceptions are generated and set to a realistic time
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "2" seconds
    And I set "Minimum time required" for courseset "Untitled set" to "14"
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
        | timeamount | 1 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Day(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
        | timeamount | 15 |
    And I click on "Set time relative to event" "button"
    Then I should see "2 learner(s) assigned: 1 active, 1 exception(s)"
    And I wait "1" seconds
    And I run the scheduled task "\totara_program\task\send_messages_task"

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu
    And I should not see "You have been enrolled on program Program Exception Tests"

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Program Exception Tests"

    When I log out
    And I log in as "user002"
    And I should see "You have been enrolled on program Program Exception Tests"
    And I click on "Required Learning" in the totara menu
    Then I should see "Program Exception Tests" in the "#program-content" "css_element"
    And I should see "Course 1" in the "#program-content" "css_element"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Exception Report (1)" "link"
    Then I should see "fn_001 ln_001"
    And I should see "Time allowance" in the "fn_001 ln_001" "table_row"

    When I set the field "selectiontype" to "Time allowance"
    And I set the field "selectionaction" to "Set realistic due date and assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "No exceptions"
    And I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_003 ln_003 (user003@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Set due date" "link" in the "fn_003 ln_003" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
        | timeamount | 3 |
    And I click on "Set time relative to event" "button"
    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I wait "1" seconds
    And I run the scheduled task "\totara_program\task\send_messages_task"

    When I log out
    And I log in as "user001"
    And I should see "You have been enrolled on program Program Exception Tests"
    And I click on "Required Learning" in the totara menu
    Then I should see "Program Exception Tests" in the "#program-content" "css_element"
    And I should see "Course 1" in the "#program-content" "css_element"

    When I click on "Course 1" "link" in the "#program-content" "css_element"
    Then I should see "You have been enrolled in course Course 1 via required learning program Program Exception Tests"

  @javascript
  Scenario: Already assigned exceptions are generated and overridden
    Given I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "fn_001 ln_001" "link"
    And I click on "Learning Plans" "link" in the "#region-main" "css_element"
    And I press "Create new learning plan"
    And I press "Create plan"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I press "Add programs"
    And I click on "Miscellaneous" "link" in the "assignprograms" "totaradialogue"
    And I click on "Program Exception Tests" "link" in the "assignprograms" "totaradialogue"
    And I click on "Save" "button" in the "assignprograms" "totaradialogue"
    And I wait "1" seconds
    And I click on "Manage plans" "link" in the "#dp-plans-menu" "css_element"
    And I click on "Approve" "link" in the "#dp-plans-list-unapproved-plans" "css_element"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    Then I should see "2 learner(s) assigned: 1 active, 1 exception(s)"
    And I wait "1" seconds
    And I run the scheduled task "\totara_program\task\send_messages_task"

    When I log out
    And I log in as "user001"
    And I should not see "You have been enrolled on program Program Exception Tests"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Program Exception Tests"

    When I log out
    And I log in as "user002"
    And I should see "You have been enrolled on program Program Exception Tests"
    And I click on "Required Learning" in the totara menu
    Then I should see "Program Exception Tests" in the "#program-content" "css_element"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Exception Report (1)" "link"
    Then I should see "fn_001 ln_001"
    And I should see "Already assigned to program" in the "fn_001 ln_001" "table_row"

    When I set the field "selectiontype" to "Already assigned to program"
    And I set the field "selectionaction" to "Assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "No exceptions"
    And I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_003 ln_003 (user003@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    Then I should see "3 learner(s) assigned: 3 active, 0 exception(s)"
    And I wait "1" seconds
    And I run the scheduled task "\totara_program\task\send_messages_task"

    When I log out
    And I log in as "user001"
    And I should see "You have been enrolled on program Program Exception Tests"
    And I click on "Required Learning" in the totara menu
    Then I should see "Program Exception Tests" in the "#program-content" "css_element"

  @javascript
  Scenario: Completion time unknown Exceptions are generated and dismissed
    Given I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I select "Date/Time" from the "Create a new profile field:" singleselect
    And I set the following fields to these values:
        | Short name | datetime    |
        | Name       | Date & Time |
    And I click on "param3" "checkbox"
    And I click on "Save changes" "button"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Profile field date"
    And I click on "Date & Time" "link" in the "completion-event-dialog" "totaradialogue"
    And I click on "Ok" "button" in the "completion-event-dialog" "totaradialogue"
    And I wait "2" seconds
    And I set the following fields to these values:
        | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    Then I should see "2 learner(s) assigned: 1 active, 1 exception(s)"

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Program Exception Tests"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Program Exception Tests" in the "#program-content" "css_element"

    When I log out
    And I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Exception Report (1)" "link"
    Then I should see "fn_001 ln_001"
    And I should see "Completion time unknown" in the "fn_001 ln_001" "table_row"

    When I set the field "selectiontype" to "Completion time unknown"
    And I set the field "selectionaction" to "Do not assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "No exceptions"
    And I should see "2 learner(s) assigned: 1 active, 0 exception(s)"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_003 ln_003 (user003@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "2" seconds
    Then I should see "3 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Program Exception Tests"

  @javascript
  Scenario: Time allowance exceptions are generated and completion dates are changed
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait until the page is ready
    And I set "Minimum time required" for courseset "Untitled set" to "21"
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_003 ln_003 (user003@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_004 ln_004 (user004@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"

    Then I wait until the page is ready
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 1 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 1 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I click on "Set due date" "link" in the "fn_003 ln_003" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 1 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I wait "1" seconds
    Then I should see "4 learner(s) assigned: 1 active, 3 exception(s)"

    When I click on "Exception Report (3)" "link"
    And I should see "fn_001 ln_001"
    And I should see "fn_002 ln_002"
    And I should see "fn_003 ln_003"

    Then I click on "exceptionid" "checkbox"
    And I wait until the page is ready
    And I set the field "selectionaction" to "Set realistic due date and assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "4 learner(s) assigned: 2 active, 2 exception(s)"

    Then I click on "exceptionid" "checkbox"
    And I wait until the page is ready
    And I set the field "selectionaction" to "Assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "4 learner(s) assigned: 3 active, 1 exception(s)"

    Then I click on "exceptionid" "checkbox"
    And I wait until the page is ready
    And I set the field "selectionaction" to "Do not assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "4 learner(s) assigned: 3 active, 0 exception(s)"

    When I click on "Assignments" "link"
    And I wait until the page is ready
    Then I should see "4 learner(s) assigned: 3 active, 0 exception(s)"

    And I should not see "No due date" in the "fn_001" "table_row"
    And I should not see "Not yet known" in the "fn_001" "table_row"
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I should not see "No due date" in the "fn_002" "table_row"
    And I should not see "Not yet known" in the "fn_002" "table_row"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I should not see "No due date" in the "fn_003" "table_row"
    And I should not see "Not yet known" in the "fn_003" "table_row"
    And I click on "Set due date" "link" in the "fn_003 ln_003" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "4 learner(s) assigned: 3 active, 1 exception(s)"

    When I click on "Exception Report (1)" "link"
    Then I click on "exceptionid" "checkbox"
    And I wait until the page is ready
    And I set the field "selectionaction" to "Do not assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "4 learner(s) assigned: 3 active, 0 exception(s)"

    Then I click on "Assignments" "link"
    And I click on "Set due date" "link" in the "fn_004 ln_004" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 1 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "4 learner(s) assigned: 2 active, 1 exception(s)"

    Then I click on "Assignments" "link"
    And I should not see "No due date" in the "fn_001" "table_row"
    And I should not see "Not yet known" in the "fn_001" "table_row"
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 4 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I should not see "No due date" in the "fn_002" "table_row"
    And I should not see "Not yet known" in the "fn_002" "table_row"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 4 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I should not see "No due date" in the "fn_003" "table_row"
    And I should not see "Not yet known" in the "fn_003" "table_row"
    And I click on "Set due date" "link" in the "fn_003 ln_003" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 4 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I should not see "No due date" in the "fn_004" "table_row"
    And I should not see "Not yet known" in the "fn_004" "table_row"
    And I click on "Set due date" "link" in the "fn_004 ln_004" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "4 learner(s) assigned: 3 active, 1 exception(s)"

    And I should not see "No due date" in the "fn_004" "table_row"
    And I should not see "Not yet known" in the "fn_004" "table_row"
    And I click on "Set due date" "link" in the "fn_004 ln_004" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 4 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "4 learner(s) assigned: 4 active, 0 exception(s)"

  @javascript
  Scenario: Time allowance exceptions are not generated when moving the due date backwards (because due dates done't move backwards)
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Exception Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait until the page is ready
    And I set "Minimum time required" for courseset "Untitled set" to "21"
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"

    When I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"

    Then I wait until the page is ready
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 5 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I wait "1" seconds

    Then I should see "2 learner(s) assigned: 1 active, 1 exception(s)"

    When I click on "Exception Report (1)" "link"
    Then I should see "fn_001 ln_001"
    Then I click on "exceptionid" "checkbox"
    And I wait until the page is ready
    And I set the field "selectionaction" to "Assign"
    And I click on "Proceed with this action" "button"
    And I click on "OK" "button"
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    Then I click on "Assignments" "link"
    And I should not see "No due date" in the "fn_001" "table_row"
    And I should not see "Not yet known" in the "fn_001" "table_row"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 5 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    Then I should not see "No due date" in the "fn_001" "table_row"
    And I should not see "Not yet known" in the "fn_001" "table_row"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    Then I should not see "No due date" in the "fn_002" "table_row"
    And I should not see "Not yet known" in the "fn_002" "table_row"
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the field "timeperiod" to "Week(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"

    And I wait until the page is ready
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"
