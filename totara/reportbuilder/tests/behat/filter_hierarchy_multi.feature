@javascript @totara @totara_reportbuilder @totara_hierarchy
Feature: Use the multi-item hierarchy filter
  To filter the courses in a report
  by several positions and/or organisations at a time
  I need to use the multi-item hierarchy filter

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | First1    | Last1    | user1@example.com |
      | user2    | First2    | Last2    | user2@example.com |
      | user3    | First3    | Last3    | user3@example.com |
      | user4    | First4    | Last4    | user4@example.com |
      | user5    | First5    | Last5    | user5@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname               | idnumber |
      | Organisation Framework | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname         | idnumber | org_framework |
      | Organisation One | org1     | orgfw         |
      | Organisation Two | org2     | orgfw         |
    And the following "position frameworks" exist in "totara_hierarchy" plugin:
      | fullname           | idnumber |
      | Position Framework | posfw    |
    And the following "positions" exist in "totara_hierarchy" plugin:
      | fullname     | idnumber | pos_framework |
      | Position One | pos1     | posfw         |
      | Position Two | pos2     | posfw         |
    And the following job assignments exist:
      | user  | position | organisation |
      | user1 | pos1     | org1         |
      | user2 | pos1     | org2         |
      | user3 | pos2     | org1         |
      | user4 | pos2     | org2         |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"

  Scenario: Use position filter with User report source
    Given I set the field "Report Name" to "Users"
    And I set the field "Source" to "user"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User's Position(s)" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "User's Position Framework ID Number(s)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should see "user5" in the ".reportbuilder-table" "css_element"
    When I select "is equal to" from the "User's Position Framework ID Number(s) field limiter" singleselect
    And I set the field "User's Position Framework ID Number(s) value" to "posfw"
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should see "user1"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"
    And I click on "Clear" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    When I select "Any of the selected" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "link" in the "Search by" "fieldset"
    And I click on "Position One" "link" in the "Choose Positions" "totaradialogue"
    And I click on "Save" "button" in the "Choose Positions" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should not see "user3" in the ".reportbuilder-table" "css_element"
    And I should not see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"
    When I select "Any of the selected" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "link" in the "Search by" "fieldset"
    And I click on "Position Two" "link" in the "Choose Positions" "totaradialogue"
    And I click on "Save" "button" in the "Choose Positions" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"

  Scenario: Use organisation filter with User report source
    Given I set the field "Report Name" to "Users"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User's Organisation(s)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I follow "View This Report"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should see "user5" in the ".reportbuilder-table" "css_element"
    When I select "Any of the selected" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "link" in the "Search by" "fieldset"
    And I click on "Organisation One" "link" in the "Choose Organisations" "totaradialogue"
    And I click on "Save" "button" in the "Choose Organisations" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should not see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should not see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"
    When I select "Any of the selected" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "link" in the "Search by" "fieldset"
    And I click on "Organisation Two" "link" in the "Choose Organisations" "totaradialogue"
    And I click on "Save" "button" in the "Choose Organisations" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"

  Scenario: Hierachy filters controls are disabled when filter is not used
    Given I set the field "Report Name" to "Users"
    And I set the field "Source" to "User"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User's Organisation(s)" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "User's Position(s)" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "User's Manager(s)" from the "newstandardfilter" singleselect
    And I press "Save changes"
    And I follow "View This Report"
    # Check that initially when filters not set - controls are disabled
    And I wait until the page is ready
    When I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
    And  I click on "Choose Positions" "text" in the "Search by" "fieldset"
    And  I should not see "Items to add"
    And  I click on "Choose Managers" "text" in the "Search by" "fieldset"
    And  I should not see "Items to add"

    # Check that when filters was set and then unset - controls are enabled and then disabled
    # Org
    When I select "Any of the selected" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Organisations" "totaradialogue"
    When I select "is any value" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
    # Pos
    When I select "Any of the selected" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Positions" "totaradialogue"
    When I select "is any value" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
    # Manager
    When I select "Any of the selected" from the "User's Manager(s) field limiter" singleselect
    And I click on "Choose Managers" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Managers" "totaradialogue"
    When I select "is any value" from the "User's Manager(s) field limiter" singleselect
    And I click on "Choose Managers" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"

    # check that when filter is applied - controls are enabled
    And I select "Any of the selected" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    And I click on "Organisation One" "link" in the "Choose Organisations" "totaradialogue"
    And I click on "Save" "button" in the "Choose Organisations" "totaradialogue"
    # Pos
    And I select "Any of the selected" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "text" in the "Search by" "fieldset"
    And I should see "Items to add"
    And I click on "Position One" "link" in the "Choose Positions" "totaradialogue"
    And I click on "Save" "button" in the "Choose Positions" "totaradialogue"
    # Manager
    And I select "Any of the selected" from the "User's Manager(s) field limiter" singleselect
    And I click on "Choose Managers" "text" in the "Search by" "fieldset"
    And I should see "Items to add"
    And I click on "First5 Last5 (user5@example.com)" "link" in the "Choose Managers" "totaradialogue"
    And I click on "Save" "button" in the "Choose Managers" "totaradialogue"
    And I click on "Search" "button" in the "#region-main" "css_element"

    # check that when filter is applies and then unset - controls are disabled
    # Org
    And I wait until the page is ready
    And I click on "Search by" "link" in the "legend.ftoggler" "css_element"
    When I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Organisations" "totaradialogue"
    When I select "is any value" from the "User's Organisation(s) field limiter" singleselect
    And I click on "Choose Organisations" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
    # Pos
    When I click on "Choose Positions" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Positions" "totaradialogue"
    When I select "is any value" from the "User's Position(s) field limiter" singleselect
    And I click on "Choose Positions" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
    # Manager
    When I click on "Choose Managers" "text" in the "Search by" "fieldset"
    Then I should see "Items to add"
    And I click on "Cancel" "button" in the "Choose Managers" "totaradialogue"
    When I select "is any value" from the "User's Manager(s) field limiter" singleselect
    And I click on "Choose Managers" "text" in the "Search by" "fieldset"
    Then I should not see "Items to add"
