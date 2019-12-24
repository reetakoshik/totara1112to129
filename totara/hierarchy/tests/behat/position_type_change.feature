@totara @totara_hierarchy @totara_hierarchy_position @totara_customfield @javascript
Feature: Test position type changes in hierarchies

  Scenario: Change type of position in a hierarchy
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | manager   | Site      | Manager   | manager@example.com   |
    And the following "role assigns" exist:
      | user      | role      | contextlevel | reference |
      | manager   | manager   | System       |           |
    And I log in as "manager"
    And I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
    And I press "Add a new type"
    And I set the following fields to these values:
    | Type full name | Position type 1 |
    And I press "Save changes"
    And I follow "Position type 1"
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Custom field 1_1 |
      | Short name (must be unique) | CF1_1            |
    And I press "Save changes"
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Custom field 1_2 |
      | Short name (must be unique) | CF1_2            |
    And I press "Save changes"
    And I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | Position type 2 |
    And I press "Save changes"
    And I follow "Position type 2"
    And I set the field "Create a new custom field" to "Text input"
    And I set the following fields to these values:
      | Full name                   | Custom field 2_1 |
      | Short name (must be unique) | CF2_1            |
    And I press "Save changes"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I press "Add new position framework"
    And I set the following fields to these values:
      | Name | My position frameworkd 1 |
    And I press "Save changes"
    And I follow "My position frameworkd 1"
    And I press "Add new position"
    And I set the following fields to these values:
      | Name | My position 1 |
      | Type | Position type 1     |
    And I press "Save changes"
    And I press "Return to position framework"
    And I click on "Edit" "link" in the "My position 1" "table_row"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 1 |
      | Custom field 1_2 | Some text 2 |
    And I press "Save changes"
    And I press "Return to position framework"
    And I press "Add new position"
    And I set the following fields to these values:
      | Name | My position 2 |
      | Type | Position type 1     |
    And I press "Save changes"
    And I press "Return to position framework"
    And I click on "Edit" "link" in the "My position 2" "table_row"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 3 |
      | Custom field 1_2 | Some text 4 |
    And I press "Save changes"
    And I press "Return to position framework"
    And I press "Add new position"
    And I set the following fields to these values:
      | Name | My position 3 |
      | Type | Position type 1     |
    And I press "Save changes"
    And I press "Return to position framework"
    And I click on "Edit" "link" in the "My position 3" "table_row"
    And I set the following fields to these values:
      | Custom field 1_1 | Some text 5 |
      | Custom field 1_2 | Some text 6 |
    And I press "Save changes"
    And I press "Return to position framework"
    And I should see "Type: Position type 1" in the "My position 1" "table_row"
    And I should see "Type: Position type 1" in the "My position 2" "table_row"
    And I should see "Type: Position type 1" in the "My position 3" "table_row"

    # Change type of single item
    When I click on "Edit" "link" in the "My position 1" "table_row"
    And I press "Change type"
    And I click on "Choose" "button" in the "Position type 2" "table_row"
    And I set the following fields to these values:
      | Data in Custom field 1_1 (Text input): | Transfer to Custom field 2_1 (Text input) |
      | Data in Custom field 1_2 (Text input): | Delete this data                          |
    And I press "Reclassify items and transfer/delete data"
    Then the field "Custom field 2_1" matches value "Some text 1"
    And I press "Save changes"
    And I press "Return to position framework"
    And I should see "Type: Position type 2" in the "My position 1" "table_row"
    And I should see "Type: Position type 1" in the "My position 2" "table_row"
    And I should see "Type: Position type 1" in the "My position 3" "table_row"

    # Bulk change types
    When I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
    And I set the following fields to these values:
      | Reclassify of all items from the type: | Position type 1 |
    And I click on "Choose" "button" in the "Position type 2" "table_row"
    And I set the following fields to these values:
      | Data in Custom field 1_1 (Text input): | Transfer to Custom field 2_1 (Text input) |
      | Data in Custom field 1_2 (Text input): | Delete this data                          |
    When I press "Reclassify items and transfer/delete data"
    And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
    And I follow "My position frameworkd 1"
    And I should see "Type: Position type 2" in the "My position 1" "table_row"
    And I should see "Type: Position type 2" in the "My position 2" "table_row"
    And I should see "Type: Position type 2" in the "My position 3" "table_row"
