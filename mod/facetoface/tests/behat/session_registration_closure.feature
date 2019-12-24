@mod @mod_facetoface @totara @javascript
Feature: Seminar Event Registration Closure
  In order to test user's status code when Face-to-face registration closes
  As a manager
  I need to set up users in various states

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | manager  | Terry     | Ter      | manager@example.com |
      | sally    | Sally     | Sal      | sally@example.com   |
      | jelly    | Jelly     | Jel      | jelly@example.com   |
      | minny    | Minny     | Min      | minny@example.com   |
      | moxxy    | Moxxy     | Mox      | moxxy@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | manager | C1     | editingteacher |
      | sally   | C1     | student        |
      | jelly   | C1     | student        |
      | minny   | C1     | student        |
      | moxxy   | C1     | student        |
    And the following job assignments exist:
      | user  | manager |
      | sally | manager |
      | jelly | manager |
      | minny | manager |
      | moxxy | manager |
    And I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "s__facetoface_approvaloptions[approval_none]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_self]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_manager]" "checkbox"
    And I click on "s__facetoface_approvaloptions[approval_admin]" "checkbox"
    And I press "Save changes"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test facetoface name        |
      | Description       | Test facetoface description |
    And I click on "Test facetoface name" "link"
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
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity                        | 4    |
      | registrationtimestart[enabled]  | 1    |
      | registrationtimestart[day]      | 1    |
      | registrationtimestart[month]    | 1    |
      | registrationtimestart[year]     | 2010 |
      | registrationtimestart[hour]     | 05   |
      | registrationtimestart[minute]   | 00   |
      | registrationtimefinish[enabled] | 1    |
      | registrationtimefinish[day]     | 30   |
      | registrationtimefinish[month]   | 12   |
      | registrationtimefinish[year]    | 2019 |
      | registrationtimefinish[hour]    | 17   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sally Sal, sally@example.com" "option"
    And I click on "Jelly Jel, jelly@example.com" "option"
    And I click on "Minny Min, minny@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Approval required"
    And I set the field "Approve Sally Sal for this event" to "1"
    And I press "Update requests"
    And I log out
    And I log in as "manager"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I click on "Test facetoface name" "link"
    And I follow "Attendees"
    And I follow "Approval required"
    And I set the field "Approve Jelly Jel for this event" to "1"
    And I press "Update requests"
    And I log out
    And I log in as "admin"

  Scenario: Session registration closure denies all pending requests and stops updates
    Given I navigate to "Create report" node in "Site administration > Reports > Report builder"
    And I set the following fields to these values:
      | Report Name | Global Session Status |
      | Source      | Seminar Sign-ups      |
    And I press "Create report"
    And I should see "Global Session Status"
    And I switch to "Columns" tab
    And I add the "Status" column to the report
    And I click on "View This Report" "link" in the ".reportbuilder-navlinks" "css_element"
    Then I should not see "Moxxy Mox"
    And I should see "Requested (2step)" in the "Jelly Jel" "table_row"
    And I should see "Requested" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"

    When I click on "Find Learning" in the totara menu
    And I click on "Course 1" "link"
    And I click on "View all events" "link"
    And I click on "Edit event" "link" in the "1 January 2020" "table_row"
    And I set the following fields to these values:
      | registrationtimefinish[day]     | 1    |
      | registrationtimefinish[month]   | 1    |
      | registrationtimefinish[year]    | 2010 |
      | registrationtimefinish[hour]    | 17   |
      | registrationtimefinish[minute]  | 00   |
    And I press "Save changes"
    And I run the "\mod_facetoface\task\close_registrations_task" task
    And I click on "Reports" in the totara menu
    And I click on "Global Session Status" "link" in the "#myreports_section" "css_element"
    Then I should not see "Moxxy Mox"
    And I should see "Declined" in the "Jelly Jel" "table_row"
    And I should see "Declined" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"

    When I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    And I should see "Seminar event registration closure" in the ".block_totara_alerts" "css_element"
    And I click on "#detailtask2-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Attendees" "link" in the "#detailtask2" "css_element"
    Then I should see "No pending approvals"

    When I click on "Dashboard" in the totara menu
    And I click on "#detailtask2-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask2" "totaradialogue"
    And I click on "#detailtask4-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask4" "totaradialogue"
    And I click on "#detailtask6-dialog" "css_element" in the ".block_totara_tasks" "css_element"
    And I click on "Accept" "button" in the "detailtask6" "totaradialogue"
    And I log out
    And I log in as "admin"
    And I click on "Reports" in the totara menu
    And I click on "Global Session Status" "link" in the "#myreports_section" "css_element"
    Then I should see "Declined" in the "Jelly Jel" "table_row"
    And I should see "Declined" in the "Minny Min" "table_row"
    And I should see "Booked" in the "Sally Sal" "table_row"
