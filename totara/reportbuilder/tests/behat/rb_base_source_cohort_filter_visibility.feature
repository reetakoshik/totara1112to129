@totara @totara_cohort @javascript
Feature: Test the capability to see and use audience filter for report builder
  In order to test the capability
  As an admin
  I need to create cohort/course/program, add users, create an user/couse/program report and add cohort filter

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Learner1   | One       | learner1@example.com |
      | learner2 | Learner2   | Two       | learner2@example.com |
      | learner3 | Learner3   | Three     | learner3@example.com |
      | learner4 | Learner4   | Four      | learner4@example.com |
    And the following "cohorts" exist:
      | name             | idnumber | contextlevel | reference |
      | Audience TL-2986 | AUD2986  | System       |           |
    And the following "cohort members" exist:
      | user     | cohort  |
      | learner1 | AUD2986 |
      | learner2 | AUD2986 |
      | learner3 | AUD2986 |
      | learner4 | AUD2986 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort  |
      | C1     | AUD2986 |


  Scenario: create user report with audience filter, test moodle/cohort:view capability
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User Report |
      | Source      | User        |
    And I click on "Create report" "button"
    And I press "Save changes"

    And I switch to "Filters" tab
    And I select "User is a member of audience" from the "newstandardfilter" singleselect
    And I press "Add"

    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"

    And I click on "View This Report" "link"
    # Make sure that we are still can see and use Audience filter for admins.
    And I should see "User is a member of audience"
    And I click on "Add audience" "link"
    And I click on "Audience TL-2986" "link"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds

    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Learner1 One"
    And I should see "Learner2 Two"
    And I should see "Learner3 Three"
    And I should see "Learner4 Four"
    And I log out

    And I log in as "learner1"
    And I click on "Reports" in the totara menu
    # Test the user with no moodle/cohort:view capability can't see the filter.
    When I follow "User Report"
    Then I should not see "User is a member of audience"
    # But still can see the user report.
    And I should see "Learner1 One"
    And I should see "Learner2 Two"
    And I should see "Learner3 Three"
    And I should see "Learner4 Four"

  Scenario: create course report with audience filter, test moodle/cohort:view capability
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Course Report |
      | Source      | Courses       |
    And I click on "Create report" "button"
    And I press "Save changes"

    And I switch to "Filters" tab
    And I select "Course with enrolled audience(s)" from the "newstandardfilter" singleselect
    And I press "Add"

    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"

    # Make sure that we are still can see and use Audience filter for admins.
    And I should see "Course with enrolled audience(s)"
    And I click on "Add audience" "link"
    And I click on "Audience TL-2986" "link"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds

    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Course 1"
    And I log out

    And I log in as "learner1"
    And I click on "Reports" in the totara menu
    # Test the user with no moodle/cohort:view capability can't see the filter.
    When I follow "Course Report"
    Then I should not see "Course with enrolled audience(s)"
    # But still can see the course report.
    And I should see "Course 1"

  Scenario: create program report with audience filter, test moodle/cohort:view capability
    Given I log in as "admin"
    And I click on "Programs" in the totara menu
    And I press "Add a new program"
    And I set the following fields to these values:
      | fullname  | Program TL2986 |
      | shortname | Program TL2986 |
    And I press "Save changes"
    And I click on "Assignments" "link"
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience TL-2986" "link"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Program Report |
      | Source      | Programs       |
    And I click on "Create report" "button"
    And I press "Save changes"

    And I switch to "Filters" tab
    And I select "Program with enrolled audience(s)" from the "newstandardfilter" singleselect
    And I press "Add"

    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I click on "View This Report" "link"

    # Make sure that we are still can see and use Audience filter for admins.
    And I should see "Program with enrolled audience(s)"
    And I click on "Add audience" "link"
    And I click on "Audience TL-2986" "link"
    And I click on "Save" "button" in the "Choose audiences" "totaradialogue"
    And I wait "1" seconds

    When I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "Program TL2986"
    And I log out

    And I log in as "learner1"
    And I click on "Reports" in the totara menu
    # Test the user with no moodle/cohort:view capability can't see the filter.
    When I follow "Program Report"
    Then I should not see "Program with enrolled audience(s)"
    # But still can see the program report.
    And I should see "Program TL2986"
