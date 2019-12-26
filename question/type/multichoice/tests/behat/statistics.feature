@core @core_question @qtype @qtype_multichoice @javascript
Feature: Generating quiz results statistics using Multiple choice questions
  As a teacher
  In order to check my Multiple choice questions will work for students
  I need to create a multiple choice quiz

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | user1@local.com     |
      | user1    | U1        | User   1 | teacher1@local.com  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | user1    | C1     | student        |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name        | Test quiz 1           |
      | Description | Test quiz description |
    Then I log out

  Scenario: Test the statistics results displays correctly when an attempted single answer quiz answer has been deleted.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I click on "Edit quiz" "button"
    And I click on "Add" "link"
    And I click on "a new question" "link"
    And I set the field "item_qtype_multichoice" to "1"
    And I press "submitbutton"

    And I set the field "Question name" to "Question 1"
    And I set the field "Question text" to "Question 1 text"
    And I click on "Shuffle the choices?" "checkbox"
    And I set the field "One or multiple answers?" to "One answer only"
    # Answer 1
    And I set the field "id_answer_0" to "A"
    And I set the field "id_fraction_0" to "None"
    # Answer 2
    And I set the field "id_answer_1" to "B"
    And I set the field "id_fraction_1" to "None"
    # Answer 3
    And I set the field "id_answer_2" to "C"
    And I set the field "id_fraction_2" to "100%"

    And I press "id_submitbutton"
    Then I should see "Question 1 text"
    And I log out

    # As the learner complete the quiz with the the correct answer.
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I click on "Attempt quiz now" "button"
    And I set the field "q1:1_answer2" to "1"
    And I click on "Finish attempt" "button"
    And I click on "Submit all and finish" "button"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "Your answer is correct."
    And I log out

    # Now as the teacher, delete the last answer, C.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I navigate to "Edit quiz" node in "Quiz administration"
    And I click on "Question 1" "link"
    And I set the field "id_answer_2" to ""
    And I set the field "id_fraction_2" to "None"
    And I set the field "id_fraction_1" to "100%"
    And I press "id_submitbutton"
    Then I should see "Question 1 text"

    # Check the Statistics results display.
    When I navigate to "Statistics" node in "Quiz administration > Results"
    Then I should see "Quiz structure analysis"

  Scenario: Test the statistics results displays correctly when an attempted multiple answer quiz answer has been deleted.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I click on "Edit quiz" "button"
    And I click on "Add" "link"
    And I click on "a new question" "link"
    And I set the field "item_qtype_multichoice" to "1"
    And I press "submitbutton"

    And I set the field "Question name" to "Question 1"
    And I set the field "Question text" to "Question 1 text"
    And I click on "Shuffle the choices?" "checkbox"
    And I set the field "One or multiple answers?" to "Multiple answers allowed"
    # Answer 1
    And I set the field "id_answer_0" to "A"
    And I set the field "id_fraction_0" to "None"
    # Answer 2
    And I set the field "id_answer_1" to "B"
    And I set the field "id_fraction_1" to "None"
    # Answer 3
    And I set the field "id_answer_2" to "C"
    And I set the field "id_fraction_2" to "50%"
    # Answer 4
    And I set the field "id_answer_3" to "D"
    And I set the field "id_fraction_3" to "50%"

    And I press "id_submitbutton"
    Then I should see "Question 1 text"
    And I log out

    # As the learner complete the quiz with the the correct answer.
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I click on "Attempt quiz now" "button"
    And I set the field "q1:1_choice2" to "1"
    And I set the field "q1:1_choice3" to "1"
    And I click on "Finish attempt" "button"
    And I click on "Submit all and finish" "button"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "Your answer is correct."
    And I log out

    # Now as the teacher, delete the last answer, C.
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "Test quiz 1" "link"
    And I navigate to "Edit quiz" node in "Quiz administration"
    And I click on "Question 1" "link"
    And I set the field "id_answer_2" to ""
    And I set the field "id_fraction_2" to "None"
    And I set the field "id_answer_3" to ""
    And I set the field "id_fraction_3" to "None"
    And I set the field "id_fraction_1" to "100%"
    And I press "id_submitbutton"
    Then I should see "Question 1 text"

    # Check the Statistics results display.
    When I navigate to "Statistics" node in "Quiz administration > Results"
    Then I should see "Quiz structure analysis"
