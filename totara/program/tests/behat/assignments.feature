@totara @totara_program
Feature: Users assignments to a program
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
      | user005  | fn_005    | ln_005   | user005@example.com |
      | catmgr   | Category  | Manager  | catmgr@example.com  |
    And the following "categories" exist:
      | name      | category | idnumber |
      | Category1 | 0        | cat1     |
    And the following "role assigns" exist:
      | user   | role          | contextlevel | reference |
      | catmgr | manager       | Category     | cat1      |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user    | cohort |
      | user002 | aud1   |
      | user003 | aud1   |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber  |
      | Organisation Framework | oframe    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber  | org_framework |
      | Organisation One | org1      | oframe        |
      | Organisation Two | org2      | oframe        |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
      | Position Two | pos2      | pframe        |
    And the following job assignments exist:
      | user    | manager | managerjaidnumber | position | organisation | idnumber | fullname |
      | user001 | admin   |                   | pos1     | org1         | 1        | 1stja1   |
      | user001 |         |                   |          |              | 2        | 2ndja1   |
      | user002 | user001 | 1                 | pos1     | org1         | 1        | ja2      |
      | user003 | user001 | 1                 | pos2     | org2         | 1        | ja3      |
      | user004 | user003 | 1                 | pos2     | org2         | 1        | ja4      |
      | user005 | user001 | 2                 |          |              | 1        | ja5      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname    | category   |
      | Assignment Program Tests | assigntest   |            |
      | Category Permission Test | cattest      | cat1       |
    # Get back the removed dashboard item for now.
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Test program assignments via individual assigments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"
    And I should see "Assigned as an individual."

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"
    And I should not see "Assigned as an individual."

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    Then I should see "Assigned as an individual."

  @javascript
  Scenario: Test program assignments and updates via audience assigments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audience"
    And I click on "Audience1" "link" in the "add-assignment-dialog-3" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-3" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"
    And I should see "Member of audience 'Audience1'."

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user004"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"
    And I should not see "Member of audience 'Audience1'."

    When I log out
    And I log in as "admin"
    And the following "cohort members" exist:
      | user    | cohort |
      | user004 | aud1   |
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Audience1" "link"
    And I click on "Edit members" "link"
    And I click on "fn_002 ln_002 (user002@example.com)" "option" in the "#removeselect" "css_element"
    And I click on "remove" "button"
    And I run the "\totara_program\task\user_assignments_task" task

    When I log out
    And I log in as "user002"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user004"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then I should see "Member of audience 'Audience1'."

  @javascript
  Scenario: Test program assignments and updates via position assigments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Positions"
    And I click on "Position One" "link" in the "add-assignment-dialog-2" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-2" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"
    And I should see "Hold position of 'Position One'"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"
    And I should not see "Hold position of 'Position One'"

    When the following job assignments exist:
      | user    | position | idnumber |
      | user001 | pos2     | 1        |
      | user002 | pos1     | 1        |
      | user003 | pos1     | 1        |
    And I run the "\totara_program\task\user_assignments_task" task

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then I should see "Hold position of 'Position One'"

  @javascript
  Scenario: Test program assignments and updates via organisation assigments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation One" "link" in the "add-assignment-dialog-1" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-1" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"
    And I should see "Member of organisation 'Organisation One'"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"
    And I should not see "Member of organisation 'Organisation One'"

    And the following job assignments exist:
      | user    | organisation | idnumber |
      | user001 | org2         | 1        |
      | user002 | org1         | 1        |
      | user003 | org1         | 1        |
    And I run the "\totara_program\task\user_assignments_task" task

    When I log out
    And I log in as "user001"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_002 ln_002" "table_row"
    Then I should see "Member of organisation 'Organisation One'"

  @javascript
  Scenario: Test program assignments and updates via manager path assigments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "Add managers to program" "totaradialogue"
    And I click on "1stja1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"

    When I log out
    And I log in as "user002"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"
    And I should see "Part of 'fn_001 ln_001' team"

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user004"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"

    When the following job assignments exist:
      | user    | manager | idnumber | managerjaidnumber |
      | user001 | admin   | 1        |                   |
      | user002 | admin   | 1        |                   |
      | user003 | user001 | 1        | 1                 |
      | user004 | user001 | 1        | 1                 |
      | user005 | user002 | 1        | 1                 |
    And I run the "\totara_program\task\user_assignments_task" task

    When I log out
    And I log in as "user002"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    Then I should not see "Assignment Program Tests"
    And I should not see "Part of 'fn_001 ln_001' team"

    When I log out
    And I log in as "user003"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "user004"
    And I click on "Required Learning" in the totara menu
    Then I should see "Assignment Program Tests"

    When I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_003 ln_003" "table_row"
    Then I should see "Part of 'fn_001 ln_001' team"

  @javascript
  Scenario: Site manager at category context can see emails when assigning individuals
    Given I log in as "catmgr"
    And I am on "Category Permission Test" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    Then I should see "fn_001 ln_001 (user001@example.com)" in the "add-assignment-dialog-5" "totaradialogue"
    And I should see "fn_002 ln_002 (user002@example.com)" in the "add-assignment-dialog-5" "totaradialogue"
    When I click on "Search" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I search for "fn_00" in the "add-assignment-dialog-5" totara dialogue
    Then I should see "fn_001 ln_001 (user001@example.com)" in the "#search-tab" "css_element"
    And I should see "fn_002 ln_002 (user002@example.com)" in the "#search-tab" "css_element"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I should see "No results"

  @javascript
  Scenario: Assignments can not be updated after program end date
    Given I log in as "admin"
    And I am on "Assignment Program Tests" program homepage
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "fn_002 ln_002 (user002@example.com)" "link" in the "add-assignment-dialog-5" "totaradialogue"
    And I click on "Ok" "button" in the "add-assignment-dialog-5" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2 learner(s) assigned: 2 active, 0 exception(s)"
    When I switch to "Details" tab
    And I set the following fields to these values:
     | availableuntil[enabled] | 1       |
     | availableuntil[day]     | 15      |
     | availableuntil[month]   | January |
     | availableuntil[year]    | 2017    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    Then I should see "This program is no longer available to learners."
    And I should see "Note: If program is reactivated, the assigned learners may be updated based on any changes within selected groups."
    Then "Add a new" "field" should not exist
