@core @core_tag @totara @totara_program
Feature: Site managers should be able to add tags to programs
  In order to add tags to a program
  As a user
  I need to be able to edit program details

  Background:
    Given I am on a totara site
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | man1     | Man       | 1        | man1@example.com |
      | usr1     | Usr       | 1        | usr1@example.com |
    And the following "system role assigns" exist:
      | user | course               | role    |
      | man1 | Acceptance test site | manager |
    And the following "tags" exist:
      | name      | isstandard |
      | Fun       | 0          |
      | Horrible  | 0          |
      | Science   | 1          |
      | Computing | 1          |
    And the following "programs" exist in "totara_program" plugin:
      | fullname           | shortname |
      | Program Tags Tests | ptagtst   |

  @javascript
  Scenario: Adding tags to programs
    When I log in as "man1"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Tags Tests" "link"
    # Yes this needs to happen twice, once to get to the overview page, once to edit the details.
    And I click on "Edit program details" "button"
    And I click on "Edit program details" "button"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Tags | Fun, Science, NewTag |
    And I press "Save changes"
    Then I should see "Tags:"
    And I should see "Fun"
    And I should see "Science"
    And I should see "NewTag"
    When I log out
    And I log in as "admin"
    And I navigate to "Manage tags" node in "Site administration > Appearance"
    Then I should see "Programs & Certifications" in the "Default collection" "table_row"
    When I click on "Default collection" "link"
    Then I should see "Fun" in the "tag-management-list" "table"
    And I should see "Horrible" in the "tag-management-list" "table"
    And I should see "Science" in the "tag-management-list" "table"
    And I should see "Computing" in the "tag-management-list" "table"
    And I should see "NewTag" in the "tag-management-list" "table"
    When I click on "Fun" "link" in the "tag-management-list" "table"
    Then I should see "Program Tags Tests"
    When I press "Manage tags"
    And I click on "Default collection" "link"
    And I click on "Horrible" "link" in the "tag-management-list" "table"
    Then I should not see "Program Tags Tests"
