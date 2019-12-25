@mod @mod_facetoface @totara @javascript
Feature: Seminar Signup Admin Approval
  In order to signup to a classroom connect
  As a learner
  I need to request approval from the manager and an admin

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
      | mickymau    | Micky     | Mau      | micky@example.com  |
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
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_admin]" "checkbox"
    And I press "Save changes"
    And I am on "Classroom Connect Course" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Classroom Connect Activity |
      | Description       | Classroom Connect Tests    |
    And I follow "View all events"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I expand all fieldsets
    And I click on "#id_approvaloptions_approval_admin" "css_element"
    And I click on "addapprovaladmins" "button"
    And I click on "Larry Lar" "link" in the "Select activity level approvers" "totaradialogue"
    And I click on "Search" "link" in the "Select activity level approvers" "totaradialogue"
    And I search for "Mick" in the "Select activity level approvers" totara dialogue
    And I click on "Micky Mau" from the search results in the "Select activity level approvers" totara dialogue
    And I click on "Save" "button" in the "Select activity level approvers" "totaradialogue"
    And I press "Save and display"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 0    |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 0    |
    And I press "OK"
    And I set the following fields to these values:
      | capacity           | 10   |
    And I press "Save changes"
    And I log out

  Scenario: Student signs up with no manager assigned when admin approval is required
    When I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I should see "More info"
    And I follow "More info"
    And I should see "Manager and Administrative approval"
    Then I should see "This seminar requires manager approval, you are currently not assigned to a manager in the system. Please contact the site administrator."

  Scenario: Student signs up with no manager assigned with manager select enabled and admin approval required
    When I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "sally"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager and Administrative approval"
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

    When I log out
    And I log in as "actapprover"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking admin request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sally Sal has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Sally Sal"

  Scenario: Student gets approved through both steps of the 2 stage approval
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager and Administrative approval"
    And I press "Request approval"
    And I run all adhoc tasks
    And I log out

    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking admin request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
    Then I should see "Jimmy Jim" in the ".lastrow" "css_element"

    When I click on "requests[8]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    Then I should not see "Jimmy Jim"
    And I switch to "Attendees" tab
    Then I should not see "Jimmy Jim"

    When I log out
    And I log in as "actapprover"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking admin request"
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Jimmy Jim has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link" in the "1 January 2020" "table_row"
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

  Scenario: Student signs up with manager assigned with manager select enabled and admin approval required and does not select manager
    When I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_managerselect" "checkbox"
    And I press "Save changes"
    And I log out

    And I log in as "sammy"
    And I am on "Classroom Connect Course" course homepage
    And I should see "Request approval"
    And I follow "Request approval"
    And I should see "Manager and Administrative approval"
    And I should see "Managers from all job assignements will be chosen if left empty"
    And I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."
    And I run all adhoc tasks

    When I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sammy Sam has requested to be booked into the following course" in the "td.message_values_statement" "css_element"
    And I click on "Attendees" "link"

  Scenario: Administrator approve and deny before manager
    # Add admin approver
    Given I log in as "admin"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    # Add users
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sammy Sam, sammy@example.com" "option"
    And I click on "Timmy Tim, timmy@example.com" "option"
    And I click on "Jimmy Jim, jimmy@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I run all adhoc tasks
    And I log out

    # Check alert
    And I log in as "actapprover"
    And I click on "Dashboard" in the totara menu
    And I click on "View all" "link"
    And I should see "This is to advise that Sammy Sam has requested to be booked into the following course"

    And I click on "Attendees" "link"
    And I should see "None" in the "Jimmy Jim" "table_row"
    And I should see "None" in the "Timmy Tim" "table_row"
    And I should see "None" in the "Sammy Sam" "table_row"
    # Decline
    And I click on ".c6 input" "css_element" in the "Jimmy Jim" "table_row"
    # Approve
    And I click on ".c7 input" "css_element" in the "Timmy Tim" "table_row"

    When I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should not see "Jimmy Jim"
    And I should not see "Timmy Tim"
    And I should see "Sammy Sam"
    And I log out

    # Check decline
    When I log in as "jimmy"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    Then I should see "Request approval"
    And I log out

    # Check approve
    When I log in as "timmy"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    Then I should see "Booked"
    And I should see "Cancel booking"
    And I log out

    # Check haven't decided
    When I log in as "sammy"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    Then I should see "Requested"
    And I should see "Cancel booking"
    And I log out

  Scenario: Multiple seminar event approvals and denials for the same user
    Given I log in as "admin"

    # Add approver column to the embedded report
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the following fields to these values:
      | Report Name value  | Seminar |
    And I click on "#id_submitgroupstandard_addfilter" "css_element"
    Then I should see "Seminars: Event attendees"

    When I follow "Seminars: Event attendees"
    And I follow "Columns"
    And I set the field "newcolumns" to "Approver name"
    And I press "Add"
    Then I should see "Approver name"
    # Add user to the event
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sammy Sam, sammy@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    Then I should not see "Sammy Sam"
    And I run all adhoc tasks
    And I log out

    # Check alert
    And I log in as "actapprover"
    And I click on "Dashboard" in the totara menu
    And I click on "View all tasks" "link"
    And I should see "This is to advise that Sammy Sam has requested to be booked into the following course"

    And I click on "Attendees" "link"
    Then I should see "None" in the "Sammy Sam" "table_row"

    # Approve
    When I click on ".c7 input" "css_element" in the "Sammy Sam" "table_row"
    And I press "Update requests"
    Then I should see "Attendance requests updated"
    And I should not see "Sammy Sam"
    And I log out

    When I log in as "admin"
    And I am on "Classroom Connect Course" course homepage
    And I follow "Classroom Connect Activity"
    And I click on "Attendees" "link"
    Then the following should exist in the "facetoface_sessions" table:
        | Name      | Status | Approver name |
        | Sammy Sam | Booked | Larry Lar     |

    # Now remove this user and re-approve
    # Only 1 row should be shown for this user
    When I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sammy Sam, sammy@example.com" "option"
    And I press "Remove"
    And I press "Continue"
    And I press "Confirm"
    Then I should not see "Sammy Sam"

    When I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sammy Sam, sammy@example.com" "option"
    And I press "Add"
    And I press "Continue"
    And I press "Confirm"
    And I follow "Approval required"
    And I click on ".c8 input" "css_element" in the "Sammy Sam" "table_row"
    And I press "Update requests"
    Then I should see "Attendance requests updated"

    When I follow "Attendees"
    Then the following should exist in the "facetoface_sessions" table:
        | Name      | Status | Approver name |
        | Sammy Sam | Booked | Admin User    |
    Then the following should not exist in the "facetoface_sessions" table:
        | Name      | Approver name |
        | Sammy Sam | Larry Lar     |
