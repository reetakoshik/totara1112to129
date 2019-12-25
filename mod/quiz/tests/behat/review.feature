@mod @mod_quiz @totara
Feature: Review quiz attempts
  In order to ensure the correct information is displayed when a user reviews a quiz
  As an admin
  I need to setup a quiz and specify the decimal places to use when showing the grades
  As a student
  I need to answer some questions correctly and review my attempt

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user | course | role |
      | student1 | C1 | student |

    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype           | name             | template    |
      | Test questions   | multichoice     | Multi-choice-001 | two_of_four |
      | Test questions   | multichoice     | Multi-choice-002 | two_of_four |
      | Test questions   | multichoice     | Multi-choice-003 | two_of_four |
      | Test questions   | multichoice     | Multi-choice-004 | two_of_four |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | Multi-choice-001 | 1 |
      | Multi-choice-002 | 1 |
      | Multi-choice-003 | 1 |
      | Multi-choice-004 | 1 |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test quiz"
    And I follow "Edit quiz"
    And I set the field "Maximum grade" to "4"
    And I press "Save"
    Then the field "Maximum grade" matches value "4.0"
    And I log out

  @javascript
  Scenario: User answers some questions correct and review quiz attempt
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz"
    And I press "Attempt quiz now"
    And I click on "Three" "checkbox" in the "//div[contains(@id, 'question-1-1')]/div[contains(@class, 'content')]" "xpath_element"
    And I click on "Three" "checkbox" in the "//div[contains(@id, 'question-1-2')]/div[contains(@class, 'content')]" "xpath_element"
    And I click on "Three" "checkbox" in the "//div[contains(@id, 'question-1-3')]/div[contains(@class, 'content')]" "xpath_element"
    And I click on "Two" "checkbox" in the "//div[contains(@id, 'question-1-4')]/div[contains(@class, 'content')]" "xpath_element"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "1.50 out of 4.00 (37.50%)"

    When I follow "Finish review"
    Then I should see "Grade / 4.00"
    And I should see "1.50" in the "1" "table_row"
    And I log out

    # Now set the decimal places in grades to 0
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test quiz"
    And I follow "Edit settings"
    And I follow "Expand all"
    And I set the field "Decimal places in grades" to "0"
    And I press "Save and display"
    Then I should see "Attempts: 1"
    And I log out

    # Check the formatting of the grade in the review
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test quiz"
    Then I should see "Grade / 4"
    And I should see "Review" in the "1" "table_row"
    And I should see "2" in the "1" "table_row"

    When I click on "Review" "link" in the "1" "table_row"
    Then I should see "2 out of 4 (38%)"
