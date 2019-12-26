@totara @totara_reportbuilder @core_badges @javascript
Feature: Badges report filter
  As an admin
  I should be able to filter badges using the report builder

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
    And I log in as "admin"
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 1           |
      | Description   | Test badge description |
      | issuername    | Test Badge Site        |
      | issuercontact | testuser@example.com   |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    When I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I click on "User One (user1@example.com)" "option"
    And I click on "User Two (user2@example.com)" "option"
    And I click on "User Three (user3@example.com)" "option"
    And I click on "Award badge" "button"

    # Add a second badge.
    And I navigate to "Manage badges" node in "Site administration > Badges"
    And I press "Add a new badge"
    And I set the following fields to these values:
      | Name          | Test Badge 2           |
      | Description   | Test badge description |
      | issuername    | Test Badge Site        |
      | issuercontact | testuser@example.com   |
    And I upload "badges/tests/behat/badge.png" file to "Image" filemanager
    When I press "Create badge"
    And I set the field "Add badge criteria" to "Manual issue by role"
    And I set the field "Site Manager" to "1"
    And I click on "Save" "button"
    And I click on "Enable access" "button"
    And I click on "Continue" "button"
    And I switch to "Recipients (0)" tab
    And I click on "Award badge" "button"
    And I click on "User One (user1@example.com)" "option"
    And I click on "Award badge" "button"

  Scenario: Test badge report builder filter
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | Badge report  |
      | Source      | Badges Issued |
    And I click on "Create report" "button"
    # The badges filter testing should be one of the default filters.
    And I click on "View This Report" "link"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should not see "User Four"

    # Now do some filtering.
    When I click on "Add badges" "link"
    And I click on "Test Badge 2" "link" in the "Choose badges" "totaradialogue"
    And I click on "Save" "button" in the "Choose badges" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should not see "User Two"
    And I should not see "User Three"
    And I should not see "User Four"

    When I click on "Add badges" "link"
    And I click on "Test Badge 1" "link" in the "Choose badges" "totaradialogue"
    And I click on "Save" "button" in the "Choose badges" "totaradialogue"
    And I wait "1" seconds
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should not see "User Four"
