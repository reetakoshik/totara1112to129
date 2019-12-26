@mod @mod_quiz @javascript
Feature: Attempt or preview quiz after role change
  As a student
  I need to be able to attempt a quiz
  after having previewed it previously as a trainer

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "activities" exist:
      | activity   | name   | intro               | course | idnumber | attempts |
      | quiz       | Quiz M | Quiz multi attempts | C1     | quizM    | 0        |
      | quiz       | Quiz 1 | Quiz 1 attempt      | C1     | quiz1    | 1        |
      | quiz       | Quiz 3 | Quiz 3 attempts     | C1     | quiz3    | 3        |
    And the following "questions" exist:
      | questioncategory | qtype       | name  | questiontext    |
      | Test questions   | truefalse   | TF1   | First question  |
      | Test questions   | truefalse   | TF2   | Second question |
    And quiz "Quiz M" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
    And quiz "Quiz 1" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |
    And quiz "Quiz 3" contains the following questions:
      | question | page | maxmark |
      | TF1      | 1    |         |
      | TF2      | 1    | 3.0     |

  Scenario: Trainer starts preview then attempts the quiz after role change
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | teacher |
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Continue the last attempt" "button" should exist

    When I press "Continue the last attempt"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Continue the last attempt" "button" should exist

    When I press "Continue the last attempt"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    And I log out

  Scenario: Trainer finishes preview then attempts the quiz after role change
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | teacher |
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished" in the "Preview" "table_row"
    And "Preview quiz now" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Preview quiz now" "button" should exist

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Re-attempt quiz" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And I should see "No more attempts are allowed"
    And "Back to the course" "button" should exist

    And I log out

  Scenario: Student starts attempt then preview the quiz after role change
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | teacher |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Continue the last preview" "button" should exist

    When I press "Continue the last preview"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Continue the last preview" "button" should exist

    When I press "Continue the last preview"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"
    And I log out

  Scenario: Student finishes attempt then previews the quiz after role change
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Re-attempt quiz" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And I should see "No more attempts are allowed"
    And "Back to the course" "button" should exist

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | teacher |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Preview quiz now" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Preview quiz now" "button" should exist

  Scenario: Multiple role changes
    Given the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Re-attempt quiz" "button" should exist

    When I press "Re-attempt quiz"
    And I click on "False" "radio" in the "First question" "question"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "75.00 out of 100.00"
    When I follow "Finish review"
    Then "Re-attempt quiz" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "False" "radio" in the "First question" "question"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "75.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And I should see "No more attempts are allowed"
    And "Back to the course" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 3"
    Then "Attempt quiz now" "button" should exist

    When I press "Attempt quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "25.00 out of 100.00"

    When I follow "Finish review"
    Then I should see "Finished"
    And "Re-attempt quiz" "button" should exist

    When I press "Re-attempt quiz"
    And I click on "False" "radio" in the "First question" "question"
    And I click on "True" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "75.00 out of 100.00"
    When I follow "Finish review"
    Then "Re-attempt quiz" "button" should exist

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | teacher |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"
    Then "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then I should see "Attempts allowed: 1"
    And I should see "Attempts: 1"
    And "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I am on "Course 1" course homepage
    And I follow "Quiz 3"
    Then I should see "Attempts allowed: 3"
    And I should see "Attempts: 2"
    And "Preview quiz now" "button" should exist

    When I press "Preview quiz now"
    And I click on "True" "radio" in the "First question" "question"
    And I click on "False" "radio" in the "Second question" "question"
    And I press "Finish attempt"
    Then I should see "Answer saved" in the "1" "table_row"
    And I should see "Answer saved" in the "2" "table_row"

    When I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Enrolled users" node in "Course administration > Users"
    Then I should see "User One"
    And I click on "Unenrol" "link" in the "User One" "table_row"
    And I press "Continue"
    Then I should not see "User One"
    And I log out

    When the following "course enrolments" exist:
      | user     | course | role    |
      | user1    | C1     | student |
    And I log in as "user1"
    And I am on "Course 1" course homepage
    And I follow "Quiz M"

    Then "Continue the last attempt" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 1"
    Then I should see "Attempts allowed: 1"
    And "Back to the course" "button" should exist

    When I am on "Course 1" course homepage
    And I follow "Quiz 3"
    Then I should see "Attempts allowed: 3"
    And "Continue the last attempt" "button" should exist
