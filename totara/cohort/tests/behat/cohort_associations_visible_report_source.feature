@totara @totara_cohort @javascript
Feature: Test the cohort association visibility report source.

  Background:
    Given I am on a totara site
    And the following "cohorts" exist:
      | name        | idnumber | description | contextlevel | reference |
      | Audience #1 | Aud #1   | Audience #1 | System       | 0         |
      | Audience #2 | Aud #2   | Audience #2 | System       | 0         |
      | Audience #3 | Aud #3   | Audience #3 | System       | 0         |
      | Audience #4 | Aud #4   | Audience #4 | System       | 0         |
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname    | category   |
      | Program #1  | Program #1   |            |
      | Program #2  | Program #2   |            |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | category |
      | Cert #1  | Cert #1   |          |
      | Cert #2  | Cert #2   |          |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course #1 | C1        | 0        |
      | Course #2 | C2        | 0        |

    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable audience-based visibility | 1 |

    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program #1" "table_row"
    And I switch to "Details" tab
    And I click on "Enrolled users and members of the selected audiences" "option" in the "#id_audiencevisible" "css_element"
    And I click on "Add visible audiences" "button"
    And I click on "Audience #1" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Audience #2" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Audience #3" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Save changes" "button"

    Given I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Cert #1" "table_row"
    And I switch to "Details" tab
    And I click on "Enrolled users and members of the selected audiences" "option" in the "#id_audiencevisible" "css_element"
    And I click on "Add visible audiences" "button"
    And I click on "Audience #1" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Audience #2" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Audience #4" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Save changes" "button"

    Given I am on "Course #1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I click on "Enrolled users and members of the selected audiences" "option" in the "#id_audiencevisible" "css_element"
    And I click on "Add visible audiences" "button"
    And I click on "Audience #1" "link" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "OK" "button" in the "course-cohorts-visible-dialog" "totaradialogue"
    And I click on "Save and display" "button"


  # -------------------------------
  Scenario: cohort_associations_visible_rs_00: custom report contents
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Audiences"
    And I set the field "Source" to "Audience: Visible Learning"
    And I press "Create report"
    And I switch to "Columns" tab
    And I change the "Name" column to "Name (with icon and link)" in the report
    And I add the "Audience Name" column to the report
    And I add the "Id" column to the report
    And I add the "Actions" column to the report
    And I press "Save changes"

    Given I switch to "Filters" tab
    And I select "Audience Name" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "Id" from the "newstandardfilter" singleselect
    And I press "Add"
    And I press "Save changes"

    When I navigate to my "Audiences" report
    And I set the following fields to these values:
      | Type | Show programs only |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "report_audiences" "table" exists
    Then the following should exist in the "report_audiences" table:
      | Audience Name | Name (with icon and link) | Type    | Visibility                                           | Id     |
      | Audience #1   | Program #1                | Program | Enrolled users and members of the selected audiences | Aud #1 |
      | Audience #2   | Program #1                | Program | Enrolled users and members of the selected audiences | Aud #2 |
      | Audience #3   | Program #1                | Program | Enrolled users and members of the selected audiences | Aud #3 |
    And I should not see "Cert #1"
    And I should not see "Cert #2"
    And I should not see "Course #1"
    And I should not see "Course #2"
    And I should not see "Audience #4"
    And I should not see "Audience #5"

    When I set the following fields to these values:
      | Type | Show certifications only |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "report_audiences" "table" exists
    Then the following should exist in the "report_audiences" table:
      | Audience Name | Name (with icon and link) | Type          | Visibility                                           | Id     |
      | Audience #1   | Cert #1                   | Certification | Enrolled users and members of the selected audiences | Aud #1 |
      | Audience #2   | Cert #1                   | Certification | Enrolled users and members of the selected audiences | Aud #2 |
      | Audience #4   | Cert #1                   | Certification | Enrolled users and members of the selected audiences | Aud #4 |
    And I should not see "Program #1"
    And I should not see "Program #2"
    And I should not see "Course #1"
    And I should not see "Course #2"
    And I should not see "Audience #3"

    When I set the following fields to these values:
      | Type | Show courses only |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "report_audiences" "table" exists
    Then the following should exist in the "report_audiences" table:
      | Audience Name |  Name (with icon and link) | Type   | Visibility                                           | Id     |
      | Audience #1   | Course #1                  | Course | Enrolled users and members of the selected audiences | Aud #1 |
    And I should not see "Program #1"
    And I should not see "Program #2"
    And I should not see "Cert #1"
    And I should not see "Cert #2"
    And I should not see "Course #2"
    And I should not see "Audience #2"
    And I should not see "Audience #3"
    And I should not see "Audience #4"
    And I should not see "Audience #5"


  # -------------------------------
  Scenario: cohort_associations_visible_rs_01: embedded report contents
    Given I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Audience: Visible Learning"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Audience: Visible Learning"
    And I switch to "Columns" tab
    And I add the "Audience Name" column to the report
    And I add the "Id" column to the report
    And I press "Save changes"

    Given I switch to "Filters" tab
    And I select "Audience Name" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "Id" from the "newstandardfilter" singleselect
    And I press "Add"
    And I press "Save changes"

    When I follow "View This Report"
    And I follow "select an audience"
    And I follow "Audience #1"
    And I switch to "Visible learning" tab
    And I wait until "cohort_associations_visible" "table" exists
    Then the following should exist in the "cohort_associations_visible" table:
      | Name       | Audience Name | Type          | Visibility                                           | Id     |
      | Program #1 | Audience #1   | Program       | Enrolled users and members of the selected audiences | Aud #1 |
      | Cert #1    | Audience #1   | Certification | Enrolled users and members of the selected audiences | Aud #1 |
      | Course #1  | Audience #1   | Course        | Enrolled users and members of the selected audiences | Aud #1 |
    And I should not see "Audience #2"
    And I should not see "Audience #3"
    And I should not see "Audience #4"

    When I set the following fields to these values:
      | Type | Show programs only |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "cohort_associations_visible" "table" exists
    Then the following should exist in the "cohort_associations_visible" table:
      | Name       | Audience Name | Type          | Visibility                                           | Id     |
      | Program #1 | Audience #1   | Program       | Enrolled users and members of the selected audiences | Aud #1 |
    And I should not see "Cert #1"
    And I should not see "Course #1"
    And I should not see "Audience #3"
    And I should not see "Audience #4"
    And I should not see "Audience #5"
