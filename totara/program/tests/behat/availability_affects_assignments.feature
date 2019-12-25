@totara @totara_program @javascript
Feature: Availability of programs affects assignments
  Availability of a program based on start and end dates
  Affects changes that can be made by admin
  On program assignments page

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
      | user003  | fn_003    | ln_003   | user003@example.com |
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
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber  | pos_framework |
      | Position One | pos1      | pframe        |
    And the following job assignments exist:
      | user    | manager | managerjaidnumber | position | organisation | idnumber | fullname |
      | user001 | admin   |                   | pos1     | org1         | 1        | ja1      |
      | user002 | user001 | 1                 | pos1     | org1         | 1        | ja2      |
      | user003 | user001 | 1                 |          |              | 1        | ja3      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                      | shortname    | category   |
      | Availability Assignment Tests | assigntest   |            |
    And I log in as "admin"
    And I am on "Availability Assignment Tests" program homepage
    And I click on "Edit program details" "button"

  Scenario: Before a program is available, an admin can add and remove assignments but assigned totals are not shown
    Given I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    Then I should see "This program is not yet available. Learner assignments will be applied following the start date"
    When I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2" in the "Audience1" "table_row"
    Then I should see "This program is not yet available. Learner assignments will be applied following the start date"
    And I should not see "2 learner(s) assigned: 2 active, 0 exception(s)"
    When I click on "Remove program assignment" "link" in the "Audience1" "table_row"
    And I click on "Remove" "button"
    Then "Audience1" "table_row" should not exist

  Scenario: Before a program is available, an admin can update due dates for assignments
    Given I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    Then I should see "This program is not yet available. Learner assignments will be applied following the start date"
    When I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | completiontime       | 15/02/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "15 Feb 2030 at 00:00" in the "fn_001 ln_001" "table_row"

  Scenario: While a program is available, an admin can add and remove assignments and assigned totals are shown
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    Then I should see "2" in the "Audience1" "table_row"
    Then I should see "Caution: Program is live - there are learners who will see or be affected by changes you make"
    And I should see "2 learner(s) assigned: 2 active, 0 exception(s)."
    When I click on "Remove program assignment" "link" in the "Audience1" "table_row"
    And I click on "Remove" "button"
    Then "Audience1" "table_row" should not exist

  Scenario: While a program is available, an admin can update due dates for assignments
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the following fields to these values:
      | completiontime       | 15/02/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "15 Feb 2030 at 00:00" in the "fn_001 ln_001" "table_row"

  Scenario: When a program is no longer available, an admin can view but not add or remove assignments
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I should see "3 learner(s) assigned: 3 active, 0 exception(s)."
    And I switch to "Details" tab
    And I set the following fields to these values:
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    Then I should see "This program is no longer available to learners."
    And I should not see "3 learner(s) assigned: 3 active, 0 exception(s)"
    When I switch to "Assignments" tab
    And I should see "Note: If program is reactivated, the assigned learners may be updated based on any changes within selected groups."
    Then "Add a new" "field" should not exist
    And "Remove program assignment" "link" should not exist in the "Audience1" "table_row"

  Scenario: When a program is no longer available, an admin can view but not update due dates for assignments
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001 (user001@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | completiontime       | 15/02/2030 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete by 15 Feb 2030 at 00:00" in the "Audience1" "table_row"
    And "Set due date" "link" should exist in the "fn_001 ln_001" "table_row"
    And I switch to "Details" tab
    And I set the following fields to these values:
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    Then I should see "This program is no longer available to learners."
    When I switch to "Assignments" tab
    Then I should see "15 Feb 2030 at 00:00" in the "Audience1" "table_row"
    # But it should not be a link
    And "15 Feb 2030 at 00:00" "link" should not exist in the "Audience1" "table_row"
    And I should see "No due date" in the "fn_001 ln_001" "table_row"
    And "Set due date" "link" should not exist in the "fn_001 ln_001" "table_row"

  # Below are some tests to ensure the correct columns appear for each assignment category and state.
  # They don't need to include existence of buttons or ability to set due dates as these are covered by tests above.
  # There is no scenario for individuals as the columns currently do not change.

  Scenario: Program assignment by audience: availability determines which columns are shown
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I should see "Set due date" in the "Audience1" "table_row"
    And I should see "View dates" in the "Audience1" "table_row"
    And I should see "2" in the "Audience1" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Before program is available.
    And I should see "Set due date" in the "Audience1" "table_row"
    And I should see "View dates" in the "Audience1" "table_row"
    And I should see "2" in the "Audience1" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled]  | 0       |
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Program is no longer available.
    And I should see "No due date" in the "Audience1" "table_row"
    And I should not see "View dates" in the "Audience1" "table_row"
    And I should not see "2" in the "Audience1" "table_row"

  Scenario: Program assignment by position: availability determines which columns are shown
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Positions"
    And I click on "Position One" "link" in the "Add positions to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add positions to program" "totaradialogue"
    And I wait "1" seconds
    # While program is available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    And I should see "Set due date" in the "Position One" "table_row"
    And I should see "View dates" in the "Position One" "table_row"
    And I should see "2" in the "Position One" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Before program is available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    And I should see "Set due date" in the "Position One" "table_row"
    And I should see "View dates" in the "Position One" "table_row"
    And I should see "2" in the "Position One" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled]  | 0       |
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Program is no longer available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    # If we fix this, we can check its disabled attribute.
    And I should see "No due date" in the "Position One" "table_row"
    And I should not see "View dates" in the "Position One" "table_row"
    And I should not see "2" in the "Position One" "table_row"

  Scenario: Program assignment by organisation: availability determines which columns are shown
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Organisations"
    And I click on "Organisation One" "link" in the "Add organisations to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add organisations to program" "totaradialogue"
    And I wait "1" seconds
    # While program is available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    And I should see "Set due date" in the "Organisation One" "table_row"
    And I should see "View dates" in the "Organisation One" "table_row"
    And I should see "2" in the "Organisation One" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Before program is available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    And I should see "Set due date" in the "Organisation One" "table_row"
    And I should see "View dates" in the "Organisation One" "table_row"
    And I should see "2" in the "Organisation One" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled]  | 0       |
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Program is no longer available.
    # Identifying the 'All below' checkbox currently requires using its id, which is not reliable.
    # If we fix this, we can check its disabled attribute.
    And I should see "No due date" in the "Organisation One" "table_row"
    And I should not see "View dates" in the "Organisation One" "table_row"
    And I should not see "2" in the "Organisation One" "table_row"

  Scenario: Program assignment by management hierarchy: availability determines which columns are shown
    Given I switch to "Assignments" tab
    And I set the field "Add a new" to "Management hierarchy"
    And I click on "fn_001 ln_001 (user001@example.com) - ja1" "link" in the "Add managers to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add managers to program" "totaradialogue"
    And I wait "1" seconds
    # While program is available.
    # The selection of direct team / all below should be available.
    And "direct team" "option" in the "fn_001 ln_001 - ja1" "table_row" should be visible
    And I should see "Set due date" in the "fn_001 ln_001 - ja1" "table_row"
    And I should see "View dates" in the "fn_001 ln_001 - ja1" "table_row"
    And I should see "2" in the "fn_001 ln_001 - ja1" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled] | 1       |
      | availablefrom[month]   | January |
      | availablefrom[day]     | 15      |
      | availablefrom[year]    | 2030    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Before program is available.
    # The selection of direct team / all below should be available.
    And "direct team" "option" in the "fn_001 ln_001 - ja1" "table_row" should be visible
    And I should see "Set due date" in the "fn_001 ln_001 - ja1" "table_row"
    And I should see "View dates" in the "fn_001 ln_001 - ja1" "table_row"
    And I should see "2" in the "fn_001 ln_001 - ja1" "table_row"

    When I switch to "Details" tab
    And I set the following fields to these values:
      | availablefrom[enabled]  | 0       |
      | availableuntil[enabled] | 1       |
      | availableuntil[month]   | January |
      | availableuntil[day]     | 15      |
      | availableuntil[year]    | 2017    |
    And I press "Save changes"
    And I switch to "Assignments" tab
    # Program is no longer available.
    # direct team should be visible as text, but not as a select option.
    And I should see "direct team" in the "fn_001 ln_001 - ja1" "table_row"
    And the "Include all below" "field" should be disabled
    And I should see "No due date" in the "fn_001 ln_001 - ja1" "table_row"
    And I should not see "View dates" in the "fn_001 ln_001 - ja1" "table_row"
    And I should not see "2" in the "fn_001 ln_001 - ja1" "table_row"
