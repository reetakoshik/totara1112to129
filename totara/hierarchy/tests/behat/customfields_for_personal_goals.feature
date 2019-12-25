@totara @totara_hierarchy @javascript @totara_customfield
Feature: Test I can manage custom fields for personal goal types
  In order to test custom fields for personal goal types
  I log in as an admin
  And I create types and custom fields

  Scenario: Test I can create and sort personal goal type custom fields
    When I log in as "admin"
    And I navigate to "Manage personal goal types" node in "Site administration > Goals"
    And I press "Add a new personal goal type"
    And I set the following fields to these values:
      | Type full name      | Self-improvement goals |
      | Goal type ID number | SG                     |
    And I press "Save changes"
    Then I should see "The goal type \"Self-improvement goals\" has been created"

    When I click on "Self-improvement goals" "link"
    Then I should see "No fields have been defined"

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | User classification |
      | Short name (must be unique) | Classification      |
    And I press "Save changes"
    Then I should see "User classification"
    And I should see "Text input"
    And I should not be able to move the "User classification" Totara custom field up
    And I should not be able to move the "User classification" Totara custom field down

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Team development lead |
      | Short name (must be unique) | Dev lead              |
    And I press "Save changes"
    Then I should see "User classification"
    And I should see "Team development lead"
    And I should not be able to move the "User classification" Totara custom field up
    And I should be able to move the "User classification" Totara custom field down
    And I should be able to move the "Team development lead" Totara custom field up
    And I should not be able to move the "Team development lead" Totara custom field down

    When I click to move the "Team development lead" Totara custom field up
    Then I should be able to move the "User classification" Totara custom field up
    And I should not be able to move the "User classification" Totara custom field down
    And I should not be able to move the "Team development lead" Totara custom field up
    And I should be able to move the "Team development lead" Totara custom field down

    When I click on "All types" "link"
    And I press "Add a new personal goal type"
    And I set the following fields to these values:
      | Type full name      | Experience goals |
      | Goal type ID number | EG               |
    And I press "Save changes"
    Then I should see "The goal type \"Experience goals\" has been created"
    And I should see "Self-improvement goals"
    And I should see "Experience goals"

    When I click on "Experience goals" "link"
    Then I should see "No fields have been defined"

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Team classification |
      | Short name (must be unique) | Classification      |
    And I press "Save changes"
    Then I should see "Team classification"
    And I should see "Text input"
    And I should not be able to move the "Team classification" Totara custom field up
    And I should not be able to move the "Team classification" Totara custom field down

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Estimated required time |
      | Short name (must be unique) | Required time           |
    And I press "Save changes"
    Then I should see "Team classification"
    And I should see "Estimated required time"
    And I should not be able to move the "Team classification" Totara custom field up
    And I should be able to move the "Team classification" Totara custom field down
    And I should be able to move the "Estimated required time" Totara custom field up
    And I should not be able to move the "Estimated required time" Totara custom field down

    When I click to move the "Estimated required time" Totara custom field up
    Then I should be able to move the "Team classification" Totara custom field up
    And I should not be able to move the "Team classification" Totara custom field down
    And I should not be able to move the "Estimated required time" Totara custom field up
    And I should be able to move the "Estimated required time" Totara custom field down