@totara @totara_reportbuilder @javascript
Feature: Test aggregated user columns can be added and viewed by the admin
  As an admin
  I create a report using the program overview report source
  I test that I can add the aggregated columns
  I test that the aggregated columns display correctly

  Background:
    Given I am on a totara site
    # Set up some courses with unlikely characters in the short names.
    And the following "courses" exist:
      | fullname         | shortname | format | enablecompletion |
      | CourseFullname-A | C+r+s-A   | topics | 1                |
      | CourseFullname-B | C,r,s-B   | topics | 1                |
      | CourseFullname-C | C.r.s-C   | topics | 1                |
      | CourseFullname-D | C/r\s-D   | topics | 1                |
      | CourseFullname-E | C_r^s-E   | topics | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | user      | one      | user1@example.com |
    And the following "programs" exist in "totara_program" plugin:
      | fullname      | shortname |
      | Program Tests | progtest  |
    And the following "program assignments" exist in "totara_program" plugin:
      | program  | user  |
      | progtest | user1 |
    And I log in as "admin"
    And I set self completion for "CourseFullname-A" in the "Miscellaneous" category
    And I set self completion for "CourseFullname-B" in the "Miscellaneous" category
    And I set self completion for "CourseFullname-C" in the "Miscellaneous" category
    And I set self completion for "CourseFullname-D" in the "Miscellaneous" category
    And I set self completion for "CourseFullname-E" in the "Miscellaneous" category
    And I navigate to "Manage programs" node in "Site administration > Programs"
    And I click on "Miscellaneous" "link"
    And I click on "Program Tests" "link"
    And I click on "Edit program details" "button"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "CourseFullname-A" "link" in the "addmulticourse" "totaradialogue"
    And I click on "CourseFullname-B" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "CourseFullname-C" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "addcontent_ce" "button" in the "#edit-program-content" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "CourseFullname-D" "link" in the "addmulticourse" "totaradialogue"
    And I click on "CourseFullname-E" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I wait "1" seconds
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"

  Scenario: View aggregated fields in the program overview report
    Given I set the field "Report Name" to "Overview Report"
    And I set the field "Source" to "Program Overview"
    And I press "Create report"
    # It would be nice to add and check some of the other aggregated columns.
    When I follow "View This Report"
    Then I should see "C+r+s-A" in the "progtest" "table_row"
    And I should see "C,r,s-B" in the "progtest" "table_row"
    And I should see "C.r.s-C" in the "progtest" "table_row"
    And I should see "C/r\s-D" in the "progtest" "table_row"
    And I should see "C_r^s-E" in the "progtest" "table_row"
