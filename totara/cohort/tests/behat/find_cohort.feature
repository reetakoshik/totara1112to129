@totara @totara_cohort @javascript
Feature: Find cohort through totara dialog window
  In order to find cohort
  As an admin
  I need to create cohorts, add users, add program and need to be able to find and assign cohort for program

  Background:
    Given the following "programs" exist in "totara_program" plugin:
      | fullname     | shortname    |
      | Program 9074 | Program 9074 |
    And the following "users" exist:
      | username | firstname | lastname | email              |
      | user1    | First     | User     | first@example.com  |
      | user2    | Second    | User     | second@example.com |
      | user3    | Third     | User     | third@example.com  |
      | user4    | Forth     | User     | forth@example.com  |
    And the following "cohorts" exist:
      | name              | idnumber | contextlevel | reference |
      | Audience TL-9074A | AUD9074A | System       |           |
      | Audience TL-9074B | AUD9074B | System       |           |
      | Audience TL-9074C | AUD9074C | System       |           |
    And the following "cohort members" exist:
      | user  | cohort   |
      | user1 | AUD9074A |
      | user2 | AUD9074A |
      | user3 | AUD9074A |
      | user4 | AUD9074A |
      | user1 | AUD9074B |
      | user3 | AUD9074B |
      | user2 | AUD9074C |
      | user4 | AUD9074C |

  Scenario: Search audiences for program
    Given I log in as "admin"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I click on "Edit" "link" in the "Audience TL-9074B" "table_row"
    And I set the field "id_contextid" to "Miscellaneous"
    And I click on "Save changes" "button"

    And I click on "Programs" in the totara menu
    And I follow "Program 9074"
    And I click on "Edit program details" "button"
    And I follow "Assignments"
    And I set the field "Add a new" to "Audiences"
    And I click on "Search" "link" in the "add-assignment-dialog-3" "totaradialogue"

    When I search for "9074A" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience TL-9074A (AUD9074A)"
    And I should not see "Audience TL-9074B (AUD9074B)"
    And I should not see "Audience TL-9074C (AUD9074C)"

    When I search for "9074B" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience TL-9074B (AUD9074B)"
    And I should not see "Audience TL-9074A (AUD9074A)"
    And I should not see "Audience TL-9074C (AUD9074C)"

    When I search for "9074C" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience TL-9074C (AUD9074C)"
    And I should not see "Audience TL-9074A (AUD9074A)"
    And I should not see "Audience TL-9074B (AUD9074B)"

    When I search for "9074" in the "add-assignment-dialog-3" totara dialogue
    Then I should see "Audience TL-9074C (AUD9074C)"
    And I should see "Audience TL-9074A (AUD9074A)"
    And I should see "Audience TL-9074B (AUD9074B)"

    And I click on "Cancel" "button" in the "add-assignment-dialog-3" "totaradialogue"
