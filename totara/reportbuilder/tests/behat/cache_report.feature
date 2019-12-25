@totara @totara_reportbuilder @javascript
Feature: Caching works as expected
  In order to cache report builder reports
  As a admin
  I need to be able set up caching

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable report caching | 1 |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Some User Report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I switch to "Performance" tab
    And I click on "Enable Report Caching" "text"
    And I click on "Generate Now" "text"
    And I click on "Save changes" "button"

  Scenario: Confirm report caching works as expected
    Given I click on "Reports" in the totara menu
    And I click on "Some User Report" "link"
    And I run the scheduled task "totara_reportbuilder\task\refresh_cache_task"
    Then I should see "Report data last updated"
    And I should see "User One"
    And I should see "User Two"

    # Create user and confirm it's not there until after report has been regenerated
    When the following "users" exist:
      | username | firstname | lastname | email             |
      | user3    | User      | Three    | user3@example.com |
    And I click on "Reports" in the totara menu
    And I click on "Some User Report" "link"
    Then I should see "User One"
    And I should see "User Two"
    And I should not see "User Three"

    # Regenerate report
    When I click on "Edit this report" "button"
    And I switch to "Performance" tab
    And I click on "Generate Now" "button"
    And I click on "OK" "button" in the "cachenow" "totaradialogue"
    And I click on "View This Report" "link"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
