@availability @availability_audience @totara
Feature: Ability to edit or delete the course's section audience availability after added
  Background:
    Given the following "courses" exist:
      | fullname  | shortname | format | enablecompletion |
      | Course101 | C101      | topics | 1                |
    And the following "cohorts" exist:
      | name   | idnumber | contextlevel | reference |
      | toyota | toy      | System       |           |
      | honda  | honda    | System       |           |

  @javascript
  Scenario: Admin is able to edit the audience availability within course section after added
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "toyota"
    And I press key "13" in the field "Member of Audience"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    And I should see "toyota"
    And I set the field "Member of Audience" to "honda"
    And I press key "13" in the field "Member of Audience"
    And I press "Save changes"
    When I edit the section "1"
    And I follow "Restrict access"
    Then I should see "honda"

  @javascript
  Scenario: Admin is able to delete the audience availability within course's section
    Given I am on a totara site
    And I log in as "admin"
    And I am on "Course101" course homepage with editing mode on
    And I edit the section "1"
    And I follow "Restrict access"
    And I click on "Add restriction..." "button"
    And I click on "Member of Audience" "button"
    And I set the field "Member of Audience" to "toyota"
    And I press key "13" in the field "Member of Audience"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    When I click on "Delete" "link"
    And I press "Save changes"
    And I edit the section "1"
    And I follow "Restrict access"
    Then I should not see "toyota"