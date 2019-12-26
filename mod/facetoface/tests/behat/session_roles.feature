@mod @mod_facetoface @totara
Feature: Use facetoface session roles
  In order to use session roles
  As a teacher
  I need to be able to setup session roles and see them in report

  @javascript
  Scenario: Setup and view facetoface session roles
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | middlename | email                |
      | teacher1 | Terry1    | Teacher1 | Midter1    | teacher1@example.com |
      | student1 | Sam1      | Student1 | Midsam1    | student1@example.com |
      | student2 | Sam2      | Student2 |            | student2@example.com |
      | student3 | Sam3      | Student3 |            | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | fullnamedisplay           | lastname middlename firstname |
      | alternativefullnameformat | lastname middlename firstname |
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I set the field "id_s__facetoface_session_roles_5" to "1"
    And I press "Save changes"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "F2F sessions"
    And I set the field "Source" to "Seminar Sessions"
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Event Learner" column to the report
    And I press "Save changes"
    And I switch to "Access" tab
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I log out

    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test facetoface name        |
      | Description | Test facetoface description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I set the field "Student1 Midsam1 Sam1" to "1"
    And I set the field "Student3 Sam3" to "1"
    And I press "Save changes"
    And I click on "Attendees" "link" in the "Booking open" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Student2 Sam2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Reports"
    And I follow "F2F sessions"
    Then I should see "Student3  Sam3" in the "Test facetoface name" "table_row"
    And I should see "Student1 Midsam1 Sam1" in the "Test facetoface name" "table_row"
    And I should not see "Student2" in the "Test facetoface name" "table_row"
