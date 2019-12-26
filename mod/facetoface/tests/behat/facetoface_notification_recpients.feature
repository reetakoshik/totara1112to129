@totara @mod_facetoface @javascript
Feature: User is not able to create a notification with a booked recipients checked but not booked type set.
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And I am on a totara site
    And I log in as "admin"

  Scenario: User is not able to create a notification due to fail validation on booked type
    Given I am on "c101" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1 |
      | Description | seminar 1 |
    And I turn editing mode off
    And I follow "Seminar 1"
    And I click on "Notifications" "link" in the "Administration" "block"
    When I click on "Add" "button"
    Then "select[name='booked_type']" "css_element" should exist
    And I set the following fields to these values:
      | booked     | 1 |
      | templateid | 1 |

    When I click on "Save" "button"
    Then I should see "Required"
    And I set the field "booked_type" to "1"
    # Notification created at this point
    When I click on "Save" "button"
    Then I should not see "Required"
