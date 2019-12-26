@mod @mod_facetoface @javascript @totara
Feature: The sign-up status is not changing when the approval type is changed
  Background:
    Given the following "users" exist:
      | username | email                | lastname  | firstname |
      | learner1 | learner1@example.com | Kim       | Sa Rang   |
      | learner2 | learner2@example.com | Park      | Min Young |
      | manager  | manager@example.com  | Shin      | Min Ah    |
    And the following "courses" exist:
      | fullname | shortname | format |
      | c101     | c101      | topics |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | learner1 | c101   | student |
      | learner2 | c101   | student |
    And the following job assignments exist:
      | user     | manager |
      | learner1 | manager |
      | learner2 | manager |
    And I am on a totara site
    And I log in as "admin"
  Scenario: User signed up with the state as requested is not changing to booked state
    when the seminar's approval type is changed from approval admin to approval manager
    Given I navigate to "Seminars > Global settings" in site administration
    And I set the following fields to these values:
      | Manager approval                    | 1 |
      | Manager and Administrative approval | 1 |
    And I click on "Save changes" "button"
    And I am on "c101" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name                                | f2f one |
      | Manager and Administrative approval | 1       |
    And I turn editing mode off
    And I follow "f2f one"
    And I follow "Add a new event"
    And I click on "Save changes" "button"
    And I follow "Attendees"
    And I set the field "Attendee actions" to "Add users"
    And I set the field "4 potential users" to "learner1@example.com"
    And I press "Add"
    And I set the field "4 potential users" to "learner2@example.com"
    And I press "Add"
    And I click on "Continue" "button"
    And I click on "Confirm" "button"
    And I follow "f2f one"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I set the field "Manager Approval" to "1"
    And I click on "Save and display" "button"
    And I follow "Attendees"
    When I follow "Approval required"
    Then I should see "Sa Rang Kim"
    And I should see "Min Young Park"
