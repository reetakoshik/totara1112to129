@totara @totara_cohort @javascript
Feature: Test the cohort associations report source.

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
    And the following "cohort enrolments" exist in "totara_cohort" plugin:
      | course | cohort |
      | C1     | Aud #1 |

    Given I log in as "admin"
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Program #1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience #1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Audience #2" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Audience #3" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"

    Given I click on "Set due date" "link" in the "Audience #1" "table_row"
    And I set the following fields to these values:
      | timeamount | 1           |
      | timeperiod | Day(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"

    Given I click on "Set due date" "link" in the "Audience #2" "table_row"
    And I set the following fields to these values:
      | completiontime       | 09/12/2030 |
      | completiontimehour   | 14         |
      | completiontimeminute | 30         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"

    Given I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Cert #1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Audiences"
    And I click on "Audience #1" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Audience #2" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Audience #4" "link" in the "Add audiences to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add audiences to program" "totaradialogue"

    Given I click on "Set due date" "link" in the "Audience #1" "table_row"
    And I set the following fields to these values:
      | timeamount | 4           |
      | timeperiod | Week(s)     |
      | eventtype  | First login |
    And I click on "Set time relative to event" "button" in the "Completion criteria" "totaradialogue"

    Given I click on "Set due date" "link" in the "Audience #2" "table_row"
    And I set the following fields to these values:
      | completiontime       | 01/03/2035 |
      | completiontimehour   | 17         |
      | completiontimeminute | 30         |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"

  # -------------------------------
  Scenario: cohort_associations_rs_00: custom report contents
    Given I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Audiences"
    And I set the field "Source" to "Audience: Enrolled Learning"
    And I press "Create report"
    And I switch to "Columns" tab
    And I change the "Name" column to "Name (with icon and link)" in the report
    And I add the "Audience Name" column to the report
    And I add the "Id" column to the report
    And I add the "Assignment due date" column to the report
    And I add the "Actual due date" column to the report
    And I add the "Actions" column to the report
    And I press "Save changes"

    Given I switch to "Filters" tab
    And I select "Audience Name" from the "newstandardfilter" singleselect
    And I press "Add"
    And I select "Id" from the "newstandardfilter" singleselect
    And I press "Add"
    And I press "Save changes"

    When I navigate to my "Audiences" report
    And I wait until "report_audiences" "table" exists
    Then the following should exist in the "report_audiences" table:
      | Audience Name | Name (with icon and link) | Type          | Assignment due date  | Id     | Actual due date |
      | Audience #3   | Program #1                | Program       | Set due date         | Aud #3 | View dates      |
      | Audience #4   | Cert #1                   | Certification | Set due date         | Aud #4 | View dates      |

    # Note this behat step has to be used here instead of the similar "the following should exist in the "report_audiences" table".
    # Currently, the assignment due date is rendered (wrongly) as a link WITH A trailing space; that space makes the other behat
    # step very unhappy.
    And the "report_audiences" table should contain the following:
      | Assignment due date                      | Audience Name | Name (with icon and link) | Type          | Id     | Actual due date |
      | Complete within 1 Day(s) of First login  | Audience #1   | Program #1                | Program       | Aud #1 | View dates      |
      | Complete by 9 Dec 2030 at 14:30          | Audience #2   | Program #1                | Program       | Aud #2 | View dates      |
      | Complete within 4 Week(s) of First login | Audience #1   | Cert #1                   | Certification | Aud #1 | View dates      |
      | Complete by 1 Mar 2035 at 17:30          | Audience #2   | Cert #1                   | Certification | Aud #2 | View dates      |
      | n/a                                      | Audience #1   | Course #1                 | Course        | Aud #1 | n/a             |
    And I should not see "Program #2"
    And I should not see "Cert #2"
    And I should not see "Course #2"

    When I set the following fields to these values:
      | associations-name_op | ends with          |
      | associations-name    | #1                 |
      | Type                 | Show programs only |
      | cohort-name_op       | ends with          |
      | cohort-name          | #1                 |
      | cohort-idnumber_op   | is equal to        |
      | cohort-idnumber      | Aud #2             |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "There are no records that match your selected criteria"

    When I set the following fields to these values:
      | Type                 | any value   |
      | cohort-idnumber_op   | is equal to |
      | cohort-idnumber      | Aud #1      |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "report_audiences" "table" exists
    Then the "report_audiences" table should contain the following:
      | Assignment due date                      | Audience Name | Name (with icon and link) | Type          | Id     | Actual due date |
      | Complete within 1 Day(s) of First login  | Audience #1   | Program #1                | Program       | Aud #1 | View dates      |
      | Complete within 4 Week(s) of First login | Audience #1   | Cert #1                   | Certification | Aud #1 | View dates      |
      | n/a                                      | Audience #1   | Course #1                 | Course        | Aud #1 | n/a             |
    And I should not see "Program #2"
    And I should not see "Cert #2"
    And I should not see "Course #2"
    And I should not see "Audience #2"
    And I should not see "Audience #3"
    And I should not see "Audience #4"


  # -------------------------------
  Scenario: cohort_associations_rs_01: embedded report contents
    Given I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Audience: Enrolled Learning"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Audience: Enrolled Learning"
    And I switch to "Columns" tab
    And I change the "Name" column to "Name (with icon and link)" in the report
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
    And I switch to "Enrolled learning" tab
    And I wait until "cohort_associations_enrolled" "table" exists
    Then the "cohort_associations_enrolled" table should contain the following:
      | Assignment due date                      | Audience Name | Name       | Type          | Id     | Actual due date |
      | Complete within 1 Day(s) of First login  | Audience #1   | Program #1 | Program       | Aud #1 | View dates      |
      | Complete within 4 Week(s) of First login | Audience #1   | Cert #1    | Certification | Aud #1 | View dates      |
      | n/a                                      | Audience #1   | Course #1  | Course        | Aud #1 | n/a             |
    And I should not see "Audience #2"
    And I should not see "Audience #3"
    And I should not see "Audience #4"
    And I should not see "Audience #5"

    When I set the following fields to these values:
      | associations-name_op | ends with          |
      | associations-name    | #1                 |
      | Type                 | Show programs only |
      | cohort-name_op       | ends with          |
      | cohort-name          | #1                 |
      | cohort-idnumber_op   | is equal to        |
      | cohort-idnumber      | Aud #2             |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    Then I should see "There are no records that match your selected criteria"

    When I set the following fields to these values:
      | Type                 | any value   |
      | cohort-idnumber_op   | is equal to |
      | cohort-idnumber      | Aud #1      |
    And I click on "Search" "button" in the ".fitem_actionbuttons" "css_element"
    And I wait until "cohort_associations_enrolled" "table" exists
    Then the "cohort_associations_enrolled" table should contain the following:
      | Assignment due date                      | Audience Name | Name       | Type          | Id     | Actual due date |
      | Complete within 1 Day(s) of First login  | Audience #1   | Program #1 | Program       | Aud #1 | View dates      |
      | Complete within 4 Week(s) of First login | Audience #1   | Cert #1    | Certification | Aud #1 | View dates      |
      | n/a                                      | Audience #1   | Course #1  | Course        | Aud #1 | n/a             |
    And I should not see "Program #2"
    And I should not see "Cert #2"
    And I should not see "Course #2"
    And I should not see "Audience #2"
    And I should not see "Audience #3"
    And I should not see "Audience #4"
