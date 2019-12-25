@mod @mod_facetoface @totara
Feature: Test notification templates according TL-10404
  In order to test notification templates for all activities
  I use Manager copy checkbox and Update all activities checkbox to activate or deactivate the changes

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
    And the following "courses" exist:
      | fullname     | shortname | category |
      | Course 10404 | C10404    | 0        |
    And the following "activities" exist:
      | activity   | name             | course | idnumber |
      | facetoface | Seminar TL-10404 | C10404 | seminar  |

  @javascript
  Scenario: Changing default Manager Copy value not using Update all activities will not affect all F2F activities notifications
    Given I log in as "admin"
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "1"
    And I set the following fields to these values:
      | ccmanager | 0 |
    And I click on "Save changes" "button"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "0"
    And I click on "Courses" in the totara menu
    And I follow "Course 10404"
    And I follow "Seminar TL-10404"
    And I navigate to "Notifications" node in "Seminar administration"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "1"

  @javascript
  Scenario: Changing default Manager Copy value using Update all activities will affect all F2F activities notifications
    Given I log in as "admin"
    And I click on "Courses" in the totara menu
    And I follow "Course 10404"
    And I follow "Seminar TL-10404"
    And I navigate to "Notifications" node in "Seminar administration"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "1"

    And I navigate to "Notification templates" node in "Site administration > Seminars"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "1"
    And I set the following fields to these values:
      | ccmanager             | 0 |
      | Update all activities | 1 |
    And I click on "Save changes" "button"
    And I click on "Courses" in the totara menu
    And I follow "Course 10404"
    And I follow "Seminar TL-10404"
    And I navigate to "Notifications" node in "Seminar administration"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "0"


  @javascript
  Scenario: Changing default Manager Copy value not using Update all activities will affect all new F2F activities notifications
    Given I log in as "admin"
    And I navigate to "Notification templates" node in "Site administration > Seminars"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "1"
    And I set the following fields to these values:
      | ccmanager | 0 |
    And I click on "Save changes" "button"

    And I am on "Course 10404" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar TL-10404A |
    And I follow "Seminar TL-10404A"
    When I navigate to "Notifications" node in "Seminar administration"
    When I click on "Edit" "link" in the "Seminar booking confirmation: [facetofacename], [starttime]-[finishtime], [sessiondate]" "table_row"
    Then the field "ccmanager" matches value "0"
