@totara @totara_program @javascript
Feature: Deferred assignments task for programs
  In order to assign users to a program sooner
  The deferred assignments task can update assignments
  After relevant changes have taken place

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
      | Position Two | pos2      | pframe        |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber  |
      | Organisation Framework | oframe    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber  | org_framework |
      | Organisation One | org1      | oframe        |
      | Organisation Two | org2      | oframe        |
    And the following job assignments exist:
      | user    | idnumber | fullname |
      | user001 | ja1      | Job1     |
      | user001 | ja2      | Job2     |
      | user002 | ja1      | Job1     |
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname |
      | Program One | program1  |
      | Program Two | program2  |

  Scenario: Deferred assignments task assigns users when their position has been added
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I select "Positions" from the "Add a new" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I press "Add position to program"
    And I click on "Position One" "link" in the "Add position to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add position to program" "totaradialogue"
    And I press "Save changes"
    # Run the task now to clear any flags that might have been set already
    And I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_001 ln_001" "link"
    And I click on "Job2" "link"
    And I press "Choose position"
    And I click on "Position One" "link" in the "Choose position" "totaradialogue"
    And I click on "OK" "button" in the "Choose position" "totaradialogue"
    And I press "Update job assignment"
    And I log out
    And I log in as "user001"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    # The task has not been run since the position was updated, so won't be assigned yet.
    Then I should not see "Hold position of 'Position One'"
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I wait "1" seconds
    Then I should see "Hold position of 'Position One'"

  Scenario: Deferred assignments task assigns users when their organisation has been added
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    And I press "Edit program details"
    And I switch to "Assignments" tab
    And I select "Organisations" from the "Add a new" singleselect
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I press "Add organisations to program"
    And I click on "Organisation One" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I press "Save changes"
    # Run the task now to clear any flags that might have been set already
    And I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "fn_001 ln_001" "link"
    And I click on "Job2" "link"
    And I press "Choose organisation"
    And I click on "Organisation One" "link" in the "Choose organisation" "totaradialogue"
    And I click on "OK" "button" in the "Choose organisation" "totaradialogue"
    And I press "Update job assignment"
    And I log out
    And I log in as "user001"
    And I click on "Programs" in the totara menu
    And I follow "Program One"
    # The task has not been run since the position was updated, so won't be assigned yet.
    Then I should not see "Member of organisation 'Organisation One'"
    When I run the scheduled task "\totara_program\task\assignments_deferred_task"
    And I wait "1" seconds
    Then I should see "Member of organisation 'Organisation One'"
