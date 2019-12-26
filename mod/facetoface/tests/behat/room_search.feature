@mod @mod_facetoface @totara @javascript @totara_customfield
Feature: Search pre-defined rooms in seminar
  In order to test seminar room search
  As a site manager
  I need to create the rooms and search in the room search dialog box

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I navigate to "Rooms" node in "Site administration > Seminars"
    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 1          |
      | Building          | That house      |
      | Address           | 123 here street |
      | Room capacity     | 5               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

    And I press "Add a new room"
    And I set the following fields to these values:
      | Name              | Room 2          |
      | Building          | Your house      |
      | Address           | 123 near street |
      | Room capacity     | 6               |
    And I click on "#id_customfield_locationsize_medium" "css_element"
    And I click on "#id_customfield_locationview_satellite" "css_element"
    And I click on "#id_customfield_locationdisplay_map" "css_element"
    And I press "Add a room"

  Scenario: Try and search a room in seminar
    Given I am on "Course 1" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Select room" "link"
    And I click on "Search" "link" in the "Choose a room" "totaradialogue"

    And I search for "Room 1" in the "Choose a room" totara dialogue
    Then I should see "Room 1 (Capacity: 5)"
    And I should not see "Room 2 (Capacity: 6)"

    And I search for "Room 2" in the "Choose a room" totara dialogue
    Then I should see "Room 2 (Capacity: 6)"
    And I should not see "Room 1 (Capacity: 5)"

    And I search for "Room" in the "Choose a room" totara dialogue
    Then I should see "Room 1 (Capacity: 5)"
    And I should see "Room 2 (Capacity: 6)"
    And I click on "Room 1 (Capacity: 5)" "text" in the "Choose a room" "totaradialogue"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    And I press "Save changes"

  Scenario: Check paginator works as expected
    Given the following "global rooms" exist in "mod_facetoface" plugin:
      | name       |
      | Room 102   |
      | Room 1021  |
      | Room 1022  |
      | Room 1023  |
      | Room 1024  |
      | Room 1025  |
      | Room 1026  |
      | Room 1027  |
      | Room 1028  |
      | Room 1029  |
      | Room 10210 |
      | Room 10211 |
      | Room 10212 |
      | Room 10213 |
      | Room 10214 |
      | Room 10215 |
      | Room 10216 |
      | Room 10217 |
      | Room 10218 |
      | Room 10219 |
      | Room 10220 |
      | Room 10221 |
      | Room 10222 |
      | Room 10223 |
      | Room 10224 |
      | Room 10225 |
      | Room 10226 |
      | Room 10227 |
      | Room 10228 |
      | Room 10229 |
      | Room 10230 |
      | Room 10231 |
      | Room 10232 |
      | Room 10233 |
      | Room 10234 |
      | Room 10235 |
      | Room 10236 |
      | Room 10237 |
      | Room 10238 |
      | Room 10239 |
      | Room 10240 |
      | Room 10241 |
      | Room 10242 |
      | Room 10243 |
      | Room 10244 |
      | Room 10245 |
      | Room 10246 |
      | Room 10247 |
      | Room 10248 |
      | Room 10249 |
      | Room 10250 |
      | Room 10251 |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 2 | C2        | 0        |
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"

    # Making sure there are results instead of an error. String order is made.
    When I click on "Select room" "link"
    And I click on "Search" "link" in the "Choose a room" "totaradialogue"
    And I search for "Room 102" in the "Choose a room" totara dialogue
    Then I should see "Room 102"
    And I should see "Room 1021"
    And I should not see "Room 1028"

    When I click on "Next" "link"
    Then I should see "Room 1028"
    And I should not see "Room 1021"

    When I click on "Previous" "link"
    Then I should see "Room 1021"

    When I click on "2" "link" in the ".paging" "css_element"
    Then I should see "Room 1028"
    And I should not see "Room 1021"

  Scenario: Check that Search populates room name that was not on first Browse page
    Given the following "global rooms" exist in "mod_facetoface" plugin:
      | name        | capacity |
      | Room 102    | 50       |
      | Room 1021   | 50       |
      | Room 1022   | 50       |
      | Room 1023   | 50       |
      | Room 1024   | 50       |
      | Room 1025   | 50       |
      | Room 1026   | 50       |
      | Room 1027   | 50       |
      | Room 1028   | 50       |
      | Room 1029   | 50       |
      | Room 10210  | 50       |
      | Room 10211  | 50       |
      | Room 10212  | 50       |
      | Room 10213  | 50       |
      | Room 10214  | 50       |
      | Room 10215  | 50       |
      | Room 10216  | 50       |
      | Room 10217  | 50       |
      | Room 10218  | 50       |
      | Room 10219  | 50       |
      | Room 10220  | 50       |
      | Room 10221  | 50       |
      | Room 10222  | 50       |
      | Room 10223  | 50       |
      | Room 10224  | 50       |
      | Room 10225  | 50       |
      | Room 10226  | 50       |
      | Room 10227  | 50       |
      | Room 10228  | 50       |
      | Room 10229  | 50       |
      | Room 10230  | 50       |
      | Room 10231  | 50       |
      | Room 10232  | 50       |
      | Room 10233  | 50       |
      | Room 10234  | 50       |
      | Room 10235  | 50       |
      | Room 10236  | 50       |
      | Room 10237  | 50       |
      | Room 10238  | 50       |
      | Room 10239  | 50       |
      | Room 10240  | 50       |
      | Room 10241  | 50       |
      | Room 10242  | 50       |
      | Room 10243  | 50       |
      | Room 10244  | 50       |
      | Room 10245  | 50       |
      | Room 10246  | 50       |
      | Room 10247  | 50       |
      | Room 10248  | 50       |
      | Room 10249  | 50       |
      | Room 10250  | 50       |
      | Room 10251  | 50       |
      | Room 10260  | 50       |
      | Room 10261  | 50       |
      | Room 10262  | 50       |
      | Room 10263  | 50       |
      | Room 10264  | 50       |
      | Room 10265  | 50       |
      | Room 10266  | 50       |
      | Room 10267  | 50       |
      | Room 10268  | 50       |
      | Room 10269  | 50       |
      | Room 10270  | 50       |
      | Room 10271  | 50       |
      | Room 10272  | 50       |
      | Room 10273  | 50       |
      | Room 10274  | 50       |
      | Room 10275  | 50       |
      | Room 10276  | 50       |
      | Room 10277  | 50       |
      | Room 10278  | 50       |
      | Room 10279  | 50       |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 2 | C2        | 0        |
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"

    # Find a previously undisplayed room using a partial search criteria.
    When I click on "Select room" "link"
    And I click on "Search" "link" in the "Choose a room" "totaradialogue"
    And I search for "Room 1027" in the "Choose a room" totara dialogue
    Then I should see "Room 10278 (Capacity: 50)"
    # Select the room and check that underlying page updates correctly.
    When I click on "Room 10278 (Capacity: 50)" "link" in the "//div[contains(@id,'search-tab')]" "xpath_element"
    And I click on "OK" "button" in the "Choose a room" "totaradialogue"
    Then I should see "Room 10278"
    When I press "Save changes"
    Then I should see "Room 10278" in the "//div[@class='upcomingsessionlist']" "xpath_element"
