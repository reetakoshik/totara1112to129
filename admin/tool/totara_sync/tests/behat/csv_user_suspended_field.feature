@totara @tool @tool_totara_sync @javascript
Feature: Test HR Import user csv suspended field import.

  Background:
    Given I am on a totara site
    When I log in as "admin"
    And I navigate to "Default settings" node in "Site administration > HR Import"
    And I set the following fields to these values:
      | File access | Upload Files |
    And I press "Save changes"
    And I navigate to "Manage elements" node in "Site administration > HR Import > Elements"
    And I "Enable" the "User" HR Import element
    And I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | CSV | 1 |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."

    When I navigate to "CSV" node in "Site administration > HR Import > Sources > User"
    And I click on "Suspended" "checkbox"
    And I press "Save changes"
    Then I should see "Settings saved"
    And I should see "\"suspended\""

  Scenario Outline: Test the user csv suspended field imports correctly
    # Create a user for the test import.
    Given the following "users" exist:
      | username | firstname   | lastname    | email              | idnumber  | suspended         | totarasync |
      |  user1   |  firstname1 |  lastname1 |  user1@example.com  |  1        |  <user suspended> |  1         |

    # Set "Empty string behaviour in CSV" field.
    When I navigate to "User" node in "Site administration > HR Import > Elements"
    And I set the following fields to these values:
      | Empty string behaviour in CSV  | <empty string behaviour> |
    And I press "Save changes"
    Then I should see "Settings updated. The source settings for this element can be configured here."
    And I should see "<empty string behaviour>" in the "Empty string behaviour in CSV" "field"

    # Upload the sync source and run sync.
    When I navigate to "Upload HR Import files" node in "Site administration > HR Import > Sources"
    And I upload "admin/tool/totara_sync/tests/fixtures/<csv>" file to "CSV" filemanager
    And I press "Upload"
    Then I should see "HR Import files uploaded successfully"
    When I navigate to "Run HR Import" node in "Site administration > HR Import"
    And I press "Run HR Import"
    Then I should see "Running HR Import cron...Done!"
    And I should not see "However, there have been some problems"

    # Check the user has the correct suspended setting.
    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I set the field "user-deleted" to "any value"
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then the following should exist in the "system_browse_users" table:
      | Username | User's Email              | User Status                        |
      | user1    | user1@example.com         | <expected outcome suspended value> |


    Examples:
      | user suspended | empty string behaviour             | csv suspend value      | csv                   | expected outcome suspended value |
      |  0             |  Empty strings are ignored         |  0                     |  user_suspended_1.csv |  Active                          |
      |  0             |  Empty strings are ignored         |  1                     |  user_suspended_2.csv |  Suspended                       |
      |  0             |  Empty strings are ignored         |                        |  user_suspended_3.csv |  Active                          |

      |  0             |  Empty strings erase existing data |  0                     |  user_suspended_1.csv |  Active                          |
      |  0             |  Empty strings erase existing data |  1                     |  user_suspended_2.csv |  Suspended                       |
      |  0             |  Empty strings erase existing data |                        |  user_suspended_3.csv |  Active                          |

      |  1             |  Empty strings are ignored         |  0                     |  user_suspended_1.csv |  Active                          |
      |  1             |  Empty strings are ignored         |  1                     |  user_suspended_2.csv |  Suspended                       |
      |  1             |  Empty strings are ignored         |                        |  user_suspended_3.csv |  Suspended                       |

      |  1             |  Empty strings erase existing data |  0                     |  user_suspended_1.csv |  Active                          |
      |  1             |  Empty strings erase existing data |  1                     |  user_suspended_2.csv |  Suspended                       |
      |  1             |  Empty strings erase existing data |                        |  user_suspended_3.csv |  Active                          |
