@totara @totara_plan @totara_courseprogressbar @totara_programprogressbar
Feature: Ensure progress is shown in Record of Learning
  As a learner
  I should be able to see progress towards completion of courses, programs and certification
  in my Record of Learning

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
    And the following "courses" exist:
      | fullname  | shortname  | enablecompletion |
      | Course 1  | course1    | 1                |
      | Course 2  | course2    | 1                |
      | Course 3  | course3    | 1                |
      | Course 4  | course4    | 1                |
      | Course 5  | course5    | 1                |
    And the following "activities" exist:
      | activity   | name              | intro           | course               | idnumber    | completion   |
      | label      | c1label1          | course1 label1  | course1              | c1label1    | 1            |
      | label      | c1label2          | course1 label2  | course1              | c1label2    | 1            |
      | label      | c1label3          | course1 label3  | course1              | c1label3    | 1            |
      | label      | c2label1          | course2 label1  | course2              | c2label1    | 1            |
      | label      | c2label2          | course2 label2  | course2              | c2label2    | 1            |
      | label      | c2label3          | course2 label3  | course2              | c2label3    | 1            |
      | label      | c3label1          | course3 label1  | course3              | c3label1    | 1            |
      | label      | c3label2          | course3 label2  | course3              | c3label2    | 1            |
      | label      | c3label3          | course3 label3  | course3              | c3label3    | 1            |
      | label      | c4label1          | course4 label1  | course4              | c4label1    | 1            |
      | label      | c4label2          | course4 label2  | course4              | c4label2    | 1            |
      | label      | c4label3          | course4 label3  | course4              | c4label3    | 1            |
      | label      | c5label1          | course5 label1  | course5              | c5label1    | 1            |
      | label      | c5label2          | course5 label2  | course5              | c5label2    | 1            |
      | label      | c5label3          | course5 label3  | course5              | c5label3    | 1            |

    # Enrolling the user through the program or certification
    And the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Test Program 1          | program1  |
    And I add a courseset with courses "course1,course2" to "program1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |

    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | activeperiod | windowperiod | recertifydatetype |
      | Certification One | cert1     | 1 month      | 1 month      | 1                 |
    And I add a courseset with courses "course3" to "cert1":
      | Set name              | set1        |
      | Learner must complete | All courses |
      | Minimum time required | 1           |
    And I add a courseset with courses "course4,course5" to "cert1":
      | Set name              | set2        |
      | Learner must complete | One course  |
      | Minimum time required | 1           |

    And the following "program assignments" exist in "totara_program" plugin:
      | user      | program  |
      | learner1  | program1 |
      | learner1  | cert1    |

    When I log in as "admin"

    # Set course completion criteria
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course1 label1" to "1"
    And I set the field "Label - course1 label2" to "1"
    And I set the field "Label - course1 label3" to "1"
    And I press "Save changes"

    And I am on "Course 2" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course2 label1" to "1"
    And I set the field "Label - course2 label2" to "1"
    And I set the field "Label - course2 label3" to "1"
    And I press "Save changes"

    And I am on "Course 3" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course3 label1" to "1"
    And I set the field "Label - course3 label2" to "1"
    And I set the field "Label - course3 label3" to "1"
    And I press "Save changes"

    And I am on "Course 4" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course4 label1" to "1"
    And I set the field "Label - course4 label2" to "1"
    And I set the field "Label - course4 label3" to "1"
    And I press "Save changes"

    And I am on "Course 5" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Label - course5 label1" to "1"
    And I set the field "Label - course5 label2" to "1"
    And I set the field "Label - course5 label3" to "1"
    And I press "Save changes"

    Then I log out

    # Complete some activities to get different progress
    When I log in as "learner1"
    # course1 - 100%
    And I am on "Course 1" course homepage
    And I click on "Not completed: course1 label1. Select to mark as complete." "link"
    And I click on "Not completed: course1 label2. Select to mark as complete." "link"
    And I click on "Not completed: course1 label3. Select to mark as complete." "link"
    Then I should see "Completed: course1 label1. Select to mark as not complete."
    And I should see "Completed: course1 label2. Select to mark as not complete."
    And I should see "Completed: course1 label3. Select to mark as not complete."

    # course2 - 33%
    When I am on "Course 2" course homepage
    And I click on "Not completed: course2 label1. Select to mark as complete." "link"
    Then I should see "Completed: course2 label1. Select to mark as not complete."

    # course3 - 66%
    When I am on "Course 3" course homepage
    And I click on "Not completed: course3 label1. Select to mark as complete." "link"
    And I click on "Not completed: course3 label2. Select to mark as complete." "link"
    Then I should see "Completed: course3 label1. Select to mark as not complete."
    And I should see "Completed: course3 label2. Select to mark as not complete."

    # course4 - 0%
    When I am on "Course 4" course homepage

    # course5 - 33%
    When I am on "Course 5" course homepage
    And I click on "Not completed: course5 label1. Select to mark as complete." "link"
    Then I should see "Completed: course5 label1. Select to mark as not complete."
    And I log out

  @javascript
  Scenario: Test progress displayed in Record of Learning
    Given I log in as "learner1"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Record of Learning : All Courses"
    And I should see "100%" in the "Course 1" "table_row"
    And I should see "33%" in the "Course 2" "table_row"
    And I should see "66%" in the "Course 3" "table_row"
    And I should see "0%" in the "Course 4" "table_row"
    And I should see "33%" in the "Course 5" "table_row"

    When I click on "Programs" "link" in the "#dp-plan-content" "css_element"
    Then I should see "66%" in the "Test Program 1" "table_row"
    When I follow "Test Program 1"
    Then I should see "66%" in the "//div[contains(@class, 'programprogress')]//div[contains(@class, 'item') and contains(., 'Progress:')]" "xpath_element"
    And I should see "100%" in the "Course 1" "table_row"
    And I should see "33%" in the "Course 2" "table_row"

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "49%" in the "Certification One" "table_row"
    When I follow "Certification One"
    Then I should see "49%" in the "//div[contains(@class, 'programprogress')]//div[contains(@class, 'item') and contains(., 'Progress:')]" "xpath_element"
    And I should see "66%" in the "Course 3" "table_row"
    And I should see "0%" in the "Course 4" "table_row"
    And I should see "33%" in the "Course 5" "table_row"

    When I follow "Required Learning"
    Then I should see "66%" in the "Test Program 1" "table_row"
    And I should see "49%" in the "Certification One" "table_row"
