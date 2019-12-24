@javascript @mod @mod_facetoface @totara @totara_customfield
Feature: Manage custom rooms by non-admin user
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

  Scenario: Add edit seminar custom room as editing teacher
    And I log in as "teacher1"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "Test seminar name"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Create new room" "link"
    And I should see "Create new room" in the "Create new room" "totaradialogue"
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

    # Edit
    And I click on "Edit room" "link"
    And I should see "Edit room" in the "Edit room" "totaradialogue"
    And I set the following fields to these values:
      | Name         | Room edited |
      | roomcapacity | 10          |
    And I should not see "Publish for reuse"
    And I click on "//div[@aria-describedby='editcustomroom0-dialog']//div[@class='ui-dialog-buttonset']/button[contains(.,'OK')]" "xpath_element"
    Then I should see "Room edited (10)"
