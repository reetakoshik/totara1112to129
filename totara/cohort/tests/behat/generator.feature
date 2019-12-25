@totara @totara_cohort
Feature: Totara cohort generators
  In order to simplify testing
  As a developers
  I need to be able to use extra Totara cohort generators for behat

  Scenario: Generate cohort members and enrolments
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname    | lastname   | email             |
      | user1       | First        | User       | user1@example.com |
      | user2       | Second       | User       | user2@example.com |
    And the following "courses" exist:
      | fullname   | shortname | description       |
      | Course 1   | c1        | About this course |
      | Course 2   | c2        | About this course |
    And the following "cohorts" exist:
      | name       | idnumber | description         | contextlevel | reference |
      | Audience 1 | AUD1     | About this audience | System       | 0         |
      | Audience 2 | AUD2     | About this audience | System       | 0         |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort |
      | c1     | AUD1   |
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort | role    |
      | c2     | AUD2   | teacher |
    And the following "cohort members" exist in "totara_cohort" plugin:
      | course | cohort | user    |
      | c1     | AUD1   | user1   |
      | c2     | AUD2   | user2   |

    When I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "First User"
    And I should see "Audience sync (Audience 1 - Learner) enrolled"
    And I should not see "Second User"

    When I click on "Find Learning" in the totara menu
    And I follow "Course 2"
    And I navigate to "Enrolled users" node in "Course administration > Users"
    And I should see "Second User"
    And I should see "Audience sync (Audience 2 - Trainer) enrolled"
    And I should not see "First User"
