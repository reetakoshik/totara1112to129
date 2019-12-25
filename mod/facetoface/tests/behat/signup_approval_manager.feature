@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Manager Approval
  In order to signup to classroom connect
  As a learner
  I need to request approval from my manager

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
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I press "Save changes"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                | Classroom Connect       |
      | Description         | Classroom Connect Tests |
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
  And I press "Save changes"

  Scenario: Student signs up with no manager assigned
    When I log out
    When I log in as "sally"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I should see "Manager Approval"
    And I should see "This seminar requires manager approval, you are currently not assigned to a manager in the system. Please contact the site administrator."

  Scenario: Student signs up with no manager assigned with manager select enabled and manager approval required
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "sally"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I should see "Manager Approval"
    And I press "Request approval"
    Then I should see "This seminar requires manager approval, please select a manager to request approval"

    And I press "Choose manager"
    And I click on "Cassy Cas" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval"

    When I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "Sally Sal" in the "td.user_namelink" "css_element"
    And I click on "Attendees" "link"

    Then I should see "Sally Sal"
    When I click on "requests[11]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Sally Sal"

  Scenario: Student gets approved through manager approval
    When I log out
    And I log in as "jimmy"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    And I log out

    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "Jimmy Jim" in the "td.user_namelink" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"

    When I log out
    And I log in as "jimmy"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation"

    When I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "View all events"
    Then I should see "Booked" in the "1 January 2020" "table_row"

    When I click on "More info" "link"
    Then I should see "Manager's name"
    And I should see "Cassy Cas"

  Scenario: Student remove the existing manager and assign a new manager itself.
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "jimmy"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I should see "Sign-up"
    And I follow "Sign-up"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"

    And I press "Choose manager"
    And I click on "Timmy Tim" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"

    And I press "Request approval"
    And I log out

    And I log in as "timmy"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "Jimmy Jim" in the "td.user_namelink" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"

    When I log out
    And I log in as "jimmy"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking confirmation"

    When I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "View all events"
    Then I should see "Booked" in the "1 January 2020" "table_row"
