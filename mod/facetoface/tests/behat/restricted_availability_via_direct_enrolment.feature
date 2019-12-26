@mod @mod_facetoface @availability @totara @javascript
Feature: Seminar availability based on activity completion using direct enrolment plugin
  In order to check if we can sign up for the course using seminar direct enrolment and
  Availabilty restrictions are honored

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username | email            |
      | student1 | student1@example.com |
    And the following config values are set as admin:
      | enableavailability  | 1 |
      | enablecompletion    | 1 |

    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I navigate to "Seminar direct enrolment" node in "Site administration > Plugins > Enrolments"
    And I select "Yes" from the "Enable existing enrolments" singleselect
    And I click on "Save changes" "button"
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |

    # Create a Seminar which will be available.
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1"
    And I set the following fields to these values:
      | Name             | Available seminar |
      | Description      | Available seminar |
    And I press "Save and return to course"
    And I follow "View all events"
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
    And I press "Save changes"

    # Create a Seminar and add restriction so it won't be available until the first activity is marked as completed.
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I add a "Seminar" to section "1"
    And I set the following fields to these values:
      | Name             | Test seminar 1 |
      | Description      | Test seminar 1 |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And "Add restriction..." "dialogue" should be visible
    And I click on "Activity completion" "button" in the "Add restriction..." "dialogue"
    And I set the field "Activity or resource" to "Available seminar"
    And I press "Save and return to course"
    And I follow "Test seminar 1"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2021 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2021 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I press "Save changes"
    And I log out

  Scenario: Signup link is not available when availabilty conditions are not met when using seminar direct enrolment
    And I log in as "student1"
    And I click on "Courses" in the totara menu
    When I follow "Course 1"
    Then I should not see "Sign up"
    And I should see "Available seminar"
    And I should not see "Test seminar 1"
    # If we see the following it means that something went wrong and the direct enrolment plugin didn't get enabled for this seminar.
    And I should not see "You can not enrol"
    And I log out

  Scenario: Signup link will be available when restricted access is disabled
    Given the following config values are set as admin:
      | enableavailability  | 0 |
    And I log in as "student1"
    And I click on "Courses" in the totara menu
    When I follow "Course 1"
    Then I should see "Test seminar 1"
    And I should see "Available seminar"
    # If we see the following it means that something went wrong and the direct enrolment plugin didn't get enabled for this seminar.
    And I should not see "You can not enrol"
    And I log out

  Scenario: Signup link will be available when restricted access is disabled and a student is enrolled to the course
    Given the following config values are set as admin:
      | enableavailability  | 0 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And I log in as "student1"
    And I click on "Courses" in the totara menu
    When I follow "Course 1"
    Then I should see "Available seminar"
    And I should see "Test seminar 1"
    And I log out
