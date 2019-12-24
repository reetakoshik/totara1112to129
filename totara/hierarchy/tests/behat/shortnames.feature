@totara @totara_hierarchy @totara_hierarchy_goals
Feature: Verify the display of the shortname field on Hierarchy forms

Background:
  Given I am on a totara site
  And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
    | fullname               | idnumber |
    | Organisation Framework | oframe   |
  And the following "position frameworks" exist in "totara_hierarchy" plugin:
    | fullname           | idnumber |
    | Position Framework | pframe   |
  And the following "competency frameworks" exist in "totara_hierarchy" plugin:
    | fullname             | idnumber |
    | Competency Framework | cframe   |
  And the following "goal frameworks" exist in "totara_hierarchy" plugin:
    | fullname       | idnumber |
    | Goal Framework | gframe   |

Scenario: Verify enabling shortnames adds them to all Hierarchy forms.
  Given I am on a totara site
  And I log in as "admin"
  And I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
  And I click on "Edit" "link" in the "Position Framework" "table_row"
  Then I should not see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Position Framework" "link" in the "Position Framework" "table_row"
  And I click on "Add new position" "button"
  Then I should not see "Position short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
  And I click on "Add a new type" "button"
  Then I should not see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
  And I click on "Edit" "link" in the "Organisation Framework" "table_row"
  Then I should not see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Organisation Framework" "link" in the "Organisation Framework" "table_row"
  And I click on "Add new organisation" "button"
  Then I should not see "Organisation short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Organisations"
  And I click on "Add a new type" "button"
  Then I should not see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
  And I click on "Edit" "link" in the "Competency Framework" "table_row"
  Then I should not see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Competency Framework" "link" in the "Competency Framework" "table_row"
  And I click on "Add new competency" "button"
  Then I should not see "Competency short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Competencies"
  And I click on "Add a new type" "button"
  Then I should not see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
  And I click on "Edit" "link" in the "Goal Framework" "table_row"
  Then I should not see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Goal Framework" "link" in the "Goal Framework" "table_row"
  And I click on "Add new goal" "button"
  Then I should not see "Goal short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage company goal types" node in "Site administration > Hierarchies > Goals"
  And I click on "Add a new company goal type" "button"
  Then I should not see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage personal goal types" node in "Site administration > Hierarchies > Goals"
  And I click on "Add a new personal goal type" "button"
  Then I should not see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Advanced features" node in "Site administration"
  And I set the field "Display Hierarchy Shortnames" to "true"
  And I click on "Save changes" "button"
  Then I should see "Changes saved"

  When I navigate to "Manage positions" node in "Site administration > Hierarchies > Positions"
  And I click on "Edit" "link" in the "Position Framework" "table_row"
  Then I should see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Position Framework" "link" in the "Position Framework" "table_row"
  And I click on "Add new position" "button"
  Then I should see "Position short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Positions"
  And I click on "Add a new type" "button"
  Then I should see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage organisations" node in "Site administration > Hierarchies > Organisations"
  And I click on "Edit" "link" in the "Organisation Framework" "table_row"
  Then I should see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Organisation Framework" "link" in the "Organisation Framework" "table_row"
  And I click on "Add new organisation" "button"
  Then I should see "Organisation short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Organisations"
  And I click on "Add a new type" "button"
  Then I should see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage competencies" node in "Site administration > Hierarchies > Competencies"
  And I click on "Edit" "link" in the "Competency Framework" "table_row"
  Then I should see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Competency Framework" "link" in the "Competency Framework" "table_row"
  And I click on "Add new competency" "button"
  Then I should see "Competency short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage types" node in "Site administration > Hierarchies > Competencies"
  And I click on "Add a new type" "button"
  Then I should see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage goals" node in "Site administration > Hierarchies > Goals"
  And I click on "Edit" "link" in the "Goal Framework" "table_row"
  Then I should see "Shortname"

  When I click on "Cancel" "button"
  And I click on "Goal Framework" "link" in the "Goal Framework" "table_row"
  And I click on "Add new goal" "button"
  Then I should see "Goal short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage company goal types" node in "Site administration > Hierarchies > Goals"
  And I click on "Add a new company goal type" "button"
  Then I should see "Type short name"

  When I click on "Cancel" "button"
  And I navigate to "Manage personal goal types" node in "Site administration > Hierarchies > Goals"
  And I click on "Add a new personal goal type" "button"
  Then I should see "Type short name"
