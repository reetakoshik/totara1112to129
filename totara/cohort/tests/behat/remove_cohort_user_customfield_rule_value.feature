@totara @totara_cohort @javascript
Feature: User remove the value of profile custom field that had already been used in an audience rule
  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | kianbomba | kian      | bomba    |
    And the following "cohorts" exist:
      | name     | idnumber | cohorttype |
      | cohort 1 | ch1      | 2          |
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname | name        |
      | text     | hw        | Hello World |
    And I am on a totara site
    And I log in as "admin"

    And I navigate to "Users > Browse list of users" in site administration
    And I follow "kian bomba"
    And I follow "Edit profile"
    And I follow "Other fields"
    And I set the field "profile_field_hw" to "abcde"
    And I click on "Update profile" "button"

    And I navigate to "Audiences > Audiences" in site administration
    And I follow "cohort 1"
    And I follow "Rule sets"

    And I set the field "Add rule" to "Hello World (Choose)"
    And I set the field "id_listofvalues" to "abcde"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I click on "Approve changes" "button"

  Scenario: User delete the value of custom field in a profile page
    then expecting the audience rules page to have a message about deleted value

    Given I navigate to "Users > Browse list of users" in site administration
    And I follow "kian bomba"
    And I follow "Edit profile"
    And I follow "Other fields"
    And I set the field "profile_field_hw" to ""
    And I click on "Update profile" "button"
    And I navigate to "Audiences > Audiences" in site administration
    When I follow "cohort 1"
    And I follow "Rule sets"
    Then I should see "Deleted (ID: abcde)"