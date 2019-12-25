@totara @totara_reportbuilder @totara_scheduledreports @tabexport @javascript
Feature: Test that report builder reports can be scheduled
  Create a report
  Go to Reports
  Create a schedule
  Check that it shows the scheduled report in the list

  Background: Set up a schedulable report
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname  | email          |
      | u1       | User      | One       | u1@example.com |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Schedulable Report"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

  Scenario: Report builder reports can be scheduled daily
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "schedulegroup[frequency]" to "Daily"
    Then the "schedulegroup[daily]" "select" should be enabled
    And the "schedulegroup[weekly]" "select" should be disabled
    And the "schedulegroup[monthly]" "select" should be disabled
    And the "schedulegroup[hourly]" "select" should be disabled
    And the "schedulegroup[minutely]" "select" should be disabled
    And I set the field "schedulegroup[daily]" to "03:00"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Daily at 03:00"

  Scenario: Report builder reports can be scheduled weekly
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "schedulegroup[frequency]" to "Weekly"
    Then the "schedulegroup[daily]" "select" should be disabled
    And the "schedulegroup[weekly]" "select" should be enabled
    And the "schedulegroup[monthly]" "select" should be disabled
    And the "schedulegroup[hourly]" "select" should be disabled
    And the "schedulegroup[minutely]" "select" should be disabled
    And I set the field "schedulegroup[weekly]" to "Tuesday"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Weekly on Tuesday"

  Scenario: Report builder reports can be scheduled monthly
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "schedulegroup[frequency]" to "Monthly"
    Then the "schedulegroup[daily]" "select" should be disabled
    And the "schedulegroup[weekly]" "select" should be disabled
    And the "schedulegroup[monthly]" "select" should be enabled
    And the "schedulegroup[hourly]" "select" should be disabled
    And the "schedulegroup[minutely]" "select" should be disabled
    And I set the field "schedulegroup[monthly]" to "7"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Monthly on the 7th"

  Scenario: Report builder reports can be scheduled hourly
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "schedulegroup[frequency]" to "Every X hours"
    Then the "schedulegroup[daily]" "select" should be disabled
    And the "schedulegroup[weekly]" "select" should be disabled
    And the "schedulegroup[monthly]" "select" should be disabled
    And the "schedulegroup[hourly]" "select" should be enabled
    And the "schedulegroup[minutely]" "select" should be disabled
    And I set the field "schedulegroup[hourly]" to "6"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Every 6 hour(s) from midnight"

  Scenario: Report builder reports can be scheduled minutely
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "schedulegroup[frequency]" to "Every X minutes"
    Then the "schedulegroup[daily]" "select" should be disabled
    And the "schedulegroup[weekly]" "select" should be disabled
    And the "schedulegroup[monthly]" "select" should be disabled
    And the "schedulegroup[hourly]" "select" should be disabled
    And the "schedulegroup[minutely]" "select" should be enabled
    And I set the field "schedulegroup[minutely]" to "15"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    When I press "Save changes"
    Then I should see "Every 15 minute(s) from the start of the hour"

  Scenario: Report builder reports can be exported in different formats
    When I click on "Reports" in the totara menu
    And I press "Add scheduled report"
    Then I should see "Schedulable Report"
    When I set the field "Export" to "CSV"
    And I set the field "externalemailsgrp[emailexternals]" to "admin@example.com"
    And I click on "Add email" "button"
    And I press "Save changes"
    Then I should see "CSV" in the "Schedulable Report" "table_row"

    When I click on "Edit" "link" in the "Schedulable Report" "table_row"
    And I set the field "Export" to "Excel"
    And I press "Save changes"
    Then I should see "Excel" in the "Schedulable Report" "table_row"

    When I click on "Edit" "link" in the "Schedulable Report" "table_row"
    And I set the field "Export" to "ODS"
    And I press "Save changes"
    Then I should see "ODS" in the "Schedulable Report" "table_row"

    When I click on "Edit" "link" in the "Schedulable Report" "table_row"
    And I set the field "Export" to "PDF landscape"
    And I press "Save changes"
    Then I should see "PDF landscape" in the "Schedulable Report" "table_row"

    When I click on "Edit" "link" in the "Schedulable Report" "table_row"
    And I set the field "Export" to "PDF portrait"
    And I press "Save changes"
    Then I should see "PDF portrait" in the "Schedulable Report" "table_row"

  Scenario: Scheduled reports can only be created by users with required capability
    When I log out
    And I log in as "u1"
    And I click on "Reports" in the totara menu
    Then I should see "Scheduled Reports"
    And I should see "There are no scheduled reports"
    And "Add scheduled report" "button" should exist

    When I press "Add scheduled report"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "06:00"
    And I set the field "Export" to "ODS"
    And I set the field "External email address to add" to "u2@example.com"
    And I press "Add email"
    And I press "Save changes"
    Then I should see "ODS" in the "Schedulable Report" "table_row"
    And I should see "Daily at 06:00" in the "Schedulable Report" "table_row"

    Given I log out
    And I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability                                  | permission |
      | totara/reportbuilder:createscheduledreports | Prevent    |
    And I log out

    When I log in as "u1"
    And I click on "Reports" in the totara menu
    Then I should not see "Scheduled Reports"
    And "Add scheduled report" "button" should not exist
    And I should not see "ODS"
    And I should not see "Daily at 06:00"

    Given I log out
    And I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | capability                                  | permission |
      | totara/reportbuilder:createscheduledreports | Allow      |
    When I log out

    When I log in as "u1"
    And I click on "Reports" in the totara menu
    Then I should see "Scheduled Reports"
    And "Add scheduled report" "button" should exist
    And I should see "ODS" in the "Schedulable Report" "table_row"
    And I should see "Daily at 06:00" in the "Schedulable Report" "table_row"

