@totara @totara_completioneditor @javascript
Feature: The course completion history records can be edited
  In order to see that the course completion history records can be edited
  I need to use the course completion editor

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname  | lastname  | email               |
      | user001  | FirstName1 | LastName1 | user001@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion |
      | Course One | course1   | topics | 1                |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | user001 | course1 | student |

  Scenario: The course completion history records can be created and edited
    # Completion editor list of users.
    When I am on "Course One" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    Then I should see "FirstName1 LastName1"

    # Completion editor history tab/list.
    When I click on "Edit course completion" "link" in the "FirstName1 LastName1" "table_row"
    And I switch to "History" tab
    Then I should see "Course completion history"
    And I should see "Nothing to display"

    # Create history but cancel.
    When I press "Add history"
    And I set the following Totara form fields to these values:
      | Time completed | 2027-07-08 16:34 |
      | Grade          | 12.3             |
    And I click on "Cancel" "button"
    Then I should not see "Completion changes have been saved"
    And I should not see "July 2027"
    And I should not see "12.3"
    And I should see "Nothing to display"

    # Create history.
    When I press "Add history"
    And I click on "savehistory" "button"
    Then I should not see "Completion changes have been saved"
    When I set the following Totara form fields to these values:
      | Time completed | 2027-07-08 16:34 |
      | Grade          | 12.3             |
    And I click on "savehistory" "button"
    Then I should see "Completion changes have been saved"
    And I should see "July 2027"
    And I should see "12.3"
    When I switch to "Transactions" tab
    Then I should see "History manually created"
    When I switch to "History" tab
    And I follow "Edit"
    Then the field "Time completed" matches value "2027-07-08T16:34:00"
    And the field "Grade" matches value "12.3"

    # Update history.
    When I set the following Totara form fields to these values:
      | Time completed | 2011-02-03 04:56 |
      | Grade          |                  |
    And I press "Save changes"
    Then I should see "Completion changes have been saved"
    And I should see "February 2011"
    When I switch to "Transactions" tab
    Then I should see "History manually edited"
    And I should see "Grade: Empty (non-numeric)"
    When I switch to "History" tab
    And I follow "Edit"
    Then the field "Time completed" matches value "2011-02-03T04:56:00"
    And the field "Grade" matches value ""

    # Update history but cancel.
    When I set the following Totara form fields to these values:
      | Time completed | 1999-09-09 09:09 |
    And I press "Cancel"
    Then I should not see "Completion changes have been saved"
    And I should see "February 2011"
    When I follow "Edit"
    Then the field "Time completed" matches value "2011-02-03T04:56:00"

    # Delete history but cancel.
    When I press "Cancel"
    And I follow "Delete"
    And I press "No"
    Then I should not see "Course completion history deleted"
    And I should not see "Nothing to display"
    And I should see "February 2011"

    # Delete history.
    When I follow "Delete"
    And I press "Yes"
    Then I should see "Course completion history deleted"
    And I should see "Nothing to display"
    When I switch to "Transactions" tab
    Then I should see "History manually deleted"
