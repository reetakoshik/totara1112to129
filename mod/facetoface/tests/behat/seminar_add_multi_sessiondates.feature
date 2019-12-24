@mod @mod_facetoface @javascript
Feature: Seminar with multi session dates compatible with room selection and new session date
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | course 1 | c1        | 0        |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name   |
      | room 1 |
      | room 2 |
    And I am on a totara site
    And I log in as "admin"

  # In a scenario where user is editing a seminar's event with multiple session dates with room selected, and user
  # should be able to see the room name corectly rendered. Furthermore, user then try to add a new session date to the
  # event. As expected behaviour, user should be able see that new session dates was added to the event, but without saving,
  # changes would not be applied to the events
  Scenario: When a new session date to the event with a room and saved, then user should the new session date and the room selected
    Given I am on "course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar 1 |
    And I follow "Seminar 1"
    And I follow "Add a new event"
    And I follow "Select room"
    And I follow "room 1"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I follow "Edit session"
    And I fill seminar session with relative date in form data:
      | sessiontimezone   | Pacific/Auckland |
      | timestart[month]  | +2               |
      | timestart[day]    | +2               |
      | timestart[year]   | 0                |
      | timefinish[month] | +2               |
      | timefinish[day]   | +2               |
      | timefinish[year]  | 0                |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Save changes" "button"
    And I follow "Edit event"
    And I click on "Add a new session" "button"
    And I click on the link "Select room" in row 2
    And I follow "room 2"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on "Save changes" "button"
    And I follow "Edit event"
    When I click on the link "Select room" in row 1
    Then I should see "room 1"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I click on the link "Select room" in row 2
    And I should see "room 2"

  # A scenario where editing an event without any session date, then the system should not try to adding a new session
  # date automatically by default, and this ability should be compatible with the ability of viewing multiple session dates
  Scenario: When editing an event without session date then the sessiondate is not automatically added
    Given I am on "course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name | Seminar 2 |
    And I follow "Seminar 2"
    And I follow "Add a new event"
    And I follow "Delete"
    And I click on "Save changes" "button"
    When I follow "Edit event"
    Then I should see "This event has no sessions."
