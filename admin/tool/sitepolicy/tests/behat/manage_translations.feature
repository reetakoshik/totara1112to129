@javascript @tool @tool_sitepolicy @totara @language_packs
Feature: Manage sitepolicy version translations
  As an admin
  I want to manage multiple translations of a site policy

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |
    And I fake the French language pack is installed for site policies
    And I fake the Dutch language pack is installed for site policies
    And I log out

  Scenario: Add a new translation to a sitepolicy version
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

    When I click on "View" "link" in the "Published" "table_row"
    Then I should see "Manage \"Policy 1\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   | Options |
      | English | Complete | -       |
    And I should not see "Add translation"

    #When I follow "English"
    When I click on "English ‎(en)‎ (primary)" "link"
    Then I should see "Policy 1 statement"
    And I should see "P1 - Consent statement 1"
    And I should see "Translations" in the ".breadcrumb-nav" "css_element"

    When I click on "Translations" "link" in the ".breadcrumb-nav" "css_element"
    Then I should see "Manage \"Policy 1\" translations"
    And I should see "Back to all versions"

    When I follow "Back to all versions"
    Then I should see "Manage \"Policy 1\" policy"

    When I click on "View" "link" in the "Draft" "table_row"
    Then I should see "Manage \"Policy 1\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   | Options |
      | English | Complete | Edit    |
    And I should see "Add translation"

    When I select "nl" from the "language" singleselect
    And I set the following fields to these values:
      | Title                                    | Beleid 1            |
      | Policy statement                         | Beleidsverklaring   |
      | statements__statement[0]                 | P1 - Stem jy saam?  |
      | statements__provided[0]                  | Ja                  |
      | statements__withheld[0]                  | Nee                 |
      | Changes since previous published version | Iets het verander   |
    And I press "Save"
    Then I should see "Nederlands ‎(nl)‎ translation of \"Policy 1\" has been saved"
    And I should see "Manage \"Policy 1\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   |
      | English | Complete |
      | Nederlands    | Complete |
    And I should see "Edit" in the "English" "table_row"
    And I should see "Edit" in the "Nederlands" "table_row"
    And I should see "Delete" in the "Nederlands" "table_row"

  Scenario: Add a new option to a multilingual sitepolicy
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title    | languages | langprefix | statement          | numoptions | consentstatement       | providetext | withholdtext | mandatory |
      | 1        | 0            | 0           | Policy 2 | en,nl,fr  | ,nl ,fr    | Policy 2 statement | 1          | P2 - Consent statement | Yes         | No           | first     |
    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then the "generaltable" table should contain the following:
      | Name     | Revisions | Status    |
      | Policy 2 | 1         | Draft |

    When I follow "Policy 2"
    Then the "generaltable" table should contain the following:
      | Version  | Status | # Translations |
      | 1        | Draft  | 3 View         |
    And "Continue editing new version" "button" should exist

    When I press "Continue editing new version"
    Then I should see "Edit version 1 of \"Policy 2\""
    When I press "Save"

    Then I should see "Manage \"Policy 2\" policy"
    And I should see "Version (1) has been saved"

    When I click on "View" "link" in the "Draft" "table_row"
    Then the "generaltable" table should contain the following:
      | Language          | Status   |
      | English           | Complete |
      | Nederlands        | Complete |
      | Français          | Complete |
    And I should see "Edit" in the "English" "table_row"
    And I should see "Edit" in the "Nederlands" "table_row"
    And I should see "Delete" in the "Nederlands" "table_row"
    And I should see "Edit" in the "Français" "table_row"
    And I should see "Delete" in the "Français" "table_row"

    When I click on "Edit" "link" in the "English" "table_row"
    Then I should see "Edit version 1 of \"Policy 2\""
    And "Remove" "button" should exist
    And "Add statement" "button" should exist

    When I press "Add statement"
    And I set the following fields to these values:
      | statements__statement[1]  | Another consent statement  |
      | statements__provided[1]   | Agree                      |
      | statements__withheld[1]   | Disagree                   |
    And I press "Save"
    Then I should see "Version (1) has been saved"
    And I should see "Manage \"Policy 2\" translations"
    And the "generaltable" table should contain the following:
      | Language          | Status   |
      | English           | Complete |
      | Nederlands        | Incomplete |
      | Français          | Incomplete |

    When I follow "Back to all versions"
    Then I should see "Manage \"Policy 2\" policy"
    And I should see "You cannot publish this draft because you have incomplete translations"
    And the "generaltable" table should contain the following:
      | Version  | Status | # Translations |
      | 1        | Draft  | 3 View         |
    And I should see "Incomplete translations" in the "Draft" "table_row"
    And "Publish" "link" should not exist

    When I click on "View" "link" in the "Draft" "table_row"
    And I click on "Edit" "link" in the "Français" "table_row"
    Then I should see "Translate \"Policy 2\" to Français"
    And "Remove" "button" should not exist
    And "Add statement" "button" should not exist

    When I set the following fields to these values:
      | statements__statement[1]  | Une autre déclaration de consentement  |
      | statements__provided[1]   | Accepter                               |
      | statements__withheld[1]   | Pas d'accord                           |
    And I press "Save"
    Then I should see "Manage \"Policy 2\" translations"
    And I should see "Français ‎(fr)‎ translation of \"Policy 2\" has been saved"
    And the "generaltable" table should contain the following:
      | Language          | Status     |
      | English           | Complete   |
      | Nederlands        | Incomplete |
      | Français          | Complete   |

    When I click on "Edit" "link" in the "English" "table_row"
    And I press "statements_remove[1]"
    And I press "Yes"
    And I press "Save"
    Then I should see "Manage \"Policy 2\" translations"
    And I should see "Version (1) has been saved"
    And the "generaltable" table should contain the following:
      | Language          | Status     |
      | English           | Complete   |
      | Nederlands        | Complete   |
      | Français          | Complete   |

    When I follow "Back to all versions"
    Then the "generaltable" table should contain the following:
      | Version | Status  | # Translations |
      | 1       | Draft   | 3 View         |
    And I should not see "Incomplete translations" in the "Draft" "table_row"
    And "Publish" "link" should exist in the "Draft" "table_row"

