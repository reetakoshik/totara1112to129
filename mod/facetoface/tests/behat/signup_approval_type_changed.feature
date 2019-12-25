@mod @mod_facetoface @totara
Feature: Seminar Manager signup approval changes
  The system should react gracefully when seminar approval type changes occur

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | teacher     | Freddy    | Fred     | freddy@example.com |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
      | sammy       | Sammy     | Sam      | sammy@example.com  |
    And the following "courses" exist:
      | fullname                 | shortname | category |
      | Classroom Connect Course | CCC       | 0        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | CCC    | editingteacher |
      | jimmy   | CCC    | student        |
      | timmy   | CCC    | student        |
      | sammy   | CCC    | student        |
    And the following job assignments exist:
      | user  | manager |
      | jimmy | manager |
      | timmy | manager |
      | sammy | manager |
    And I log in as "teacher"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
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
    And I set the following fields to these values:
      | capacity                       | 1    |
      | Enable waitlist                | 1    |
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: The waitlisted report should be correct when the approval type changes
    When I log in as "jimmy"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "Sign-up"
    Then I should not see "Manager Approval"

    When I press "Sign-up"
    Then I should see "Your request was accepted"

    When I log out
    And I log in as "timmy"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "Join waitlist"
    Then I should not see "Manager Approval"
    And I should see "This event is currently full. By clicking the \"Join waitlist\" button, you will be placed on the event's waitlist."

    Given I press "Sign-up"
    And I log out
    And I log in as "teacher"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "Test seminar name"
    And I navigate to "Edit settings" in current page administration
    And I click on "Approval Options" "link"
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I log out

    When I log in as "sammy"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "Join waitlist"
    Then I should see "Manager Approval"
    And I should see "This event is currently full. By clicking the \"Join waitlist\" button, you will be placed on the event's waitlist."
    When I press "Request approval"
    Then I should see "Your request was sent to your manager for approval."

    Given I log out
    And I log in as "manager"
    And I click on "Dashboard" in the totara menu
    Then I should see "Seminar booking request"
    And I click on "View all tasks" "link"
    And I should see "Sammy Sam" in the "td.user_namelink" "css_element"
    And I click on "Attendees" "link" in the "Sammy Sam" "table_row"
    Then I should see "Sammy Sam" in the ".lastrow" "css_element"

    Given I click on "requests[7]" "radio" in the ".lastrow .lastcol" "css_element"
    And I click on "Update requests" "button"
    And I log out

    When I log in as "teacher"
    And I click on "Find Learning" in the totara menu
    And I follow "Classroom Connect Course"
    And I follow "Test seminar name"
    And I follow "Attendees"
    Then I should see "Booked" in the "Jimmy Jim" "table_row"

    When I follow "Wait-list"
    And I should see "Wait-listed" in the "Timmy Tim" "table_row"
    And I should not see "Thursday, 1 January 1970, 1:00 AM" in the "Timmy Tim" "table_row"
    And I should see "Wait-listed" in the "Sammy Sam" "table_row"
    And I should see "Cassy Cas" in the "Sammy Sam" "table_row"

