@totara @totara_cohort @javascript
Feature: Cohort with members that match with the authentication type rule
  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email             | auth  |
      | user1    | Sa Rang   | Kim      | bolo1@example.com | email |
      | user2    | Shyn Hye  | Park     | bolo2@example.com | lti   |
      | user3    | Min Young | Park     | bolo3@example.com | lti   |
      | user4    | Ji Won    | Kim      | bolo4@example.com | ldap  |
      | user5    | Yoon ah   | Im       | bolo5@example.com | ldap  |
      | user6    | Sooyoun   | Jung     | bolo6@example.com | ldap  |
      | user7    | Sandara   | Park     | bolo7@example.com | email |
      | user8    | Jisoo     | Kim      | bolo8@example.com | email |
    And the following "cohorts" exist:
      | name | idnumber | contextlevel | reference | cohorttype |
      | ch1  | ch1      | System       | 0         | 2          |
    And I am on a totara site
    And I log in as "admin"
    And I navigate to "Audiences > Audiences" in site administration

  Scenario: User creates a rule with an authentication type that user is equal to
    Given I follow "ch1"
    And I follow "Rule sets"
    And I set the field "Add rule" to "user-authenticationtype"
    And I set the following fields to these values:
      | listofvalues[] | lti |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I click on "Approve changes" "button"
    When I follow "Members"
    Then I should see "Shyn Hye Park"
    And I should see "Min Young Park"

  Scenario: User create a rule with an authentication type that user is not equal to
    Given I follow "ch1"
    And I follow "Rule sets"
    And I set the field "Add rule" to "user-authenticationtype"
    And I set the following fields to these values:
      | listofvalues[] | lti |
      | equal          | 0   |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I set the field "Add rule" to "user-authenticationtype"
    And I set the following fields to these values:
      | listofvalues[] | ldap |
      | equal          | 0    |
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    And I click on "Approve changes" "button"
    When I follow "Members"
    Then I should see "Sandara Park"
    And I should see "Jisoo Kim"
    And I should see "Sa Rang Kim"
