@javascript @tool @tool_sitepolicy @totara @language_packs
Feature: Sitepolicy version navigation
  As an admin
  I must be able to view available sitepolicies
  and navigate between the different pages to view detail

  Background:
    Given I am on a totara site
    And  I log in as "admin"
    And I set the following administration settings values:
      | Enable site policies | 1 |

    And I log out

  Scenario: I test navigation for all links on sitepolicy list page
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title       | languages | langprefix | statement             | numoptions | consentstatement     | providetext | withholdtext | mandatory |
      | 1        | 0            | 0           | Draft multi | en,nl     | ,nl        | Draft multi statement | 2          | dm-Consent statement | Yes         | No           | 1         |
      | 0        | 1            | 0           | Published 1 | en        |            | Poblished 1 statement | 2          | p1-Consent statement | Yes         | No           | first     |

    And I log in as "admin"
    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"

    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"
    And the "generaltable" table should contain the following:
      | Name        | Status    |
      | Draft multi | Draft     |
      | Published 1 | Published |
    And I should see "1 new version (draft)" in the "Draft multi" "table_row"
    And "Create new policy" "button" should exist

    ## "Create new policy" -> sitepoliciesform for creation of primary version
    When I press "Create new policy"
    Then I should see "Create new policy"

    # Cancel brings us back to index page
    When I press "Cancel"
    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"

    When I press "Create new policy"
    And I set the following fields to these values:
      | Title                             | Another policy                 |
      | Policy statement                  | Some or other policy statement |
      | Consent statement                 | A statement to consent to      |
      | Provide consent label             | Yes                            |
      | Withhold consent label            | No                             |
      | Consent required to use this site | 1                              |
    And I press "Save"
    # Saving new policy also brings us back to index page
    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"
    And the "generaltable" table should contain the following:
      | Name           | Status    |
      | Draft multi    | Draft     |
      | Another policy | Draft     |
      | Published 1    | Published |


    ## Test Shortcut for editing of primary version -> Cancel
    When I click on "1 new version (draft)" "link" in the "Draft multi" "table_row"
    # Edit primary version - versionform.php
    Then I should see "Edit version 1 of \"Draft multi\""
    When I press "Cancel"
    # Return to page where navigated from
    Then I should see "Site policies"
    And the "generaltable" table should contain the following:
      | Name           | Status    |
      | Draft multi    | Draft     |
      | Another policy | Draft     |
      | Published 1    | Published |


    ## Test Shortcut for editing of primary version -> Save
    When I click on "1 new version (draft)" "link" in the "Draft multi" "table_row"
    Then I should see "Edit version 1 of \"Draft multi\""
    When I press "Save"
    Then I should see "\"Draft multi\" has been saved"
    And I should see "Site policies"


    ## Test following the policy name link -> Draft
    # Takes us to versionlist.php
    When I follow "Draft multi"
    Then I should see "Manage \"Draft multi\" policy"
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 1        | Draft     | 2 View         |
    And "Continue editing new version" "button" should exist
    And I should see "Publish" in the "1" "table_row"
    And I should see "Edit" in the "1" "table_row"
    And I should see "Delete" in the "1" "table_row"
    And I should not see "Archive" in the "1" "table_row"

    # - navigating back to index.php
    And I should see "Manage policies" in the ".breadcrumb-nav" "css_element"
    When I click on "Manage policies" "link" in the ".breadcrumb-nav" "css_element"
    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"

    ## Test following the policy name link -> Non-draft
    When I follow "Published 1"
    Then I should see "Manage \"Published 1\" policy"
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 1        | Published | 1 View         |
    And "Create new version" "button" should exist
    And I should see "Archive" in the "1" "table_row"
    And I log out


  Scenario: I test navigation from the versionlist page
    Given the following "multiversionpolicies" exist in "tool_sitepolicy" plugin:
      | hasdraft | numpublished | allarchived | title       | languages | langprefix | statement             | numoptions | consentstatement     | providetext | withholdtext | mandatory |
      | 1        | 0            | 0           | Draft 1     | en        |            | Draft 1 statement     | 2          | d1-Consent statement | Yes         | No           | first     |
      | 1        | 2            | 0           | All multi   | nl,en     | nl,en      | All multi statement   | 2          | am-Consent statement | Yes         | No           | first     |
    And I log in as "admin"
    And I navigate to "Language packs" node in "Site administration > Localisation"
    And I set the field "Available language packs" to "nl"
    And I press "Install selected language pack(s)"
    And I wait until "Language pack 'nl' was successfully installed" "text" exists

    And I navigate to "Manage policies" node in "Site administration > Security > Site policies"
    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"
    And the "generaltable" table should contain the following:
      | Name          | Status    |
      | Draft 1       | Draft     |
      | nl All multi  | Draft     |

    When I follow "nl All multi"
    Then I should see "Manage \"nl All multi\" policy"
    And "Continue editing new version" "button" should exist
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 3        | Draft     | 1 View         |
      | 2        | Published | 1 View         |
      | 1        | Archived  | 2 View         |
    And I should see "Publish" in the "1" "table_row"
    And I should see "Edit" in the "1" "table_row"
    And I should see "Delete" in the "1" "table_row"
    And I should see "Archive" in the "2" "table_row"

    ## List translations
    When I click on "View" "link" in the "Archived" "table_row"
    Then I should see "Manage \"nl All multi\" translations"
    And the "generaltable" table should contain the following:
      | Language             | Status   | Options |
      | Nederlands           | Complete | -       |
      | English              | Complete | -       |
    And "Back to all versions" "link" should exist
    # navigate back to versionlist
    And I follow "Back to all versions"
    Then I should see "Manage \"nl All multi\" policy"

    ## Continue editing draft - Cancel : back versionlist
    When I press "Continue editing new version"
    And I press "Cancel"
    Then I should see "Manage \"nl All multi\" policy"

    ## Continue editing draft - Save : on to translationlist
    When I press "Continue editing new version"
    And I press "Save"
    Then I should see "Manage \"nl All multi\" policy"

    ## Archive - Cancel : back to versionlist
    When I click on "Archive" "link" in the "Published" "table_row"
    Then I should see "Are you sure you want to archive version 2 of \"nl All multi\""
    When I press "Cancel"
    Then I should see "Manage \"nl All multi\" policy"
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 3        | Draft     | 1 View         |
      | 2        | Published | 1 View         |
      | 1        | Archived  | 2 View         |

    ## Archive - Continue : back to versionlist
    When I click on "Archive" "link" in the "Published" "table_row"
    Then I should see "Are you sure you want to archive version 2 of \"nl All multi\""
    When I press "Archive"
    Then I should see "Version 2 of \"nl All multi\" has been archived successfully"
    And I should see "Manage \"nl All multi\" policy"
    And the "generaltable" table should contain the following:
      | Version  | Status    | # Translations |
      | 3        | Draft     | 1 View         |
      | 2        | Archived  | 1 View         |
      | 1        | Archived  | 2 View         |

    ## Edit - Cancel: back to versionlist
    When I click on "Edit" "link" in the "Draft" "table_row"
    Then I should see "Edit version 3 of \"nl All multi\""
    When I press "Cancel"
    Then I should see "Manage \"nl All multi\" policy"

    ## Edit - Save: on to translationlist
    When I click on "Edit" "link" in the "Draft" "table_row"
    Then I should see "Edit version 3 of \"nl All multi\""
    When I press "Save"
    Then I should see "Version (3) has been saved"
    And I should see "Manage \"nl All multi\" policy"

    ## Delete - Cancel: back to versionlist
    When I click on "Delete" "link" in the "Draft" "table_row"
    Then I should see "Are you sure you want to delete version 3 of \"nl All multi\""
    When I press "Cancel"
    Then I should see "Manage \"nl All multi\" policy"

    ## Delete - Delete: back to versionlist
    When I click on "Delete" "link" in the "Draft" "table_row"
    Then I should see "Are you sure you want to delete version 3 of \"nl All multi\""
    When I press "Delete"
    Then I should see "Version 3 of \"nl All multi\" has been deleted successfully"
    And I should see "Manage \"nl All multi\" policy"

    ## Publish - Cancel
    When I click on "Manage policies" "link" in the ".breadcrumb-nav" "css_element"
    Then I should see "Site policies" in the "//div[@id='region-main']//h2" "xpath_element"
    When I follow "Draft 1"
    Then I should see "Manage \"Draft 1\" policy"
    When I follow "Publish"
    Then I should see "Are you sure you want to publish \"Draft 1\""
    When I press "Cancel"
    Then I should see "Manage \"Draft 1\" policy"

    ## Publish - Continue
    When I follow "Publish"
    Then I should see "Are you sure you want to publish \"Draft 1\""
    When I press "Publish"
    Then I should see "Version 1 of \"Draft 1\" has been published successfully"
    And I should see "Manage \"Draft 1\" policy"

    And I log out
