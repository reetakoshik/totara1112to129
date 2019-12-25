@totara @totara_certification @totara_courseprogressbar @javascript
Feature: User reassignment to a certification
  In order to view a certification
  As a user
  I need to login if forcelogin enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username    | firstname | lastname | email              |
      | manager     | Cassy     | Cas      | cassy@example.com  |
      | jimmy       | Jimmy     | Jim      | jimmy@example.com  |
      | timmy       | Timmy     | Tim      | timmy@example.com  |
    And the following "courses" exist:
      | fullname         | shortname | format | enablecompletion |
      | Certify Course   | CC1       | topics | 1                |
      | Recertify Course | RC1       | topics | 1                |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname       | shortname |
      | Reassign Tests | reasstst  |
    And I log in as "admin"
    And I set the following administration settings values:
      | menulifetime                  | 0       |
      | enableprogramcompletioneditor | 1       |
      | enableprograms                | Disable |
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Certify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Recertify Course" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Timmy Tim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

  Scenario: Reassign someone with no history records
    # And I unassign jimmy.
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Certifications" in the ".tabtree" "css_element"
    And I log out
    And I log in as "admin"

    # And I reassign jimmy.
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Not certified" in the "Jimmy Jim" "table_row"

    # We should also check reassignment via the Record of Learning to make sure
    # the way we confirmed unassignment earlier was valid.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Certifications" in the ".tabtree" "css_element"

  Scenario: Reassign someone where history records all have unassigned set to No
    # And I create some history records.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[day]          | 1    |
      | timecompleted[month]        | 1    |
      | timecompleted[year]         | 2000 |
      | timecompleted[hour]         | 7    |
      | timecompleted[minute]       | 00   |
      | timewindowopens[day]        | 1    |
      | timewindowopens[month]      | 1    |
      | timewindowopens[year]       | 2020 |
      | timewindowopens[hour]       | 7    |
      | timewindowopens[minute]     | 00   |
      | timeexpires[day]            | 1    |
      | timeexpires[month]          | 1    |
      | timeexpires[year]           | 2030 |
      | timeexpires[hour]           | 7    |
      | timeexpires[minute]         | 00   |
      | baselinetimeexpires[day]    | 1    |
      | baselinetimeexpires[month]  | 1    |
      | baselinetimeexpires[year]   | 2030 |
      | baselinetimeexpires[hour]   | 7    |
      | baselinetimeexpires[minute] | 00   |
      | Unassigned                  | No   |
    And I click on "Save changes" "button"
    And I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[day]          | 1    |
      | timecompleted[month]        | 1    |
      | timecompleted[year]         | 1990 |
      | timecompleted[hour]         | 7    |
      | timecompleted[minute]       | 00   |
      | timewindowopens[day]        | 1    |
      | timewindowopens[month]      | 1    |
      | timewindowopens[year]       | 2000 |
      | timewindowopens[hour]       | 7    |
      | timewindowopens[minute]     | 00   |
      | timeexpires[day]            | 1    |
      | timeexpires[month]          | 1    |
      | timeexpires[year]           | 2010 |
      | timeexpires[hour]           | 7    |
      | timeexpires[minute]         | 00   |
      | baselinetimeexpires[day]    | 1    |
      | baselinetimeexpires[month]  | 1    |
      | baselinetimeexpires[year]   | 2010 |
      | baselinetimeexpires[hour]   | 7    |
      | baselinetimeexpires[minute] | 00   |
      | Unassigned                  | No   |
    And I click on "Save changes" "button"
    And I follow "Return to certification"

    # And I unassign jimmy.
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Certifications" in the ".tabtree" "css_element"

    # And I reassign jimmy.
    And I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Not certified" in the "Jimmy Jim" "table_row"

    # We should also check reassignment via the Record of Learning to make sure
    # the way we confirmed unassignment earlier was valid.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Certifications" in the ".tabtree" "css_element"

  Scenario: Check the validation on the unassigned field in the editor
    # And I create some history records.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[day]          | 1    |
      | timecompleted[month]        | 1    |
      | timecompleted[year]         | 1980 |
      | timecompleted[hour]         | 7    |
      | timecompleted[minute]       | 00   |
      | timewindowopens[day]        | 1    |
      | timewindowopens[month]      | 1    |
      | timewindowopens[year]       | 1990 |
      | timewindowopens[hour]       | 7    |
      | timewindowopens[minute]     | 00   |
      | timeexpires[day]            | 1    |
      | timeexpires[month]          | 1    |
      | timeexpires[year]           | 2000 |
      | timeexpires[hour]           | 7    |
      | timeexpires[minute]         | 00   |
      | baselinetimeexpires[day]    | 1    |
      | baselinetimeexpires[month]  | 1    |
      | baselinetimeexpires[year]   | 2000 |
      | baselinetimeexpires[hour]   | 7    |
      | baselinetimeexpires[minute] | 00   |
      | Unassigned                  | Yes  |
    And I click on "Save changes" "button"
    Then I should see "Only one historical record can be marked as unassigned, and only if there is no current assignment"

    When I set the following fields to these values:
      | Unassigned              | No |
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    # And I unassign jimmy.
    When I follow "Return to certification"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[day]          | 1    |
      | timecompleted[month]        | 1    |
      | timecompleted[year]         | 1990 |
      | timecompleted[hour]         | 7    |
      | timecompleted[minute]       | 00   |
      | timewindowopens[day]        | 1    |
      | timewindowopens[month]      | 1    |
      | timewindowopens[year]       | 2000 |
      | timewindowopens[hour]       | 7    |
      | timewindowopens[minute]     | 00   |
      | timeexpires[day]            | 1    |
      | timeexpires[month]          | 1    |
      | timeexpires[year]           | 2010 |
      | timeexpires[hour]           | 7    |
      | timeexpires[minute]         | 00   |
      | baselinetimeexpires[day]    | 1    |
      | baselinetimeexpires[month]  | 1    |
      | baselinetimeexpires[year]   | 2010 |
      | baselinetimeexpires[hour]   | 7    |
      | baselinetimeexpires[minute] | 00   |
      | Unassigned                  | Yes  |
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    When I click on "Add history" "button"
    And I set the following fields to these values:
      | timecompleted[day]          | 1    |
      | timecompleted[month]        | 1    |
      | timecompleted[year]         | 2000 |
      | timecompleted[hour]         | 7    |
      | timecompleted[minute]       | 00   |
      | timewindowopens[day]        | 1    |
      | timewindowopens[month]      | 1    |
      | timewindowopens[year]       | 2010 |
      | timewindowopens[hour]       | 7    |
      | timewindowopens[minute]     | 00   |
      | timeexpires[day]            | 1    |
      | timeexpires[month]          | 1    |
      | timeexpires[year]           | 2020 |
      | timeexpires[hour]           | 7    |
      | timeexpires[minute]         | 00   |
      | baselinetimeexpires[day]    | 1    |
      | baselinetimeexpires[month]  | 1    |
      | baselinetimeexpires[year]   | 2020 |
      | baselinetimeexpires[hour]   | 7    |
      | baselinetimeexpires[minute] | 00   |
      | Unassigned                  | Yes  |
    And I click on "Save changes" "button"
    Then I should see "Only one historical record can be marked as unassigned, and only if there is no current assignment"

    When I set the following fields to these values:
      | Unassigned              | No |
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"

    # And I reassign jimmy.
    When I follow "Return to certification"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Certified" in the "Jimmy Jim" "table_row"

    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "Certified"
    And I should see "Not due for renewal"
    And I should see "1 Jan 1990"
    And I should see "1 Jan 2000"
    And I should see "1 Jan 2010"

  Scenario: Reassign someone with an assigned history record
    # And I update the certification status.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Newly assigned |
      | timedue[day]                   | 1              |
      | timedue[month]                 | 1              |
      | timedue[year]                  | 2020           |
      | timedue[hour]                  | 7              |
      | timedue[minute]                | 00             |
    And I click on "Save changes" "button"
    And I follow "Return to certification"

    # And I unassign jimmy.
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should not see "Certifications" in the ".tabtree" "css_element"

    # And I reassign jimmy.
    And I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Not certified" in the "Jimmy Jim" "table_row"

    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    Then I should see "Certifications" in the ".tabtree" "css_element"
    And I switch to "Certifications" tab
    Then I should see "Not certified"
    And I should see "Due"
    And I should not see "1 Jan 2020"

  Scenario: Reassign someone with a certified history record
    # And I update the certification status.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Certified, before window opens |
      | timecompleted[day]             | 1                              |
      | timecompleted[month]           | 1                              |
      | timecompleted[year]            | 2010                           |
      | timecompleted[hour]            | 7                              |
      | timecompleted[minute]          | 00                             |
      | timewindowopens[day]           | 1                              |
      | timewindowopens[month]         | 1                              |
      | timewindowopens[year]          | 2020                           |
      | timewindowopens[hour]          | 7                              |
      | timewindowopens[minute]        | 00                             |
      | timeexpires[day]               | 1                              |
      | timeexpires[month]             | 1                              |
      | timeexpires[year]              | 2030                           |
      | timeexpires[hour]              | 7                              |
      | timeexpires[minute]            | 00                             |
      | baselinetimeexpires[day]       | 1                              |
      | baselinetimeexpires[month]     | 1                              |
      | baselinetimeexpires[year]      | 2030                           |
      | baselinetimeexpires[hour]      | 7                              |
      | baselinetimeexpires[minute]    | 00                             |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I follow "Return to certification"

    # And I unassign jimmy.
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "prior to unassigned from certification"

    # And I reassign jimmy.
    And I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Certified" in the "Jimmy Jim" "table_row"

    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "Certified"
    And I should see "Not due for renewal"
    And I should see "1 Jan 2010"
    And I should see "1 Jan 2020"
    And I should see "1 Jan 2030"

  Scenario: Reassign someone with a window opened history record
    # And I update the certification status.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Certified, window is open |
      | timecompleted[day]             | 1                         |
      | timecompleted[month]           | 1                         |
      | timecompleted[year]            | 2000                      |
      | timecompleted[hour]            | 7                         |
      | timecompleted[minute]          | 00                        |
      | timewindowopens[day]           | 1                         |
      | timewindowopens[month]         | 1                         |
      | timewindowopens[year]          | 2010                      |
      | timewindowopens[hour]          | 7                         |
      | timewindowopens[minute]        | 00                        |
      | timeexpires[day]               | 1                         |
      | timeexpires[month]             | 1                         |
      | timeexpires[year]              | 2020                      |
      | timeexpires[hour]              | 7                         |
      | timeexpires[minute]            | 00                        |
      | baselinetimeexpires[day]       | 1                         |
      | baselinetimeexpires[month]     | 1                         |
      | baselinetimeexpires[year]      | 2020                      |
      | baselinetimeexpires[hour]      | 7                         |
      | baselinetimeexpires[minute]    | 00                        |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I follow "Return to certification"

    # And I unassign jimmy.
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "prior to unassigned from certification"

    # And I reassign.
    And I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check they are assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Certified" in the "Jimmy Jim" "table_row"

    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "Certified"
    And I should see "Due"
    And I should see "1 Jan 2000"
    And I should see "1 Jan 2010"
    And I should see "1 Jan 2020"

  Scenario: Reassign someone with an expired history record
    # And I update the certification status.
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Jimmy Jim" "table_row"
    # Note: the timecompleted, timewindowopens and timeexpires are static, only "Due date" could be changed now
    And I set the following fields to these values:
      | Certification completion state | Expired |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I follow "Return to certification"

    # And I unassign jimmy.
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "Not assigned"

    # And I reassign jimmy.
    And I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds

    # And I check jimmy is assigned.
    And I switch to "Completion" tab
    Then I should see "Jimmy Jim"
    And I should see "Expired" in the "Jimmy Jim" "table_row"

    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab

    Then I should see "Expired"
    And I should see "Renewal expired"

  Scenario: Full run through with several reassignments.
    # A little additional setup.
    When I switch to "Certification" tab
    And I set the following fields to these values:
      | activenum | 6 |
      | windownum | 2 |
    And I set the field "activeperiod" to "Month(s)"
    And I set the field "windowperiod" to "Month(s)"
    And I set the field "recertifydatetype" to "Use certification completion date"
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I set self completion for "Certify Course" in the "Miscellaneous" category
    And I set self completion for "Recertify Course" in the "Miscellaneous" category

    # Get back the removed dashboard item for now.
    And I navigate to "Main menu" node in "Site administration > Navigation"
    And I click on "Edit" "link" in the "Required Learning" "table_row"
    And I set the field "Parent item" to "Top"
    And I press "Save changes"

    # Complete the certification.
    And I log out
    And I log in as "jimmy"
    And I click on "Required Learning" in the totara menu
    Then I should see "Reassign Tests"
    And I should see "Certify Course"
    And I should not see "Recertify Course"

    When I click on "Certify Course" "link" in the ".display-program" "css_element"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    Then I should see "100%" in the "Certify Course" "table_row"
    And I switch to "Certifications" tab
    And I should see "Certified" in the "Reassign Tests" "table_row"

    # And I unassign jimmy.
    When I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "prior to unassigned from certification" in the "Reassign Tests" "table_row"

    # Wind back certification dates.
    When I log out
    And I log in as "admin"
    And I wind back certification dates by 5 months

    # Reassign & Run certification update task.
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I run the "\totara_certification\task\update_certification_task" task

    # Check window opening.
    When I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "Open" in the "Reassign Tests" "table_row"

    # And I unassign jimmy.
    When I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "prior to unassigned from certification" in the "Reassign Tests" "table_row"

    # Wind back certification dates.
    When I log out
    And I log in as "admin"
    And I wind back certification dates by 5 months

    # Reassign & Run certification update task.
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I run the "\totara_certification\task\update_certification_task" task

    # Check certification expiration
    And I switch to "Completion" tab
    Then I should see "Expired" in the "Jimmy Jim" "table_row"

    # And I unassign jimmy.
    When I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I click on "Remove program assignment" "link" in the "Jimmy Jim" "table_row"
    And I click on "Remove" "button"

    # Confirm unassignment.
    And I log out
    And I log in as "jimmy"
    And I click on "Record of Learning" in the totara menu
    And I switch to "Certifications" tab
    Then I should see "prior to unassigned from certification" in the "Reassign Tests" "table_row"

    # Complete course
    When the following "course enrolments" exist:
      | user  | course | role    |
      | jimmy | CC1    | student |
    And I switch to "Courses" tab
    And I click on "Certify Course" "link" in the "Certify Course" "table_row"
    And I click on "Complete course" "link"
    And I click on "Yes" "button"
    And I click on "Record of Learning" in the totara menu
    Then I should see "100%" in the "Certify Course" "table_row"

    # Reassign & Run certification update task.
    When I log out
    And I log in as "admin"
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Reassign Tests" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Jimmy Jim" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I run the "\totara_program\task\completions_task" task
    And I run the "\totara_certification\task\update_certification_task" task

    # Check certification recertification
    And I switch to "Completion" tab
    Then I should see "Certified" in the "Jimmy Jim" "table_row"
