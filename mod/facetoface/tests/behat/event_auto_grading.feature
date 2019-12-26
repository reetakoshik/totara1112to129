@mod @mod_facetoface @javascript
Feature: Event auto grading for T12
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course1  | course1   | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | One       | Uno      | user1@example.com |
      | user2    | Two       | Duex     | user2@example.com |
      | user3    | Three     | Toru     | user3@example.com |
      | user4    | Four      | Wha      | user4@example.com |
      | user5    | Five      | Cinq     | user5@example.com |
      | user6    | Six       | Sechs    | user6@example.com |
    And the following "course enrolments" exist:
     | user     | course   | role    |
     | user1    | course1  | student |
     | user2    | course1  | student |
     | user3    | course1  | student |
     | user4    | course1  | student |
     | user5    | course1  | student |
    And I log in as "admin"
    And I am on "course1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                 | seminar 1 |
      | How many times the user can sign-up? | 2         |
      | multisignuprestrictfully             | 1         |
      | multisignuprestrictpartly            | 1         |
      | multisignuprestrictnoshow            | 1         |
    And I turn editing mode off
    And I follow "seminar 1"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]   | ## first day of January last year ## j ## |
      | timestart[month] | ## first day of January last year ## n ## |
      | timestart[year]  | ## first day of January last year ## Y ## |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Save changes" "button"

    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I set the following fields to these values:
      | timestart[day]   | ## first day of February last year ## j ## |
      | timestart[month] | ## first day of February last year ## n ## |
      | timestart[year]  | ## first day of February last year ## Y ## |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Save changes" "button"

    And I click on "Attendees" "link" in the "1 January" "table_row"
    And I set the field "Attendee actions" to "add"
    And I set the field "potential users" to "One Uno, user1@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Two Duex, user2@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Three Toru, user3@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Four Wha, user4@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Five Cinq, user5@example.com"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"

  Scenario: Take attendance and confirm grader report
    Given I am on "course1" course homepage
    And I follow "seminar 1"
    And I click on "Attendees" "link" in the "1 January" "table_row"
    And I follow "Take attendance"
    And I click on "Fully attended" "option" in the "One Uno" "table_row"
    And I click on "Partially attended" "option" in the "Two Duex" "table_row"
    And I click on "Partially attended" "option" in the "Three Toru" "table_row"
    And I click on "No show" "option" in the "Four Wha" "table_row"
    And I click on "Not set" "option" in the "Five Cinq" "table_row"
    And I click on "Save attendance" "button"

    And I navigate to "Grades" node in "Course administration"

    When I follow "Grader report"
    Then I should see "100.00" in the "One Uno" "table_row"
    And I should see "50.00" in the "Two Duex" "table_row"
    And I should see "50.00" in the "Three Toru" "table_row"
    And I should see "0.00" in the "Four Wha" "table_row"
    And I should see "-" in the "Five Cinq" "table_row"
    And I should not see "Six Sechs" in the "#user-grades" "css_element"

    And I am on "course1" course homepage
    And I follow "seminar 1"
    And I click on "Attendees" "link" in the "1 February" "table_row"
    And I set the field "Attendee actions" to "add"
    And I set the field "potential users" to "One Uno, user1@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Two Duex, user2@example.com"
    And I click on "Add" "button"
    And I set the field "potential users" to "Three Toru, user3@example.com"
    And I click on "Add" "button"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"

    And I follow "Take attendance"
    And I click on "Not set" "option" in the "One Uno" "table_row"
    And I click on "Not set" "option" in the "Two Duex" "table_row"
    And I click on "Fully attended" "option" in the "Three Toru" "table_row"
    And I click on "Save attendance" "button"

    And I navigate to "Grades" node in "Course administration"

    When I follow "Grader report"
    Then I should see "100.00" in the "One Uno" "table_row"
    And I should see "50.00" in the "Two Duex" "table_row"
    And I should see "100.00" in the "Three Toru" "table_row"
    And I should see "0.00" in the "Four Wha" "table_row"
    And I should see "-" in the "Five Cinq" "table_row"
    And I should not see "Six Sechs" in the "#user-grades" "css_element"
