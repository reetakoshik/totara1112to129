@totara @totara_hierarchy @totara_hierarchy_competency @javascript
Feature: The competencies can be disabled
  In order to check the correct behaviour related to the visibility settings for the competency feature
  As a admin
  I need to choose among the three different settings (show/hide/disabled) and check the GUI

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname      | idnumber | description           |
      | Competency1   | CFW001   | Framework description |
    And the following "competency" hierarchy exists:
      | framework | fullname      | idnumber |
      | CFW001    | Competency 1  | COMP001  |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | completionstartonenrol |
      | Course 1 | C1        | topics | 1                | 1                      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname  |
      | Program 1 | program1   |
    And the following "plans" exist in "totara_plan" plugin:
      | user  | name            |
      | admin | Learning Plan 1 |
    # Add a competency and a course to the plan.
    And I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I click on "Learning Plan 1" "link"
    And I click on "Competencies" "link" in the "#dp-plan-content" "css_element"
    And I press "Add competencies"
    And I click on "Competency 1" "link"
    When I click on "Continue" "button" in the "Add competencies" "totaradialogue"
    Then I should see "Competency 1" in the ".dp-plan-component-items" "css_element"
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Add courses" "button"
    And I click on "Miscellaneous" "link"
    And I click on "Course 1" "link"
    When I click on "Save" "button" in the "Add courses" "totaradialogue"
    Then I should see "Course 1" in the "#dp-component-update-table" "css_element"
    And I click on "Course 1" "link"
    And I click on "Add linked competencies" "button" in the "#dp-plan-content" "css_element"
    And I click on "Competency 1" "link"
    And I click on "Save" "button" in the "Add linked competencies" "totaradialogue"

  Scenario: Show competency feature. All links and options related to the feature should be in place.
    Given I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Competencies" to "Show"
    And I press "Save changes"

    # Check competency component is displayed in learning plans.
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should see "Competencies"
    And I click on "Learning Plan 1" "link"
    Then I should see "Competencies"
    And I click on "Competencies" "link"
    Then I should see "Competency 1"
    And I should see "Add competencies"

    # Check competencies are displayed when going to a course in the learning plan.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Course 1" "link"
    Then I should see "Linked Competencies"
    And I should see "Competency 1"

    # Check competencies menu is visible.
    When I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    Then I should see "Competency frameworks"
    When I navigate to "Manage types" node in "Site administration > Hierarchies > Competencies"
    Then I should see "Types"

    # Check competencies could be add to the program's content.
    When I click on "Programs" in the totara menu
    And I click on "Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And the "contenttype_ce" select box should contain "Competency"

  Scenario: Disable competency feature. All links and options related to the feature should not be in available anywhere.
    Given I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable Competencies" to "Disable"
    And I press "Save changes"

    # Check competency component is not displayed in learning plans.
    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    Then I should not see "Competencies"
    And I click on "Learning Plan 1" "link"
    Then I should not see "Competencies"

    # Check competencies are not displayed when going to a course in the learning plan.
    When I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Course 1" "link"
    Then I should not see "Linked Competencies"
    And I should not see "Competency 1"

    # Check competencies menu is not visible because the feature is disabled.
    When I am on homepage
    And I expand "Site administration" node
    And I expand "Hierarchies" node
    Then I should not see "Competencies"

    # Check competencies are not an option to be added in the program's content.
    When I click on "Programs" in the totara menu
    And I click on "Program 1" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And the "contenttype_ce" select box should not contain "Competency"
