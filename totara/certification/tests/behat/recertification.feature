@totara @totara_certification
Feature: User recertification and expiry of certification
  In order to view a program
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
    And the following "courses" exist:
      | fullname         | shortname | format | enablecompletion | completionstartonenrol |
      | Certify Course   | CC1       | topics | 1                | 1                      |
      | Recertify Course | RC1       | topics | 1                | 1                      |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime   | 0       |
      | enableprograms | Disable |
    And I set self completion for "Certify Course" in the "Miscellaneous" category
    And I set self completion for "Recertify Course" in the "Miscellaneous" category
    And I click on "Certifications" in the totara menu
    And I press "Create Certification"
    And I set the following fields to these values:
        | Full name  | Test Certification |
        | Short name | tstcert            |
    And I press "Save changes"
    And I click on "Content" "link"
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
    And I click on "Month(s)" "option" in the "#id_activeperiod" "css_element"
    And I click on "Month(s)" "option" in the "#id_windowperiod" "css_element"
    And I click on "Use certification completion date" "option" in the "#id_recertifydatetype" "css_element"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Appearance"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"
    And I log out
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user    |
      | tstcert | user001 |

  # Test recertification path:
  # Initial Cert -> Recert -> Recert -> Expired -> Cert -> Recert
  @javascript
  Scenario: A user can recertify multiple times
    Given I log in as "user001"
    And I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Certify Course"
    And I should not see "Recertify Course"

    When I click on "Certify Course" "link" in the ".display-program" "css_element"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Test Certification"
    And I should see "Completed"
    And I should see "Not due for renewal"

    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Completed"
    And I should see "Due for renewal"

    When I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Recertify Course"
    And I should not see "Certify Course"

    When I click on "Recertify Course" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Test Certification"
    And I should see "Completed"
    And I should see "Not due for renewal"

    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Completed"
    And I should see "Due for renewal"

    When I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Recertify Course"
    And I should not see "Certify Course"

    When I click on "Recertify Course" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Test Certification"
    And I should see "Complete"
    And I should see "Not due for renewal"

    When I wind back certification dates by 7 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Not certified"
    And I should see "Renewal expired"

    When I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Overdue"
    And I should see "Test Certification"
    And I should see "Certify Course"
    And I should not see "Recertify Course"

    When I click on "Certify Course" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Test Certification"
    And I should see "Complete"
    And I should see "Not due for renewal"

    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Courses" "link" in the "#dp-plan-content" "css_element"
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Complete"
    And I should see "Due for renewal"

    When I click on "Required Learning" in the totara menu
    Then I should see "Test Certification"
    And I should see "Recertify Course"
    And I should not see "Certify Course"

    When I click on "Recertify Course" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    Then I should not see "Required Learning" in the totara menu

    When I click on "Record of Learning" in the totara menu
    And I click on "Certifications" "link" in the "#dp-plan-content" "css_element"
    Then I should see "Test Certification"
    And I should see "Complete"
    And I should see "Not due for renewal"
