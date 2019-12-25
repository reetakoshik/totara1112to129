@javascript @tool @tool_sitepolicy @totara
Feature: Preview sitepolicies
  As an admin
  I want to preview a site policy while editing

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I fake the French language pack is installed for site policies
    And I fake the Dutch language pack is installed for site policies
    And I log out

  Scenario: Preview and continue editing new sitepolicy
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | A policy                                                                                          |
      | Policy statement                  | <p>A test policy</p><p>With bullets</p><p><ul><li>One</li><li>Two and</li><li>Three</li></ul></p> |
      | Consent statement                 | A statement to consent to                                                                         |
      | Provide consent label             | Yes                                                                                               |
      | Withhold consent label            | No                                                                                                |
      | Consent required to use this site | 1                                                                                                 |
    And I press "Preview"
    Then I should not see "Create new policy"
    And I should see "This is a preview of how the policy will appear"
    And "Continue editing" "button" should exist
    When I press "Continue editing"
    Then I should see "Create new policy"

  Scenario: Save new sitepolicy from preview
    Given I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "No policies created"
    And "Create new policy" "button" should exist

    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | A policy                                                                                          |
      | Policy statement                  | <p>A test policy</p><p>With bullets</p><p><ul><li>One</li><li>Two and</li><li>Three</li></ul></p> |
      | Consent statement                 | A statement to consent to                                                                         |
      | Provide consent label             | Yes                                                                                               |
      | Withhold consent label            | No                                                                                                |
      | Consent required to use this site | 1                                                                                                 |
    And I press "Preview"
    Then I should not see "Create new policy"
    And I should see "This is a preview of how the policy will appear"
    When I press "Save"
    Then I should see "New policy (A policy) has been saved"

  Scenario: Preview a new sitepolicy translation
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages | langprefix | statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 1        | 1            | 0           | Policy 1 | en        |            | Policy 1 statement | 1          | P1 - Consent statement | Yes         | No           | first     |

    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then the "generaltable" table should contain the following:
      | Name     | Status    |
      | Policy 1 | Draft     |
    And I should see "1 new version (draft)" in the "Policy 1" "table_row"

    When I follow "Policy 1"
    Then I should see "Manage \"Policy 1\" policy"
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 2        | Draft     | 1 View         |
      | 1        | Published | 1 View         |
    And "Continue editing new version" "button" should exist

    When I click on "View" "link" in the "Draft" "table_row"
    Then I should see "Manage \"Policy 1\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   | Options |
      | English | Complete | Edit    |
    And I should see "Add translation"

    When I select "nl" from the "language" singleselect
    Then I should see "Translate \"Policy 1\" to Nederlands ‎(nl)‎"

    When I set the following fields to these values:
      | Title                                    | Beleid 1                                                                                          |
      | Policy statement                         | <p>'n Toetsbeleid</p><p>Met punte</p><p><ul><li>Een</li><li>Twee en</li><li>Drie</li></ul></p>   |
      | statements__statement[0]                 | P1 - Stem jy saam?                                                                                |
      | statements__provided[0]                  | Ja                                                                                                |
      | statements__withheld[0]                  | Nee                                                                                               |
      | Changes since previous published version | <p>Iets het verander</p>                                                                          |
    And I press "Preview"
    Then I should not see "Translate \"Policy 1\" to Nederlands ‎(nl)‎"
    And I should see "This is a preview of how the policy will appear"
    When I press "Save"
    Then I should see "Nederlands ‎(nl)‎ translation of \"Policy 1\" has been saved"
    And I should see "Manage \"Policy 1\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   |
      | English | Complete |
      | Nederlands    | Complete |
    And I should see "Edit" in the "English" "table_row"
    And I should see "Edit" in the "Nederlands" "table_row"
    And I should see "Delete" in the "Nederlands" "table_row"

