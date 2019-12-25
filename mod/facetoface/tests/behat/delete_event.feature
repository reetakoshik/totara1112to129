@javascript @mod @mod_facetoface @totara
Feature: Test deletion of a Seminar event
  In order to test that non-admin user
  As a editing teacher
  I need to create and edit custom rooms

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  # Tests that it is possible to delete an event with a custom asset and that the asset is cleaned up.
  Scenario: Delete an event that is using a custom asset
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Select assets" "link"
    And I click on "Create new asset" "link"
    And I set the following fields to these values:
      | Asset name        | Projector       |
      | Asset description | A 3D projector  |
    When I click on "OK" "button" in the "Create new asset" "totaradialogue"
    Then I should see "Projector"

    When I press "Save changes"
    Then a seminar custom asset called "Projector" should exist

    When I click on "Delete event" "link"
    Then I should see "Deleting event in Test seminar name"
    And I should see "Are you completely sure you want to delete this event and all sign-ups and attendance for this event?"

    When I press "Continue"
    Then I should see "All events in Test seminar name"
    And I should see "No results"
    And a seminar custom asset called "Projector" should not exist

  # Tests that it is possible to delete a room with custom event and that the room is cleaned up.
  Scenario: Delete an event that is using a custom room
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Create new room" "link"
    And I set the following fields to these values:
      | Name         | Room 1          |
      | Building     | That house      |
      | Address      | 123 here street |
      | roomcapacity | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I should not see "Publish for reuse"
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should see "Room 1 (5)"

    When I press "Save changes"
    Then I should see "Room 1"
    And I should see "That house"
    And a seminar custom room called "Room 1" should exist

    When I click on "Delete event" "link"
    Then I should see "Deleting event in Test seminar name"
    And I should see "Room 1"
    And I should see "That house"
    And I should see "Are you completely sure you want to delete this event and all sign-ups and attendance for this event?"

    When I press "Continue"
    Then I should see "All events in Test seminar name"
    And I should see "No results"
    And a seminar custom room called "Room 1" should not exist
