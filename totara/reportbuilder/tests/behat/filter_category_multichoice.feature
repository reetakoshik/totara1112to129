@totara @totara_reportbuilder @course @javascript
Feature: Multichoice category report filter
  As an admin
  I should be able to use the course category multichoice filter

  Background:
    Given I am on a totara site
    And the following "categories" exist:
      | category | name   | idnumber |
      | 0        | cat 1  | cat1     |
      | cat1     | cat 1a | cat1a    |
      | cat1     | cat 1b | cat1b    |
      | 0        | cat 2  | cat2     |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course 0  | c0        | 0        |
      | Course 1z | c1        | cat1     |
      | Course 1a | c1a       | cat1a    |
      | Course 1b | c1b       | cat1b    |
      | Course 2  | c2        | cat2     |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Course report |
      | Source      | Courses       |
    And I click on "Create report" "button"
    # The category multichoice filter is a default one.
    And I click on "View This Report" "link"
    Then I should see "Course 0"
    And I should see "Course 1z"
    And I should see "Course 1a"
    And I should see "Course 1b"
    And I should see "Course 2"
    And the "Choose Categories" "button" should be disabled

  Scenario: Test the enable/disable choose categories button
    Given I set the field "course_category-path_op" to "is equal to"
    Then the "Choose Categories" "button" should be enabled
    When I set the field "course_category-path_op" to "is any value"
    Then the "Choose Categories" "button" should be disabled
    When I set the field "course_category-path_op" to "isn't equal to"
    Then the "Choose Categories" "button" should be enabled

  Scenario Outline: Test course category report builder filter
    Given I set the field "course_category-path_op" to "<type>"
    And I click on "Choose Categories" "button"
    And I click on "cat 1" "link" in the "Choose Categories" "totaradialogue"
    And I click on "Save" "button" in the "Choose Categories" "totaradialogue"
    And I wait "1" seconds

    When I set the field "Include sub-categories?" to "<includesub>"
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should <c0> "Course 0"
    And I should <c1> "Course 1z"
    And I should <c1a> "Course 1a"
    And I should <c1b> "Course 1b"
    And I should <c2> "Course 2"

    Examples:
      | type           | includesub | c0      | c1      | c1a     | c1b     | c2      |
      | is equal to    | 0          | not see | see     | not see | not see | not see |
      | isn't equal to | 0          | see     | not see | see     | see     | see     |
      | is equal to    | 1          | not see | see     | see     | see     | not see |
      | isn't equal to | 1          | see     | not see | not see | not see | see     |
