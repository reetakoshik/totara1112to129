@totara @totara_cohort @totara_program
Feature: Set due date for program assignments via audience management
  In order to create a due date for users
  As an admin
  I should be able to add a due date via audience management interface

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             | timezone         |
      | user1    | John      | Smith    | user1@example.com | Europe/Rome      |
      | user2    | Mary      | Jones    | user2@example.com | America/New_York |
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname    |
      | Set Due Date Tests      | duedatetest  |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | Audience1 | aud1     | System       |           |
    And the following "cohort members" exist:
      | user  | cohort |
      | user1 | aud1   |
      | user2 | aud1   |
    And I log in as "admin"
    And I navigate to "Browse list of users" node in "Site administration > Users > Accounts"
    And I click on "Edit" "link" in the "Admin User" "table_row"
    And I select "Europe/Rome" from the "Timezone" singleselect
    And I press "Update profile"
    And I navigate to "Audiences" node in "Site administration > Users > Accounts"
    And I click on "Audience1" "link"
    And I switch to "Enrolled learning" tab
    And I press "Add programs"
    And I click on "Miscellaneous" "link" in the "Add Programs to Enrolled Learning" "totaradialogue"
    And I click on "Set Due Date Tests" "link" in the "Add Programs to Enrolled Learning" "totaradialogue"
    And I click on "Save" "button" in the "Add Programs to Enrolled Learning" "totaradialogue"
    And I wait "1" seconds

  @javascript
  Scenario: Fixed due dates can be set via audience management tab
    Given I click on "Set due date" "link" in the "Set Due Date Tests" "table_row"
    And I set the following fields to these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete by 9 Dec 2015 at 14:30" in the "Set Due Date Tests" "table_row"
    When I click on "Complete by 9 Dec 2015 at 14:30" "link" in the "Set Due Date Tests" "table_row"
    Then the following fields match these values:
      | completiontime       | 09/12/2015 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Cancel" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    When I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Set Due Date Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Edit program assignments" "button"
    Then I should see "Complete by 9 Dec 2015 at 14:30" in the "Audience1" "table_row"
    When I click on "Exception Report (2)" "link"
    And I select "All learners" from the "selectiontype" singleselect
    And I select "Assign" from the "selectionaction" singleselect
    And I press "Proceed with this action"
    And I click on "OK" "button" in the "Confirm issue resolution" "totaradialogue"
    And I log out
    And I log in as "user1"
    And I click on "Programs" in the totara menu
    And I click on "Set Due Date Tests" "link"
    Then I should see "Due date: 09 December 2015, 2:30 PM"
    When I log out
    And I log in as "user2"
    And I click on "Programs" in the totara menu
    And I click on "Set Due Date Tests" "link"
    Then I should see "Due date: 09 December 2015, 8:30 AM"

  @javascript
  Scenario: Relative due dates can be set via audience management tab
    Given I click on "Set due date" "link" in the "Set Due Date Tests" "table_row"
    And I set the following fields to these values:
      | timeamount | 2                       |
      | timeperiod | Day(s)                  |
      | eventtype  | Program enrollment date |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"
    And I wait "1" seconds
    Then I should see "Complete within 2 Day(s) of Program enrollment date" in the "Set Due Date Tests" "table_row"
    When I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Set Due Date Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Edit program assignments" "button"
    Then I should see "Complete within 2 Day(s) of Program enrollment date" in the "Audience1" "table_row"
