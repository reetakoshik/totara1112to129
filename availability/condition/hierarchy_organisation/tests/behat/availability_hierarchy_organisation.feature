@availability @availability_hierarchy_organisation @totara
Feature: Adding organisation assignment activity access restriction
  In order to control student access to activities
  As a teacher
  I need to set date conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
    And the following "users" exist:
      | username | email         |
      | teacher1 | t@example.com |
      | student1 | s@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname       | idnumber | org_framework |
      | Organisation 1 | org1     | orgfw         |
      | Organisation 2 | org2     | orgfw         |
    And the following job assignments exist:
      | user     | organisation |
      | student1 | org1         |


  @javascript
  Scenario: Test organisation assignment condition prevents student access
    # Basic setup.
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on

    # Add a page.
    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | Test Page 1      |
      | Description  | Some description |
      | Page content | page content     |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Assigned to Organisation" to "Organisation 1"
    And I press key "13" in the field "Assigned to Organisation"
    Then I should see "Organisation 1"
    And I press "Save and return to course"
    Then I should see "Not available unless: You are assigned to the Organisation: Organisation 1"

    # Add a Page with condition of assignment to 'Organisation 2'.
    And I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Test Page 2            |
      | Description  | Some other description |
      | Page content | more page content      |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Assigned to Organisation" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Assigned to Organisation" to "Organisation 2"
    And I press key "13" in the field "Assigned to Organisation"
    Then I should see "Organisation 2"
    And I press "Save and return to course"
    Then I should see "Not available unless: You are assigned to the Organisation: Organisation 2"

    # Log in as student
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage

    Then I should see "Test Page 1" in the "region-main" "region"
    And I should not see "Test Page 2" in the "region-main" "region"
