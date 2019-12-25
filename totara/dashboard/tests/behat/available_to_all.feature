@javascript @totara @totara_dashboard
Feature: Dashboard available for all logged in users
  In order to ensure that dashboard available for all users
  As a user
  I need to access and change dashboard

  Background:
    Given I am on a totara site
    And the following "users" exist:
        | username |
        | learner1 |
        | learner2 |
    And the following "cohorts" exist:
        | name | idnumber |
        | Cohort 1 | CH1 |
    And the following "cohort members" exist:
      | user     | cohort |
      | learner1 | CH1    |
    And the following totara_dashboards exist:
      | name               | locked | published | cohorts |
      | Audience dashboard | 1      | 1         | CH1     |
      | Public dashboard   | 0      | 2         |         |

  Scenario: Check that user that have cohort dashboard and available to all can access and change "available to all"
    Given I log in as "learner1"
    And I should see "Audience dashboard"
    And I should see "Public dashboard"
    When I click on "Public dashboard" "link"
    Then I press "Customise this page"

  Scenario: Check that user that doesn't have any cohort dashboards can access and change "available to all"
    Given I log in as "learner2"
    And I should see "Dashboard" in the totara menu
    When I click on "Dashboard" in the totara menu
    Then I press "Customise this page"

  Scenario: Check that guest cannot access any dashboards
    Given I log in as "admin"
    And I set the following administration settings values:
      | Guest login button | Show |
    And I log out
    And I click on "#guestlogin input[type=submit]" "css_element"
    And I should not see "Dashboard"
