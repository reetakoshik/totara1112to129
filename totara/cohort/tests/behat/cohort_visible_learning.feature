@totara @totara_cohort @javascript
Feature: User is performing actions on audience learning visibility report
  Scenario: Performing a delete action on a learning visibility report
    Given the following "courses" exist:
      | fullname | shortname | category |
      | c101     | c101      | 0        |
    And the following "cohorts" exist:
      | name        | idnumber |
      | Audiences 1 | aud1 |
      | Audiences 2 | aud2 |
    And I am on a totara site
    And I log in as "admin"
    And I navigate to "System information > Advanced features" in site administration
    And I set the field "Enable audience-based visibility" to "1"
    And I click on "Save changes" "button"
    And I am on "c101" course homepage
    And I navigate to "Edit settings" in current page administration
    And I set the field "Visibility" to "1"
    And I click on "Add visible audiences" "button"
    And I follow "Audiences 1"
    And I follow "Audiences 2"
    And I click on "OK" "button" in the "Course audiences (visible)" "totaradialogue"
    And I click on "Save and display" "button"
    And I navigate to "Reports > Manage user reports" in site administration
    And I click on "Create report" "button"
    And I set the following fields to these values:
      | Report Name | Audience Visibility |
      | Source      | cohort_associations_visible |
    And I click on "Create report" "button"
    And I follow "Columns"
    And I set the field "newcolumns" to "associations-actionsvisible"
    And I click on "Add" "button"
    And I click on "Save changes" "button"
    And I follow "View This Report"
    And I should see "Enrolled users and members of the selected audiences" exactly "2" times
    When I set the field with xpath "//select[@data-name='c101_aud1']" to "No users"
    Then I should see "No users" exactly "2" times
    When I click on "Delete" "link" confirming the dialogue
    Then I should see "No users" exactly "1" times
