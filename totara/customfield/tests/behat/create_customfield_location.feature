@totara @totara_customfield @javascript
Feature: Administrators can add a custom location field to complete during room / session creation
  In order for the custom field to appear during room creation
  As admin
  I need to select the location custom field and add the relevant settings

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | catalogtype | enhanced |

    # Set up first custom field
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"
    When I set the field "datatype" to "Location"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Full name         | Custom Location Field 1 |
      | Short name        | clf1                    |
      | Default Address   | 21 Royal Avenue Belfast |
      | id_size_small     | 1                       |
      | id_view_satellite | 1                       |
      | id_display_both   | 1                       |
    And I press "Use Address"
    And I press "Save changes"

    # And a second field
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"
    When I set the field "datatype" to "Location"
    And I expand all fieldsets
    When I set the following fields to these values:
      | Full name          | Custom Location Field 2   |
      | Short name         | clf2                      |
      | Default Address    | 150 Willis st, Wellington |
      | id_size_large      | 1                         |
      | id_view_map        | 1                         |
      | id_display_address | 1                         |
    And I click on "Use Address" "button"
    And I click on "Save changes" "button"

  Scenario: Test custom location field
    Given I click on "Courses" in the totara menu
    And I click on "Create Course" "button"
    And I expand all fieldsets
    # Check the defaults
    And the following fields match these values:
      | id_customfield_clf1address         | 21 Royal Avenue Belfast   |
      | id_customfield_clf1size_small      | 1                         |
      | id_customfield_clf1view_satellite  | 1                         |
      | id_customfield_clf1display_both    | 1                         |
      | id_customfield_clf2address         | 150 Willis st, Wellington |
      | id_customfield_clf2size_large      | 1                         |
      | id_customfield_clf2view_map        | 1                         |
      | id_customfield_clf2display_address | 1                         |

    When I set the following fields to these values:
      | Course full name               | Course 1                 |
      | Course short name              | c1                       |
      | id_customfield_clf2address     | 150 Cameron Rd, Tauranga |
      | id_customfield_clf2size_medium | 1                        |
      | id_customfield_clf2view_hybrid | 1                        |
      | id_customfield_clf2display_map | 1                        |
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then the following fields match these values:
      | id_customfield_clf1address         | 21 Royal Avenue Belfast  |
      | id_customfield_clf1size_small      | 1                        |
      | id_customfield_clf1view_satellite  | 1                        |
      | id_customfield_clf1display_both    | 1                        |
      | id_customfield_clf2address         | 150 Cameron Rd, Tauranga |
      | id_customfield_clf2size_large      |                          |
      | id_customfield_clf2view_map        |                          |
      | id_customfield_clf2display_address |                          |
      | id_customfield_clf2size_medium     | 1                        |
      | id_customfield_clf2view_hybrid     | 1                        |
      | id_customfield_clf2display_map     | 1                        |

    When I set the following fields to these values:
      | id_customfield_clf1address      | 150 Victoria St, Wellington |
      | id_customfield_clf1size_large   | 1                           |
      | id_customfield_clf1view_map     | 1                           |
      | id_customfield_clf1display_both | 1                           |
    And I click on "Save and display" "button"
    And I navigate to "Edit settings" node in "Course administration"
    Then the following fields match these values:
      | id_customfield_clf1address         | 150 Victoria St, Wellington |
      | id_customfield_clf1size_small      |                             |
      | id_customfield_clf1view_satellite  |                             |
      | id_customfield_clf1display_both    |                             |
      | id_customfield_clf1size_large      | 1                           |
      | id_customfield_clf1view_map        | 1                           |
      | id_customfield_clf1display_both    | 1                           |
      | id_customfield_clf2address         | 150 Cameron Rd, Tauranga    |
      | id_customfield_clf2size_large      |                             |
      | id_customfield_clf2view_map        |                             |
      | id_customfield_clf2display_address |                             |
      | id_customfield_clf2size_medium     | 1                           |
      | id_customfield_clf2view_hybrid     | 1                           |
      | id_customfield_clf2display_map     | 1                           |

  Scenario: Confirm invalid location displays error message
    Given I click on "Edit" "link" in the "Custom Location Field 1" "table_row"
    And I set the field "addresslookup" to "abcdefghijklmnopqr"
    And I click on "Search" "button" in the "Set map location" "fieldset"
    Then I should see "Location not found" in the "Set map location" "fieldset"

    When I set the field "addresslookup" to "150 Willis st"
    And I click on "Search" "button" in the "Set map location" "fieldset"
    # Google maps without applied key will not work.
    #Then I should not see "Location not found" in the "Set map location" "fieldset"