@javascript @tool @tool_sitepolicy @totara @language_packs
Feature: Manage sitepolicy primary language
  As an admin
  I want to manage the primary language of a site policy

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I fake the Dutch language pack is installed for site policies
    And I log out

  Scenario: Manage the primary language of a draft sitepolicy
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

    # Create a new sitepolicy with default primary language.
    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | A policy                  |
      | Policy statement                  | A policy statement        |
      | Consent statement                 | A statement to consent to |
      | Provide consent label             | Yes                       |
      | Withhold consent label            | No                        |
      | Consent required to use this site | 1                         |
    And I press "Save"
    Then I should see "New policy (A policy) has been saved"
    And I should see "1 new version (draft)" in the "A policy" "table_row"

    # Change primary language.
    When I follow "1 new version (draft)"
    And I set the following fields to these values:
      | Primary language                  | nl                                     |
      | Title                             | Test policy                            |
      | Policy statement                  | Policy statement for the test policy   |
      | Consent statement                 | The statement the user must consent to |
      | Provide consent label             | Agree                                  |
      | Withhold consent label            | Disagree                               |
      | Consent required to use this site | 1                                      |
    And I press "Save"
    Then I should see "Site policies"
    And I should see "\"Test policy\" has been saved"

    # Confirm that changed language has been stored.
    When I follow "1 new version (draft)"
    Then I should see "Manage policies"
    When I follow "Manage policies"
    Then I should see "1 new version (draft)" in the "Test policy" "table_row"
    When I follow "Test policy"
    Then I should see "View" in the "1" "table_row"
    And I should see "Publish" in the "1" "table_row"
    And I should see "Edit" in the "1" "table_row"
    And I should see "Delete" in the "1" "table_row"
    When I follow "View"
    Then I should see "Nederlands ‎(nl)‎ (primary)"

    # Delete the test policy.
    When I follow "Back to all versions"
    Then I should see "Manage \"Test policy\" policy"
    And I should see "Delete" in the "1" "table_row"
    When I follow "Delete"
    Then I should see "Are you sure you want to delete policy \"Test policy\""
    When I press "Delete"
    Then I should see "Policy \"Test policy\" has been deleted successfully"
