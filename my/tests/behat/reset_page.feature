@core @core_my @block @javascript
Feature: Reset dashboard page to default
  In order to remove customisations from dashboard page
  As a user
  I need to reset dashboard page

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "student1"
    And I click on "Dashboard" in the totara menu

  Scenario: Add blocks to page and reset
    When I press "Customise this page"
    And I add the "Online users" block
    And I add the "Comments" block
    And I press "Reset dashboard to default"
    Then I should not see "Online users"
    And I should see "Latest badges"
    And I should see "Upcoming events"
    And I should not see "Comments"
    And I should not see "Reset dashboard to default"
