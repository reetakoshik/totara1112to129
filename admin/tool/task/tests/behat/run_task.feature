@tool @tool_task @totara
Feature: Run scheduled task
  In order to write tests that involve scheduled tasks
  As anybody
  I need to be able to use the run task step

  Scenario: Run shechuled task without javascript
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And I log in as "student1"
    And I follow "Course 1"
    And I should see "Topic 1"
    When I run the scheduled task "\core\task\context_cleanup_task"
    Then I should see "Topic 1"
    When I run the scheduled task "core\task\context_cleanup_task"
    Then I should see "Topic 1"

  @javascript
  Scenario: Run shechuled task with javascript
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | One      | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And I log in as "student1"
    And I follow "Course 1"
    And I should see "Topic 1"
    When I run the scheduled task "\core\task\context_cleanup_task"
    Then I should see "Topic 1"
    When I run the scheduled task "core\task\context_cleanup_task"
    Then I should see "Topic 1"
