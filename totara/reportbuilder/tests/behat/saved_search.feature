@totara @totara_reportbuilder @javascript
Feature: Test report builder saved search

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname        | lastname | email                 |
      | user1    | User1-firstname  | Test     | user1@example.com     |
      | user2    | User2-firstname  | Test     | user2@example.com     |
      | user3    | User3-firstname  | Test     | user3@example.com     |
      | user4    | User4-firstname  | Test     | user4@example.com     |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom user report 1"
    And I set the field "Source" to "User"
    When I press "Create report"
    Then I should see "Edit Report 'Custom user report 1'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated user | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom user report 2"
    And I set the field "Source" to "User"
    And I press "Create report"
    Then I should see "Edit Report 'Custom user report 2'"
    When I switch to "Access" tab
    And I set the following fields to these values:
      | Authenticated user | 1 |
    And I press "Save changes"
    Then I should see "Report Updated"
    And I log out

  Scenario: I can delete a saved search
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    And I set the field "user-fullname" to "Search 1"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I press "Save changes"
    Then the "sid" select box should contain "My search 1"

    When I press "Manage searches"
    And I click on "Delete" "link" in the "My search 1" "table_row"
    Then I should see "Are you sure you want to delete this saved search 'My search 1'?"
    And I press "Continue"
    Then I should not see "My search 1"
    And I should see "This report does not have any saved searches."

  Scenario: I can delete a saved search that is being used for a scheduled report
    # Create a saved search.
    Given I log in as "admin"
    When I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    And I set the field "user-fullname" to "Search 1"
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the field "Search Name" to "My search 1"
    And I set the field "Let other users view" to "1"
    And I press "Save changes"
    Then the "sid" select box should contain "My search 1"

    # Create a scheduled report that doesn't use the saved search.
    When I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "All data"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "01:00"
    And I set the field "Export" to "CSV"
    And I press "Save changes"
    Then I should see "All data" in the "Daily at 01:00 AM" "table_row"

    # Create a couple of scheduled reports that use the saved search.
    When I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "02:00"
    And I set the field "Export" to "CSV"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 02:00 AM" "table_row"

    When I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "03:00"
    And I set the field "Export" to "Excel"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 03:00 AM" "table_row"
    And I log out

    # Create a scheduled report as another user using the same saved search.
    When I log in as "user1"
    And I click on "Reports" in the totara menu
    And I select "Custom user report 1" from the "addanewscheduledreport[reportid]" singleselect
    And I press "Add scheduled report"
    And I set the field "Data" to "My search 1"
    And I set the field "schedulegroup[frequency]" to "Daily"
    And I set the field "schedulegroup[daily]" to "04:00"
    And I set the field "Export" to "ODS"
    And I press "Save changes"
    Then I should see "Custom user report 1" in the "Daily at 04:00 AM" "table_row"
    And I log out

    # Delete the search.
    When I log in as "admin"
    And I click on "Reports" in the totara menu
    And I follow "Custom user report 1"
    And I press "Manage searches"
    And I click on "Delete" "link" in the "My search 1" "table_row"
    Then I should see "This saved search is currently being used by 3 scheduled reports. Deleting it will also delete these scheduled reports. Are you sure?"
    And I should see "Report: Custom user report 1"
    And I should not see "Daily at 01:00 AM"
    And I should see "Saved search: My search 1"
    And I should see "You" in the "Daily at 02:00 AM" "table_row"
    And I should see "You" in the "Daily at 03:00 AM" "table_row"
    And I should not see "You" in the "Daily at 04:00 AM" "table_row"
    When I press "Continue"
    Then I should not see "My search 1"
    And I should see "This report does not have any saved searches."

    When I click on "Reports" in the totara menu
    Then I should not see "My search 1"
