@javascript @mod @mod_assign @totara @totara_certification
Feature: Learners can submit assignments again when certification is expired
  In order to redo my assignment when certification is expired
  As a student
  I need to my assignment to be unlocked

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | C1        | 1                |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | Student | 1 | student1@example.com |

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I set self completion for "Course 1" in the "Miscellaneous" category
    And I turn editing mode on
    And I add a "Assignment" to section "1" and I fill the form with:
      | Assignment name | Test assignment name |
      | Description | Submit your online text |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |

    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I press "Add new certification"
    And I set the following fields to these values:
        | Full name  | Test Certification |
        | Short name | tstcert            |
    And I press "Save changes"

    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course 1" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I set the following fields to these values:
        | Use the existing certification content | 1 |
    And I press "Save changes"
    And I click on "Save all changes" "button"

    And I click on "Certification" "link" in the ".tabtree" "css_element"
    And I set the following fields to these values:
        | activenum | 6 |
        | windownum | 2 |
    And I click on "Month(s)" "option" in the "#id_activeperiod" "css_element"
    And I click on "Month(s)" "option" in the "#id_windowperiod" "css_element"
    And I click on "Use certification completion date" "option" in the "#id_recertifydatetype" "css_element"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I log out

    And the following "program assignments" exist in "totara_program" plugin:
      | program | user     |
      | tstcert | student1 |

  Scenario: Add submission then lock it then reset certification and confirm that assignment submission is unlocked
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student first submission |
    And I press "Save changes"
    And I follow "C1"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I log out

    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I follow "View all submissions"
    And I click on "Edit" "link" in the "Student 1" "table_row"
    And I click on "Prevent submission changes" "link"
    And I should see "Submission changes not allowed"

    When I wind back certification dates by 5 months
    And I run the "\totara_certification\task\update_certification_task" task
    And I should not see "Submission changes not allowed"
    And I log out

    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test assignment name"
    And I press "Add submission"
    And I set the following fields to these values:
      | Online text | I'm the student second submission |
    When I press "Save changes"
    Then I should see "Submitted"
