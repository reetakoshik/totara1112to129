@tool @tool_uploadcourse @_file_upload
Feature: An admin can create or update courses with visible fields using a CSV file
  In order to create or update courses with visible fields using a CSV file
  As an admin
  I need to be able to upload a CSV file with visible fields and navigate through the import process

  Background:
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 0 |

  @javascript
  Scenario: Creating a course with visible and audiencevisible fields
    Given I navigate to "Upload courses" node in "Site administration > Courses"
    And I upload "admin/tool/uploadcourse/tests/fixtures/courses_visible_fields.csv" file to "File" filemanager
    And I click on "Preview" "button"
    When I click on "Upload courses" "button"
    Then I should see "Course created"
    And I should see "Courses total: 3"
    And I should see "Courses created: 3"
    And I should see "Courses errors: 0"
    And I click on "Courses" in the totara menu
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    When I follow "Course 1"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "visible" matches value "Hide"
    And I click on "Courses" in the totara menu
    When I follow "Course 2"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "visible" matches value "Show"
    And I click on "Courses" in the totara menu
    When I follow "Course 3"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "visible" matches value "Hide"
    When I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I click on "Courses" in the totara menu
    And I follow "Course 1"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "All users"
    And I click on "Courses" in the totara menu
    And I follow "Course 2"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "No users"
    And I click on "Courses" in the totara menu
    And I follow "Course 3"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "Enrolled users and members of the selected audiences"

  @javascript
  Scenario: Updating a course with audiencevisible field from the course default value
    Given I set the following administration settings values:
      | Enable audience-based visibility | 1 |
    And I navigate to "Upload courses" node in "Site administration > Courses"
    And I upload "admin/tool/uploadcourse/tests/fixtures/courses.csv" file to "File" filemanager
    And I set the field "Upload mode" to "Create new courses, or update existing ones"
    And I set the field "Update mode" to "Update with CSV data and defaults"
    And I click on "Preview" "button"
    And I set the field "Audience-based visibility" to "No users"
    When I click on "Upload courses" "button"
    Then I should see "Course created"
    And I should see "Courses total: 3"
    And I should see "Courses created: 3"
    And I should see "Courses errors: 0"
    And I click on "Courses" in the totara menu
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"
    When I follow "Course 1"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "No users"
    And I click on "Courses" in the totara menu
    And I follow "Course 2"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "No users"
    And I click on "Courses" in the totara menu
    And I follow "Course 3"
    And I navigate to "Edit settings" node in "Course administration"
    Then the field "Visibility" matches value "No users"
