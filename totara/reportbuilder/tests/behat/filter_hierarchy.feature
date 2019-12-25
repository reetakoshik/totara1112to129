@totara @totara_reportbuilder @totara_hierarchy @javascript
Feature: Single hierarchy report filter
  As an admin
  I should be able to use the hierarchy filter

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User      | One      | user1@example.com |
      | user2    | User      | Two      | user2@example.com |
      | user3    | User      | Three    | user3@example.com |
      | user4    | User      | Four     | user4@example.com |
      | user5    | User      | Five     | user5@example.com |
    And the following "organisation frameworks" exist in "totara_hierarchy" plugin:
      | fullname | idnumber |
      | Org Fram | orgfw    |
    And the following "organisations" exist in "totara_hierarchy" plugin:
      | fullname        | idnumber | org_framework |
      | Organisation 1z | org1z    | orgfw         |
      | Organisation 1a | org1a    | orgfw         |
      | Organisation 1b | org1b    | orgfw         |
      | Organisation 2z | org2z    | orgfw         |
    And the following job assignments exist:
      | user  | organisation |
      | user1 | org1z        |
      | user2 | org1a        |
      | user3 | org1b        |
      | user4 | org2z        |
    And I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "Org Fram" "link"
    And I set the field "jump" to "Move"
    And I click on "Organisation 1a" "option"
    And I click on "Organisation 1b" "option"
    And I click on "Add" "button"
    And I set the field "newparent" to "Organisation 1z"
    And I click on "Move" "button"
    And I click on "Continue" "button"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the following fields to these values:
      | Report Name | User report |
      | Source      | User        |
    And I click on "Create report" "button"
    And I switch to "Filters" tab
    And I set the field "newstandardfilter" to "User's Organisation(s)"
    And I click on "Add" "button"
    And I click on "View This Report" "link"
    Then I should see "User One"
    And I should see "User Two"
    And I should see "User Three"
    And I should see "User Four"
    And I should see "User Five"

  Scenario Outline: Test organisation report builder filter
    Given I set the field "job_assignment-allorganisations_op" to "<type>"
    And I click on "Choose Organisations" "link" in the "Search by" "fieldset"
    And I click on "Organisation 1z" "link" in the "Choose Organisations" "totaradialogue"
    And I click on "Save" "button" in the "Choose Organisations" "totaradialogue"
    And I wait "1" seconds

    When I set the field "Include children" to "<includesub>"
    # This needs to be limited as otherwise it clicks the legend ...
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should <u1> "User One"
    And I should <u2> "User Two"
    And I should <u3> "User Three"
    And I should <u4> "User Four"
    And I should <u5> "User Five"

    # The filter text should still be displayed after page reload.
    # We can reload the page by sorting a column.
    And I click on "User's Fullname" "link"
    And I should see "<organisation>"
    And the field "job_assignment-allorganisations_op" matches value "<type>"
    And the field "job_assignment-allorganisations_child" matches value "<includesub>"

    Examples:
      | type                    | includesub | u1      | u2      | u3      | u4      | u5      | organisation     |
      | Any of the selected     | 0          | see     | not see | not see | not see | not see | Organisation 1z  |
      | Not any of the selected | 0          | not see | see     | see     | see     | see     | Organisation 1z  |
      | Any of the selected     | 1          | see     | see     | see     | not see | not see | Organisation 1z  |
      | Not any of the selected | 1          | not see | not see | not see | see     | see     | Organisation 1z  |
