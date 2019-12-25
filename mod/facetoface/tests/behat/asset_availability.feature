@mod @mod_facetoface @totara @javascript
Feature: Seminar asset availability
  In order to prevent asset conflicts
  As an editing trainer
  I need to see only available assets

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | teacher2 | Teacher   | Two      | teacher2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | teacher2 | C1     | editingteacher |
    And I log in as "admin"
    And I navigate to "Assets" node in "Site administration > Seminars"
    And I press "Add a new asset"
    And I set the following fields to these values:
      | Asset name                    | Asset 1         |
      | Allow asset booking conflicts | 0               |
    And I press "Add an asset"
    And I press "Add a new asset"
    And I set the following fields to these values:
      | Asset name                    | Asset 2         |
      | Allow asset booking conflicts | 1               |
    And I press "Add an asset"
    And I press "Add a new asset"
    And I set the following fields to these values:
      | Asset name                    | Asset 3         |
      | Allow asset booking conflicts | 0               |
    And I press "Add an asset"
    And I click on "Hide from users when choosing an asset on the Add/Edit event page" "link" in the "Asset 3" "table_row"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar 1 |
      | Description | test           |
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test Seminar 2 |
      | Description | test           |
    And I log out

  Scenario: Time based seminar asset conflicts
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 10" "table_row"
    And I should see "Asset 1" in the "1 January 2025 1:00 PM" "table_row"
    And I should not see "Asset 2" in the "1 January 2025 1:00 PM" "table_row"
    And I should see "Asset 1" in the "1 January 2025 11:00 AM" "table_row"
    And I should see "Asset 2" in the "1 January 2025 11:00 AM" "table_row"
    And I should not see "Asset 1" in the "January 2026" "table_row"
    And I should see "Asset 2" in the "January 2026" "table_row"
    And I press "Cancel"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 10   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 11   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I should see "Asset 1" in the "1 January 2025" "table_row"
    And I should not see "Asset 2" in the "1 January 2025" "table_row"
    And I press "Cancel"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 14   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 30" "table_row"
    And I should see "Asset 1" in the "1 January 2025" "table_row"
    And I should see "Asset 2" in the "1 January 2025" "table_row"
    And I press "Cancel"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 40" "table_row"
    And I should not see "Asset 1" in the "1 January 2026" "table_row"
    And I should see "Asset 2" in the "1 January 2026" "table_row"
    And I press "Cancel"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 50 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    Then I should see "Asset 1 (asset unavailable on selected dates)"
    Then I should not see "Asset 2 (asset unavailable on selected dates)"
    When I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"

    And I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I should see "Asset 1 is already booked"
    When I click on "Cancel" "button" in the "Select date" "totaradialogue"
    And I press "Cancel"

  Scenario: Hiding related seminar asset availability
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I log out
    And I log in as "admin"
    And I navigate to "Assets" node in "Site administration > Seminars"
    And I click on "Hide from users when choosing an asset on the Add/Edit event page" "link" in the "Asset 1" "table_row"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"

    When I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"

    When I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Select asset" "link"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 20" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2026 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2026 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    Then I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

  Scenario: Custom seminar asset availability
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Create new asset" "link" in the "Choose assets" "totaradialogue"
    And I set the following fields to these values:
      | Asset name                    | Etwas 1 |
      | Allow asset booking conflicts | 0       |
    And I click on "OK" "button" in the "Create new asset" "totaradialogue"

    When  I press "Add a new session"
    # The UI is not usable much here, we just save this and go back and the last added session will be listed first.
    And I press "Save changes"
    And I click on "Edit event" "link" in the "0 / 30" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    Then I should see "Etwas 1 (Seminar: Test Seminar 1)"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Etwas 1 (Seminar: Test Seminar 1)" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I should see "Upcoming events"

    When I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    Then I should see "Etwas 1 (asset unavailable on selected dates) (Seminar: Test Seminar 1)"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Create new asset" "link" in the "Choose assets" "totaradialogue"
    And I set the following fields to these values:
      | Asset name                    | Etwas 2 |
      | Allow asset booking conflicts | 0       |
    And I click on "OK" "button" in the "Create new asset" "totaradialogue"
    And I click on "Delete" "link" in the "Etwas 2" "table_row"
    And I press "Save changes"

    When I click on "Edit event" "link" in the "0 / 40" "table_row"
    And I should not see "Etwas 2" in the "1 January 2025" "table_row"
    And I click on "Select asset" "link"
    Then I should see "Etwas 1 (asset unavailable on selected dates) (Seminar: Test Seminar 1)"
    And I should see "Etwas 2 (Seminar: Test Seminar 1)"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 2"
    And I follow "Add a new event"
    And I click on "Select asset" "link"
    Then I should not see "Etwas 1"
    And I should see "Etwas 2 (Seminar: Test Seminar 2)"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"
    And I log out

    When I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 2"
    And I follow "Add a new event"
    And I click on "Select asset" "link"
    Then I should not see "Etwas 1"
    And I should not see "Etwas 2"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I click on "Select asset" "link"
    Then I should see "Etwas 1 (Seminar: Test Seminar 1)"
    And I should not see "Etwas 2"
    And I should see "Asset 1"
    And I should see "Asset 2"
    And I should not see "Asset 3"
    And I click on "Cancel" "button" in the "Choose assets" "totaradialogue"
    And I press "Cancel"

  Scenario: Seminar switch site asset to not allow conflicts
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    When I press "Save changes"

    When I navigate to "Assets" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Asset 2" "table_row"
    And I set the following fields to these values:
      | Allow asset booking conflicts | 0               |
    And I press "Save changes"
    Then I should see "Asset has conflicting usage"
    And I press "Cancel"

    When I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I click on "Edit event" "link" in the "0 / 30" "table_row"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Assets" node in "Site administration > Seminars"
    And I click on "Edit" "link" in the "Asset 2" "table_row"
    And I set the following fields to these values:
      | Allow asset booking conflicts | 0               |
    And I press "Save changes"
    Then I should not see "Asset has conflicting usage"

  Scenario: Seminar switch custom asset to not allow conflicts
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 40 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Create new asset" "link" in the "Choose assets" "totaradialogue"
    And I set the following fields to these values:
      | Asset name                    | Etwas 1 |
      | Allow asset booking conflicts | 1       |
    And I click on "OK" "button" in the "Create new asset" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 50 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Etwas 1 (Seminar: Test Seminar 1)" "link"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"

    When I click on "Edit event" "link" in the "0 / 50" "table_row"
    And I click on "Edit asset" "link" in the "Etwas 1" "table_row"
    And I set the following fields to these values:
      | Allow asset booking conflicts | 0 |
    And I click on "OK" "button" in the "Edit asset" "totaradialogue"
    Then I should see "Asset has conflicting usage" in the "Edit asset" "totaradialogue"
    And I click on "Cancel" "button" in the "Edit asset" "totaradialogue"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 12   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 13   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I press "Save changes"
    When I click on "Edit event" "link" in the "0 / 50" "table_row"
    And I click on "Edit asset" "link" in the "Etwas 1" "table_row"
    And I set the following fields to these values:
      | Allow asset booking conflicts | 0 |
    And I click on "OK" "button" in the "Edit asset" "totaradialogue"
    Then I should not see "Asset has conflicting usage"
    And I press "Save changes"

  Scenario: Reportbuilder seminar asset availability filter
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Seminar 1"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 20 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 13   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 14   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Asset 1" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I follow "Add a new event"
    And I set the following fields to these values:
      | Maximum bookings | 30 |
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2025 |
      | timestart[hour]    | 15   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2025 |
      | timefinish[hour]   | 16   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Select asset" "link"
    And I click on "Asset 2" "text" in the "Choose assets" "totaradialogue"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I press "Save changes"
    And I navigate to "Assets" node in "Site administration > Seminars"

    # NOTE: We cannot use "Search" button because there is already "Search by" aria button above.

    When I set the following fields to these values:
      | asset-assetavailable_enable        | Free between the following times |
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 10                               |
      | asset-assetavailable_start[minute] | 00                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 11                               |
      | asset-assetavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 10                               |
      | asset-assetavailable_start[minute] | 00                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 11                               |
      | asset-assetavailable_end[minute]   | 01                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 11                               |
      | asset-assetavailable_start[minute] | 30                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 12                               |
      | asset-assetavailable_end[minute]   | 30                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 12                               |
      | asset-assetavailable_start[minute] | 59                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 14                               |
      | asset-assetavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 10                               |
      | asset-assetavailable_start[minute] | 00                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 14                               |
      | asset-assetavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2025                             |
      | asset-assetavailable_start[hour]   | 14                               |
      | asset-assetavailable_start[minute] | 00                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2025                             |
      | asset-assetavailable_end[hour]     | 15                               |
      | asset-assetavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"

    When I set the following fields to these values:
      | asset-assetavailable_start[day]    | 1                                |
      | asset-assetavailable_start[month]  | January                          |
      | asset-assetavailable_start[year]   | 2001                             |
      | asset-assetavailable_start[hour]   | 10                               |
      | asset-assetavailable_start[minute] | 00                               |
      | asset-assetavailable_end[day]      | 1                                |
      | asset-assetavailable_end[month]    | January                          |
      | asset-assetavailable_end[year]     | 2030                             |
      | asset-assetavailable_end[hour]     | 14                               |
      | asset-assetavailable_end[minute]   | 00                               |
    And I press "submitgroupstandard[addfilter]"
    Then I should not see "Asset 1"
    And I should see "Asset 2"
    And I should see "Asset 3"
