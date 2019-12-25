@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Seminar event custom field validation works as expected
  After seminar events have been created
  As an admin
  I need to be able to test custom fields validation

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname     | shortname | category |
      | Course 13901 | C13901    | 0        |
    And the following "activities" exist:
      | activity   | name             | course | idnumber |
      | facetoface | Seminar TL-13901 | C13901 | S13901   |

  Scenario: Test unique validation on text custom fields for Seminar events
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I set the field "datatype" to "Text input"
    And I set the following fields to these values:
      | fullname           | Unique identifier |
      | shortname          | UID               |
      | forceunique        | 1                 |
    And I press "Save changes"

    And I am on "Course 13901" course homepage
    And I follow "View all events"

    And I follow "Add a new event"
    And I click on "#id_customfields" "css_element"
    And I set the field "customfield_UID" to "20172017"
    When I press "Save changes"
    Then I should see "Unique identifier"
    And I should see "20172017"

    And I follow "Add a new event"
    And I click on "#id_customfields" "css_element"
    And I set the field "customfield_UID" to "20172017"
    When I press "Save changes"
    And I click on "#id_customfields" "css_element"
    Then I should see "This value has already been used."

    When I set the field "customfield_UID" to "20172018"
    And I press "Save changes"
    Then I should see "20172018"

