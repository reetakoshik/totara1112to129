@totara @totara_reportbuilder @totara_cohort @mod_facetoface @javascript
Feature: Test the visibility to see the seminar summary report depending on the course audience visibility setting
  In order to test the visibility
  As an admin
  I need to create a course with audience visibility setting, create seminar with event, create seminar summary report

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname      | shortname | category |
      | Course 17392A | C17392A   | 0        |
      | Course 17392B | C17392B   | 0        |
    And the following "activities" exist:
      | activity   | name           | course  | idnumber |
      | facetoface | Seminar 17392A | C17392A | S17392A  |
      | facetoface | Seminar 17392B | C17392B | S17392B  |

    And I am on "Course 17392A" course homepage
    And I follow "Seminar 17392A"
    And I follow "Add a new event"
    And I press "Save changes"

    And I am on "Course 17392B" course homepage
    And I follow "Seminar 17392B"
    And I follow "Add a new event"
    And I press "Save changes"

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Seminar Sessions"
    And I set the field "Source" to "Seminar Sessions"
    And I press "Create report"
    And I wait until "Edit Report 'Seminar Sessions'" "text" exists
    And I click on "Access" "link" in the ".tabtree" "css_element"
    And I set the field "All users can view this report" to "1"
    And I press "Save changes"
    And I log out

  Scenario: Learner see Seminar summary report with different course visibility
    # Learner see all seminars with visibility All users for all courses
    Given I log in as "student1"
    And I click on "Reports" in the totara menu
    And I follow "Seminar Sessions"
    And I should see "Seminar Sessions: 2 records shown"
    And I should see "Course 17392A"
    And I should see "Seminar 17392A"
    And I should see "Course 17392B"
    And I should see "Seminar 17392B"
    And I log out

    #  Learner see seminar with visibility All users and should not see a seminar with visibility No users
    And I log in as "admin"
    And I am on "Course 17392B" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I set the field "Visibility" to "No users"
    And I press "Save and display"
    And I log out

    And I log in as "student1"
    And I click on "Reports" in the totara menu
    And I follow "Seminar Sessions"
    And I should see "Seminar Sessions: 1 record shown"
    And I should see "Course 17392A"
    And I should see "Seminar 17392A"
    And I should not see "Course 17392B"
    And I should not see "Seminar 17392B"
    And I log out
