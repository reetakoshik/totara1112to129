@totara @totara_reportbuilder @javascript
Feature: Caching works as expected when adding search columns
  In order to check cache report builder is working when adding search columns
  As a admin
  I need to be able set up caching and add search columns as filters

  Background:
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable report caching | 1 |

  Scenario: Report Builder caching works with search-columns when there is no data for "Custom Course Report"
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom Course Report"
    And I set the field "Source" to "Courses"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "Tags" from the "newsearchcolumn" singleselect
    And I press "Add"
    And I switch to "Performance" tab
    And I click on "Enable Report Caching" "text"
    And I click on "Generate Now" "text"
    And I click on "Save changes" "button"
    And I should see "Last cached"
    And I should not see "Not cached yet"

  Scenario: Report Builder caching works with search-columns when there is no data for "Custom Seminar Sessions Report"
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom Seminar Sessions Report"
    And I set the field "Source" to "Seminar Sessions"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "Building" from the "newsearchcolumn" singleselect
    And I press "Add"
    And I switch to "Performance" tab
    And I click on "Enable Report Caching" "text"
    And I click on "Generate Now" "text"
    And I click on "Save changes" "button"
    And I should see "Last cached"
    And I should not see "Not cached yet"

  Scenario: Report Builder caching works with search-columns when there is no data for "Custom Audience Report"
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Custom Audience Report"
    And I set the field "Source" to "Audiences"
    And I press "Create report"
    And I switch to "Filters" tab
    And I select "User's Organisation Name(s)" from the "newsearchcolumn" singleselect
    And I press "Add"
    And I switch to "Performance" tab
    And I click on "Enable Report Caching" "text"
    And I click on "Generate Now" "text"
    And I click on "Save changes" "button"
    And I should see "Last cached"
    And I should not see "Not cached yet"
