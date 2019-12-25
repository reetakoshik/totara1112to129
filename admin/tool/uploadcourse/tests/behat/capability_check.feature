@tool @tool_uploadcourse
Feature: Check the course upload interface is shown to people with the right capabilities
  In order to ensure the upload course UI is shown according to permissions
  As an admin
  I need to to check the right capabilities are applied

  Background:
    Given the following "users" exist:
      | username      | firstname         | lastname | email                      |
      | manager       | Manager           | One      | manager1@example.com       |
      | coursecreator | Coursecreator     | One      | coursecreator1@example.com |
      | staffmanager  | Staffmanager      | One      | staffmanager1@example.com  |
    And the following "system role assigns" exist:
      | user          | role          |
      | manager       | manager       |
      | staffmanager  | staffmanager  |
      | coursecreator | coursecreator |

  @javascript
  Scenario: Check course upload is shown to people with the right capabilities
    When I log in as "manager"
    And I navigate to "Upload courses" node in "Site administration > Courses"
    Then I should see "Upload courses"
    And I click on "Upload courses" "link"
    And I should see "Upload courses"
    And I log out

    When I log in as "coursecreator"
    And I navigate to "Upload courses" node in "Site administration > Courses"
    Then I should see "Upload courses"
    And I click on "Upload courses" "link"
    And I should see "Upload courses"
    And I log out

    When I log in as "staffmanager"
    And I expand "Site administration" node
    # Totara fix
    Then I should see "Audiences" in the "Administration" "block"
    And I should not see "Courses" in the "Administration" "block"
    And I log out

    # Remove capability for the coursecreator role and try again.
    When I log in as "admin"
    And I set the following system permissions of "Course creator" role:
      | capability                      | permission |
      | tool/uploadcourse:uploadcourses | Prevent    |
    When I follow "Edit Course creator role"
    Then "tool/uploadcourse:uploadcourses" capability has "Prevent" permission
    And I log out

    When I log in as "coursecreator"
    And I navigate to "Courses and categories" node in "Site administration > Courses"
    Then I should see "Courses and categories" in the "Administration" "block"
    And I should not see "Upload courses" in the "Administration" "block"
    And I log out
