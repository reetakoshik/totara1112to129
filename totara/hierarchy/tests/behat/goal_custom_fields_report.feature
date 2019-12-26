@totara @totara_hierarchy @javascript @totara_customfield
Feature: Verify I can see all appropriate fields in the goal custom fields report source.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Bob1      | learner1 | learner1@example.com |
    And the following "goal" frameworks exist:
      | fullname                 | idnumber |
      | Company Goal Framework 1 | CGF1     |
    And the following "goal" hierarchy exists:
      | framework | fullname       | idnumber | description                              |
      | CGF1      | Company Goal 1 | CG1      | <p>Precise and accurate description!</p> |

    # Add a couple of goals to the admin user.
    And I log in as "admin"
    And I click on "Goals" in the totara menu

    # Add a company goal.
    And I press "Add company goal"
    And I follow "Company Goal 1"
    And I press "Save"

    # Add a personal goal.
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name        | Personal Goal 1                           |
      | Description | Woolly and imprecise description |
    And I press "Save changes"
    And I log out

  Scenario: Verify the basic goal fields can be see in the Goal Custom Fields report.
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Goal Custom Fields report |
      | Source      | Goal Custom Fields        |
    And I press "Create report"

    # Add the description column to the report.
    And I follow "Columns"
    And I set the field "newcolumns" to "Goal Description"
    And I press "Add"
    And I press "Save changes"

    # Add the description filter to the report.
    And I follow "Filters"
    And I set the field "newstandardfilter" to "Goal Description"
    And I press "Add"
    And I press "Save changes"

    # View and check the report contains the right data.
    When I follow "View This Report"
    Then I should see "Goal Custom Fields report: 2 records shown"
    And the following should exist in the "report_goal_custom_fields_report" table:
      | User's Fullname | Goal Name       | Personal or Company  | Goal Type | Goal Description                 |
      | Admin User      | Company Goal 1  | Company              | No Type   | Precise and accurate description |
      | Admin User      | Personal Goal 1 | Personal             | No Type   | Woolly and imprecise description |

  Scenario: Status and target date fields are shown in the Goal Custom Fields report
    Given a goal scale called "Good Bad" exists with the following values:
      | value |
      | Great |
      | Good  |
      | Okay  |
      | Bad   |
    And the following "goal" frameworks exist:
      | fullname                 | idnumber | scale |
      | Company Goal Framework 2 | CGF2     | 2     |
    And the following "goal" hierarchy exists:
      | framework | fullname       | idnumber | targetdate |
      | CGF2      | Company Goal 2 | CG2      | 05/08/2017 |
    And I log in as "learner1"
    And I click on "Goals" in the totara menu
    And I press "Add company goal"
    And I follow "Company Goal 1"
    And I click on "Search" "link" in the "Assign goals" "totaradialogue"
    And I search for "Goal" in the "Assign goals" totara dialogue
    And I follow "Company Goal 2 (CG2)"
    And I press "Save"
    And I wait "1" seconds
    And I press "Add personal goal"
    And I set the following fields to these values:
      | Name                | Think of more goals |
      | Scale               | Goal scale          |
      | targetdate[enabled] | 1                   |
      | targetdate[year]    | 2030                |
      | targetdate[month]   | August              |
      | targetdate[day]     | 15                  |
    And I press "Save changes"
    And I set the field with xpath "//a[text()='Company Goal 2']/ancestor::tr//select[@name='scalevalueid']" to "Good"
    And I set the field with xpath "//a[text()='Think of more goals']/ancestor::tr//select[@name='scalevalueid']" to "Goal In Progress"

    And I log out
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Goal Custom Fields report |
      | Source      | Goal Custom Fields        |
    And I press "Create report"

    # Add the description column to the report.
    And I follow "Columns"
    And I set the field "newcolumns" to "Status"
    And I press "Add"
    And I set the field "newcolumns" to "Target date"
    And I press "Add"
    And I press "Save changes"

    # View and check the report contains the right data.
    And I follow "View This Report"
    Then I should see "Goal Custom Fields report: 5 records shown"
    And the following should exist in the "report_goal_custom_fields_report" table:
      | User's Fullname | Goal Name           | Personal or Company  | Goal Type | Status           | Target date |
      | Admin User      | Company Goal 1      | Company              | No Type   | Goal Assigned    |             |
      | Admin User      | Personal Goal 1     | Personal             | No Type   |                  |             |
      | Bob1 learner1   | Company Goal 1      | Company              | No Type   | Goal Assigned    |             |
      | Bob1 learner1   | Company Goal 2      | Company              | No Type   | Good             | 5 Aug 2017  |
      | Bob1 learner1   | Think of more goals | Personal             | No Type   | Goal In Progress | 15 Aug 2030 |
