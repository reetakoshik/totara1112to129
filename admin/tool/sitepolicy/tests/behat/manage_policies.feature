@javascript @tool @tool_sitepolicy @totara
Feature: Manage sitepolicies
  As an admin
  I want to manage site policies

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I log out

  Scenario: Create, update and delete a draft sitepolicy
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

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

    When I follow "1 new version (draft)"
    And I set the following fields to these values:
      | Title                             | Test policy                            |
      | Policy statement                  | Policy statement for the test policy   |
      | Consent statement                 | The statement the user must consent to |
      | Provide consent label             | Agree                                  |
      | Withhold consent label            | Disagree                               |
      | Consent required to use this site | 1                                      |
    And I press "Save"
    Then I should see "Site policies"
    And I should see "\"Test policy\" has been saved"

    When I follow "Test policy"
    Then I should see "Manage \"Test policy\" policy"
    And the "generaltable" table should contain the following:
      | Version | Status | # Translations | Date published | Date archived |
      | 1       | Draft  | 1 View         | -              | -             |
    And I should see "Publish" in the "1" "table_row"
    And I should see "Edit" in the "1" "table_row"
    And I should see "Delete" in the "1" "table_row"

    When I follow "Delete"
    Then I should see "Are you sure you want to delete policy \"Test policy\""
    When I press "Delete"
    Then I should see "Policy \"Test policy\" has been deleted successfully"
    And I should see "No policies created"

  Scenario: Create, publish and archive a sitepolicy
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | Test policy                            |
      | Policy statement                  | Policy statement for the test policy   |
      | Consent statement                 | The statement the user must consent to |
      | Provide consent label             | Agree                                  |
      | Withhold consent label            | Disagree                               |
      | Consent required to use this site | 1                                      |
    And I press "Save"
    Then I should see "1 new version (draft)" in the "Test policy" "table_row"

    When I follow "Test policy"
    Then I should see "Manage \"Test policy\" policy"
    And the "generaltable" table should contain the following:
      | Version | Status | # Translations | Date published | Date archived |
      | 1       | Draft  | 1 View         | -              | -             |
    And I should see "Publish" in the "1" "table_row"
    And I should see "Edit" in the "1" "table_row"
    And I should see "Delete" in the "1" "table_row"
    And I should not see "Archive" in the "1" "table_row"

    When I follow "Publish"
    Then I should see "Are you sure you want to publish \"Test policy\""
    When I press "Publish"
    Then I should see "Version 1 of \"Test policy\" has been published successfully"
    And the "generaltable" table should contain the following:
      | Version | Status     | # Translations |
      | 1       | Published  | 1 View         |
    And I should not see "Edit" in the "1" "table_row"
    And I should not see "Delete" in the "1" "table_row"
    And I should see "Archive" in the "1" "table_row"

    When I follow "Archive"
    Then I should see "Are you sure you want to archive version 1 of \"Test policy\""
    When I press "Archive"
    Then I should see "Version 1 of \"Test policy\" has been archived successfully"
    And the "generaltable" table should contain the following:
      | Version | Status     | # Translations | Actions |
      | 1       | Archived   | 1 View         |         |

  Scenario: Continue editing a site policy
    Given the following "draftpolicies" exist in "tool_sitepolicy" plugin:
      | title    | languages | statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | Policy 1 | en        | Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | first     |
    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "0" in the "Policy 1" "table_row"
    And I should see "1 new version (draft)" in the "Policy 1" "table_row"

    When I follow "1 new version (draft)"
    And the following fields match these values:
      | Primary language         | en                       |
      | Title                    | Policy 1                 |
      | Policy statement         | Policy 1 statement       |
      | statements__statement[0] | P1 - Consent statement 1 |
      | statements__provided[0]  | Yes                      |
      | statements__withheld[0]  | No                       |
      | statements__mandatory[0] | 1                        |
    And I set the following fields to these values:
      | Title                    | Acceptable use policy     |
    And I press "Save"
    Then I should see "\"Acceptable use policy\" has been saved"
    And I should see "Site policies"

