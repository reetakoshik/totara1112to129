@totara @totara_userdata @javascript
Feature: Deleted user accounts feature
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | idnumber  |email                    | maildisplay |
      | username1 | Bob1      | Learner  | idnumber1 |bob1.learner@example.com | 1           |
      | username2 | Bob2      | Learner  | idnumber2 |bob2.learner@example.com | 1           |
      | username3 | Bob3      | Learner  | idnumber3 |bob3.learner@example.com | 0           |
      | username4 | Bob4      | Learner  | idnumber4 |bob4.learner@example.com | 2           |

  Scenario: Verify Deleted user accounts report is empty initially
    When I log in as "admin"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    Then I should see "Deleted user accounts: 0 records shown"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I should see "Browse list of users: 6 records shown"

  Scenario: Verify proper full user delete works
    Given I log in as "admin"
    And I set the following administration settings values:
      | authdeleteusers | fullproper |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete Bob1 Learner" "link"
    When I press "Delete"
    And I should see "Browse list of users: 5 records shown"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    Then I should see "Bob1 Learner"
    And I should not see "username1"
    And I should not see "idnumber1"
    And I should not see "example.com"
    And I should not see "2222b0104b5621b7a68474f2741bcbf1"
    And I should not see "Undelete Bob1 Learner"

  Scenario: Verify legacy full user delete works
    Given I log in as "admin"
    And I set the following administration settings values:
      | authdeleteusers | full |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete Bob1 Learner" "link"
    When I press "Delete"
    And I should see "Browse list of users: 5 records shown"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    Then I should see "Bob1 Learner"
    And I should not see "username1"
    And I should not see "idnumber1"
    And I should see "bob1.learner@example.com."
    And I should see "2222b0104b5621b7a68474f2741bcbf1"
    And I should not see "Undelete Bob1 Learner"

  Scenario: Verify partial user delete and undelete works
    Given I log in as "admin"
    And I set the following administration settings values:
      | authdeleteusers | partial |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete Bob1 Learner" "link"
    When I press "Delete"
    And I should see "Browse list of users: 5 records shown"
    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    Then I should see "Bob1 Learner"
    And I should see "username1"
    And I should see "idnumber1"
    And I should see "bob1.learner@example.com"
    And I should see "Undelete Bob1 Learner"
    When I click on "Undelete Bob1 Learner" "link"
    And I press "Undelete"
    Then I should see "Undeleted Bob1 Learner"
    And I should see "Bob1 Learner"
    And I should see "Job assignments"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I should see "Browse list of users: 6 records shown"
    And I should see "Bob1 Learner"
    And I should see "username1"
    And I should see "bob1.learner@example.com"
