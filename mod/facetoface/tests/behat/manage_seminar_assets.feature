@mod @mod_facetoface @totara
Feature: Ability to view the seminar's asset, even though the asset is being used in an on-going seminar's event

  Background: Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | category |
      | course101 | c101      | 0        |
    And the following "global assets" exist in "mod_facetoface" plugin:
      | name   |
      | asset1 |

  @javascript
  Scenario: Seminar asset is displaying within the report builder
    even though the asset is being used
    by an on-going seminar's event
    Given I log in as "admin"
    And I am on "course101" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1             |
      | Description | This is description 1 |
    And I follow "Seminar 1"
    And I follow "Add a new event"
    And I follow "Select assets"
    And I follow "asset1"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[month]   | 0                |
      | timestart[day]     | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | 0                |
      | timestart[minute]  | -5               |
      | timefinish[month]  | 0                |
      | timefinish[day]    | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | +5               |
    And I click on "OK" "button" in the "Select date" "totaradialogue"
    And I click on "Save changes" "button"
    And I should see "Event in progress"
    And I navigate to "Reports > Manage user reports" in site administration
    And I click on "Create report" "button"
    And I set the following fields to these values:
      | fullname | Asset Session    |
      | source   | Seminar Sessions |
    And I click on "Create report" "button"
    And I click on "Columns" "link"
    And I set the field "newcolumns" to "Asset Name"
    And I click on "Add" "button"
    And I click on "Save changes" "button"
    And I click on "Filters" "link"
    And I set the field "newstandardfilter" to "Asset Availability"
    And I click on "Add" "button"
    And I click on "Save changes" "button"
    And I navigate to "Seminars > Assets" in site administration
    Then I should see "asset1"
    And I click on "Reports" in the totara menu
    And I follow "Asset Session"
    And I should see "asset1"
