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
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1             |
      | Description | This is description   |
    And I follow "Seminar 1"
    And I click on "Notifications" "link" in the "Administration" "block"
    And I click on "Add" "button" in the "#region-main" "css_element"
    And I should not see "Date(s) and location(s):" in the "#id_body_editoreditable" "css_element"
    And I should not see "Below is the message that was sent to the learner:" in the "#id_managerprefix_editoreditable" "css_element"
    When I set the field "templateid" to "Seminar booking decline"
    Then I should see "Date(s) and location(s):" in the "#id_body_editoreditable" "css_element"
    And I should see "Below is the message that was sent to the learner:" in the "#id_managerprefix_editoreditable" "css_element"
