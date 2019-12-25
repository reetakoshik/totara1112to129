@javascript @tool @tool_sitepolicy @totara @language_packs
Feature: Manage sitepolicy translations when site only has primary language
  As an admin
  I want to manage multiple translations of a site policy but site only has one language

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I log out

  # This is a negative test, so narrative can appear to be "silly".
  Scenario: Manage the translation of a draft sitepolicy when no other language packs are installed
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

    # Create a new sitepolicy with default primary language.
    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | A translatable policy     |
      | Policy statement                  | A policy statement        |
      | Consent statement                 | A statement to consent to |
      | Provide consent label             | Yes                       |
      | Withhold consent label            | No                        |
      | Consent required to use this site | 1                         |
    And I press "Save"
    Then I should see "New policy (A translatable policy) has been saved"
    And I should see "1 new version (draft)" in the "A translatable policy" "table_row"

    # See that we are unable to translate due to no other language packs currently installed.
    When I follow "A translatable policy"
    Then I should see "View" in the "1" "table_row"
    When I follow "View"
    Then the "title" attribute of "language" "select" should contain "No languages found. To add translation you must first install the desired languages. Go to 'Language packs' or contact your system administrator."

    # Delete the test policy.
    When I follow "Back to all versions"
    Then I should see "Manage \"A translatable policy\" policy"
    And I should see "Delete" in the "1" "table_row"
    When I follow "Delete"
    Then I should see "Are you sure you want to delete policy \"A translatable policy\""
    When I press "Delete"
    Then I should see "Policy \"A translatable policy\" has been deleted successfully"
