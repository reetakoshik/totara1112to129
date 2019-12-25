@mod @mod_feedback @totara @totara_reportbuilder @javascript
Feature: Test the site logstore report
  In order to test the report
  As an admin
  I need to be able to create and collect feedbacks, create and test report

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | user1    | Username  | 1        |
      | user2    | Username  | 2        |
      | teacher  | Teacher   | 3        |
      | manager  | Manager   | 4        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C1     | student |
      | user2 | C1     | student |
      | teacher | C1   | editingteacher |
    And the following "system role assigns" exist:
      | user    | course               | role    |
      | manager | Acceptance test site | manager |
    And the following "activities" exist:
      | activity   | name            | course               | idnumber  | anonymous | publish_stats | section |
      | feedback   | Site feedback   | Acceptance test site | feedback0 | 2         | 1             | 1       |
      | feedback   | Course feedback | C1                   | feedback1 | 2         | 1             | 0       |
    When I log in as "manager"
    And I am on site homepage
    And I follow "Site feedback"
    And I click on "Edit questions" "link" in the "[role=main]" "css_element"
    And I add a "Multiple choice" question to the feedback with:
      | Question                       | Do you like our site?              |
      | Label                          | multichoice2                       |
      | Multiple choice type           | Multiple choice - single answer    |
      | Hide the "Not selected" option | Yes                                |
      | Multiple choice values         | Yes of course\nNot at all\nI don't know |
    And I log out

  Scenario: Complete non anonymous feedback on the front page as an authenticated user
    And I log in as "user1"
    And I am on site homepage
    And I follow "Site feedback"
    And I follow "Preview"
    And I should see "Do you like our site?"
    And I press "Continue"
    And I follow "Answer the questions..."
    And I should see "Do you like our site?"
    And I set the following fields to these values:
      | Yes of course | 1 |
    And I press "Submit your answers"
    And I should not see "Submitted answers"
    And I press "Continue"
    And I log out

    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Site Logs |
      | Source      | Site Logs |
    And I press "Create report"
    And I switch to "Columns" tab
    And I add the "Event Name (linked to event source)" column to the report
    When I follow "View This Report"
    Then I should see "Course viewed"
    And I should see "User has logged in"
    And I should see "Course module viewed"
    And I should see "User logged out"
    And I should see "Response submitted"
    And I should see "Report created"
    And I should see "Report updated"
