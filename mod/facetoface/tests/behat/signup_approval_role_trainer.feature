@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Role Approval Trainer Role
  In order to signup to classroom connect
  As a learner
  I need to request approval from a session role
  And role approver must see tasks block

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | trainer | CCC    | teacher        |
      | jimmy   | CCC    | student        |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_session_roles[4]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I press "Save changes"
    And I click on "s__facetoface_approvaloptions[approval_role_4]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect Course" course homepage
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 10   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity              | 10   |
    And I click on "Benny Ben" "checkbox" in the "#id_trainerroles" "css_element"
    And I press "Save changes"
    And I log out

  Scenario: Student gets approved through role approval
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    Then I should see "Trainer"
    And I should see "Benny Ben"
    And I press "Request approval"
    And I run all adhoc tasks
    And I log out

    # Staying in same scenario to prevent re-load of data.
    When I log in as "trainer"
    And I click on "Dashboard" in the totara menu
    Then I should see "View all tasks"
    And I should see "Seminar booking role request: Test seminar name"
