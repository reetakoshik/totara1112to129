@totara @totara_coursecatalog @javascript
Feature: Filter courses by multicheck filters on sidebar
  In order to filter results quickly
  As a user
  I can check the multicheck boxes on the sidebar

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "courses" exist:
      | fullname       | shortname |
      | Course Alpha 1 | A1        |
      | Course Alpha 2 | A2        |
      | Course Beta 1  | B1        |
    And I log in as "admin"
    And I click on "Courses" in the totara menu
    And I follow "Course Alpha 1"
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Assignment1 |
    And I add a "Quiz" to section "2" and I fill the form with:
      | Name | Quiz1 |
    And I click on "Courses" in the totara menu
    And I follow "Course Alpha 2"
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Assignment2 |
    And I log out
    And I log in as "user1"

  Scenario: Courses can be searched according to content types
    When I click on "Courses" in the totara menu
    Then I should see "E-learning (3)"
    And I should see "Blended (0)"
    And I should see "Seminar (0)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should see "Course Beta 1"

    When I set the following fields to these values:
      | Assignment (2) | 1 |
    Then I should see "E-learning (2)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should not see "Course Beta 1"

    When I set the following fields to these values:
      | Quiz (1) | 1 |
    Then I should see "E-learning (2)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should not see "Course Beta 1"

    When I set the following fields to these values:
      | Assignment (2) | 0 |
    Then I should see "E-learning (1)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should not see "Course Alpha 2"
    And I should not see "Course Beta 1"

    When I set the following fields to these values:
      | Quiz (1) | 0 |
    Then I should see "E-learning (3)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should see "Course Beta 1"

  Scenario: Multicheck filter counts are influenced correctly by other searches
    When I click on "Courses" in the totara menu
    Then I should see "E-learning (3)"
    And I should see "Blended (0)"
    And I should see "Seminar (0)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should see "Course Beta 1"

    When I set the following fields to these values:
      | Search by | Alpha |
    And I press "toolbarsearchbutton"
    Then I should see "E-learning (2)"
    And I should see "Blended (0)"
    And I should see "Seminar (0)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should not see "Course Beta 1"

    When I set the following fields to these values:
      | Quiz (1) | 1 |
    Then I should see "E-learning (1)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should not see "Course Alpha 2"
    And I should not see "Course Beta 1"

    When I set the following fields to these values:
     | Quiz (1) | 0 |
    And I press "cleartoolbarsearchtext"
    Then I should see "E-learning (3)"
    And I should see "Assignment (2)"
    And I should see "Book (0)"
    And I should see "Quiz (1)"
    And I should see "Course Alpha 1"
    And I should see "Course Alpha 2"
    And I should see "Course Beta 1"

    When I set the following fields to these values:
      | Search by | Beta |
    And I press "toolbarsearchbutton"
    Then I should see "E-learning (1)"
    And I should see "Assignment (0)"
    And I should see "Book (0)"
    And I should see "Quiz (0)"
    And I should not see "Course Alpha 1"
    And I should not see "Course Alpha 2"
    And I should see "Course Beta 1"
