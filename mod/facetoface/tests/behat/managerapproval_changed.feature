@mod @mod_facetoface @totara
Feature: Seminar Approval required
  In order to test user's status code when seminar is changed from approval required to not
  As a manager
  I need to change approval required value

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Sam1      | Student1 | student1@example.com |
      | student2 | Sam2      | Student2 | student2@example.com |
      | student3 | Sam3      | Student3 | student3@example.com |
      | student4 | Sam4      | Student4 | student4@example.com |
      | student5 | Sam5      | Student5 | student5@example.com |
      | student6 | Sam6      | Student6 | student6@example.com |

    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | student3 | C1     | student        |
      | student4 | C1     | student        |
      | student5 | C1     | student        |
      | student6 | C1     | student        |

    And the following job assignments exist:
      | user     | fullname           | idnumber | manager |
      | student1 | Job Assignment One | 1        | admin   |
      | student2 | Job Assignment One | 1        | admin   |
      | student3 | Job Assignment One | 1        | admin   |
      | student4 | Job Assignment One | 1        | admin   |
      | student5 | Job Assignment One | 1        | admin   |
      | student6 | Job Assignment One | 1        | admin   |

    And I log in as "admin"

  @javascript
  Scenario: Update user's status code depending from session capacity when seminar approval required is changed to false
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I click on "Test seminar name" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Approval Options" "link"
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity           | 4    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I wait "1" seconds
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam5 Student5, student5@example.com" "option"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Approval required"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"

    And I set the following fields to these values:
      | Approve Sam1 Student1 for this event | 1 |
      | Approve Sam2 Student2 for this event | 1 |
    And I press "Update requests"
    When I follow "Attendees"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"

    Then I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Approval Options" "link"
    And I click on "#id_approvaloptions_approval_none" "css_element"
    And I press "Save and display"

    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"

    And I should see "Sam3 Student3" in the "#facetoface_sessions" "css_element"
    And I should see "Sam4 Student4" in the "#facetoface_sessions" "css_element"
    And I should not see "Sam5 Student5" in the "#facetoface_sessions" "css_element"
    And I should not see "Sam6 Student6" in the "#facetoface_sessions" "css_element"

    When I follow "Wait-list"
    Then I should not see "Sam1 Student1" in the "table.waitlist" "css_element"
    And I should not see "Sam2 Student2" in the "table.waitlist" "css_element"
    And I should not see "Sam3 Student3" in the "table.waitlist" "css_element"
    And I should not see "Sam4 Student4" in the "table.waitlist" "css_element"
    And I should see "Sam5 Student5" in the "table.waitlist" "css_element"
    And I should see "Sam6 Student6" in the "table.waitlist" "css_element"

  @javascript
  Scenario: Update user's status code with override enabled when seminar approval required is removed
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name              | Test seminar name        |
      | Description       | Test seminar description |
    And I click on "Test seminar name" "link"
    And I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Approval Options" "link"
    And I click on "#id_approvaloptions_approval_manager" "css_element"
    And I press "Save and display"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]     | 1    |
      | timestart[month]   | 1    |
      | timestart[year]    | 2020 |
      | timestart[hour]    | 11   |
      | timestart[minute]  | 00   |
      | timefinish[day]    | 1    |
      | timefinish[month]  | 1    |
      | timefinish[year]   | 2020 |
      | timefinish[hour]   | 12   |
      | timefinish[minute] | 00   |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I set the following fields to these values:
      | capacity           | 4    |
      | Enable waitlist    | 1    |
    And I press "Save changes"

    When I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Sam1 Student1, student1@example.com" "option"
    And I click on "Sam2 Student2, student2@example.com" "option"
    And I click on "Sam3 Student3, student3@example.com" "option"
    And I click on "Sam4 Student4, student4@example.com" "option"
    And I click on "Sam5 Student5, student5@example.com" "option"
    And I click on "Sam6 Student6, student6@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"

    When I follow "Approval required"
    Then I should see "Sam1 Student1"
    And I should see "Sam2 Student2"
    And I should see "Sam3 Student3"
    And I should see "Sam4 Student4"
    And I should see "Sam5 Student5"
    And I should see "Sam6 Student6"

    And I set the following fields to these values:
      | Approve Sam1 Student1 for this event | 1 |
      | Approve Sam2 Student2 for this event | 1 |
    And I press "Update requests"
    When I follow "Attendees"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"

    Then I navigate to "Edit settings" node in "Seminar administration"
    And I click on "Approval Options" "link"
    And I click on "#id_approvaloptions_approval_none" "css_element"
    And I press "Save and display"

    When I click on "Attendees" "link"
    Then I should see "Sam1 Student1" in the "#facetoface_sessions" "css_element"
    And I should see "Sam2 Student2" in the "#facetoface_sessions" "css_element"
    And I should see "Sam3 Student3" in the "#facetoface_sessions" "css_element"
    And I should see "Sam4 Student4" in the "#facetoface_sessions" "css_element"
    And I should see "Sam5 Student5" in the "#facetoface_sessions" "css_element"
    And I should see "Sam6 Student6" in the "#facetoface_sessions" "css_element"
