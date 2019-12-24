@totaraperformance @totarametrics
Feature: Test Totara performance of login and index page
  In order to test the Totara performance
  I load number of core pages to get and compare their metrics later

  Scenario: Load home page as user
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1 | topics |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |

    Then I log in as "student1"

    And I skip the scenario until issue "TL-13952" lands

#    And I save metrics as "Student login"

    Then I log out
#    And I save metrics

#    And I start saving metrics as "metrics name"
    Then I log in as "admin"
#    And I stop saving metrics

#    And I start saving metrics
    Then I log out
#    And I stop saving metrics