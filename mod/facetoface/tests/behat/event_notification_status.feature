@mod @mod_facetoface @totara @javascript
Feature: Seminar event notification must not be available for user after it has been disabled locally or globally
  After seminar events have been created
  As a user I should not be prompted to receive notifications if notifications have been disabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname     | email               |
      | student1 | Boris     | Nikolaevich  | boris@example.com    |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    When I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
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
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I press "OK"
    And I press "Save changes"

  # Booking confirmation notifications.
  @javascript
  Scenario Outline: Seminar booking confirmation notifications are not available when disabled
    Given I am on a totara site
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    And I set the field "Status" to "<signup_enabled>"
    And I press "Save"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Boris Nikolaevich, boris@example.com" "option"
    And I press exact "add"
    When I press "Continue"
    Then I <visibility> "Send booking confirmation to new attendees"
    And I <visibility> "Send booking confirmation to new attendees' managers"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    When I follow "Sign-up"
    Then I <visibility> "Receive confirmation by"
    When I press "Sign-up"
    Then I <visibility> "You will receive a booking confirmation email shortly."
    And I log out
    Examples:
      | signup_enabled | visibility     |
      | 1              | should see     |
      | 0              | should not see |

  # Booking cancellation notifications.
  @javascript
  Scenario Outline: Seminar booking cancellation notifications are not available when disabled
    Given I am on a totara site
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I navigate to "Notifications" node in "Seminar administration"
    And I click on "Edit" "link" in the "Seminar booking cancellation" "table_row"
    And I set the field "Status" to "<cancellation_enabled>"
    And I press "Save"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I follow "Attendees"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Boris Nikolaevich, boris@example.com" "option"
    And I press exact "add"
    And I press "Continue"
    And I press "Confirm"
    And I click on "Remove users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Boris Nikolaevich, boris@example.com" "option"
    And I press "Remove"
    When I press "Continue"
    Then I <visibility> "Notify cancelled attendees"
    Then I <visibility> "Notify cancelled attendees' managers"
    And I log out

    Examples:
      | cancellation_enabled | visibility     |
      | 1                    | should see     |
      | 0                    | should not see |