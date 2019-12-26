@block @block_totara_report_table @javascript @totara @totara_reportbuilder @dashboard
Feature: Only Alerts Report table block on dashboard
  In order to test the Alerts report table block functions on its own on the dashboard
  As a user
  I need to use a dashboard containing only the Alerts report table block
  and ensure that my messages are shown and everything functions properly

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Bob2      | Student2 | student2@example.com |
    And the following "cohorts" exist:
      | name       | idnumber | description            | contextlevel | reference |
      | Audience 1 | A1       | Audience 1 description | System       | 0         |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name              | course | idnumber |
      | facetoface | Test seminar name | C1     | seminar  |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

    # Create a Seminar.
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam1 Student1 |
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"

    # Set up the dashboard.
    And I navigate to "Dashboards" node in "Site administration > Navigation"
    And I press "Create dashboard"
    And I set the field "Name" to "My Dashboard"
    And I click on "Available only to the following audiences" "radio"
    And I press "Assign new audiences"
    And I follow "Audience 1"
    And I press "OK"
    And I press "Create dashboard"
    Then I should see "Dashboard saved"
    And I click on "moveup" "link"

    # Create an audience that we can allocate to the dashboard.
    When I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Audience 1"
    And I follow "Edit members"
    And I set the field "Potential users" to "Admin User (moodle@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "Sam1 Student1 (student1@example.com)"
    And I press "Add"
    And I set the field "Potential users" to "Bob2 Student2 (student2@example.com)"
    And I press "Add"
    And I follow "Members"
    Then I should see "Admin User"
    Then I should see "Sam1 Student1"
    Then I should see "Bob2 Student2"

    And I run all adhoc tasks
    And I log out

  Scenario: Add only the Alerts report table block to the dashboard
    When I log in as "student1"
    And I click on "Dashboard" in the totara menu
    When I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes            |
      | Block title                  | MyAlerts block |
      | Report                       | Alerts         |

    And I press "Save changes"
    And I press "Stop customising this page"

    Then I should see "Sam1 Student1" in the "MyAlerts block" "block"
    And I should see "Test seminar name" in the "MyAlerts block" "block"
    And I log out

    # Check that other users don't see your messages
    When I log in as "student2"
    And I click on "Dashboard" in the totara menu
    When I press "Customise this page"
    And I add the "Report table" block
    And I configure the "Report table" block
    And I set the following fields to these values:
      | Override default block title | Yes            |
      | Block title                  | MyAlerts block |
      | Report                       | Alerts         |

    And I press "Save changes"
    And I press "Stop customising this page"
    Then I should not see "Sam1 Student1" in the "MyAlerts block" "block"
    And I should see "There are no records in this report"
    And I log out

    # Check that the dismiss dialog box is shown correctly
    When I log in as "student1"
    And I follow "Dismiss message"
    Then I should see "Review Item(s)"
    When I press "Dismiss"
    Then I should not see "Test seminar name" in the "MyAlerts block" "block"
    And I should see "There are no records in this report"
