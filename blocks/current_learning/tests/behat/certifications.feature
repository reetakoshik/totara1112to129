@totara @block @block_current_learning @totara_certification
Feature: User certifications and their courses appear correctly in the current learning block
  In order to ensure certifications appear correctly in the current learning block
  As an admin
  I need to create a certification with content

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email |
      | user001 | fn_001 | ln_001 | user001@example.com |
    And the following "courses" exist:
      | fullname           | shortname | format | enablecompletion |
      | Certify Course 1   | CC1       | topics | 1                |
      | Certify Course 2   | CC2       | topics | 1                |
      | Recertify Course 1 | RC1       | topics | 1                |
      | Recertify Course 2 | RC2       | topics | 1                |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime | 0 |
    And I set self completion for "Certify Course 1" in the "Miscellaneous" category
    And I set self completion for "Certify Course 2" in the "Miscellaneous" category
    And I set self completion for "Recertify Course 1" in the "Miscellaneous" category
    And I set self completion for "Recertify Course 2" in the "Miscellaneous" category
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I press "Add new certification"
    And I set the following fields to these values:
      | Full name  | Test Certification |
      | Short name | tstcert            |
    And I press "Save changes"

    # Add CS 1.
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Certify Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"

    # Add CS 2.
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Certify Course 2" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"

    # Use OR operator.
    And I set the field with xpath "//div[@class='nextsetoperator-and']/child::select" to "or"

    # Add recert CS.
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Recertify Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Recertify Course 2" "link" in the "addmulticourse" "totaradialogue"
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
    And I log out
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user    |
      | tstcert | user001 |

  @javascript
  Scenario: A user can view their certification and it's courses in the current learning block
    Given I log in as "user001"
    And I click on "Dashboard" in the totara menu
    Then I should see "Test Certification" in the "Current Learning" "block"
    And I toggle "Test Certification" in the current learning block
    And I should see "Certify Course 1" in "Test Certification" within the current learning block
    And I should see "Certify Course 2" in "Test Certification" within the current learning block
    And I should not see "Recertify Course 1" in "Test Certification" within the current learning block
    And I should not see "Recertify Course 2" in "Test Certification" within the current learning block

    # Complete Certify Course 1.
    When I click on "Certify Course 1" "link"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"

    # The current learning block should now be empty.
    When I click on "Dashboard" in the totara menu
    Then I should not see "Test Certification" in the "Current Learning" "block"
    And I should not see "Certify Course 1" in the "Current Learning" "block"
    And I should not see "Certify Course 2" in the "Current Learning" "block"
    And I should not see "Recertify Course 1" in the "Current Learning" "block"
    And I should not see "Recertify Course 2" in the "Current Learning" "block"

    # Push user to recert and check block contents.
    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I click on "Dashboard" in the totara menu
    Then I should see "Test Certification" in the "Current Learning" "block"
    And I toggle "Test Certification" in the current learning block
    And I should not see "Certify Course 1" in "Test Certification" within the current learning block
    And I should not see "Certify Course 2" in "Test Certification" within the current learning block
    And I should see "Recertify Course 1" in "Test Certification" within the current learning block
    And I should see "Recertify Course 2" in "Test Certification" within the current learning block

    # Add manual enrolment for the user.
    And I log out
    When the following "course enrolments" exist:
      | user      | course  | role         |
      | user001   | CC1     | student      |
      | user001   | CC2     | student      |

    # Courses should now be displayed in the block as stand alone courses.
    Given I log in as "user001"
    And I click on "Dashboard" in the totara menu
    And I should see "Certify Course 1" in the "Current Learning" "block"
    And I should see "Certify Course 2" in the "Current Learning" "block"

    # The certification should still contain the correct content.
    And I should see "Test Certification" in the "Current Learning" "block"
    And I toggle "Test Certification" in the current learning block
    And I should see "Recertify Course 1" in "Test Certification" within the current learning block
    And I should see "Recertify Course 2" in "Test Certification" within the current learning block
    And I should not see "Certify Course 1" in "Test Certification" within the current learning block
    And I should not see "Certify Course 2" in "Test Certification" within the current learning block
