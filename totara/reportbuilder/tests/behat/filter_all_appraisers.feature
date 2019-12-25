@totara @totara_reportbuilder
Feature: Use the multi-item appraiser filter
  To filter the users in a report by several appraisers at a time
  As an authenticated user
  I need to use the all appraisers filter

  @javascript
  Scenario: Filter a list of users by a single appraiser
    Given I am on a totara site
    And the following "users" exist:
      | username   | firstname  | lastname | email                  |
      | user1      | First1     | Last1    | user1@example.com      |
      | user2      | First2     | Last2    | user2@example.com      |
      | user3      | First3     | Last3    | user3@example.com      |
      | user4      | First4     | Last4    | user4@example.com      |
      | user5      | First5     | Last5    | user5@example.com      |
      | manager1   | Manager1   | One1     | manager1@example.com   |
      | manager2   | Manager2   | Two2     | manager2@example.com   |
      | manager3   | Manager3   | Three3   | manager3@example.com   |
      | appraiser1 | Appraiser1 | One1     | appraiser1@example.com |
      | appraiser2 | Appraiser2 | Two2     | appraiser2@example.com |
      | appraiser3 | Appraiser3 | Three3   | appraiser3@example.com |
    And the following job assignments exist:
      | user    | manager  | appraiser  |
      | user1   | manager1 |            |
      | user2   | manager1 | appraiser1 |
      | user3   | manager2 | appraiser1 |
      | user4   | manager3 | appraiser2 |
      | user5   |          | appraiser3 |
    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    Given I set the field "Report Name" to "Users Report"
    And I set the field "Source" to "user"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User's Appraiser(s)" from the "newstandardfilter" singleselect
    And I press "Add"
    And I switch to "Access" tab
    # We'll get a standard user to use the filter, as there are some access checks that
    # shouldn't fail if the user is allowed to view the report.
    And I set the following fields to these values:
      | Authenticated user | 1 |
    And I press "Save changes"
    And I log out
    And I log in as "user1"
    And I click on "Reports" in the totara menu
    And I click on "Users Report" "link"
    Then I should see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should see "user5" in the ".reportbuilder-table" "css_element"
    # Select
    When I select "Any of the selected" from the "User's Appraiser(s) field limiter" singleselect
    And I click on "Choose Appraisers" "link" in the "Search by" "fieldset"
    And I click on "Appraiser1 One1" "link" in the "Choose Appraisers" "totaradialogue"
    And I click on "Save" "button" in the "Choose Appraisers" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should not see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should not see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"
    # Search
    When I select "Any of the selected" from the "User's Appraiser(s) field limiter" singleselect
    And I click on "Choose Appraisers" "link" in the "Search by" "fieldset"
    And I switch to "Search" tab
    And I set the following fields to these values:
      | query | Appr |
    And I press "dialogsearchsubmitbutton"
    And I click on "Appraiser2 Two2" "link" in the "#search-tab" "css_element"
    And I click on "Save" "button" in the "Choose Appraisers" "totaradialogue"
    And I wait "1" seconds
    And I click on "Search" "button" in the "#fgroup_id_submitgroupstandard" "css_element"
    Then I should not see "user1" in the ".reportbuilder-table" "css_element"
    And I should see "user2" in the ".reportbuilder-table" "css_element"
    And I should see "user3" in the ".reportbuilder-table" "css_element"
    And I should see "user4" in the ".reportbuilder-table" "css_element"
    And I should not see "user5" in the ".reportbuilder-table" "css_element"
