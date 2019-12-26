@mod @mod_quiz @totara
Feature: Test various combinations of Marks Review options
  In order to ensure that marks are revealed to learners at appropriate times
  As a manager
  I need to set Review options appropriately

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
    And the following config values are set as admin:
      | grade_item_advanced | hiddenuntil |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype     | name           | questiontext  | defaultmark |
      | Test questions   | truefalse | First question | Answer false  | 1.00        |
    And the following "activities" exist:
      | activity   | name                | course | idnumber | timeclose  | gradepass | completion | completionpass |
      | quiz       | Test Quiz           | C1     | quiz1    | 1956528000 | 5.00      | 2          | 1              |
    And quiz "Test Quiz" contains the following questions:
      | question       | page |
      | First question | 1    |

  Scenario: student1 passes the quiz with all marks options checked (default) and sees marks and completion pass
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"

  Scenario: student1 passes the quiz with no marks options checked and sees no marks, and complete (but not pass)
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksimmediately" to "0"
    And I set the field "marksopen" to "0"
    And I set the field "marksclosed" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should not see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should not exist in the "li.modtype_quiz" "css_element"
    Then "//span[contains(., 'Completed: Test Quiz')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"

  Scenario: student1 passes the quiz with only Marks Immediately checked and sees marks, and complete with pass
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksopen" to "0"
    And I set the field "marksclosed" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"

  Scenario: student1 passes the quiz with only Marks Later checked and sees no marks, and complete (but not pass),
  until 2 minutes have passed and marks and complete with pass become visible
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksimmediately" to "0"
    And I set the field "marksclosed" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should not see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should not exist in the "li.modtype_quiz" "css_element"
    Then "//span[contains(., 'Completed: Test Quiz')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"
    And I log out
    And I age the "Test Quiz" "responses" in the "mod_quiz" plugin "60" seconds
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should not exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should not see "100.00"
    And I age the "Test Quiz" "responses" in the "mod_quiz" plugin "60" seconds
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should see "100.00"

  Scenario: student1 passes the quiz with only Marks Closed checked and sees no marks, and complete (but not pass),
  until quiz is closed, when marks and complete with pass become visible
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksimmediately" to "0"
    And I set the field "marksopen" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should not see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should not exist in the "li.modtype_quiz" "css_element"
    Then "//span[contains(., 'Completed: Test Quiz')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | timeclose[day]       | 1        |
      | timeclose[month]     | January  |
      | timeclose[year]      | 2010     |
      | timeclose[hour]      | 08       |
      | timeclose[minute]    | 00       |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should see "Your final grade for this quiz is 100"

  Scenario: student1 passes the quiz with only Marks Immediately and Marks Later checked and sees marks, and complete with pass,
  until quiz is closed, when marks become hidden but complete with pass stays visible
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksclosed" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | timeclose[day]       | 1        |
      | timeclose[month]     | January  |
      | timeclose[year]      | 2010     |
      | timeclose[hour]      | 08       |
      | timeclose[minute]    | 00       |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should not see "Your final grade for this quiz is 100"

  Scenario: student1 passes the quiz with only Marks Immediately and Marks After checked and sees marks, and complete with pass,
  until 2 minutes have passed, when marks disappear
  until quiz is closed, when marks are visible again
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksopen" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I age the "Test Quiz" "responses" in the "mod_quiz" plugin "120" seconds
    And I follow "Test Quiz"
    Then I should not see "100.00"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | timeclose[day]       | 1        |
      | timeclose[month]     | January  |
      | timeclose[year]      | 2010     |
      | timeclose[hour]      | 08       |
      | timeclose[minute]    | 00       |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should see "Your final grade for this quiz is 100"

  Scenario: student1 passes the quiz with only Marks Later and Marks After checked and sees no marks, and complete (but not pass),
  until after 2 minutes or quiz is closed, when marks and complete with pass become visible
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "marksimmediately" to "0"
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And the "Test Quiz" "quiz" activity with "auto" completion should be marked as not complete
    And I follow "Test Quiz"
    And I press "Attempt quiz now"
    And I set the field "True" to "1"
    And I press "Finish attempt"
    And I press "Submit all and finish"
    Then I should see "The correct answer is 'True'."
    Then I should not see "100.00"
    And I follow "C1"
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should not exist in the "li.modtype_quiz" "css_element"
    Then "//span[contains(., 'Completed: Test Quiz')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I age the "Test Quiz" "responses" in the "mod_quiz" plugin "120" seconds
    And I follow "Test Quiz"
    Then I should see "100.00"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Activity completion" node in "Course administration > Reports"
    Then "//span[contains(.,'Test Quiz: Completed (achieved pass grade)')]" "xpath_element" should exist in the "Student 1" "table_row"
    And I am on "Course 1" course homepage
    And I follow "Test Quiz"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the following fields to these values:
      | timeclose[day]       | 1        |
      | timeclose[month]     | January  |
      | timeclose[year]      | 2010     |
      | timeclose[hour]      | 08       |
      | timeclose[minute]    | 00       |
    And I press "Save and display"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    Then "//span[contains(., 'Completed: Test Quiz (achieved pass grade)')]" "xpath_element" should exist in the "li.modtype_quiz" "css_element"
    And I follow "Test Quiz"
    Then I should see "Your final grade for this quiz is 100"