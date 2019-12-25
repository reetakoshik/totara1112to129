@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Seminar room multiselect custom field.
  When defining seminar rooms
  As an admin
  I should be able to attach custom fields to the room details.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I click on "Room" "link"
    And I set the field "datatype" to "Multi-select"
    And I set the following fields to these values:
      | fullname                   | RoomAttributes |
      | shortname                  | RoomAttributes |
      | multiselectitem[0][option] | Windows        |
      | multiselectitem[1][option] | Aircon         |
      | multiselectitem[2][option] | Furniture      |
    And I press "Save changes"

    Given I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | RoomAllSelected |
      | Room capacity                 | 20              |
      | customfield_RoomAttributes[0] | 1               |
      | customfield_RoomAttributes[1] | 1               |
      | customfield_RoomAttributes[2] | 1               |
    And I press "Add a room"

    Given I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | RoomOneSelected |
      | Room capacity                 | 20              |
      | customfield_RoomAttributes[0] | 0               |
      | customfield_RoomAttributes[1] | 1               |
      | customfield_RoomAttributes[2] | 0               |
    And I press "Add a room"

    Given I press "Add a new room"
    And I set the following fields to these values:
      | Name                          | RoomNoneSelected |
      | Room capacity                 | 20               |
      | customfield_RoomAttributes[0] | 0                |
      | customfield_RoomAttributes[1] | 0                |
      | customfield_RoomAttributes[2] | 0                |
    And I press "Add a room"

    Given I press "Edit this report"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "RoomAttributes (text)"
    And I press "Add"
    And I press "Save changes"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_room_multiselect_custom_field_01: standard filters
    Given I click on "Filters" "link"
    And I set the field "newstandardfilter" to "RoomAttributes (text)"
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    And I click on "Aircon" "checkbox"
    And I click on "input[value=Search]" "css_element"
    Then I should see "RoomAllSelected"
    And I should see "RoomOneSelected"
    And I should not see "RoomNoneSelected"

    When I click on "Aircon" "checkbox"
    And I click on "Windows" "checkbox"
    And I click on "input[value=Search]" "css_element"
    Then I should see "RoomAllSelected"
    And I should not see "RoomOneSelected"
    And I should not see "RoomNoneSelected"


  # ----------------------------------------------------------------------------
  Scenario: mod_facetoface_room_multiselect_custom_field_02: side filters
    Given I click on "Filters" "link"
    And I set the field "newsidebarfilter" to "RoomAttributes (text)"
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    Then I should see "Windows (1)"
    And I should see "Aircon (2)"
    And I should see "Furniture (1)"

    When I click on "Windows" "checkbox"
    Then I should see "RoomAllSelected"
    And I should not see "RoomOneSelected"
    And I should not see "RoomNoneSelected"

    When I click on "Windows (1)" "checkbox"
    And I click on "Aircon (2)" "checkbox"
    Then I should see "RoomAllSelected"
    And I should see "RoomOneSelected"
    And I should not see "RoomNoneSelected"
