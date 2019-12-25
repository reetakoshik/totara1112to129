@enrol @totara @enrol_totara_facetoface @javascript @mod_facetoface
Feature: Test add/update/delete actions for Seminar direct enrolment method
  In order to manage Seminar direct enrolment method
  I use Enrolments plugins to enable Seminar direct enrolment
  As an admin
  I need to add/update/delete Seminar direct enrolment

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | alice    | Alice     | Smith    | alice@example.com |
    And the following "courses" exist:
      | fullname     | shortname |
      | Course 10782 | C10782    |
    And the following "activities" exist:
      | activity   | name          | course | idnumber |
      | facetoface | Seminar 10782 | C10782 | S10782   |
    And I log in as "admin"
    And I navigate to "Manage enrol plugins" node in "Site administration > Plugins > Enrolments"
    And I click on "Enable" "link" in the "Seminar direct enrolment" "table_row"
    And I click on "Home" in the totara menu
    And I follow "Course 10782"
    And I add "Seminar direct enrolment" enrolment method with:
      | Custom instance name | Test student enrolment |

  Scenario: Check Seminar direct enrolment when no users enrolled
    Given I click on "Home" in the totara menu
    And I follow "Course 10782"
    When I navigate to "Enrolment methods" node in "Course administration > Users"
    Then I should see "Test student enrolment"
    And I should see "Delete" in the "Test student enrolment" "table_row"
    And I should see "Disable" in the "Test student enrolment" "table_row"
    And I should see "Edit" in the "Test student enrolment" "table_row"

    When I click on "Edit" "link" in the "Test student enrolment" "table_row"
    And I set the following fields to these values:
      | Custom instance name | Seminar enrolment 10782 |
    And I press "Save changes"
    Then I should see "Seminar enrolment 10782"
    And I should not see "Test student enrolment"
    And I should see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should see "Disable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"

    When I click on "Disable" "link" in the "Seminar enrolment 10782" "table_row"
    Then I should see "Seminar enrolment 10782"
    And I should see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should see "Enable" in the "Seminar enrolment 10782" "table_row"
    And I should not see "Disable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"

    When I click on "Delete" "link" in the "Seminar enrolment 10782" "table_row"
    And I should see "You are about to delete the enrolment method \"Seminar enrolment 10782\". Are you sure you want to continue?"
    And I press "Cancel"
    Then I should see "Seminar enrolment 10782"
    And I should see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should see "Enable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"

    When I click on "Delete" "link" in the "Seminar enrolment 10782" "table_row"
    And I should see "You are about to delete the enrolment method \"Seminar enrolment 10782\". Are you sure you want to continue?"
    And I press "Continue"
    Then I should not see "Seminar enrolment 10782"

  Scenario: Check Seminar direct enrolment with users enrolled
    Given I click on "Home" in the totara menu
    And I follow "Course 10782"
    And I follow "Seminar 10782"
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
    And I log out
    And I log in as "alice"
    And I am on "Course 10782" course homepage
    And I follow "Sign-up"
    When I press "Sign-up"
    Then I should see "Seminar 10782: Your request was accepted"
    And I log out

    And I log in as "admin"
    And I follow "Course 10782"
    When I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "Alice Smith" in the "Learner" "table_row"
    When I navigate to "Enrolment methods" node in "Course administration > Users"
    Then I should see "Test student enrolment"
    And I should see "1" in the "Test student enrolment" "table_row"
    And I should not see "Delete" in the "Test student enrolment" "table_row"
    And I should see "Disable" in the "Test student enrolment" "table_row"
    And I should see "Edit" in the "Test student enrolment" "table_row"

    When I click on "Edit" "link" in the "Test student enrolment" "table_row"
    And I set the following fields to these values:
      | Custom instance name | Seminar enrolment 10782 |
    And I press "Save changes"
    Then I should see "Seminar enrolment 10782"
    And I should not see "Test student enrolment"
    And I should not see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should see "Disable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"

    When I click on "Disable" "link" in the "Seminar enrolment 10782" "table_row"
    Then I should see "Seminar enrolment 10782"
    And I should not see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should see "Enable" in the "Seminar enrolment 10782" "table_row"
    And I should not see "Disable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"

    When I click on "Enable" "link" in the "Seminar enrolment 10782" "table_row"
    Then I should see "Seminar enrolment 10782"
    And I should not see "Delete" in the "Seminar enrolment 10782" "table_row"
    And I should not see "Enable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Disable" in the "Seminar enrolment 10782" "table_row"
    And I should see "Edit" in the "Seminar enrolment 10782" "table_row"
