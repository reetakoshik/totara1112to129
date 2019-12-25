@totara @totara_reportbuilder @totara_scheduledreports @javascript
Feature: Test scheduled reports with new frequency setting
  Create a report
  Go to Reports
  Create a scheduled report
  Check schedule frequency setting

  Background: Set up a schedulable report
    Given I log in as "admin"
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Site Logs |
      | Source      | Site Logs |
    And I click on "Create report" "button"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I set the following administration settings values:
      | Minimum scheduled report frequency | 1 |
    And I log out

  Scenario: Test Minimum scheduled report frequency setting and overridescheduledfrequency capability
    Given I log in as "admin"
    And I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    # Test Minimum scheduled report frequency for admin and set it to Daily
    # Schedule setting should still have all options available
    And the "schedulegroup[frequency]" select box should contain "Every X minutes"
    And the "schedulegroup[frequency]" select box should contain "Every X hours"
    And the "schedulegroup[frequency]" select box should contain "Daily"
    And the "schedulegroup[frequency]" select box should contain "Weekly"
    And the "schedulegroup[frequency]" select box should contain "Monthly"
    And I set the field "schedulegroup[frequency]" to "Every X minutes"
    And I set the field "schedulegroup[minutely]" to "15"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Every 15 minute(s) from the start of the hour"
    And I log out

    # Test Minimum scheduled report frequency for user when it set to Daily
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    And the "schedulegroup[frequency]" select box should contain "Daily"
    And the "schedulegroup[frequency]" select box should contain "Weekly"
    And the "schedulegroup[frequency]" select box should contain "Monthly"
    And the "schedulegroup[frequency]" select box should not contain "Every X minutes"
    And the "schedulegroup[frequency]" select box should not contain "Every X hours"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "10:00"
    And I set the field "externalemailsgrp[emailexternals]" to "user1@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Daily at 10:00 AM"
    And I log out

    # Change new capability to allow for the user
    And I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability                                      | permission |
      | totara/reportbuilder:overridescheduledfrequency | Allow      |
    And I log out
    # Test Schedule setting with capability and Minimum scheduled report frequency setting
    # All options should be available
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    When I click on "Edit" "link" in the "Site Logs" "table_row"
    Then the "schedulegroup[frequency]" select box should contain "Every X minutes"
    And the "schedulegroup[frequency]" select box should contain "Every X hours"
    And the "schedulegroup[frequency]" select box should contain "Daily"
    And the "schedulegroup[frequency]" select box should contain "Weekly"
    And the "schedulegroup[frequency]" select box should contain "Monthly"
