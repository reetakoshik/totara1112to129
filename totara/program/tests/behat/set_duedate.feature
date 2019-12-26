@totara @totara_program
Feature: Set due date for program assignments
  In order to create a due date for users
  As an admin
  I must be able to add a due date to program assignments

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             | timezone         |
      | user1    | John      | Smith    | user1@example.com | Europe/Rome      |
      | user2    | Mary      | Jones    | user2@example.com | America/New_York |
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname | name                     | param1      |
      | text     | pfti      | Profile field text input |             |
    And the following "courses" exist:
      | fullname               | shortname     | format | enablecompletion |
      | Course search result x | coursesearchx | topics | 1                |
      | Course search result y | coursesearchy | topics | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname   |
      | Set Due Date Tests      | duedatetest |
      | Program search result x | progsearchx |
      | Program search result y | progsearchy |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber  |
      | Position Framework | pframe    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname                 | idnumber  | pos_framework |
      | Position One             | pos1      | pframe        |
      | Position search result x | posx      | pframe        |
      | Position search result y | posy      | pframe        |
    And I log in as "admin"
    # Unfortunately new custom fields are popping up in auth plugin settings.
    And I confirm new default admin settings
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "Admin User" "table_row"
    And I select "Europe/Rome" from the "Timezone" singleselect
    And I press "Update profile"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Set Due Date Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Edit program assignments" "button"

  @javascript
  Scenario: Fixed due dates can be set for individuals
    Given I set the field "Add a new" to "Individuals"
    And I click on "John Smith (user1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Mary Jones (user2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | completiontime       | 10/12/2015 |
      | completiontimehour   | 15         |
      | completiontimeminute | 45         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Mary Jones" "table_row"
    And I set the following fields to these values:
      | completiontime       | 12/12/2015 |
      | completiontimehour   | 02         |
      | completiontimeminute | 20         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "10 Dec 2015 at 15:45" in the "John Smith" "table_row"
    And I should see "12 Dec 2015 at 02:20" in the "Mary Jones" "table_row"
    When I click on "Exception Report (2)" "link"
    And I select "All learners" from the "selectiontype" singleselect
    And I select "Assign" from the "selectionaction" singleselect
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I log out
    And I log in as "user1"
    And I click on "Required Learning" in the totara menu
    Then I should see "Due date: 10 December 2015, 3:45 PM"
    When I log out
    And I log in as "user2"
    And I click on "Required Learning" in the totara menu
    Then I should see "Due date: 11 December 2015, 8:20 PM"

  @javascript
  Scenario: Fixed due dates can be set for audiences
    Given I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete by 9 Dec 2015 at 14:30" in the "Audience1" "table_row"
    When I click on "Set due date" "link" in the "Audience1" "table_row"
    Then the following fields match these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Cancel" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    When I click on "Exception Report (2)" "link"
    And I select "All learners" from the "selectiontype" singleselect
    And I select "Assign" from the "selectionaction" singleselect
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I log out
    And I log in as "user1"
    And I am on "Set Due Date Tests" program homepage
    Then I should see "Due date: 09 December 2015, 2:30 PM"
    When I log out
    And I log in as "user2"
    And I am on "Set Due Date Tests" program homepage
    Then I should see "Due date: 09 December 2015, 8:30 AM"

  @javascript
  Scenario: Relative due dates can be set for individuals
    Given I set the field "Add a new" to "Individuals"
    And I click on "John Smith (user1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Mary Jones (user2@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Mary Jones" "table_row"
    And I set the following fields to these values:
      | timeamount | 6                 |
      | timeperiod | Month(s)          |
      | eventtype  | Course completion |
    And I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    And I click on "Course search result y" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 4 Week(s) of First login" in the "John Smith" "table_row"
    And I should see "Complete within 6 Month(s) of completion of course 'Course search result y'" in the "Mary Jones" "table_row"

    # Now check the completion data is correctly repopulated in the dialogue correctly
    When I click on "Set due date" "link" in the "John Smith" "table_row"
    Then the following fields match these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    When I set the field "timeamount" to "5"
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 5 Week(s) of First login" in the "John Smith" "table_row"

    When I click on "Set due date" "link" in the "Mary Jones" "table_row"
    Then the following fields match these values:
      | timeamount | 6                 |
      | timeperiod | Month(s)          |
      | eventtype  | Course completion |
    When I set the field "timeperiod" to "Week(s)"
    And I click on "Course search result y" "link" in the "Completion criteria" "totaradialogue"
    And I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    And I click on "Course search result x" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 6 Week(s) of completion of course 'Course search result x'" in the "Mary Jones" "table_row"

  @javascript
  Scenario: Relative due dates can be set for audiences
    Given I set the field "Add a new" to "Audiences"
    And I click on "Audience1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set due date" "link" in the "Audience1" "table_row"
    And I set the following fields to these values:
      | timeamount | 2                      |
      | timeperiod | Year(s)                |
      | eventtype  | Position assigned date |
    And I click on "Position One" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    And I wait "1" seconds
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 2 Year(s) of being assigned position 'Position One'" in the "Audience1" "table_row"

  @javascript
  Scenario: Relative due date related objects can be selected and searched
    Given I set the field "Add a new" to "Individuals"
    And I click on "John Smith (user1@example.com)" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    # Program completion.
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | timeamount | 2                  |
      | timeperiod | Month(s)           |
      | eventtype  | Program completion |
    And I wait "1" seconds
    Then I should see "Miscellaneous" in the "Choose item" "totaradialogue"
    When I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    Then I should see "Program search result x" in the "Choose item" "totaradialogue"
    And I should see "Program search result y" in the "Choose item" "totaradialogue"
    And I click on "Search" "link" in the "Choose item" "totaradialogue"
    And I search for "x" in the "Choose item" totara dialogue
    Then I should see "Program search result x" in the "Choose item" "totaradialogue"
    And I should not see "Program search result y" in the "Choose item" "totaradialogue"
    And I click on "Program search result x" "link" in the "#search-tab" "css_element"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    When I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "Complete within 2 Month(s) of completion of program 'Program search result x'"
    # Position assigned date.
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | eventtype  | Position assigned date |
    And I wait "1" seconds
    Then I should see "Position search result x" in the "Choose item" "totaradialogue"
    And I should see "Position search result y" in the "Choose item" "totaradialogue"
    And I click on "Search" "link" in the "Choose item" "totaradialogue"
    And I search for "x" in the "Choose item" totara dialogue
    Then I should see "Position search result x" in the "Choose item" "totaradialogue"
    And I should not see "Position search result y" in the "Choose item" "totaradialogue"
    And I click on "Position search result x" "link" in the "#search-tab" "css_element"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    When I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "Complete within 2 Month(s) of being assigned position 'Position search result x'"
    # Job assignment start date.
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | eventtype  | Job assignment start date |
    And I wait "1" seconds
    Then I should see "Position search result x" in the "Choose item" "totaradialogue"
    And I should see "Position search result y" in the "Choose item" "totaradialogue"
    And I click on "Search" "link" in the "Choose item" "totaradialogue"
    And I search for "x" in the "Choose item" totara dialogue
    Then I should see "Position search result x" in the "Choose item" "totaradialogue"
    And I should not see "Position search result y" in the "Choose item" "totaradialogue"
    And I click on "Position search result x" "link" in the "#search-tab" "css_element"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    When I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "Complete within 2 Month(s) of start in position 'Position search result x'"
    # Course completion date.
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | eventtype  | Course completion |
    And I wait "1" seconds
    Then I should see "Miscellaneous" in the "Choose item" "totaradialogue"
    When I click on "Miscellaneous" "link" in the "Choose item" "totaradialogue"
    Then I should see "Course search result x" in the "Choose item" "totaradialogue"
    And I should see "Course search result y" in the "Choose item" "totaradialogue"
    And I click on "Search" "link" in the "Choose item" "totaradialogue"
    And I search for "x" in the "Choose item" totara dialogue
    Then I should see "Course search result x" in the "Choose item" "totaradialogue"
    And I should not see "Course search result y" in the "Choose item" "totaradialogue"
    And I click on "Course search result x" "link" in the "#search-tab" "css_element"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    When I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "Complete within 2 Month(s) of completion of course 'Course search result x'"
    # Profile field date.
    And I click on "Set due date" "link" in the "John Smith" "table_row"
    And I set the following fields to these values:
      | eventtype  | Profile field date |
    And I wait "1" seconds
    Then I should see "Profile field text input" in the "Choose item" "totaradialogue"
    And I click on "Profile field text input" "link" in the "Choose item" "totaradialogue"
    And I click on "Ok" "button" in the "Choose item" "totaradialogue"
    When I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "Complete within 2 Month(s) of date in profile field 'Profile field text input'"
