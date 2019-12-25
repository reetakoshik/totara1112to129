@javascript @mod @mod_facetoface @totara @totara_customfield @totara_reportbuilder
Feature: Manage custom rooms by admin and non-admin user
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
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Test seminar name |
    And I log out

  Scenario: Add edit seminar custom room as admin
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "//a[contains(.,'Add event') or contains(.,'Add a new event')]" "xpath_element"

    # Create a custom room
    When I click on "Select room" "link"
    And I click on "Create new room" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    And the field "Publish for reuse by other events" matches value "0"
    When I set the following fields to these values:
      | Name         | Room created    |
      | Building     | That house      |
      | Address      | 123 here street |
      | roomcapacity | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Room created (5)"
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I should see "There are no records in this report"
    And "#facetoface_rooms" "css_element" should not exist
    And I press the "back" button in the browser
    And I click on "Edit event" "link"

    # Edit
    When I click on "Edit room" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    And the field "Publish for reuse by other events" matches value "0"
    When I set the following fields to these values:
      | Name         | Room edited |
      | roomcapacity | 10          |
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room edited (10)"
    And I press "Save changes"

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I should see "There are no records in this report"
    And "#facetoface_rooms" "css_element" should not exist
    And I press the "back" button in the browser
    And I click on "Edit event" "link"

    # Publish a custom room i.e. make it a site-wide room
    When I click on "Edit room" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    And the field "Publish for reuse by other events" matches value "0"
    When I set the following fields to these values:
      | Name         | Room published |
      | roomcapacity | 15             |
    And I set the field "Publish for reuse by other events" to "1"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room published (15)"
    And I should not see "Edit room" in the "Room published (15)" "table_row"
    # No need to submit a form here; the room is published as soon as the totaradialogue is closed

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I should not see "There are no records in this report"
    And I should not see "Room created"
    And I should not see "Room edited"
    And the "facetoface_rooms" table should contain the following:
      | Room Name      | Building   | Location        | Room Capacity  | Room Visible |
      | Room published | That house | 123 here street | 15             | Yes          |
    And I press the "back" button in the browser

    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "//a[contains(.,'Add event') or contains(.,'Add a new event')]" "xpath_element"

    # Create a site-wide room
    When I click on "Select room" "link"
    And I click on "Create new room" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    And the field "Publish for reuse by other events" matches value "0"
    When I set the following fields to these values:
      | Name         | Site-wide room      |
      | Building     | This building       |
      | Address      | 456 there boulevard |
      | roomcapacity | 20                  |
    And I set the field "Publish for reuse by other events" to "1"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Site-wide room (20)"
    And I should not see "Edit room" in the "Site-wide room (20)" "table_row"
    # No need to submit a form here; the room is published as soon as the totaradialogue is closed

    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I should not see "There are no records in this report"
    And I should not see "Room created"
    And I should not see "Room edited"
    And the "facetoface_rooms" table should contain the following:
      | Room Name      | Building      | Location            | Room Capacity  | Room Visible |
      | Site-wide room | This building | 456 there boulevard | 20             | Yes          |

  Scenario: Add edit seminar custom room as editing teacher
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Test seminar name"
    And I click on "//a[contains(.,'Add event') or contains(.,'Add a new event')]" "xpath_element"
    When I click on "Select room" "link"
    And I click on "Create new room" "link"
    Then I should see "Create new room" in the "Create new room" "totaradialogue"
    And I should not see "Publish for reuse"
    When I set the following fields to these values:
      | Name         | Room 1          |
      | Building     | That house      |
      | Address      | 123 here street |
      | roomcapacity | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Create new room" "totaradialogue"
    Then I should see "Room 1 (5)"

    # Edit
    When I click on "Edit room" "link"
    Then I should see "Edit room" in the "Edit room" "totaradialogue"
    And I should not see "Publish for reuse"
    When I set the following fields to these values:
      | Name         | Room edited |
      | roomcapacity | 10          |
    And I click on "//*[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element" in the "Edit room" "totaradialogue"
    Then I should see "Room edited (10)"
