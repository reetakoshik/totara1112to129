@totara @totara_userdata @javascript
Feature: Yet another manual user data purging
  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username  | firstname | lastname | email             |
      | user1     | One       | Uno      | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | enablecompletion | completionstartonenrol | format         | activitytype |
      | Akoranga | akoranga  | 1                | 1                      | singleactivity | <activity>   |
    And the following "course enrolments" exist:
      | user  | course   | role    |
      | user1 | akoranga | student |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname  | shortname | activeperiod | windowperiod | recertifydatetype |
      | Tiwhikete | tiwhikete | 1 month      | 1 month      | 1                 |
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1       |
      | enableprograms                | Disable |

  Scenario: Purge user certification data manually
    Given I am on a totara site
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I follow "Tiwhikete"
    And I press "Edit certification details"

    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Akoranga" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"

    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "One Uno" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    And I am on "Akoranga" course homepage
    And I navigate to "Completion editor" node in "Course administration"
    And I click on "Edit course completion" "link" in the "One Uno" "table_row"
    And I switch to "Current completion" tab
    And I click on "Complete" "option"
    And I wait "2" seconds
    And I set the field "Time completed" to "2019-03-27 00:00"
    And I click on "Done" "button" in the ".ui-datepicker" "css_element"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Yes" "button"

    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I follow "Tiwhikete"
    And I press "Edit certification details"
    When I switch to "Completion" tab
    Then I should not see "There are no records in this report"
    And I should see "One Uno" in the "#certification_membership" "css_element"

    And I click on "Edit completion records" "link" in the "One Uno" "table_row"
    And I click on "Certified, before window opens" "option"
    When I click on "Save changes" "button"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"
    And the field "Program status" matches value "Program complete"

    ## DELETE USER ##

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I click on "Delete One Uno" "link"
    And I click on "Delete" "button"
    Then I should not see "One Uno" in the "#system_browse_users" "css_element"

    # The user record is still in the completion editors
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I follow "Tiwhikete"
    And I press "Edit certification details"
    When I switch to "Completion" tab
    Then I should not see "There are no records in this report"
    And I should see "One Uno" in the "#certification_membership" "css_element"

    And I am on "Akoranga" course homepage
    When I navigate to "Completion editor" node in "Course administration"
    Then I should not see "There are no records in this report"
    And I should see "One Uno" in the "#course_membership" "css_element"

    And I navigate to "Purge types" node in "Site administration > User data management"
    And I click on "Add purge type" "button"
    And I click on "Delete" "radio"
    And I click on "Continue" "button"
    And I set the following fields to these values:
      | Full name                                | Tahitahi |
      | ID number                                | tahitahi |
      | Manual data purging                      | 1        |
      | Certification assignments and completion | 1        |
    And I click on "Add" "button"

    And I navigate to "Deleted user accounts" node in "Site administration > User data management"
    When I click on "User data" "link" in the "One Uno" "table_row"
    Then I should see "None" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"

    And I click on "Select purge type" "button"
    And I click on "Tahitahi" "option"
    And I click on "Purge user data" "button"
    When I click on "Proceed with purge" "button"
    Then I should see "An ad hoc task for manual user data purging was created. You will receive a notification once it has completed successfully."
    And I should see "1" in the "All data purges" "definition_exact"
    And I should see "1" in the "Pending purges" "definition_exact"

    When I run the scheduled task "totara_userdata\task\purge_deleted"
    Then I should see "1" in the "All data purges" "definition_exact"
    And I should see "None" in the "Pending purges" "definition_exact"

    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I follow "Tiwhikete"
    And I press "Edit certification details"
    When I switch to "Completion" tab
    Then I should see "There are no records in this report"

    And I am on "Akoranga" course homepage
    When I navigate to "Completion editor" node in "Course administration"
    Then I should not see "There are no records in this report"
    And I should see "One Uno" in the "#course_membership" "css_element"
