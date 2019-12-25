@totara @block @block_current_learning @totara_program
Feature: Check to see that programs with only a single course are displayed as expected
  In order to ensure single course programs appear correctly in the current learning block
  As an admin
  I need to create a program with content

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course 1   | C1        | topics | 1                |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime | 0 |
    And I set self completion for "Course 1" in the "Miscellaneous" category
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I press "Add a new program"
    And I set the following fields to these values:
      | Full name  | Test Single Course Program |
      | Short name | testsinglecourseprog       |
    And I press "Save changes"

    # Add Courseset 1 with Course 1.
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"

    And I log out
    And the following "program assignments" exist in "totara_program" plugin:
      | program              | user    |
      | testsinglecourseprog | user001 |

  @javascript
  Scenario: A user can view their single course program in the current learning block
    Given I log in as "user001"
    When I click on "Dashboard" in the totara menu
    Then I should see "Test Single Course Program" in the "Current Learning" "block"
    And I should not be able to toggle "Test Single Course Program" row within the current learning block
    And I should not see "Course 1"
    And the current learning block learning type icon text is "This program contains a single course: Course 1" for the "Test Single Course Program"

