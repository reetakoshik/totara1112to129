@totara @totara_hierarchy @javascript @totara_customfield
Feature: Test I can manage custom fields for company goal types
  In order to test custom fields for company goal types
  I log in as an admin
  And I create types and custom fields

  Scenario: Test I can create and sort company goal type custom fields
    When I log in as "admin"
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
      | Type full name      | Development goals |
      | Goal type ID number | DG                |
    And I press "Save changes"
    Then I should see "The goal type \"Development goals\" has been created"

    When I click on "Development goals" "link"
    Then I should see "No fields have been defined"

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Development team |
      | Short name (must be unique) | Dev team         |
    And I press "Save changes"
    Then I should see "Development team"
    And I should see "Text input"
    And I should not be able to move the "Development team" Totara custom field up
    And I should not be able to move the "Development team" Totara custom field down

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Team development lead |
      | Short name (must be unique) | Dev lead              |
    And I press "Save changes"
    Then I should see "Development team"
    And I should see "Team development lead"
    And I should not be able to move the "Development team" Totara custom field up
    And I should be able to move the "Development team" Totara custom field down
    And I should be able to move the "Team development lead" Totara custom field up
    And I should not be able to move the "Team development lead" Totara custom field down

    When I click to move the "Team development lead" Totara custom field up
    Then I should be able to move the "Development team" Totara custom field up
    And I should not be able to move the "Development team" Totara custom field down
    And I should not be able to move the "Team development lead" Totara custom field up
    And I should be able to move the "Team development lead" Totara custom field down

    When I click on "All types" "link"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
      | Type full name      | Management goals |
      | Goal type ID number | MG               |
    And I press "Save changes"
    Then I should see "The goal type \"Management goals\" has been created"
    And I should see "Development goals"
    And I should see "Management goals"

    When I click on "Management goals" "link"
    Then I should see "No fields have been defined"

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Management team |
      | Short name (must be unique) | Managers        |
    And I press "Save changes"
    Then I should see "Management team"
    And I should see "Text input"
    And I should not be able to move the "Management team" Totara custom field up
    And I should not be able to move the "Management team" Totara custom field down

    When I set the field "datatype" to "Text input"
    And I wait "1" seconds
    And I should see "Editing custom field: Text input"
    And I set the following fields to these values:
      | Full name                   | Team Management lead |
      | Short name (must be unique) | Managers lead        |
    And I press "Save changes"
    Then I should see "Management team"
    And I should see "Team Management lead"
    And I should not be able to move the "Management team" Totara custom field up
    And I should be able to move the "Management team" Totara custom field down
    And I should be able to move the "Team Management lead" Totara custom field up
    And I should not be able to move the "Team Management lead" Totara custom field down

    When I click to move the "Team Management lead" Totara custom field up
    Then I should be able to move the "Management team" Totara custom field up
    And I should not be able to move the "Management team" Totara custom field down
    And I should not be able to move the "Team Management lead" Totara custom field up
    And I should be able to move the "Team Management lead" Totara custom field down