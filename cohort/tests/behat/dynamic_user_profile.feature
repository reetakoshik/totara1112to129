@core @core_cohort @javascript
Feature: Limit an audience based on user profile fields
  In order to create an audience based on a user profile field
  As an admin
  I need to be able to set up a dynamic audience

# Totara: audiences are very different from upstream.

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "User profile fields" node in "Site administration > Users"
    And I set the field "datatype" to "Text input"
    #redirect
    And I set the following fields to these values:
      | Short name | f1   |
      | Name       | test |
    And I press "Save changes"
    And the following "users" exist:
      | username | firstname | lastname | email             | test |
      | user1    | User      | One      | user1@example.com | 1    |
      | user2    | User      | Two      | user2@example.com | 2    |
      | user3    | User      | Three    | user3@example.com | 3    |
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Edit" "link" in the "User One" "table_row"
    And I expand all fieldsets
    And I set the field "test" to "1"
    And I press "Update profile"
    And I click on "Edit" "link" in the "User Two" "table_row"
    And I expand all fieldsets
    And I set the field "test" to "2"
    And I press "Update profile"
    And I click on "Edit" "link" in the "User Three" "table_row"
    And I expand all fieldsets
    And I set the field "test" to "3"
    And I press "Update profile"
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "Add new audience"
    And I set the following fields to these values:
      | Name | Test    |
      | Type | Dynamic |
    And I press "Save changes"

  Scenario: Test one value in the audience filter
    Given I set the field "addrulesetmenu" to "test (Text)"
    And I wait "1" seconds
    And I set the field "listofvalues" to "1"
    And I press "Save"
    And I wait "1" seconds
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "User One" in the "cohort_members" "table"
    And I should not see "User Two" in the "cohort_members" "table"
    And I should not see "User Three" in the "cohort_members" "table"

  Scenario: Test 2 values in the audience filter
    Given I set the field "addrulesetmenu" to "test (Text)"
    And I wait "1" seconds
    And I set the field "listofvalues" to "1,2"
    And I press "Save"
    And I wait "1" seconds
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "User One" in the "cohort_members" "table"
    And I should see "User Two" in the "cohort_members" "table"
    And I should not see "User Three" in the "cohort_members" "table"

  Scenario: Test no values in the audience filter
    Given I set the field "addrulesetmenu" to "test (Text)"
    And I wait "1" seconds
    And I set the field "listofvalues" to "5"
    And I press "Save"
    And I wait "1" seconds
    And I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "User One" in the ".rb-display-table-container" "css_element"
    And I should not see "User Two" in the ".rb-display-table-container" "css_element"
    And I should not see "User Three" in the ".rb-display-table-container" "css_element"
