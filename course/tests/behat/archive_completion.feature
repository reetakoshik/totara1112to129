@totara @core_course
Feature: Test we can manually archive course completion.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname   | email                |
      | learner1 | Learner   | One        | learner1@example.com |
      | learner2 | Learner   | Two        | learner2@example.com |
      | learner3 | Learner   | Three      | learner3@example.com |
      | learner4 | Learner   | Four       | learner4@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
    And the following "course enrolments" exist:
      | user     | course  | role    |
      | learner1 | course1 | student |
      | learner2 | course1 | student |
      | learner3 | course1 | student |
      | learner4 | course1 | student |

  @javascript
  Scenario: Test completion can be archived with manually enrolled courses
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Self completion" block
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    And I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Complete course" "link"
    And I press "Yes"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Completions archive" node in "Course administration"
    And I should see "The course completion data that will be archived is limited to: id; courseid; userid; timecompleted; grade."
    And I should see "1 users will be affected"
    And I press "Continue"
    And I should see "1 users completion records have been successfully archived"
    And I press "Continue"
    And I navigate to "Completions archive" node in "Course administration"
    Then I should see "There are no users that have completed this course"


  @javascript
  Scenario: Test completion cannot be archived for program enrolled courses
    Given the following "programs" exist in "totara_program" plugin:
      | fullname                | shortname |
      | Completion archive test | compltest |
    And the following "program assignments" exist in "totara_program" plugin:
      | program   | user     |
      | compltest | learner1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Self completion" block
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I click on "criteria_self_value" "checkbox"
    And I press "Save changes"
    And I am on "Completion archive test" program homepage
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out
    And I log in as "learner1"
    And I am on "Course 1" course homepage
    And I click on "Complete course" "link"
    And I press "Yes"
    And I log out
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Completions archive" node in "Course administration"
    Then I should see "Courses which are a part of a Program or Certification can not be manually archived."
    And I should see "Completion archive test"
    And I should not see "The course completion data that will be archived is limited to: id; courseid; userid; timecompleted; grade."

  @javascript @totara_reportbuilder
  Scenario: Grades are archived but can be viewed via report builder
    Given I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Expand all" "link"
    And I set the following fields to these values:
      | criteria_grade | 1 |
      | criteria_grade_value | 15 |
    And I press "Save changes"
    And I navigate to "Gradebook setup" node in "Course administration"
    And I press "Add grade item"
    And I set the following fields to these values:
      | Item name     | Misc grade item |
      | Maximum grade | 35              |
      | Minimum grade | 5              |
    And I press "Save changes"
    When I follow "View"
    And I follow "User report"
    And I select "Learner Two" from the "Select all or one user" singleselect
    Then I should see "Learner Two"
    And I should see "5–35"
    And I should see "0–35"

    When I follow "Grader report"
    And I turn editing mode on
    And I give the grade "0" to the user "Learner One" for the grade item "Course total"
    And I give the grade "10" to the user "Learner Two" for the grade item "Course total"
    And I give the grade "20" to the user "Learner Three" for the grade item "Course total"
    And I give the grade "30" to the user "Learner Four" for the grade item "Course total"
    And I press "Save changes"
    And I turn editing mode off
    Then I should see "0.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner One')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "10.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Two')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "20.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Three')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "30.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Four')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "15.00" in the "//table[@id='user-grades']//th[contains(text(), 'Overall average')]/ancestor::tr/td[contains(@class, 'lastcol')]" "xpath_element"

    When I run the scheduled task "core\task\completion_regular_task"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration > Reports"
    Then "//table[@id='completion-progress']//th/a[text()='Learner One']/ancestor::tr//span[contains(@title, 'Not completed')]" "xpath_element" should exist
    And "//table[@id='completion-progress']//th/a[text()='Learner Two']/ancestor::tr//span[contains(@title, 'Not completed')]" "xpath_element" should exist
    And "//table[@id='completion-progress']//th/a[text()='Learner Three']/ancestor::tr//span[contains(@title, 'Completed')]" "xpath_element" should exist
    And "//table[@id='completion-progress']//th/a[text()='Learner Four']/ancestor::tr//span[contains(@title, 'Completed')]" "xpath_element" should exist

    When I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Test course completion report |
      | Source      | Course Completion             |
    And I press "Create report"
    And I follow "Columns"
    And I set the field "newcolumns" to "Grade and required grade"
    And I set the field "newcustomheading" to "1"
    And I set the field "newheading" to "Required grade"
    And I press "Add"
    And I set the field "newcolumns" to "Grade"
    And I press "Add"
    And I set the field "newcolumns" to "Pass Grade"
    And I press "Add"
    And I press "Save changes"
    And I follow "View This Report"
    # Grade column
    Then "Learner One" row "Grade" column of "report_test_course_completion_report" table should contain "0.0%"
    And "Learner Two" row "Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    And "Learner Three" row "Grade" column of "report_test_course_completion_report" table should contain "57.1%"
    And "Learner Four" row "Grade" column of "report_test_course_completion_report" table should contain "85.7%"
    # Completion status
    And "Learner One" row "Completion Status" column of "report_test_course_completion_report" table should contain "Not yet started"
    And "Learner Two" row "Completion Status" column of "report_test_course_completion_report" table should contain "Not yet started"
    And "Learner Three" row "Completion Status" column of "report_test_course_completion_report" table should contain "Complete"
    And "Learner Four" row "Completion Status" column of "report_test_course_completion_report" table should contain "Complete"
    # Pass grade
    And "Learner One" row "Pass Grade" column of "report_test_course_completion_report" table should contain "42.9%"
    And "Learner Two" row "Pass Grade" column of "report_test_course_completion_report" table should contain "42.9%"
    And "Learner Three" row "Pass Grade" column of "report_test_course_completion_report" table should contain "42.9%"
    And "Learner Four" row "Pass Grade" column of "report_test_course_completion_report" table should contain "42.9%"
    # Required grade
    And "Learner One" row "Required grade" column of "report_test_course_completion_report" table should contain "0.0% (42.9% to complete)"
    And "Learner Two" row "Required grade" column of "report_test_course_completion_report" table should contain "28.6% (42.9% to complete)"
    And "Learner Three" row "Required grade" column of "report_test_course_completion_report" table should contain "57.1% (42.9% to complete)"
    And "Learner Four" row "Required grade" column of "report_test_course_completion_report" table should contain "85.7% (42.9% to complete)"

    When I am on homepage
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Test course completion including history report |
      | Source      | Course Completion Including History |
    And I press "Create report"
    And I follow "View This Report"
    Then "Learner Three" row "Grade at time of completion" column of "report_test_course_completion_including_history_report" table should contain "57.1%"
    And "Learner Four" row "Grade at time of completion" column of "report_test_course_completion_including_history_report" table should contain "85.7%"
    And I should not see "Learner One"
    And I should not see "Learner Two"

    When I follow "Course 1"
    And I follow "Completions archive"
    And I press "Continue"
    Then I should see "2 users completion records have been successfully archived"
    And I press "Continue"

    When I navigate to "Course completion" node in "Course administration"
    And I click on "Expand all" "link"
    And I set the following fields to these values:
      | criteria_grade_value | 10 |
    And I press "Save changes"
    And I navigate to "Gradebook setup" node in "Course administration"
    And I follow "View"
    Then I should see "0.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner One')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "10.00" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Two')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "-" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Three')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "-" in the "//table[@id='user-grades']//th/a[contains(text(), 'Learner Four')]/ancestor::tr/td[contains(@class, 'course')]/span[contains(@class, 'gradevalue')]" "xpath_element"
    And I should see "5.00" in the "//table[@id='user-grades']//th[contains(text(), 'Overall average')]/ancestor::tr/td[contains(@class, 'lastcol')]" "xpath_element"

    When I run the scheduled task "core\task\completion_regular_task"
    And I am on homepage
    And I follow "Reports"
    And I follow "Test course completion report"
    # Grade column
    Then "Learner One" row "Grade" column of "report_test_course_completion_report" table should contain "0.0%"
    And "Learner Two" row "Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    And "Learner Three" row "Grade" column of "report_test_course_completion_report" table should contain "-"
    And "Learner Four" row "Grade" column of "report_test_course_completion_report" table should contain "-"
    # Completion status
    And "Learner One" row "Completion Status" column of "report_test_course_completion_report" table should contain "Not yet started"
    And "Learner Two" row "Completion Status" column of "report_test_course_completion_report" table should contain "Complete"
    And "Learner Three" row "Completion Status" column of "report_test_course_completion_report" table should contain "Not yet started"
    And "Learner Four" row "Completion Status" column of "report_test_course_completion_report" table should contain "Not yet started"
    # Pass grade
    And "Learner One" row "Pass Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    And "Learner Two" row "Pass Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    And "Learner Three" row "Pass Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    And "Learner Four" row "Pass Grade" column of "report_test_course_completion_report" table should contain "28.6%"
    # Required grade
    And "Learner One" row "Required grade" column of "report_test_course_completion_report" table should contain "0.0% (28.6% to complete)"
    And "Learner Two" row "Required grade" column of "report_test_course_completion_report" table should contain "28.6% (28.6% to complete)"
    And "Learner Three" row "Required grade" column of "report_test_course_completion_report" table should contain ""
    And "Learner Four" row "Required grade" column of "report_test_course_completion_report" table should contain ""

    When I follow "Reports"
    And I follow "Test course completion including history report"
    Then I should not see "Learner One"
    And "Learner Two" row "Grade at time of completion" column of "report_test_course_completion_including_history_report" table should contain "28.6%"
    And "Learner Three" row "Grade at time of completion" column of "report_test_course_completion_including_history_report" table should contain "57.1%"
    And "Learner Four" row "Grade at time of completion" column of "report_test_course_completion_including_history_report" table should contain "85.7%"
