@javascript @tool @tool_sitepolicy @totara
Feature: Manage sitepolicy versions
  As an admin
  I want to manage multiple versions of a site policy

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I log out

  Scenario: Add and publish a new version to a single language sitepolicy
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages | langprefix | statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 1 | en        |            | Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | first     |

    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then the "generaltable" table should contain the following:
      | Name     | Revisions | Status    |
      | Policy 1 | 1         | Published |

    When I follow "Policy 1"
    Then the "generaltable" table should contain the following:
      | Version  | Status    | # Translations | Actions |
      | 1        | Published | 1 View         | Archive |
    And "Create new version" "button" should exist

    When I follow "View"
    Then I should see "English"

    When I follow "Back to all versions"
    And I press "Create new version"
    And I set the following fields to these values:
      | Primary language                         | en                           |
      | Title                                    | New version                  |
      | Policy statement                         | New version policy Statement |
      | Consent statement                        | New consert statement        |
      | Provide consent label                    | Yes                          |
      | Withhold consent label                   | No                           |
      | Consent required to use this site        | 1                            |
      | Changes since previous published version | Made a new version           |
    And I press "Save"
    Then I should see "Version (2) has been saved"
    And I should see "Manage \"New version\" policy"
    And the "generaltable" table should contain the following:
      | Version | Status    | # Translations |
      | 2       | Draft     | 1 View         |
      | 1       | Published | 1 View         |
    And I should see "Publish" in the "Draft" "table_row"
    And I should see "Edit" in the "Draft" "table_row"
    And I should see "Delete" in the "Draft" "table_row"
    And I should see "Archive" in the "Published" "table_row"

    When I follow "Publish"
    Then I should see "Are you sure you want to publish \"New version\""
    When I press "Publish"
    Then I should see "Version 2 of \"New version\" has been published successfully"
    And I should see "Manage \"New version\" policy"
    And the "generaltable" table should contain the following:
      | Version | Status    | # Translations |
      | 2       | Published | 1 View         |
      | 1       | Archived  | 1 View         |


  Scenario: Add a new version to a multilingual sitepolicy
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages | langprefix | statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 0        | 1            | 0           | Policy 2 | en,nl,fr  | ,nl ,fr    | Policy 2 statement | 2          | P2 - Consent statement | Yes         | No           | first     |
    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then the "generaltable" table should contain the following:
      | Name     | Revisions | Status    |
      | Policy 2 | 1         | Published |

    When I follow "Policy 2"
    Then the "generaltable" table should contain the following:
      | Version  | Status    | # Translations | Actions |
      | 1        | Published | 3 View         | Archive |
    And "Create new version" "button" should exist

    When I follow "View"
    Then I should see "English"
    And I should see "Dutch; Flemish"
    And I should see "French"

    When I follow "Back to all versions"
    And I press "Create new version"
    And I set the following fields to these values:
      | Primary language                         | en                           |
      | Title                                    | New version                  |
      | Policy statement                         | New version policy Statement |
      | Consent statement                        | New consert statement        |
      | Provide consent label                    | Yes                          |
      | Withhold consent label                   | No                           |
      | Consent required to use this site        | 1                            |
      | Changes since previous published version | Made a new version           |
    And I press "Save"
    Then I should see "Manage \"New version\" policy"
    And I should see "Version (2) has been saved"
    And the "generaltable" table should contain the following:
      | Version | Status    | # Translations |
      | 2       | Draft     | 1 View         |
      | 1       | Published | 3 View         |
