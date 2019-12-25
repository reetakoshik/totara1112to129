@totara @totara_completioneditor @core_grades @mod_assign @mod_assign @javascript
Feature: Completion reaggregation in course completion editor
  Background:
    Given the following "users" exist:
      | username  | firstname | lastname | email             |
      | user1     | User      | One      | user1@example.com |
      | user2     | User      | Two      | user2@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | enablecompletion |
      | Course 1 | C1        | topics | 1                |
      | Course 2 | C2        | topics | 1                |
    And the following "course enrolments" exist:
      | user  | course | role    |
      | user1 | C1     | student |
      | user2 | C1     | student |
      | user1 | C2     | student |
    And I log in as "admin"

    And I am on "Course 1" course homepage with editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 1-A |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 1-B |
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 1-C |

    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ALL conditions are met"
    And I set the field "Test assignment 1-B" to "1"
    And I set the field "Test assignment 1-C" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    And I am on "Course 2" course homepage
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment 2 |

    When I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Completion requirements" to "Course is complete when ALL conditions are met"
    And I set the field "Test assignment 2" to "1"
    And I press "Save changes"
    Then I should see "Course completion criteria changes have been saved"

    # Flush stale course completion records here
    And I run the scheduled task "core\task\completion_regular_task"

    # Complete user1's assignment2
    And I am on "Course 2" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "User One" "table_row"
    Then the field "Course completion status" matches value "Not yet started"

    When I switch to "Criteria and Activities" tab
    And I click on "Edit" "link" in the "Test assignment" "table_row"
    And I set the field "Activity status" to "Completed (achieved pass grade)"
    And I set the "Activity time completed" Totara form field to "2001-02-03 04:05"
    And I set the field "View" to "1"
    And I press "Save changes"
    And I click on "Yes" "button"
    Then I should see "Completion changes have been saved"

    When I switch to "Overview" tab
    Then the field "Course completion status" matches value "Not yet started"

  Scenario: Automatically reaggregate course completion via cron task
    # Complete user1's assignment 1-A (not in criteria)
    When I am on "Course 1" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "User One" "table_row"
    Then the field "Course completion status" matches value "Not yet started"

    When I switch to "Criteria and Activities" tab
    And I click on "Edit" "link" in the "Test assignment 1-A" "table_row"
    And I set the field "Activity status" to "Completed (achieved pass grade)"
    And I set the "Activity time completed" Totara form field to "2001-02-03 04:05"
    And I set the field "View" to "1"
    And I press "Save changes"
    And I click on "Yes" "button"
    Then I should see "Completion changes have been saved"

    When I switch to "Overview" tab
    Then the field "Course completion status" matches value "Not yet started"
    And I should not see "Completion reaggregation scheduled"

    And I wait "1" seconds
    When I run the scheduled task "core\task\completion_regular_task"
    Then the field "Course completion status" matches value "Not yet started"

    # Complete user1's assignment 1-B (in criteria)
    When I switch to "Criteria and Activities" tab
    And I click on "Edit" "link" in the "Test assignment 1-B" "table_row"
    And I set the field "Activity status" to "Completed (achieved pass grade)"
    And I set the "Activity time completed" Totara form field to "2009-08-07 06:05"
    And I set the field "View" to "1"
    And I press "Save changes"
    And I click on "Yes" "button"
    Then I should see "Completion changes have been saved"

    When I switch to "Overview" tab
    Then the field "Course completion status" matches value "Not yet started"
    And I should see "Completion reaggregation scheduled" exactly "1" times

    And I wait "1" seconds
    When I run the scheduled task "core\task\completion_regular_task"
    Then the field "Course completion status" matches value "In progress"

    # Complete user1's assignmenr 1-C (in criteria)
    When I switch to "Criteria and Activities" tab
    And I click on "Edit" "link" in the "Test assignment 1-C" "table_row"
    And I set the field "Activity status" to "Completed"
    And I set the "Activity time completed" Totara form field to "2012-03-04 05:06"
    And I set the field "View" to "1"
    And I press "Save changes"
    And I click on "Yes" "button"
    Then I should see "Completion changes have been saved"

    When I switch to "Overview" tab
    Then the field "Course completion status" matches value "In progress"
    And I should see "Completion reaggregation scheduled" exactly "2" times
    And I wait "1" seconds
    When I run the scheduled task "core\task\completion_regular_task"
    Then the field "Course completion status" matches value "Complete"

    When I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "User Two" "table_row"
    Then the field "Course completion status" matches value "Not yet started"

    When I am on "Course 2" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "User One" "table_row"
    Then the field "Course completion status" matches value "Complete"
