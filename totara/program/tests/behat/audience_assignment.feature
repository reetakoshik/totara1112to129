@totara @totara_program @javascript
Feature: Check user view capability for audiences in the assignments tab
  In order to view audiences in the dialog
  As a user
  I need to have the view cohort capability in the right context

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | user1    | First     | User     | first@example.com  |
      | catmgr   | Category  | Manager  | catmgr@example.com |
    And the following "categories" exist:
      | name      | category | idnumber |
      | Category1 | 0        | CAT1     |
      | Category2 | 0        | CAT2     |
    And the following "role assigns" exist:
      | user   | role          | contextlevel | reference |
      | catmgr | manager       | Category     | CAT1      |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
      | Audience2 | aud1     | Category     | CAT1      |
      | Audience3 | aud1     | Category     | CAT2      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                  | shortname    | category   |
      | Assignment Program Tests  | assigntest   |            |
      | Category1 Permission Test | cattest      | CAT1       |
      | Category2 Permission Test | cattest      | CAT2       |

  Scenario: Test Audience assignment dialog is showing audiences the user is allowed to
#    catmgr should see Audience2 in the audience dialog of Category1 Permission Test program.
    Given I log in as "catmgr"
    And I am on "Category1 Permission Test" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    Then I should see "Audience2"
    And I should not see "Audience1"
    And I should not see "Audience3"

    When I click on "Search" "link" in the "Add audiences to program" "totaradialogue"
    And I search for "Au" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience2" in the "Add audiences to program" "totaradialogue"
    And I should not see "Audience1" in the "Add audiences to program" "totaradialogue"
    And I should not see "Audience3" in the "Add audiences to program" "totaradialogue"
    And I click on "Cancel" "button" in the "Add audiences to program" "totaradialogue"
    And I log out

#    Assign audiencewatcher role to catmgr in the system level, so he can now see Audiences in the sys context.
    And I log in as "admin"
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "Add a new role" "button"
    And I click on "Continue" "button"
    And I set the following fields to these values:
      | Short name                       | audiencewatcher       |
      | Custom full name                 | Audience watcher      |
      | contextlevel10                   | 1                     |
      | moodle/cohort:view               | 1                     |
    And I click on "Create this role" "button"
    And the following "role assigns" exist:
      | user         | role            | contextlevel | reference |
      | catmgr       | audiencewatcher | System       |           |
    And I log out

    When I log in as "catmgr"
    And I am on "Category1 Permission Test" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    Then I should see "Audience1"
    And I should see "Audience2"
    And I should not see "Audience3"

#    Search tab should also show Audience 1
    When I click on "Search" "link" in the "Add audiences to program" "totaradialogue"
    And I search for "Au" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience2" in the "Add audiences to program" "totaradialogue"
    And I should see "Audience1" in the "Add audiences to program" "totaradialogue"
    And I should not see "Audience3" in the "Add audiences to program" "totaradialogue"

#    Select Audience 1
    When I click on "Browse" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Audience1" in the "totara_program__assignments__results__table" "table"
    And I log out

#    Remove audiencewatcher role from catmgr and make sure the audience is still visible and assigned
#    but not reachable through the search.
    And I log in as "admin"
    And I navigate to "Assign system roles" node in "Site administration > Permissions"
    And I click on "Audience watcher" "link"
    And I set the field "Existing users" to "Category Manager (catmgr@example.com)"
    And I press "Remove"
    And I log out

    And I log in as "catmgr"
    And I am on "Category1 Permission Test" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    Then I should see "Audience1" in the "totara_program__assignments__results__table" "table"
    And I set the field "Add a new" to "Audiences"
    And I should see "Audience1"
    And I should see "Audience2"
    And I should not see "Audience3"

    When I click on "Search" "link" in the "Add audiences to program" "totaradialogue"
    And I search for "Au" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience2" in the "Add audiences to program" "totaradialogue"
    And I should not see "Audience1" in the "Add audiences to program" "totaradialogue"
    And I should not see "Audience3" in the "Add audiences to program" "totaradialogue"
