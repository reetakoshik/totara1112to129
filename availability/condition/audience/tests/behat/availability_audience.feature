@availability @availability_audience @totara
Feature: Adding audience membership activity access restriction
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
    And the following "cohorts" exist:
        | name      | idnumber | contextlevel | reference |
        | Audience1 | aud1     | System       |           |
        | Audience2 | aud2     | System       |           |
    And the following "cohort members" exist:
        | user     | cohort |
        | student1 | aud1   |


  @javascript
  Scenario: Test audience condition prevents student access
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
    And I click on "Audience" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Member of Audience" to "Audience1"
    And I press key "13" in the field "Member of Audience"
    And I press "Save and return to course"

    # Add a Page with a grade condition for 'any grade'.
    And I add a "Page" to section "2"
    And I set the following fields to these values:
      | Name         | Test Page 2            |
      | Description  | Some other description |
      | Page content | more page content      |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Audience" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "Member of Audience" to "Audience2"
    And I press key "13" in the field "Member of Audience"
    And I press "Save and return to course"

    # Log in as student
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage

    Then I should see "Test Page 1" in the "region-main" "region"
    And I should not see "Test Page 2" in the "region-main" "region"
