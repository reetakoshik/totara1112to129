@totara @totara_certification @report @javascript
Feature: The Certification Completion report displays correctly for a learner.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "courses" exist:
      | fullname         | shortname | format | enablecompletion |
      | Certify Course   | CC1       | topics | 1                |
      | Recertify Course | RC1       | topics | 1                |
    And I log in as "admin"
    And I set self completion for "Certify Course" in the "Miscellaneous" category
    And I set self completion for "Recertify Course" in the "Miscellaneous" category
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I press "Add new certification"
    And I set the following fields to these values:
      | Full name  | Test Certification |
      | Short name | tstcert            |
    And I press "Save changes"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Certify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Recertify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Certification" tab
    And I set the following fields to these values:
      | activenum | 6 |
      | windownum | 2 |
    And I set the field "activeperiod" to "Month(s)"
    And I set the field "windowperiod" to "Month(s)"
    And I set the field "recertifydatetype" to "Use certification completion date"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user    |
      | tstcert | user001 |
      | tstcert | user002 |

    # Add Certification Completion report so we can check the status.
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "Report Name" to "Certification Completion Report"
    And I set the field "Source" to "Certification Completion"
    And I press "Create report"
    And I follow "Access"
    And I click on "All users can view this report" "radio"
    And I press "Save changes"
    And I log out

  Scenario: A users certification red-amber-green status is correct
    Given I log in as "user001"
    When I click on "Dashboard" in the totara menu
    Then I should see "Test Certification" in the "Current Learning" "block"
    When I follow "Test Certification"
    Then I should see "Certify Course"
    And I should not see "Recertify Course"

    #
    # Status: Assigned without due date.
    #
    When I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Assigned" in the "fn_001 ln_001" "table_row"

    #
    # Status: Assigned with due date.
    #
    When I log out
    And I log in as "admin"
    And I am on "Test Certification" certification homepage
    And I press "Edit certification details"
    And I switch to "Assignments" tab
    And I click on "Set due date" "link" in the "fn_001 ln_001" "table_row"
    And I set the field "timeperiod" to "Day(s)"
    And I set the field "eventtype" to "Program enrollment date"
    And I set the following fields to these values:
      | timeamount | 2 |
    And I click on "Set time relative to event" "button" in the "completion-dialog" "totaradialogue"
    And I log out
    And I log in as "user001"
    And I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Due " in the "fn_001 ln_001" "table_row"

    #
    # Status: Currently certified.
    #
    When I follow "Test Certification"
    And I click on "Certify Course" "link" in the ".display-program" "css_element"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Window opens " in the "fn_001 ln_001" "table_row"

    #
    # Status: Window open.
    #
    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Dashboard" in the totara menu
    Then I should see "Test Certification" in the "Current Learning" "block"
    And I follow "Test Certification"
    Then I should not see "Certify Course"
    And I should see "Recertify Course"
    When I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Expires " in the "fn_001 ln_001" "table_row"

    #
    # Status: Currently certified (after re-certification).
    #
    When I follow "Test Certification"
    And I click on "Recertify Course" "link" in the ".display-program" "css_element"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Window opens " in the "fn_001 ln_001" "table_row"
    And I log out

    #
    # Status: Overdue since.
    #
    When I log in as "admin"
    And I am on "Test Certification" certification homepage
    And I press "Edit certification details"
    And I switch to "Assignments" tab
    And I click on "Set due date" "link" in the "fn_002 ln_002" "table_row"
    And I set the following fields to these values:
      | completiontime       | 15/04/2017 |
    And I click on "Set fixed completion date" "button" in the "Completion criteria" "totaradialogue"
    Then I should see "15 Apr 2017 at 00:00" in the "fn_002 ln_002" "table_row"

    When I log out
    And I log in as "user002"
    And I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_002 ln_002" "table_row"
    And I should see "Overdue since 15 Apr 2017" in the "fn_002 ln_002" "table_row"

    #
    # Status: Expired since.
    #
    When I wind back certification dates by 7 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Reports" in the totara menu
    And I follow "Certification Completion Report"
    Then I should see "Test Certification" in the "fn_001 ln_001" "table_row"
    And I should see "Expired since" in the "fn_001 ln_001" "table_row"
    And I should see "Test Certification" in the "fn_002 ln_002" "table_row"
    And I should see "Overdue since " in the "fn_002 ln_002" "table_row"
