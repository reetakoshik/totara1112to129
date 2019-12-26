@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Check room details capability view for student and manager
  In order to test room details capability
  As a site manager
  I need to create an event and room, add attendees, login as student and check room details

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "activities" exist:
      | activity   | name            | course | idnumber |
      | facetoface | Seminar TL-9052 | C1     | seminar  |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | Building 123    |
      | Address           | 123 Tory street |
      | Room capacity     | 10              |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I am on "Course 1" course homepage
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Room 1, Building 123, 123 Tory street (Capacity: 10)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    Then I should see "Sam1 Student1"
    And I log out

  Scenario: Login as a student and check seminar room details
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "Room details" "link"
    When I switch to "popup" window
    Then I should see "View room"
    And I should see "Room 1"
    And I should see "Building 123"
    And I should not see "Upcoming sessions in this room"
    #NOTE: do not switch back to main window and instead rely on restart of browser after any switch.

  Scenario: Login as a manager and check seminar room details
    When I log in as "admin"
    And I am on "Course 1" course homepage
    When I click on "Room details" "link"
    When I switch to "popup" window
    Then I should see "View room"
    And I should see "Upcoming sessions in this room"
    And I should see "Seminar TL-9052"
    #NOTE: do not switch back to main window and instead rely on restart of browser after any switch.
