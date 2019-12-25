@totara @block_totara_program_completion @javascript
Feature: Configure the totara program completion block
  In order to use the totara program completion block
  As an admin
  I need to be able to configure the block

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname  | shortname |
      | Program 1 | program1  |
      | Program 2 | program2  |
      | Program 3 | program3  |
      | Program 4 | program4  |
      | Program 5 | program5  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user  | program  |
      | admin | program1 |
      | admin | program2 |
      | admin | program3 |
      | admin | program4 |
    And I log in as "admin"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Program completions" block
    And I configure the "Program completions" block
    And I expand all fieldsets
    And I press "Add programs"
    And I click on "Miscellaneous" "link" in the "Add programs" "totaradialogue"
    And I click on "Program 1" "link" in the "Add programs" "totaradialogue"
    And I click on "Program 2" "link" in the "Add programs" "totaradialogue"
    And I click on "Program 3" "link" in the "Add programs" "totaradialogue"
    And I click on "Program 4" "link" in the "Add programs" "totaradialogue"
    And I click on "Program 5" "link" in the "Add programs" "totaradialogue"
    And I click on "Save" "button" in the "Add programs" "totaradialogue"
    And I wait "1" seconds
    And I set the field "Maximum show" to "3"
    And I press "Save changes"
    And I press "Stop customising this page"

  Scenario: See that the program completions displays the correct number of programs
    And I click on "Dashboard" in the totara menu
    Then I should see "Program 1" in the "Program completions" "block"
    And I should see "Program 2" in the "Program completions" "block"
    And I should see "Program 3" in the "Program completions" "block"
    And I should not see "Program 4" in the "Program completions" "block"
    And I should not see "Program 5" in the "Program completions" "block"
    When I click on "Show more..." "link" in the "Program completions" "block"
    Then I should see "Program 4" in the "Program completions" "block"
    Then I should not see "Program 5" in the "Program completions" "block"
