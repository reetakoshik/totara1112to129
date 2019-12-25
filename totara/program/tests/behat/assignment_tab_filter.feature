@totara @totara_program @javascript
Feature: Filtering on program assignments page
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
      | user003 | user001 | 1                 | pos2     | org2         | 1        | ja3      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                 | shortname    |
      | Assignment Program Tests | assigntest   |
    # Get back the removed dashboard item for now.
    And I log in as "admin"
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"

    And I navigate to "Manage programs" node in "Site administration > Programs"
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
    And I click on "fn_001 ln_001 (user001@example.com) - 1stja1" "link" in the "Add managers to program" "totaradialogue"
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

  Scenario: Confirm filtering on the assignments tab works as expected
    # One filter
    When I click on "Audience" "link" in the ".totara_program__assignments__filters" "css_element"
    Then I should see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_005 ln 005" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"

    # Two filters
    When I click on "Position" "link" in the ".totara_program__assignments__filters" "css_element"
    Then I should see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_005 ln 005" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"

  Scenario: Confirm text filter works as expected
    When I set the field "Search assignments" to "Two"
    And I click on "Search" "button" in the ".totara_program__assignments-search" "css_element"
    Then I should see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_005 ln 005" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"

    # Clear filter
    When I click on ".tw-selectSearchText__field_clear" "css_element"
    Then I should see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "fn_005 ln_005" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"

  Scenario: Confirm text and type filter work together
    When I set the field "Search assignments" to "Two"
    And I click on "Search" "button" in the ".totara_program__assignments-search" "css_element"
    And I click on "Position" "link" in the ".totara_program__assignments__filters" "css_element"
    And I should see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_005 ln 005" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"

  Scenario: Confirm the no results template can be shown
    When I click on "Audience" "link" in the ".totara_program__assignments__filters" "css_element"
    And I set the field "Search assignments" to "junk"
    And I click on "Search" "button" in the ".totara_program__assignments-search" "css_element"
    Then I should see "No results"
    And ".totara_program__assignments__results__table" "css_element" should not exist

    When I click on "Clear all" "link" in the ".totara_program__assignments__filters" "css_element"
    And I click on ".tw-selectSearchText__field_clear" "css_element"
    Then I should see "Position Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Organisation Two" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "Audience1" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "fn_005 ln_005" in the ".totara_program__assignments__results__table" "css_element"
    And I should see "fn_001 ln_001 - 1stja1" in the ".totara_program__assignments__results__table" "css_element"
    And I should not see "No results"
