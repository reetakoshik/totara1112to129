@mod @mod_facetoface @totara @javascript
Feature: User is able to see the template used when editting a seminar notification
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course1  | c1        | 0        |
    And I am on a totara site
    And I log in as "admin"
    And I navigate to "Seminars >  Notification templates" in site administration
    And I click on "Add" "button"
    And I set the following fields to these values:
      | Title | This is title                        |
      | Body  | This  is the body of notification !! |
    And I click on "Add" "button"

  Scenario: User is adding a notification using a template and he/she should be able to view it when editting
    Given I am on "course1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1             |
      | Description | This is description   |
    And I turn editing mode off
    And I follow "Seminar 1"
    And I follow "Notifications"
    And I click on "Add" "button"
    And I set the following fields to these values:
      | booked         | 1              |
      | Template       | This is title  |
    And I click on "Save" "button"
    When I click on "Edit" "link" in the "This is title" "table_row"
    Then I should see "This is title" exactly "2" times
    And I click on "Save" "button"
    # This is for the case where user click on the save button to check whether the the server change the template id
    When I click on "Edit" "link" in the "This is title" "table_row"
    Then I should see "This is title" exactly "2" times