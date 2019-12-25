@totara @totara_program @javascript
Feature: Legacy Users assignments to a program
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
    And the following config values are set as admin:
        | enablelegacyprogramassignments | 1 |
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out

  Scenario: Old assignments match new assignments
    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add individuals to program" "button"
    And I click on "fn_005 ln_005 (user005@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Positions"
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add position to program" "button"
    And I click on "Position Two" "link" in the "Add position to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add position to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Audiences"
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add audiences to program" "button"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Organisations"
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add organisations to program" "button"
    And I click on "Organisation Two" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "Add" "button" in the "#category_select" "css_element"
    And I click on "Add managers to program" "button"
    And I click on "fn_003 ln_003 (user003@example.com) - ja3" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the field "completiontime" to "01/02/2030"
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "fn_005 ln_005" "table_row"
    And I set the following fields to these values:
      | timeamount | 5           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds

    And I press "Save changes"
    And I press "Save all changes"
    Then I should see "4 learner(s) assigned: 4 active, 0 exception(s)"

    When the following config values are set as admin:
      | enablelegacyprogramassignments | 0 |
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    Then I should see "4 learner(s) assigned: 4 active, 0 exception(s)"
    And I should see "Complete by 1 Feb 2030 at 00:00" in the "Audience1" "table_row"
    And I should see "Complete within 5 Week(s) of First login" in the "fn_005 ln_005" "table_row"
    And I should see "View dates" in the "Organisation Two" "table_row"

  Scenario: New assignments match old assignments
    Given I log in as "admin"
    When the following config values are set as admin:
      | enablelegacyprogramassignments | 0 |
    When I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_005 ln_005 (user005@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Positions"
    And I click on "Position Two" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation Two" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "fn_003 ln_003 (user003@example.com) - ja3" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the field "completiontime" to "01/02/2030"
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "fn_005 ln_005" "table_row"
    And I set the following fields to these values:
      | timeamount | 5           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And the following config values are set as admin:
        | enableprogramlargeassignments | 0 |
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Assignment Program Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Assignments" "link"
    Then I should see "4 learner(s) assigned: 4 active, 0 exception(s)"
    And I should see "Complete by 1 Feb 2030 at 00:00" in the "Audience1" "table_row"
    And I should see "Complete within 5 Week(s) of First login" in the "fn_005 ln_005" "table_row"
    And I should see "Set due date" in the "fn_003 ln_003 - ja3" "table_row"
