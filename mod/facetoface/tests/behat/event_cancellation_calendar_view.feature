@mod @mod_facetoface @totara @javascript
Feature: Seminar event cancellation calendar views
  After seminar events have been cancelled
  Calendars should also be updated

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | learner1 | Learner   | One      | learner1@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |

    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | learner1 | C1     | student        |

    Given I log in as "admin"
    And I navigate to "Global settings" node in "Site administration > Seminars"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_roles" "css_element"
    And I click on "Editing Trainer" "text" in the "#admin-facetoface_session_rolesnotify" "css_element"
    And I press "Save changes"
    And I log out

    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar |
      | Description | Test Seminar |
    And I follow "View all events"

    Given I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 29 |
    And I follow "show-selectdate0-dialog"
    And I fill seminar session with relative date in form data:
      | sessiontimezone     | Australia/Perth |
      | timestart[day]      | 1                |
      | timestart[month]    | 0                |
      | timestart[year]     | 0                |
      | timestart[hour]     | 0                |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Australia/Perth |
      | timefinish[day]     | 1                |
      | timefinish[month]   | 0                |
      | timefinish[year]    | 0                |
      | timefinish[hour]    | 0                |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Australia/Perth |
    And I press "OK"

    Given I follow "show-selectdate0-dialog"
    And I set the following fields to these values:
      | sessiontimezone     | Australia/Perth |
      | timestart[hour]     | 10               |
      | timestart[minute]   | 0                |
      | timestart[timezone] | Australia/Perth |
      | timefinish[hour]    | 16               |
      | timefinish[minute]  | 0                |
      | timefinish[timezone]| Australia/Perth |
    And I press "OK"
    And I click on "Teacher One" "checkbox"
    And I press "Save changes"

    Given I click on "Attendees" "link" in the "0 / 29" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I follow "Go back"


  Scenario: mod_facetoface_cancel_800: cancelled events removed from learner calendar.
    When I log out
    And I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    Then I should see "Course 1"
    And I should see "10:00 AM - 4:00 PM Australia/Perth"
    And I should see "Teacher One"

    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "1 / 29" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Event cancelled" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"
    And I should see "Sign-up unavailable" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"

    When I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM Australia/Perth"
    And I should not see "Editing Trainer Teacher One"
    And I should see "There are no upcoming events"


  Scenario: mod_facetoface_cancel_801: cancelled events removed from session role calendar.
    When I log out
    And I log in as "teacher1"
    And I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    Then I should see "Course 1"
    And I should see "10:00 AM - 4:00 PM Australia/Perth"
    And I should see "Teacher One"

    Given I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I click on "Cancel event" "link" in the "1 / 29" "table_row"
    And I press "Yes"

    When I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Event cancelled" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"
    And I should see "Sign-up unavailable" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"

    When I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM Australia/Perth"
    And I should not see "You are booked for this Seminar event"
    And I should not see "Editing Trainer Teacher One"

  Scenario: mod_facetoface_cancel_802: cancelled events do not re-create in calendar when seminar updated
    Given I am on "Course 1" course homepage
    And I follow "Test Seminar"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I set the following fields to these values:
      | Description | Test Seminar Lorem ipsum dolor sit amet |
    And I press "Save and display"
    And I click on "Cancel event" "link" in the "1 / 29" "table_row"
    And I press "Yes"
    And I log out

    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "View all events"
    Then I should see date "1 day Australia/Perth" formatted "%d %B %Y"
    And I should see "Event cancelled" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"
    And I should see "Sign-up unavailable" in the "10:00 AM - 4:00 PM Australia/Perth" "table_row"

    When I click on "Dashboard" in the totara menu
    And I click on "Go to calendar" "link"
    Then I should not see "Course 1"
    And I should not see "10:00 AM - 4:00 PM Australia/Perth"
    And I should not see "You are booked for this Seminar event"
    And I should not see "Editing Trainer Teacher One"