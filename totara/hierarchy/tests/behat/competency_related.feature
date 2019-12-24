@totara @totara_hierarchy @totara_hierarchy_competency @javascript
Feature: Test competencies can be related
  In order to test that competencies can be related to each other
  As an admin
  I need to be able to relate competencies together

  Background:
    Given I am on a totara site
    And the following "competency" frameworks exist:
      | fullname    | idnumber |
      | framework 1 | CFW001   |
    And the following "competency" hierarchy exists:
      | framework | fullname | idnumber |
      | CFW001    | comp 1   | COMP001  |
      | CFW001    | comp 2   | COMP002  |
      | CFW001    | comp 3   | COMP003  |

  Scenario: Relate competencies to each other
    Given I log in as "admin"
    And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "framework 1"
    And I follow "comp 1"
    And I click on "Assign related competencies" "button"
    And I click on "comp 2" "link" in the "Assign related competencies" "totaradialogue"
    And I click on "Save" "button" in the "Assign related competencies" "totaradialogue"
    And I wait "1" seconds
    Then I should see "comp 2"
    And I should not see "comp 3"

    When I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
    And I follow "framework 1"
    And I follow "comp 2"
    Then I should see "comp 1"
    And I should not see "comp 3"