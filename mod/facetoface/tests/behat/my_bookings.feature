@mod @mod_facetoface @totara @javascript
Feature: Check My bookings displays the right information for future and past events
  In order to check my future and past bookings are displayed correctly
  As a user
  I need to sign-up for "over", "in progress" and "upcoming" events

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |

    # Create a Seminar.
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | seminar1                   |
      | Description                             | Test seminar description   |
      | How many times the user can sign-up?    | Unlimited                  |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link" in the "Select room" "table_row"

    # Future event.
    And I fill seminar session with relative date in form data:
      | timestart[day]       | +1 |
      | timestart[month]     | 0  |
      | timestart[year]      | 0  |
      | timestart[hour]      | 0  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | +3 |
      | timefinish[month]    | 0  |
      | timefinish[year]     | 0  |
      | timefinish[hour]     | 0  |
      | timefinish[minute]   | 0  |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I set the field "Maximum bookings" to "115"
    And I click on "Save changes" "button"

    # In progress event.
    And I follow "Add a new event"
    And I click on "Edit session" "link" in the "Select room" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | -1 |
      | timestart[month]     | 0  |
      | timestart[year]      | 0  |
      | timestart[hour]      | 0  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | +3 |
      | timefinish[month]    | 0  |
      | timefinish[year]     | 0  |
      | timefinish[hour]     | 0  |
      | timefinish[minute]   | 0  |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Attendees" "link" in the "Event in progress" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I set the following fields to these values:
      | searchtext | Sam2 Student2 |
    And I click on "Search" "button" in the "#region-main" "css_element"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    # Create another Seminar.
    And I follow "Course 1"
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                    | seminar2                   |
      | Description                             | Test seminar description2  |
      | How many times the user can sign-up?    | Unlimited                  |
    And I click on "seminar2" "link"

    # Past event.
    And I follow "Add a new event"
    And I click on "Edit session" "link" in the "Select room" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | -3 |
      | timestart[month]     | 0  |
      | timestart[year]      | 0  |
      | timestart[hour]      | 0  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | -2 |
      | timefinish[month]    | 0  |
      | timefinish[year]     | 0  |
      | timefinish[hour]     | 0  |
      | timefinish[minute]   | 0  |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Attendees" "link" in the "Event over" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

     # Future event.
    And I click on "seminar2" "link"
    And I follow "Add a new event"
    And I click on "Edit session" "link" in the "Select room" "table_row"
    And I fill seminar session with relative date in form data:
      | timestart[day]       | +4 |
      | timestart[month]     | 0  |
      | timestart[year]      | 0  |
      | timestart[hour]      | 0  |
      | timestart[minute]    | 0  |
      | timefinish[day]      | +5 |
      | timefinish[month]    | 0  |
      | timefinish[year]     | 0  |
      | timefinish[hour]     | 0  |
      | timefinish[minute]   | 0  |
    And I click on "OK" "button"
    And I wait "1" seconds
    And I set the field "Maximum bookings" to "125"
    And I click on "Save changes" "button"
    And I log out

  Scenario: Check my future bookings
    Given I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Bookings"

    # Checking there is no records in my future bookings.
    When I click on "Bookings" "link"
    Then I should see "My Future Bookings: 0 records shown"

    # Sign-up for Seminar 1 and Seminar 2.
    When I am on "Course 1" course homepage
    And I click on "Sign-up" "link" in the "115" "table_row"
    And I press "Sign-up"
    And I click on "Sign-up" "link" in the "125" "table_row"
    And I press "Sign-up"

    # Check my future bookings again.
    When I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    Then I should see "My Future Bookings: 2 records shown"
    And I should see "seminar1"
    And I should see "seminar2"

    # Check there is no past bookings.
    When I click on "Past Bookings" "link"
    Then I should see "My Past Bookings: 0 records shown"
    And I log out

    # Check future bookings for student2.
    When I log in as "student2"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    Then I should see "My Future Bookings: 1 record shown"
    And I should see "seminar1"
    And I log out

  Scenario: Check my past bookings
    Given I log in as "student2"
    When I click on "Dashboard" in the totara menu
    Then I should see "My Learning" in the ".breadcrumb-nav" "css_element"
    And I should see "Bookings"

    # Checking there is no records in my future bookings.
    When I click on "Bookings" "link"
    Then I should see "My Future Bookings: 1 record shown"
    And I should see "seminar1"

    # Check past bookings for student2.
    When I click on "Past Bookings" "link"
    Then I should see "My Past Bookings: 1 record shown"
    And I should see "seminar2"
    And I log out

    # Check student1 does not have bookings.
    When I log in as "student1"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    Then I should see "My Future Bookings: 0 records shown"
    And I click on "Past Bookings" "link"
    And I should see "My Past Bookings: 0 records shown"
    And I log out

    # Login as admin and add past booking for student2.
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I click on "seminar2" "link"
    And I click on "Attendees" "link" in the "Event over" "table_row"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press exact "add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I log out

    # Check again bookings for student1.
    And I log in as "student1"
    And I click on "Dashboard" in the totara menu
    And I click on "Bookings" "link"
    Then I should see "My Future Bookings: 0 records shown"
    And I click on "Past Bookings" "link"
    And I should see "My Past Bookings: 1 record shown"
    And I should see "seminar2"
    And I log out
