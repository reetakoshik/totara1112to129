@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Role Approval
  In order to signup to classroom connect
  As a learner
  I need to request approval from a session role

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | sysapprover | Terry     | Ter      | terry@example.com  |
      | actapprover | Larry     | Lar      | larry@example.com  |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | trainer     | Benny     | Ben      | benny@example.com  |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
      | sally       | Sally     | Sal      | sally@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | trainer | CCC    | teacher        |
      | manager | CCC    | teacher        |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
      | sally   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_session_roles[3]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I press "Save changes"
    And I click on "s__facetoface_approvaloptions[approval_role_3]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect Course" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Classroom Connect       |
      | Description       | Classroom Connect Tests |
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
    And I click on "Freddy Fred" "checkbox" in the "#id_trainerroles" "css_element"
    And I press "Save changes"
    And I log out

  Scenario: Student signs up a with no roles assigned
    When I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Editing Trainer"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."

  Scenario: Student gets approved through role approval
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    Then I should see "Editing Trainer"
    And I should see "Freddy Fred"

    When I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks

    And I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    Then I should not see "Seminar trainer confirmation"

    When I log out
    And I log in as "teacher"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar trainer confirmation"

    When I click on "View all tasks" "link"
    And I click on "Attendees" "link"
    Then I should see "Jimmy Jim"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"
    And I run all adhoc tasks

    When I log out
    And I log in as "jimmy"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation"

    When I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    Then I should see "Booked" in the "1 January 2020" "table_row"
