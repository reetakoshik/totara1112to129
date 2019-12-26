@totara @totara_reportbuilder @enrol @enrol_self
Feature: Event Name Site Logs report filter
  As an admin
  I should be able to use the site logs event name filter

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |

  @javascript
  Scenario: Test site logs event name filter
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    When I add "Self enrolment" enrolment method with:
      | Custom instance name | Self enrolment |

    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Site Logs |
      | Source      | Site Logs |
    And I click on "Create report" "button"
    And I follow "Columns"
    And I add the "Event Class Name" column to the report
    And I log out

    And I log in as "student1"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "link"
    And I click on "Enrol" "link"
    And I click on "Enrol me" "button"
    Then I should see "Topic 1"
    And I log out

    And I log in as "student2"
    And I click on "Find Learning" in the totara menu
    And I click on "Course 1" "link"
    And I click on "Enrol" "link"
    And I click on "Enrol me" "button"
    Then I should see "Topic 1"
    And I log out

    And I log in as "admin"
    And I click on "Reports" in the totara menu
    When I follow "Site Logs"
    Then I should see "\core\event\course_viewed"
    And I should see "\core\event\user_loggedout"
    And I should see "\core\event\user_loggedin"

    When I set the field "logstore_standard_log-eventname_op" to "2"
    And I set the field "logstore_standard_log-eventname" to "\core\event\course_viewed"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "\core\event\course_viewed"
    And I should not see "\core\event\user_loggedout"
    And I should not see "\core\event\user_loggedin"

    When I set the field "logstore_standard_log-eventname" to "\core\event\user_loggedin"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "\core\event\user_loggedin"
    And I should not see "\core\event\course_viewed"
    And I should not see "\core\event\user_loggedout"

    When I set the field "logstore_standard_log-eventname" to "\core\event\user_loggedout"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "\core\event\user_loggedout"
    And I should not see "\core\event\course_viewed"
    And I should not see "\core\event\user_loggedin"
