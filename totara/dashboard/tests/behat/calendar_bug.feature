@javascript @totara @totara_dashboard
Feature: Test that calendar works properly with calendar when dashboard set as home page

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                  |
      | student1 | Student   | One      | student.one@local.host |
    And the following "cohorts" exist:
      | name | idnumber |
      | Cohort 1 | CH1 |
    And the following "cohort members" exist:
      | user     | cohort |
      | student1 | CH1    |
  Scenario: Calendar navigation when dashboard set as homepage works correctly
    Given I log in as "admin"
    # Add the calendar block to the site front page.
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    # Add a dashboard.
    And I press "Create dashboard"
    And I set the following fields to these values:
     | Name | The Dashboard |
    And I click on "Available only to the following audiences" "radio"
    And I press "Assign new audiences"
    And I click on "Cohort 1" "link"
    And I press "OK"
    And I wait "1" seconds
    And I press "Create dashboard"
    And I set the following administration settings values:
      | defaulthomepage | Totara dashboard |
    And I log out
    When I log in as "student1"
    And I click on "Home" in the totara menu
    And I should see "Calendar" in the ".block_calendar_month .title" "css_element"
    And I click on "Previous month" "link" in the ".minicalendar .calendar-controls" "css_element"
    Then I should see "Calendar" in the ".block_calendar_month .title" "css_element"
