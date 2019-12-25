@availability @availability_language @totara
Feature: Adding language activity access restriction
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

  @javascript
  Scenario: Test language condition allows student access
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
    And I click on "User language" "button" in the "Add restriction..." "dialogue"
    And I click on ".availability-item .availability-eye" "css_element"
    And I set the field "User language" to "en"
    And I press "Save and return to course"
    Then I should not see "Not available unless: Your language is English (en)"

    # Log in as student
    When I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "Test Page 1" in the "region-main" "region"
    And I follow "Test Page 1"
    Then I should see "Test Page 1"
    And I should see "Manually mark this activity when complete"

    # It would be nice to check access is not available but changing a lang pack in Behat can not easily be done.
