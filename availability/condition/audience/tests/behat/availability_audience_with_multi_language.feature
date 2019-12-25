@totara @availability @availability_audience
Feature: Searching for an audience with the language that does not use alphabets

  Background:
    Given the following "courses" exist:
      | fullname  | shortname | format |
      | course101 | c101      | topics |
    And the following "cohorts" exist:
      | name      | idnumber | contextlevel | reference |
      | כל משתמש | hebrew   | System       |           |

  @javascript
  Scenario: Admin is able to search for the audience that is not using alphabets as a name
    Given I am on a totara site
    And I log in as "admin"
    And I am on "course101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    When I set the field "Member of Audience" to "כל "
    And I press key "13" in the field "Member of Audience"
    Then I should see "כל משתמש"
    And I log out