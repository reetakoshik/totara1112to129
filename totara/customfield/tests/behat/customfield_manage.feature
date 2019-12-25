@totara @totara_customfield @javascript
Feature: Administrators can manage customs field from the index page
  In order speed up course/program/certification custom fields management
  As admin
  I need to be able to perform actions from the index page

  Background:
    Given I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Courses"
    # Course custom fields
    # Checkbox.
    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Course checkbox |
      | Short name (must be unique) | coursecheckbox  |
    And I press "Save changes"
    # File
    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Course file |
      | Short name (must be unique) | coursefile  |
    And I press "Save changes"
    # Text area
    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Course text area |
      | Short name (must be unique) | coursetextarea   |
    And I press "Save changes"
    # Program custom fields
    And I switch to "Programs / Certifications" tab
    # Checkbox.
    And I click on "Checkbox" "option"
    And I set the following fields to these values:
      | Full name                   | Program checkbox |
      | Short name (must be unique) | programcheckbox |
    And I press "Save changes"
    # File
    And I click on "File" "option"
    And I set the following fields to these values:
      | Full name                   | Program file |
      | Short name (must be unique) | programfile |
    And I press "Save changes"
    # Text area
    And I click on "Text area" "option"
    And I set the following fields to these values:
      | Full name                   | Program text area |
      | Short name (must be unique) | programtextarea  |
    And I press "Save changes"

  Scenario: I can hide custom fields
    When I click on "Hide" "link" in the "Program file" "table_row"
    Then I should see "Show" in the "Program file" "table_row"
    And I should see "Hide" in the "Program text area" "table_row"
    And I should see "Hide" in the "Program checkbox" "table_row"

  Scenario: I can delete custom fields
    When I switch to "Courses" tab
    And I click on "Delete" "link" in the "Course text area" "table_row"
    And I click on "Yes" "button"
    Then I should not see "Course text area" in the "customfields_program" "table"
    And I should see "Course checkbox" in the "customfields_program" "table"
    When I switch to "Programs / Certifications" tab
    Then I should see "Program text area" in the "customfields_program" "table"
