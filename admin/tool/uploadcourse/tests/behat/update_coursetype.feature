@tool @tool_uploadcourse @_file_upload
Feature: An admin can update only existing courses using a CSV file without resetting coursetype
  In order to update courses using a CSV file
  As an admin
  I need to be able to upload a CSV file and navigate through the import process

  Background:
    And the following "courses" exist:
      | fullname | shortname | category | coursetype | idnumber |
      | Course 1 | C1        | 0        | 1          | ID1      |
      | Course 2 | C2        | 0        | 2          | ID2      |
    And I log in as "admin"
    And I navigate to "Upload courses" node in "Site administration > Courses"

  @javascript
  Scenario: Updating a course fullname
    Given I upload "admin/tool/uploadcourse/tests/fixtures/courses.csv" file to "File" filemanager
    And I set the field "Upload mode" to "Only update existing courses"
    And I set the field "Update mode" to "Update with CSV data only"
    And I click on "Preview" "button"
    When I click on "Upload courses" "button"
    Then I should see "Course updated"
    And I should see "Courses total: 3"
    And I should see "Courses updated: 2"
    And I should see "Courses created: 0"
    When I am on "Course 1" course homepage
    And I navigate to "Edit settings" in current page administration
    Then the field "Course Type" matches value "Blended"
    When I am on "Course 2" course homepage
    And I navigate to "Edit settings" in current page administration
    Then the field "Course Type" matches value "Seminar"

