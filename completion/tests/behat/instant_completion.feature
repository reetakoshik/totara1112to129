@core @core_completion @totara @instant_completion @totara_courseprogressbar
Feature: Instant completion
  In order to test instant completion
  As a teacher
  I need to create courses and set completion criteria

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
      | Course 2 | C2        | 0        | 1                |
      | Course 3 | C3        | 0        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | Frist | teacher1@example.com |
      | student1 | Student | First | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role           |
      | teacher1 | C1 | editingteacher |
      | teacher1 | C2 | editingteacher |
      | teacher1 | C3 | editingteacher |
      | student1 | C1 | student        |
      | student1 | C2 | student        |
      | student1 | C3 | student        |
    And I log in as "admin"
    And I set the following administration settings values:
      | Enable completion tracking | 1 |
      | Enable restricted access   | 1 |
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And completion tracking is "Enabled" in current course
    And I turn editing mode on
    And I add the "Course completion status" block
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name                | Test quiz name                                    |
      | Description         | Test quiz description                             |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "True/False" question to the "Test quiz name" quiz with:
      | Question name                      | First question                          |
      | Question text                      | Answer the first question               |
      | General feedback                   | Thank you, this is the general feedback |
      | Correct answer                     | True                                    |
      | Feedback for the response 'True'.  | So you think it is true                 |
      | Feedback for the response 'False'. | So you think it is false                |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Quiz - Test quiz name | 1 |
    And I press "Save changes"
    And I click on "Courses" in the totara menu
    And I click on "Course 2" "link"
    And completion tracking is "Enabled" in current course
    And I click on "Edit settings" "link" in the "Administration" "block"
    And I set the following fields to these values:
      | Enable completion tracking | Yes |
    And I press "Save and display"
    And I add a "Quiz" to section "1" and I fill the form with:
      | Name                | Test quiz name2                                   |
      | Description         | Test quiz description2                            |
      | Completion tracking | Show activity as complete when conditions are met |
      | completionusegrade  | 1                                                 |
    And I add a "True/False" question to the "Test quiz name2" quiz with:
      | Question name                      | First question                          |
      | Question text                      | Answer the first question               |
      | General feedback                   | Thank you, this is the general feedback |
      | Correct answer                     | True                                    |
      | Feedback for the response 'True'.  | So you think it is true                 |
      | Feedback for the response 'False'. | So you think it is false                |
    And I navigate to "Course completion" node in "Course administration"
    And I set the following fields to these values:
      | Quiz - Test quiz name2   | 1 |
      | id_criteria_course_value | Miscellaneous / Course 1 |
    And I press "Save changes"
    And I click on "Courses" in the totara menu
    And I click on "Course 3" "link"
    And completion tracking is "Enabled" in current course
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name                     | Test assignment name                              |
      | Description                         | Submit your online text                           |
      | Use marking workflow                | Yes                                               |
      | assignsubmission_onlinetext_enabled | 1                                                 |
      | assignsubmission_file_enabled       | 0                                                 |
      | Completion tracking                 | Show activity as complete when conditions are met |
      | completionusegrade                  | 1                                                 |
      | Grade to pass                       | 50                                                |
    And I click on "Course completion" "link" in the "Administration" "block"
    And I set the field "id_overall_aggregation" to "2"
    And I click on "Condition: Activity completion" "link"
    And I set the field "Assignment - Test assignment name" to "1"
    And I press "Save changes"
    And I log out

  @javascript
  Scenario: Instant course completion criteria
    Given the following "programs" exist in "totara_program" plugin:
      | fullname | shortname |
      | Program1 | program1  |
    And the following "program assignments" exist in "totara_program" plugin:
      | user     | program  |
      | student1 | program1 |
    And I log in as "admin"
    And I click on "Programs" in the totara menu
    And I click on "Program1" "link"
    And I press "Edit program details"
    And I switch to "Content" tab
    And I set the following fields to these values:
      | contenttype_ce | Set of courses |
    And I press "Add"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 3" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I wait "1" seconds
    And I click on "Save all changes" "button"
    And I log out

    When I log in as "student1"
    And I click on "Courses" in the totara menu
    And I click on "Course 2" "link"
    And I follow "Test quiz name2"
    And I press "Attempt quiz now"
    And I click on "True" "radio" in the ".answer" "css_element"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "10.00 out of 10.00"

    And I click on "Courses" in the totara menu
    And I click on "Course 1" "link"
    And I follow "Test quiz name"
    And I press "Attempt quiz now"
    And I click on "True" "radio" in the ".answer" "css_element"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    And I click on "Submit all and finish" "button" in the "Confirmation" "dialogue"
    Then I should see "10.00 out of 10.00"

    And I click on "Courses" in the totara menu
    And I click on "Course 3" "link"
    And I follow "Test assignment name"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | This is my submission |
    And I press "Save changes"
    And I log out
    And I log in as "teacher1"
    And I am on homepage
    And I follow "Course 3"
    And I follow "Test assignment name"
    And I follow "View all submissions"
    And I should see "Not marked" in the "Student First" "table_row"
    And I click on "Grade" "link" in the "Student First" "table_row"
    And I set the field "Grade out of 100" to "30"
    And I set the field "Feedback comments" to "Great job! Lol, not really."
    And I set the field "Marking workflow state" to "Released"
    And I press "Save changes"
    And I press "Ok"
    And I follow "Course: Course 3"
    And I follow "Test assignment name"
    And I follow "View all submissions"
    Then I should see "Released" in the "Student First" "table_row"

    When I log out
    When I log in as "student1"
    And I click on "Record of Learning" in the totara menu
    Then the following should exist in the "plan_courses" table:
      | Course Title  | Progress |
      | Course 1      | 100%     |
      | Course 2      | 100%     |
      | Course 3      | 100%     |
    Then I should see "100%" in the "Course 1" "table_row"
    And  I should see "100%" in the "Course 2" "table_row"
    And  I should see "100%" in the "Course 3" "table_row"
    And I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    And I click on "Program1" "link"
    Then I should see "100%" program progress
