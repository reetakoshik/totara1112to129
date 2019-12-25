@totara @totara_reportbuilder @totara_customfield @javascript
Feature: Filter reportbuilder results by multicheck filters on sidebar
  As an admin
  I filter reportbuilder results using faceted search

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname |
      | Course 0  | C0        |
      | Course 1  | C1        |
      | Course 2  | C2        |
      | Course 3  | C3        |
      | Course 13 | C13       |
    And the following "users" exist:
      | username | firstname | lastname | email             | country |
      | user1    | user      | one      | user1@example.com | AU      |
      | user2    | user      | two      | user2@example.com | NZ      |

  Scenario: Seminar events report works correctly with course sidebar filter
    And I log in as "admin"
    # Add multi-check custom field
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I click on "Multi-select" "option"
    And I set the following fields to these values:
      | Full name                   | Multi select |
      | Short name (must be unique) | multiselect  |
      | multiselectitem[0][option]  | Option 1     |
      | multiselectitem[1][option]  | Option 2     |
      | multiselectitem[2][option]  | Option 3     |
    And I press "Save changes"

    # Add customfield options to courses
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | customfield_multiselect[0]    | 1    |
    And I press "Save and display"

    And I am on "Course 2" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | customfield_multiselect[1]    | 1    |
    And I press "Save and display"

    And I am on "Course 3" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | customfield_multiselect[2]    | 1    |
    And I press "Save and display"

    And I am on "Course 13" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the following fields to these values:
      | customfield_multiselect[0]    | 1    |
      | customfield_multiselect[2]    | 1    |
    And I press "Save and display"

    And the following "activities" exist:
      | activity   | name       | course | idnumber |
      | facetoface | Seminar 0  | C0     | s0       |
      | facetoface | Seminar 1  | C1     | s1       |
      | facetoface | Seminar 2  | C2     | s2       |
      | facetoface | Seminar 3  | C3     | s3       |
      | facetoface | Seminar 13 | C13    | s13      |

    # Add seminar events
    And I am on "Course 0" course homepage
    And I follow "Seminar 0"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 1" course homepage
    And I follow "Seminar 1"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 2" course homepage
    And I follow "Seminar 2"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 3" course homepage
    And I follow "Seminar 3"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 13" course homepage
    And I follow "Seminar 13"
    And I follow "Add a new event"
    And I press "Save changes"

    # Create reportbuilder for seminar events with sidebar multi-check filter
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Given I set the field "Report Name" to "Seminar Sessions"
    And I set the field "Source" to "facetoface_summary"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "Multi select (text)" from the "newsidebarfilter" singleselect
    And I press "Add"
    And I select "Course Category" from the "newsidebarfilter" singleselect
    And I press "Add"
    And I press "Save changes"

    # Test it
    When I follow "View This Report"
    Then I should see "Option 1 (2)"
    And I should see "Option 2 (1)"
    And I should see "Option 3 (2)"
    And I should see "Course 0" in the "Seminar 0" "table_row"
    And I should see "Course 1" in the "Seminar 1" "table_row"
    And I should see "Course 2" in the "Seminar 2" "table_row"
    And I should see "Course 3" in the "Seminar 3" "table_row"
    And I should see "Course 13" in the "Seminar 13" "table_row"

    When I set the following fields to these values:
      | Option 1 (2) | 1 |
      | course_category-id_op  | 1 |
    Then I should not see "Course 0"
    And I should see "Course 1" in the "Seminar 1" "table_row"
    And I should not see "Course 2"
    And I should not see "Course 3"
    And I should see "Course 13" in the "Seminar 13" "table_row"

    When I set the following fields to these values:
      | Option 2 (1) | 1 |
    Then I should not see "Course 0"
    And I should see "Course 1" in the "Seminar 1" "table_row"
    And I should see "Course 2" in the "Seminar 2" "table_row"
    And I should not see "Course 3"
    And I should see "Course 13" in the "Seminar 13" "table_row"

    When I set the following fields to these values:
      | Option 1 (2) | 0 |
      | Option 2 (1) | 0 |
      | Option 3 (2) | 1 |
    Then I should not see "Course 0"
    And I should not see "Course 2"
    And I should see "Course 3" in the "Seminar 3" "table_row"
    And I should see "Course 13" in the "Seminar 13" "table_row"

  @_alert
  Scenario: Report with only sidefilter works correctly
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "User report"
    And I set the field "Source" to "user"
    And I press "Create report"
    And I switch to "Filters" tab
    And I click on "Delete" "link" in the "User's Fullname" "table_row" confirming the dialogue
    And I select "User's Country" from the "newsidebarfilter" singleselect
    And I press "Add"
    And I press "Save changes"

      # Test it
    When I follow "View This Report"
    Then I should see "user one"
    And I should see "user two"
    When I select "New Zealand" from the "user-country" singleselect
    Then I should not see "user one"
    And I should see "user two"
