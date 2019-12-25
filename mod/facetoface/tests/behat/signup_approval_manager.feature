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
    And I am on "Classroom Connect Course" course homepage with editing mode on
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
    And I am on "Classroom Connect Course" course homepage
    And I should see "More info"
    And I follow "More info"
    And I should see "Manager Approval"
    And I should see "This seminar requires manager approval, you are currently not assigned to a manager in the system. Please contact the site administrator."

  Scenario: Student signs up with two managers assigned with manager select enabled and manager approval required
    # Add two more managers
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | tammy       | Tammy     | Tam      | tammy@example.com  |
      | yummy       | Yummy     | Yum      | yummy@example.com  |
      | funny       | Funny     | Fun      | funny@example.com  |
    And the following job assignments exist:
      | user  | fullname | idnumber | manager |
      | sally | jajaja1  | 1        | tammy   |
      | sally | jajaja2  | 2        | yummy   |
    And I set the following administration settings values:
      | facetoface_managerselect | 1 |
    And I log out

    And I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks
    And I log out

    And I log in as "tammy"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "mod/facetoface/attendees" "link" in the "td.message_values_statement" "css_element"
    Then I should see "Tammy Tam" in the "Sally Sal" "table_row"
    Then I should see "Yummy Yum" in the "Sally Sal" "table_row"
    And I log out

    And I log in as "yummy"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "mod/facetoface/attendees" "link" in the "td.message_values_statement" "css_element"
    Then I should see "Tammy Tam" in the "Sally Sal" "table_row"
    Then I should see "Yummy Yum" in the "Sally Sal" "table_row"
    And I log out

    And I log in as "funny"
    And I click on "Dashboard" in the totara menu
    And I should not see "View all tasks"

  Scenario: Student signs up with no manager assigned with manager select enabled and manager approval required
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I press "Request approval"
    Then I should see "This seminar requires manager approval, please select a manager to request approval"

    And I press "Choose manager"
    And I click on "Cassy Cas" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks

    When I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link"

    Then I should see "Sally Sal"
    When I click on "requests[11]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Sally Sal"

  Scenario: Student gets approved through manager approval
    When I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    And I run all adhoc tasks
    And I log out

    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

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

    When I click on "More info" "link"
    Then I should see "Manager's name"
    And I should see "Cassy Cas"

  Scenario: Student remove the existing manager and assign a new manager itself.
    When I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"

    And I press "Choose manager"
    And I click on "Timmy Tim" "link" in the "Select manager" "totaradialogue"
    And I click on "OK" "button" in the "Select manager" "totaradialogue"

    And I press "Request approval"
    And I run all adhoc tasks
    And I log out

    And I log in as "timmy"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

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

  Scenario: Trainer is given permission to approve any bookings
    And I log out
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager Approval"
    And I should see "Cassy Cas"
    And I press "Request approval"
    And I run all adhoc tasks
    And I log out
    When I log in as "trainer"
    And I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    Then I should not see "Approval required" in the ".tabtree" "css_element"

    And I log out
    And I log in as "admin"
    And the following "permission overrides" exist:
      | capability                       | permission | role    | contextlevel | reference |
      | mod/facetoface:approveanyrequest | Allow      | teacher | Course       | CCC       |
    And I log out
    When I log in as "trainer"
    And I am on "Classroom Connect Course" course homepage
    And I follow "View all events"
    And I follow "Attendees"
    And I follow "Approval required"
    And I click on "input[value='2']" "css_element" in the "Jimmy Jim" "table_row"
    And I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should not see "Jimmy Jim"
