@javascript @totara @totara_plan
Feature: Evidence types.

  Background:
    Given I am on a totara site

  Scenario: As an admin I create, view and edit evidence types.

    Given I log in as "admin"

    # Add evidence type.
    When I navigate to "Evidence types" node in "Site administration > Learning Plans"
    And I click on "Add a new Evidence type" "button"
    And I set the following fields to these values:
      | Name         | Evidence type 1                   |
      | Description  | Evidence type 1 description...    |
    And I press "Save changes"
    Then I should see "Evidence type \"Evidence type 1\" added"
    And I should see "Evidence type 1"

    # View evidence type.
    When I follow "Evidence type 1"
    Then I should see "Evidence type 1"
    And I should see "Evidence type 1 description..."
    When I click on "All evidence types" "button"

    # Edit evidence type.
    And I click on "Edit" "link" in the "Evidence type 1" "table_row"
    Then I should see "Evidence type 1"
    And I should see "Evidence type 1 description..."
    When I set the following fields to these values:
      | Name         | Evidence type 1 (edited)                   |
      | Description  | Evidence type 1 description... (edited)    |
    And I press "Save changes"
    Then I should see "Evidence type \"Evidence type 1 (edited)\" updated"
    And I should see "Evidence type 1 (edited)"
    When I follow "Evidence type 1 (edited)"
    Then I should see "Evidence type 1 (edited)"
    And I should see "Evidence type 1 description... (edited)"
    When I click on "All evidence types" "button"

    # Delete evidence type.
    And I click on "Delete" "link" in the "Evidence type 1" "table_row"
    Then I should see "Are you absolutely sure you want to completely delete this Evidence type?"
    And I should see "Evidence type 1 (edited)"
    When I click on "Continue" "button"
    Then I should see "The Evidence type \"Evidence type 1 (edited)\" has been completely deleted."
