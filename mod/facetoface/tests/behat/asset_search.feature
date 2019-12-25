@mod @mod_facetoface @totara @javascript
Feature: Search pre-defined assets in seminar
  In order to test seminar asset search
  As a site manager
  I need to create the assets and search in the asset search dialog box

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And I log in as "admin"
    And I navigate to "Assets" node in "Site administration > Seminars"
    And I press "Add a new asset"
    And I set the following fields to these values:
      | Asset name | Basset Asset |
    And I press "Add an asset"
    Then I should see "Basset Asset"

  Scenario: Check that Search populates asset name that was not on first Browse page
    Given the following "global assets" exist in "mod_facetoface" plugin:
      | name         |
      | Asset 102    |
      | Asset 1021   |
      | Asset 1022   |
      | Asset 1023   |
      | Asset 1024   |
      | Asset 1025   |
      | Asset 1026   |
      | Asset 1027   |
      | Asset 1028   |
      | Asset 1029   |
      | Asset 10210  |
      | Asset 10211  |
      | Asset 10212  |
      | Asset 10213  |
      | Asset 10214  |
      | Asset 10215  |
      | Asset 10216  |
      | Asset 10217  |
      | Asset 10218  |
      | Asset 10219  |
      | Asset 10220  |
      | Asset 10221  |
      | Asset 10222  |
      | Asset 10223  |
      | Asset 10224  |
      | Asset 10225  |
      | Asset 10226  |
      | Asset 10227  |
      | Asset 10228  |
      | Asset 10229  |
      | Asset 10230  |
      | Asset 10231  |
      | Asset 10232  |
      | Asset 10233  |
      | Asset 10234  |
      | Asset 10235  |
      | Asset 10236  |
      | Asset 10237  |
      | Asset 10238  |
      | Asset 10239  |
      | Asset 10240  |
      | Asset 10241  |
      | Asset 10242  |
      | Asset 10243  |
      | Asset 10244  |
      | Asset 10245  |
      | Asset 10246  |
      | Asset 10247  |
      | Asset 10248  |
      | Asset 10249  |
      | Asset 10250  |
      | Asset 10251  |
      | Asset 10260  |
      | Asset 10261  |
      | Asset 10262  |
      | Asset 10263  |
      | Asset 10264  |
      | Asset 10265  |
      | Asset 10266  |
      | Asset 10267  |
      | Asset 10268  |
      | Asset 10269  |
      | Asset 10270  |
      | Asset 10271  |
      | Asset 10272  |
      | Asset 10273  |
      | Asset 10274  |
      | Asset 10275  |
      | Asset 10276  |
      | Asset 10277  |
      | Asset 10278  |
      | Asset 10279  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 2 | C2        | 0        |
    And I am on "Course 2" course homepage with editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Test seminar name        |
      | Description | Test seminar description |
    And I follow "View all events"
    And I follow "Add a new event"

    # Find a previously undisplayed asset using a partial search criteria.
    When I click on "Select assets" "link"
    And I click on "Search" "link" in the "Choose assets" "totaradialogue"
    And I search for "Bass" in the "Choose assets" totara dialogue
    Then I should see "Basset Asset"
    # Select the asset and check that underlying page updates correctly.
    When I click on "Basset Asset" "link" in the "//div[contains(@id,'search-tab')]" "xpath_element"
    And I click on "OK" "button" in the "Choose assets" "totaradialogue"
    Then I should see "Basset Asset"
    When I press "Save changes"
    And I click on "Edit event" "link"
    Then I should see "Basset Asset" in the "//div[@id='fitem_id_sessiondates']" "xpath_element"
