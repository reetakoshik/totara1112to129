@block @block_totara_report_table @javascript @totara @totara_reportbuilder
Feature: Report builder table block
  In order to test report builder table block
  As an admin
  I can add and view report table block

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname |
      | teacher1    | Teacher   | 1        |
      | teacher2    | Teacher   | 2        |
      | learner1    | Learner   | 1        |
      | learner2    | Learner   | 2        |
      | learner3    | Learner   | 3        |
      | learner4    | Learner   | 4        |
      | learner5    | Learner   | 5        |
      | learner6    | Learner   | 6        |
      | learner7    | Learner   | 7        |
      | learner8    | Learner   | 8        |
      | learner9    | Learner   | 9        |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User report |
      | Source      | User        |
    And I press "Create report"
    And I set the following fields to these values:
      | Number of records per page | 5 |
    And I press "Save changes"

  Scenario: Test report block navigation without sid
    # Add and configure block without sid
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes           |
      | Block title                  | Report wo sid |
      | Report                       | User report   |
    And I press "Save changes"
    And I press "Stop customising this page"

    # Change page and check (wo sid)
    And I click on "Username" "link" in the "Report wo sid" "block"
    Then I should see "Admin" in the "Report wo sid" "block"
    And I should see "learner3" in the "Report wo sid" "block"
    And I click on "Next" "link" in the "Report wo sid" "block"
    And I should see "learner4" in the "Report wo sid" "block"
    And I should see "learner8" in the "Report wo sid" "block"
    And I click on "Previous" "link" in the "Report wo sid" "block"
    And I should see "Admin" in the "Report wo sid" "block"
    And I should see "learner3" in the "Report wo sid" "block"

    # Change sorting and check (wo sid)
    And I click on "Username" "link" in the "Report wo sid" "block"
    And I should see "teacher1" in the "Report wo sid" "block"
    And I should see "learner7" in the "Report wo sid" "block"
    And I click on "Username" "link" in the "Report wo sid" "block"
    And I should see "learner3" in the "Report wo sid" "block"
    And I should see "Admin" in the "Report wo sid" "block"

  Scenario: Test report block navigation with sid
    # Create saved search for report.
    And I click on "View This Report" "link"
    # User filter field.
    And I set the following fields to these values:
      | user-fullname | learner |
    # "Search" button ambigous with "Search by" form section
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the following fields to these values:
      | Search Name          | LearnerSearch |
      | Let other users view | 1             |
    And I press "Save changes"

    # Add and configure block with sid
    When I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes         |
      | Block title                  | Report sid  |
      | Report                       | User report |
    And I press "Save changes"
    And I configure the "Report sid" block
    And I set the following fields to these values:
      | Saved search | LearnerSearch |
    And I press "Save changes"
    And I press "Stop customising this page"

    # Change page and check (sid)
    And I click on "Username" "link" in the "Report sid" "block"
    Then I should see "learner1" in the "Report sid" "block"
    And I should see "learner5" in the "Report sid" "block"
    And I should not see "teacher1" in the "Report sid" "block"
    And I should not see "teacher2" in the "Report sid" "block"
    And I click on "Next" "link" in the "Report sid" "block"
    And I should see "learner6" in the "Report sid" "block"
    And I should see "learner9" in the "Report sid" "block"
    And I should not see "teacher1" in the "Report sid" "block"
    And I should not see "teacher2" in the "Report sid" "block"
    And I should not see "Next" in the "Report sid" "block"
    And I click on "Previous" "link" in the "Report sid" "block"
    And I should see "learner1" in the "Report sid" "block"
    And I should see "learner5" in the "Report sid" "block"
    And I should not see "teacher1" in the "Report sid" "block"
    And I should not see "teacher2" in the "Report sid" "block"

    # Change sorting and check (sid)
    And I click on "Username" "link" in the "Report sid" "block"
    And I should see "learner9" in the "Report sid" "block"
    And I should see "learner5" in the "Report sid" "block"
    And I should not see "teacher1" in the "Report sid" "block"
    And I should not see "teacher2" in the "Report sid" "block"
    And I click on "Username" "link" in the "Report sid" "block"
    And I should see "learner4" in the "Report sid" "block"
    And I should see "learner1" in the "Report sid" "block"
    And I should not see "teacher1" in the "Report sid" "block"
    And I should not see "teacher2" in the "Report sid" "block"

  Scenario: Test report block navigation with sid from filter
  # Add Filter
    And I click on "Filters" "link"
    And I set the following fields to these values:
      | newsearchcolumn | User's Fullname |
    And I press "Save changes"

  # Create saved search
    And I click on "View This Report" "link"
    And I set the following fields to these values:
      | toolbarsearchtext | Learner |
  # "Search" button ambigous with "Search by" form section
    And I press "id_toolbarsearchbutton"
    And I press "Save this search"
    And I set the following fields to these values:
      | Search Name          | LearnerFilter |
      | Let other users view | 1             |
    And I press "Save changes"

  # Add and configure block with sid
    When I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes         |
      | Block title                  | Report sid  |
      | Report                       | User report |
    And I press "Save changes"
    And I configure the "Report sid" block
    And I set the following fields to these values:
      | Saved search | LearnerFilter |
    And I press "Save changes"
    And I press "Stop customising this page"

  # Change page and check filter has been applied
  # Don't check specific names this time, as that is tested above
    Then I should see "Learner" in the "Report sid" "block"
    And I should not see "Teacher" in the "Report sid" "block"
    And I click on "Next" "link" in the "Report sid" "block"
    And I should see "Learner" in the "Report sid" "block"
    And I should not see "Teacher" in the "Report sid" "block"
    And I click on "Previous" "link" in the "Report sid" "block"
    And I should see "Learner" in the "Report sid" "block"
    And I should not see "Teacher" in the "Report sid" "block"

  # Change sorting and check (sid)
    And I click on "Username" "link" in the "Report sid" "block"
    And I should see "Learner" in the "Report sid" "block"
    And I should not see "Teacher" in the "Report sid" "block"
    And I click on "Username" "link" in the "Report sid" "block"
    And I should see "Learner" in the "Report sid" "block"
    And I should not see "Teacher" in the "Report sid" "block"

  Scenario: Test block settings by user that does not have access to report
    # Make report public
    And I switch to "Access" tab
    And I click on "All users can view this report" "radio"
    And I press "Save changes"
    And I log out
    # Log in as a user and add report to my learning
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes                |
      | Block title                  | Report access test |
      | Report                       | User report        |
    And I press "Save changes"
    And I press "Stop customising this page"
    And I should see "Admin" in the "Report access test" "block"
    And I log out
    # Remove access to report
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Settings" "link" in the "User report" "table_row"
    When I switch to "Access" tab
    And I click on "Only certain users can view this report (see below)" "radio"
    And I press "Save changes"
    And I log out
    # Log in as a user and check that report name and content is not shown
    When I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    Then I should not see "Admin" in the "Report access test" "block"
    And I configure the "Report access test" block
    And I expand all fieldsets
    And I should see "Current report (inaccessible)"

  Scenario: Test block settings when report saved search became not public
    # Make public saved search
    And I click on "View This Report" "link"
    And I set the following fields to these values:
      | user-fullname | learner |
    # "Search" button ambigous with "Search by" form section
    And I press "id_submitgroupstandard_addfilter"
    And I press "Save this search"
    And I set the following fields to these values:
      | Search Name          | LearnerSearch |
      | Let other users view | 1             |
    And I press "Save changes"
    # Create block with it
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes                    |
      | Block title                  | Report sid access test |
      | Report                       | User report            |
      | Saved search                 | LearnerSearch          |
    And I press "Save changes"
    And I should see "learner2" in the "Report sid access test" "block"
    # Make this saved search non-public
    And I click on "Reports" in the totara menu
    And I click on "User report" "link"
    And I press "Manage searches"
    And I click on "Edit" "link" in the "LearnerSearch" "table_row"
    And I set the following fields to these values:
      | Let other users view | 0 |
    And I press "Save changes"
    And I press "Close"
    # Confirm that block report is not shown
    When I click on "Dashboard" in the totara menu
    Then I should not see "learner2" in the "Report sid access test" "block"
    # Confirm that name of saved search is not shown
    And I configure the "Report sid access test" block
    And I expand all fieldsets
    And I should see "Current saved search (inaccessible)"
    And I should not see "LearnerSearch"

  Scenario: Test block when report is removed
    # Make block of report
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes                    |
      | Block title                  | Report not exists test |
      | Report                       | User report            |
    And I press "Save changes"
    # Remove report
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Delete" "link" in the "User report" "table_row"
    And I press "Delete"
    # Confirm that report is not shown, but page still works fine
    When I click on "Dashboard" in the totara menu
    Then I should not see "Can not find data record in database."

  Scenario: Test view full report link for none embedded report block navigation
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes           |
      | Block title                  | Report wo sid |
      | Report                       | User report   |
    And I press "Save changes"
    And I press "Stop customising this page"
    And I click on "View full report" "link" in the "Report wo sid" "block"
    And I should see "User report: 13 records shown"

  Scenario: Test view full report link for embedded report block navigation
    # Enable report-based catalogue to be able to select it.
    And I set the following administration settings values:
      | catalogtype | enhanced |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |

    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes                             |
      | Block title                  | Course Catalog                  |
      | Report                       | Report-based catalogue: courses |
    And I press "Save changes"
    And I press "Stop customising this page"
    And I click on "View full report" "link" in the "Course Catalog" "block"
    And I should see "Search Courses: 2 records shown"
