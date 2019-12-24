@totara @totara_hierarchy @totara_program
Feature: Adding competencies to program content
  In order to test adding competencies to programs
  As an admin
  I need to assign courses to a competency
  Then assign the competency to a program

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion | completionstartonenrol |
      | Course 1 | C1        | topics | 1                | 1                      |
      | Course 2 | C2        | topics | 1                | 1                      |
      | Course 3 | C3        | topics | 1                | 1                      |
    And the following "programs" exist in "totara_program" plugin:
      | fullname              | shortname  |
      | Program Content Tests | conttest   |
    And the following "competency" frameworks exist:
      | fullname             | idnumber | description                |
      | Competency Framework | CFrame   | Framework for Competencies |
    And the following "competency" hierarchy exists:
      | framework | fullname       | idnumber | description                       |
      | CFrame    | Competency101  | Comp101  | Competency with linked courses    |
      | CFrame    | Competency102  | Comp102  | Competency without linked courses |
    And I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I click on "Competency Framework" "link"
    And I click on "Competency101" "link"
    And I click on "Assign course completions" "button"
    And I click on "Miscellaneous" "link" in the "evidence" "totaradialogue"
    And I click on "Course 1" "link" in the "evidence" "totaradialogue"
    And I click on "Course 2" "link" in the "evidence" "totaradialogue"
    And I click on "Save" "button" in the "evidence" "totaradialogue"
    And I click on "Mandatory" "option" in the "Course 1" "table_row"

  @javascript
  Scenario: Test program completion with courseset "AND"
    Given I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Content Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    And I click on "Competency" "option" in the "#contenttype_ce" "css_element"
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    Then I should see "Competency101" in the "addcompetency" "totaradialogue"
    And I should not see "Competency102" in the "addcompetency" "totaradialogue"

    When I click on "Competency101" "link" in the "addcompetency" "totaradialogue"
    And I click on "Ok" "button" in the "addcompetency" "totaradialogue"
    Then I should see "Course 1"
    And I should see "Course 2"

    When I press "Save changes"
    And I click on "Save all changes" "button"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I click on "Competency Framework" "link"
    And I click on "Competency101" "link"
    And I click on "Remove" "link" in the "Course 2" "table_row"
    And I click on "Continue" "button"
    And I click on "Assign course completions" "button"
    And I click on "Miscellaneous" "link" in the "evidence" "totaradialogue"
    And I click on "Course 3" "link" in the "evidence" "totaradialogue"
    And I click on "Save" "button" in the "evidence" "totaradialogue"
    And I navigate to "Manage programs" node in "Site administration > Courses"
    And I click on "Miscellaneous" "link"
    And I click on "Program Content Tests" "link"
    And I click on "Edit program details" "button"
    And I click on "Content" "link"
    Then I should see "Course 1"
    And I should not see "Course 2"
    And I should see "Course 3"
