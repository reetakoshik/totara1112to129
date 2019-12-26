@totara @totara_reportbuilder
Feature: Make sure the message report is shown correctly
  In order to check the message report is not throwing any errors
  As admin
  I need to create a custom message report and add some content and access restrictions.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | user1    | User      | One      | user1@example.com    |
      | user2    | User      | Two      | user2@example.com    |
      | manager1 | Manager   | One      | manager1@example.com |
    And the following job assignments exist:
      | user     | manager  |
      | user1    | manager1 |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom message report"
    And I set the field "Source" to "Message"
    And I press "Create report"
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And the field "Only certain users can view this report (see below)" matches value "1"
    And I set the field "Authenticated user" to "1"
    And I press "Save changes"
    And I click on "Content" "link" in the ".tabtree" "css_element"
    And I set the field "Show records matching all of the checked criteria below" to "true"
    And I set the field "Show records based on user" to "1"
    And I set the field "Records for user's direct reports for any of the user's job assignments" to "1"
    And I press "Save changes"
    And I log out

  Scenario: Manager seeing the report.
    Given I log in as "manager1"
    And I click on "Reports" in the totara menu
    When I click on "Custom message report" "link" in the "#myreports_section" "css_element"
    Then I should not see "'user' not in join list for content"
