@mod @mod_facetoface @totara
Feature: Adding a new seminar's notification
  with selected seminar's notification template
  then it should populate the text boxes

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | c101      | 0        |


  @javascript
  Scenario: I add the seminar and the notification to the course
    Given I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1             |
      | Description | This is description   |
    And I follow "Seminar 1"
    And I follow "Notifications"
    And I click on "Add" "button" in the "#region-main" "css_element"
    And I should not see "Date(s) and location(s):" in the "#id_body_editoreditable" "css_element"
    And I should not see "Below is the message that was sent to the learner:" in the "#id_managerprefix_editoreditable" "css_element"
    When I set the field "templateid" to "Seminar booking decline"
    Then I should see "Date(s) and location(s):" in the "#id_body_editoreditable" "css_element"
    And I should see "Below is the message that was sent to the learner:" in the "#id_managerprefix_editoreditable" "css_element"

  @javascript
  Scenario: I add a custom notification to the seminar
    Given I log in as "admin"
    And I click on "Find Learning" in the totara menu
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1             |
      | Description | This is description   |
    And I follow "Seminar 1"
    And I follow "Notifications"
    And I click on "Add" "button" in the "#region-main" "css_element"
    And I should see "Add notification"
    When I click on "#id_type_2" "css_element"
    And I click on "#id_booked" "css_element"
    And I click on "#id_booked_type_2" "css_element"
    And I set the following fields to these values:
      | Title                 | My Custom Note |
    And I press "Save"
    Then I should see "Notification saved"
    And I should see "Attended only" in the "My Custom Note" "table_row"
    When I click on "Edit" "link" in the "My Custom Note" "table_row"
    Then I should see "My Custom Note"
    And the following fields match these values:
      | id_booked        | 1              |
      | id_booked_type_2 | 2              |
      | Title            | My Custom Note |
