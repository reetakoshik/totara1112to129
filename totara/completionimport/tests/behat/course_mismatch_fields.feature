@totara @totara_customfield @totara_completion_upload @javascript @_file_upload
Feature: Verify the case insensitive shortnames for course completion imports works as expected
  As an admin
  I import course completions with case mismatches
  In order to test the case insensitive shortname setting

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname  | lastname  | email                |
      | learner01 | Bob1       | Learner1  | learner01@example.com |
      | learner02 | Bob2       | Learner2  | learner02@example.com |
      | learner03 | Bob3       | Learner3  | learner03@example.com |
      | learner04 | Bob4       | Learner4  | learner04@example.com |
      | learner05 | Bob5       | Learner5  | learner05@example.com |
      | learner06 | Bob6       | Learner6  | learner06@example.com |
      | learner07 | Bob7       | Learner7  | learner07@example.com |
      | learner08 | Bob8       | Learner8  | learner08@example.com |

    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | CP101     | c1       |
      | Course 2 | CP102     | c2       |

    And the following "course enrolments" exist:
      | user      | course    | role    |
      | learner01 | CP101     | student |
      | learner02 | CP101     | student |
      | learner03 | CP101     | student |
      | learner04 | CP101     | student |
      | learner05 | CP101     | student |
      | learner06 | CP101     | student |
      | learner07 | CP101     | student |
      | learner08 | CP101     | student |
      | learner01 | CP102     | student |
      | learner02 | CP102     | student |
      | learner03 | CP102     | student |
      | learner04 | CP102     | student |
      | learner05 | CP102     | student |
      | learner06 | CP102     | student |
      | learner07 | CP102     | student |
      | learner08 | CP102     | student |

  Scenario: Basic course completion import case insensitive is turned on
    When I log in as "admin"
    And I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_mismatch_fields_1.csv" file to "Choose course file to upload" filemanager
    And I set the field "forcecaseinsensitivecourse" to "1"
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "0 Records with data errors - these were ignored"
    And I should see "5 Records created as evidence"
    And I should see "7 Records successfully imported as courses"
    And I should see "12 Records in total"

    When I follow "Course import report"
    And "1" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "6" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "7" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "9" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob1 Learner1 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "100%"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "100%"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "100%"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP102"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob8 Learner8 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP101"

  Scenario: Basic course completion import case insensitive is turned off

    When I log in as "admin"
    And I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_mismatch_fields_1.csv" file to "Choose course file to upload" filemanager
    And I click on "Upload" "button" in the "#mform1" "css_element"
    Then I should see "CSV import completed"
    And I should see "9 Records with data errors - these were ignored"
    And I should see "2 Records created as evidence"
    And I should see "1 Records successfully imported as courses"
    And I should see "12 Records in total"

    When I follow "Course import report"
    And "1" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "2" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "3" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "4" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "5" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "6" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "7" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"
    And "8" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "9" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "10" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "11" row "Imported as evidence?" column of "completionimport_course" table should contain "No"
    And "12" row "Imported as evidence?" column of "completionimport_course" table should contain "Yes"

    And "1" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "3" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "4" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "5" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "6" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "8" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "9" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "10" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"
    And "11" row "Errors" column of "completionimport_course" table should contain "Duplicate ID Number"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob1 Learner1 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "0 records shown"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob4 Learner4"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob4 Learner4 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "Completed course : CP102"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Bob8 Learner8"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    Then I should see "Record of Learning for Bob8 Learner8 : All Courses"
    And "Course 1" row "Progress" column of "plan_courses" table should contain "Not tracked"
    And "Course 2" row "Progress" column of "plan_courses" table should contain "Not tracked"

    When I follow "Other Evidence"
    Then I should see "0 records shown"
