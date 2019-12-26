@totara @totara_completioneditor @javascript
Feature: The current course completion record can be edited
  In order to see that the current course completion record can be edited
  I need to use the course completion editor

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
      | user002  | FirstName2 | LastName2 | user002@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |

  Scenario: The current course completion record can be edited when the user is assigned
    # Completion editor list of users.
    When I am on "Course One" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor overview.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    Then I should see "Current course completion record"
    And the field "Course completion status" matches value "Not yet started"
    And "Time started" "field" should not exist
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade" "field" should not exist
    And the "Course completion status" "field" should be disabled
    And I should see "Course completion criteria"
    And I should see "No completion criteria set for this course"
    And I should see "Activity completion"
    And I should see "The course has no activities"
    And I should see "Course completion history"
    And I should see "Nothing to display"
    And I should see "Transactions"
    And I should not see "Completion manually edited"

    # Default data was created and is loaded correctly.
    When I switch to "Current completion" tab
    Then the field "Course completion status" matches value "Not yet started"
    And "Time started" "field" should not exist
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade" "field" should not exist

    # Cancel editing current completion..
    When I click on "Complete via rpl" "option" in the "#tfiid_status_totara_completioneditor_form_course_completion" "css_element"
    And I set the following Totara form fields to these values:
      | Time started   | 2011-02-03 04:56                 |
      | Time completed | 2027-07-08 16:34                 |
      | RPL            | This is an RPL completion reason |
      | RPL Grade (%)  | 12.3                             |
    And I press "Cancel"
    And I switch to "Current completion" tab
    Then the field "Course completion status" matches value "Not yet started"
    And "Time started" "field" should not exist
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade" "field" should not exist

    # Save "Complete via rpl" (sets data in all fields).
    When I click on "Complete via rpl" "option" in the "#tfiid_status_totara_completioneditor_form_course_completion" "css_element"
    And I set the following Totara form fields to these values:
      | Time started   | 2011-02-03 04:56                 |
      | Time completed | 2027-07-08 16:34                 |
      | RPL            | This is an RPL completion reason |
      | RPL Grade (%)  | 12.3                             |
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And the field "Course completion status" matches value "Complete via rpl"
    And the field "Time started" matches value "2011-02-03T04:56:00"
    And the field "Time completed" matches value "2027-07-08T16:34:00"
    And the field "RPL" matches value "This is an RPL completion reason"
    And the field "RPL Grade" matches value "12.3"
    And the "Course completion status" "field" should be disabled
    And the "Time started" "field" should be disabled
    And the "Time completed" "field" should be disabled
    And the "RPL" "field" should be disabled
    And the "RPL Grade" "field" should be disabled
    And I should see "Completion manually edited"
    And I should see "Status: Complete via rpl"
    When I switch to "Current completion" tab
    Then the field "Course completion status" matches value "Complete via rpl"
    And the field "Time started" matches value "2011-02-03T04:56:00"
    And the field "Time completed" matches value "2027-07-08T16:34:00"
    And the field "RPL" matches value "This is an RPL completion reason"
    And the field "RPL Grade (%)" matches value "12.3"

    # Save "Complete" (removes the two RPL fields).
    When I click on "Complete" "option" in the "#tfiid_status_totara_completioneditor_form_course_completion" "css_element"
    And I set the following Totara form fields to these values:
      | Time started   |                  |
      | Time completed | 2022-01-02 01:23 |
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And the field "Course completion status" matches value "Complete"
    And the field "Time started" matches value ""
    And the field "Time completed" matches value "2022-01-02T01:23:00"
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist
    And the "Course completion status" "field" should be disabled
    And the "Time started" "field" should be disabled
    And the "Time completed" "field" should be disabled
    When I switch to "Current completion" tab
    Then the field "Course completion status" matches value "Complete"
    And the field "Time started" matches value ""
    And the field "Time completed" matches value "2022-01-02T01:23:00"
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist

    # Save "In progress" (removes the time completed field).
    When I click on "In progress" "option" in the "#tfiid_status_totara_completioneditor_form_course_completion" "css_element"
    And I set the following Totara form fields to these values:
      | Time started | 2012-05-06 07:19 |
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And the field "Course completion status" matches value "In progress"
    And the field "Time started" matches value "2012-05-06T07:19:00"
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist
    And the "Course completion status" "field" should be disabled
    And the "Time started" "field" should be disabled
    When I switch to "Current completion" tab
    And the field "Course completion status" matches value "In progress"
    And the field "Time started" matches value "2012-05-06T07:19:00"
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist

    # Save "Not yet started" (removes the time started field).
    When I click on "Not yet started" "option" in the "#tfiid_status_totara_completioneditor_form_course_completion" "css_element"
    And I press "Save changes"
    Then I should see "Changing the completion record may lead to changes in course completions"
    When I click on "Yes" "button"
    Then I should see "Completion changes have been saved"
    And the field "Course completion status" matches value "Not yet started"
    And "Time started" "field" should not exist
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist
    And the "Course completion status" "field" should be disabled
    When I switch to "Current completion" tab
    Then the field "Course completion status" matches value "Not yet started"
    And "Time started" "field" should not exist
    And "Time completed" "field" should not exist
    And "RPL" "field" should not exist
    And "RPL Grade (%)" "field" should not exist

  Scenario: The current course completion record cannot be edited when the user is not assigned
    When I am on "Course One" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    Then I should not see "FirstName2"
    # Should check that the correct stuff is displayed when the user's completion is edited, but how?
