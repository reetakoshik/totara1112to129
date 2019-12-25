@totara @totara_program @totara_courseprogressbar @javascript
Feature: Mark course in a program complete via required learning
  Mark a course within a program complete
  As admin or manager
  Via required learning

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username       | firstname | lastname | email                      |
      | learner1       | Learner   | One      | learner1@example.com       |
      | learner2       | Learner   | Two      | learner2@example.com       |
      | manager1       | Manager   | One      | manager1@example.com       |
      | coursecomplete | Mark      | Complete | coursecomplete@example.com |
    And the following job assignments exist:
      | user     | manager  |
      | learner1 | manager1 |
      | learner2 | manager1 |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
      | Course Two | course2   | topics | 1                |
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |
    And I add a courseset with courses "course1" to "prog1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I add a courseset with courses "course2" to "prog1":
      | Set name              | set2        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user     |
      | prog1   | learner1 |
      | prog1   | learner2 |

  Scenario: Managers can not mark a course complete if they have no mark complete permissions
    # Staff managers will have the markstaffcomplete permission by default.
    Given the following "permission overrides" exist:
      | capability                              | permission | role         | contextlevel | reference |
      | totara/program:markstaffcoursecomplete  | Prohibit   | staffmanager | User         | learner1  |
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Learner One" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should not see "Mark complete"
    # Below is the screen reader text for the mark complete checkbox (actually image of a checkbox with a link)
    And "Not completed: Course One. Select to mark as complete." "link" should not exist

  Scenario: By default, Managers can mark a course complete in program page for required learning
    # Staff managers will have the markstaffcomplete permission by default.
    Given I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Learner One" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should see "Mark complete"
    When I click on "Not completed: Course One. Select to mark as complete." "link"
    And I press "Save changes"
    Then I should see "Course marked as manually completed"
    When I switch to "Courses" tab
    Then I should see "100%" in the "Course One" "table_row"

  Scenario: totara/core:markcoursecomplete allows marking complete of a given course in program page for required learning
    # Remove these so we test only what we intend to.
    Given the following "permission overrides" exist:
      | capability                              | permission | role         | contextlevel | reference |
      | totara/program:markstaffcoursecomplete  | Prohibit   | staffmanager | User         | learner1  |
    And the following "roles" exist:
      | shortname          |
      | coursemarkcomplete |
    And the following "role assigns" exist:
      | user     | role               | contextlevel  | reference |
      | manager1 | coursemarkcomplete | Course        | course1   |
    And the following "permission overrides" exist:
      | capability                         | permission | role               | contextlevel | reference |
      | totara/program:markcoursecomplete  | Allow      | coursemarkcomplete | Course       | course1   |
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Learner One" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should see "Mark complete"

    # The capability was not allowed for Course Two.
    And "Not completed: Course Two. Select to mark as complete." "link" should not exist

    # The capability was allowed for Course One.
    When I click on "Not completed: Course One. Select to mark as complete." "link"
    And I press "Save changes"
    Then I should see "Course marked as manually completed"

  Scenario: totara/core:markusercoursecomplete allows marking complete of a given users courses in program page for required learning
    # Remove these so we test only what we intend to.
    Given the following "permission overrides" exist:
      | capability                              | permission | role         | contextlevel | reference |
      | totara/program:markstaffcoursecomplete  | Prohibit   | staffmanager | User         | learner1  |
      | totara/program:markstaffcoursecomplete  | Prohibit   | staffmanager | User         | learner2  |
    And the following "roles" exist:
      | shortname        |
      | usermarkcomplete |
    And the following "role assigns" exist:
      | user     | role             | contextlevel | reference |
      | manager1 | usermarkcomplete | User         | learner1  |
    And the following "permission overrides" exist:
      | capability                          | permission | role             | contextlevel | reference |
      | totara/core:markusercoursecomplete  | Allow      | usermarkcomplete | User         | learner1  |
    And I log in as "manager1"
    And I click on "Team" in the totara menu
    And I click on "Learner One" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should see "Mark complete"
    When I click on "Not completed: Course One. Select to mark as complete." "link"
    And I press "Save changes"
    Then I should see "Course marked as manually completed"
    When I click on "Program One" "link"
    And I click on "Not completed: Course Two. Select to mark as complete." "link"
    And I press "Save changes"
    Then I should see "Course marked as manually completed"
    And I click on "Team" in the totara menu

    # The capability was only allowed for Learner One. Let's check Learner Two.
    And I click on "Learner Two" "link"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Programs" tab
    And I click on "Program One" "link"
    Then I should not see "Mark complete"
    And "Not completed: Course One. Select to mark as complete." "link" should not exist
    And "Not completed: Course Two. Select to mark as complete." "link" should not exist
