@totara @totara_certification @javascript
Feature: Certification editing tool
  In order to edit certification completions
  I need to have the tool enabled

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
      | user002  | fn_002    | ln_002   | user002@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname |
      | Cert 1   | filtest   |
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1       |
      | enableprograms                | Disable |
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Cert 1" "table_row"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "fn_001 ln_001" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "fn_002 ln_002" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"

  Scenario: Confirm that you can not save an invalid state
    Given I set the field "Certification completion state" to "Invalid state - select a valid state"
    Then the "In progress" "field" should be disabled
    And the "Certification status" "field" should be disabled
    And the "Renewal status" "field" should be disabled
    And the "Certification path" "field" should be disabled
    And the "Program status" "field" should be disabled
    And the "Save changes" "button" should be disabled

  Scenario: Confirm that the newly assigned state is saved correctly
    Given I set the field "Certification completion state" to "Newly assigned"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Not certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Not due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Certification"
    And the "Program status" "field" should be disabled
    And the field "Program status" matches value "Program incomplete"

    When I set the following fields to these values:
      | In progress      | Yes       |
      | timedue[enabled] | 1         |
      | timedue[day]     | 3         |
      | timedue[month]   | September |
      | timedue[year]    | 2030      |
      | timedue[hour]    | 12        |
      | timedue[minute]  | 30        |
    Then the field "Certification status" matches value "In progress"
    And I click on "Save changes" "button"
    # A second press is required to bring it back to the edit screen to check the values again
    When I click on "Save changes" "button"
    And I should see "Due date: 3 September 2030" in the "Transactions" "fieldset"
    And I should see "Renewal status: renewalstatus_notdue" in the "Transactions" "fieldset"
    And I should see "Status: status_inprogress" in the "Transactions" "fieldset"
    Then I should see "Completion changes have been saved"
    And the following fields match these values:
      | In progress          | Yes         |
      | timedue[enabled]     | 1           |
      | timedue[day]         | 3           |
      | timedue[month]       | September   |
      | timedue[year]        | 2030        |
      | timedue[hour]        | 12          |
      | timedue[minute]      | 30          |
      | Certification status | In progress |
    When I follow "Return to certification"
    Then I should see "In progress" in the "fn_001 ln_001" "table_row"
    And I should see "Not certified" in the "fn_002 ln_002" "table_row"

  Scenario: Confirm that the Certified, before window opens state is saved correctly
    Given I set the field "Certification completion state" to "Certified, before window opens"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Not due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Recertification"
    And the "Program status" "field" should be disabled
    And the field "Program status" matches value "Program complete"
    # Note: "In progress" should be a static element with "Not applicable"
    And I set the following fields to these values:
      | timecompleted[day]          | 3         |
      | timecompleted[month]        | September |
      | timecompleted[year]         | 2030      |
      | timecompleted[hour]         | 12        |
      | timecompleted[minute]       | 30        |
      | timewindowopens[day]        | 2         |
      | timewindowopens[month]      | September |
      | timewindowopens[year]       | 2030      |
      | timewindowopens[hour]       | 12        |
      | timewindowopens[minute]     | 30        |
      | timeexpires[day]            | 1         |
      | timeexpires[month]          | September |
      | timeexpires[year]           | 2030      |
      | timeexpires[hour]           | 12        |
      | timeexpires[minute]         | 30        |
      | baselinetimeexpires[day]    | 1         |
      | baselinetimeexpires[month]  | September |
      | baselinetimeexpires[year]   | 2030      |
      | baselinetimeexpires[hour]   | 12        |
      | baselinetimeexpires[minute] | 30        |
    And I click on "Save changes" "button"
    Then I should see "Window open date should not be before Completion date when user is certified and recertification window has not yet opened."
    And I should see "Expiry date should not be before Window open date when user is certified and recertification window has not yet opened."

    When I set the following fields to these values:
      | timewindowopens[day]    | 4         |
      | timewindowopens[month]  | September |
      | timewindowopens[year]   | 2030      |
      | timewindowopens[hour]   | 12        |
      | timewindowopens[minute] | 30        |
    And I click on "Save changes" "button"
    Then I should not see "Window open date should not be before Completion date when user is certified and recertification window has not yet opened."
    And I should see "Expiry date should not be before Window open date when user is certified and recertification window has not yet opened."

    When I set the following fields to these values:
      | timeexpires[day]            | 5         |
      | timeexpires[month]          | September |
      | timeexpires[year]           | 2030      |
      | timeexpires[hour]           | 12        |
      | timeexpires[minute]         | 30        |
      | baselinetimeexpires[day]    | 5         |
      | baselinetimeexpires[month]  | September |
      | baselinetimeexpires[year]   | 2030      |
      | baselinetimeexpires[hour]   | 12        |
      | baselinetimeexpires[minute] | 30        |
    And I click on "Save changes" "button"
    Then I should not see "Window open date should not be before Completion date when user is certified and recertification window has not yet opened."
    And I should not see "Expiry date should not be before Window open date when user is certified and recertification window has not yet opened."
    And I should see "They will change from the primary certification path to the recertification path."
    And I should see "They will no longer be due to complete certification."

    When I click on "Save changes" "button"
    Then the following fields match these values:
      | Certification completion state | Certified, before window opens |
      | timecompleted[day]             | 3                              |
      | timecompleted[month]           | September                      |
      | timecompleted[year]            | 2030                           |
      | timecompleted[hour]            | 12                             |
      | timecompleted[minute]          | 30                             |
      | timewindowopens[day]           | 4                              |
      | timewindowopens[month]         | September                      |
      | timewindowopens[year]          | 2030                           |
      | timewindowopens[hour]          | 12                             |
      | timewindowopens[minute]        | 30                             |
      | timeexpires[day]               | 5                              |
      | timeexpires[month]             | September                      |
      | timeexpires[year]              | 2030                           |
      | timeexpires[hour]              | 12                             |
      | timeexpires[minute]            | 30                             |
      | baselinetimeexpires[day]       | 5                              |
      | baselinetimeexpires[month]     | September                      |
      | baselinetimeexpires[year]      | 2030                           |
      | baselinetimeexpires[hour]      | 12                             |
      | baselinetimeexpires[minute]    | 30                             |
    And I should see "Due date: 5 September 2030" in the "Transactions" "fieldset"
    And I should see "Completion date: 3 September 2030" in the "Transactions" "fieldset"
    And I should see "Window open date: 4 September 2030" in the "Transactions" "fieldset"
    And I should see "Status: status_certified" in the "Transactions" "fieldset"
    And I should see "Renewal status: renewalstatus_notdue" in the "Transactions" "fieldset"
    When I follow "Return to certification"
    Then I should see "Certified" in the "fn_001 ln_001" "table_row"
    And I should see "Not certified" in the "fn_002 ln_002" "table_row"

  Scenario: Confirm that the Certified, window is open state is saved correctly
    Given I set the field "Certification completion state" to "Certified, window is open"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Recertification"
    And the "Program status" "field" should be disabled
    And the field "Program status" matches value "Program incomplete"
    And I set the following fields to these values:
      | In progress                 | Yes       |
      | timecompleted[day]          | 3         |
      | timecompleted[month]        | September |
      | timecompleted[year]         | 2030      |
      | timecompleted[hour]         | 12        |
      | timecompleted[minute]       | 30        |
      | timewindowopens[day]        | 2         |
      | timewindowopens[month]      | September |
      | timewindowopens[year]       | 2030      |
      | timewindowopens[hour]       | 12        |
      | timewindowopens[minute]     | 30        |
      | timeexpires[day]            | 1         |
      | timeexpires[month]          | September |
      | timeexpires[year]           | 2030      |
      | timeexpires[hour]           | 12        |
      | timeexpires[minute]         | 30        |
      | baselinetimeexpires[day]    | 1         |
      | baselinetimeexpires[month]  | September |
      | baselinetimeexpires[year]   | 2030      |
      | baselinetimeexpires[hour]   | 12        |
      | baselinetimeexpires[minute] | 30        |
    Then the field "Certification status" matches value "In progress"
    And I click on "Save changes" "button"
    Then I should see "Window open date must be after Completion date when user is certified and recertification window is open."
    And I should see "Expiry date should not be before Window open date when user is certified and recertification window is open."

    When I set the following fields to these values:
      | timewindowopens[day]    | 4         |
      | timewindowopens[month]  | September |
      | timewindowopens[year]   | 2030      |
      | timewindowopens[hour]   | 12        |
      | timewindowopens[minute] | 30        |
    And I click on "Save changes" "button"
    Then I should not see "Window open date must be after Completion date when user is certified and recertification window is open."
    And I should see "Expiry date should not be before Window open date when user is certified and recertification window is open."

    When I set the following fields to these values:
      | timeexpires[day]            | 5         |
      | timeexpires[month]          | September |
      | timeexpires[year]           | 2030      |
      | timeexpires[hour]           | 12        |
      | timeexpires[minute]         | 30        |
      | baselinetimeexpires[day]    | 5         |
      | baselinetimeexpires[month]  | September |
      | baselinetimeexpires[year]   | 2030      |
      | baselinetimeexpires[hour]   | 12        |
      | baselinetimeexpires[minute] | 30        |
    And I click on "Save changes" "button"
    Then I should not see "Window open date must be after Completion date when user is certified and recertification window is open."
    And I should not see "Expiry date should not be before Window open date when user is certified and recertification window is open."
    And I should see "They will change from the primary certification path to the recertification path."
    And I should see "Their courses will not be reset, and existing course progress may contribute to recertification, possibly triggering completion immediately (by cron). If you want to have the courses reset, set the Certification completion state to 'Certified, before window opens' and allow cron to automatically open the window for the user."

    When I click on "Save changes" "button"
    Then the following fields match these values:
      | Certification completion state | Certified, window is open |
      | timecompleted[day]             | 3                         |
      | timecompleted[month]           | September                 |
      | timecompleted[year]            | 2030                      |
      | timecompleted[hour]            | 12                        |
      | timecompleted[minute]          | 30                        |
      | timewindowopens[day]           | 4                         |
      | timewindowopens[month]         | September                 |
      | timewindowopens[year]          | 2030                      |
      | timewindowopens[hour]          | 12                        |
      | timewindowopens[minute]        | 30                        |
      | timeexpires[day]               | 5                         |
      | timeexpires[month]             | September                 |
      | timeexpires[year]              | 2030                      |
      | timeexpires[hour]              | 12                        |
      | timeexpires[minute]            | 30                        |
      | baselinetimeexpires[day]       | 5                         |
      | baselinetimeexpires[month]     | September                 |
      | baselinetimeexpires[year]      | 2030                      |
      | baselinetimeexpires[hour]      | 12                        |
      | baselinetimeexpires[minute]    | 30                        |
    And I should see "Due date: 5 September 2030" in the "Transactions" "fieldset"
    And I should see "Completion date: 3 September 2030" in the "Transactions" "fieldset"
    And I should see "Window open date: 4 September 2030" in the "Transactions" "fieldset"
    And I should see "Status: status_inprogress" in the "Transactions" "fieldset"
    And I should see "Renewal status: renewalstatus_dueforrenewal" in the "Transactions" "fieldset"
    When I follow "Return to certification"
    Then I should see "In progress" in the "fn_001 ln_001" "table_row"
    And I should see "Not certified" in the "fn_002 ln_002" "table_row"

  Scenario: Confirm that the Expired state is saved correctly
    Given I set the field "Certification completion state" to "Expired"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Expired"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Renewal expired"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Certification"
    And the "Program status" "field" should be disabled
    And the field "Program status" matches value "Program incomplete"

    When I set the following fields to these values:
      | In progress      | Yes       |
      | timedue[enabled] | 1         |
      | timedue[day]     | 3         |
      | timedue[month]   | September |
      | timedue[year]    | 2030      |
      | timedue[hour]    | 12        |
      | timedue[minute]  | 30        |
    Then the field "Certification status" matches value "In progress"
    And I click on "Save changes" "button"
    And I should see "Their courses will not be reset, and existing course progress may contribute to recertification, possibly triggering completion immediately (by cron). If you want to have the courses reset, set the Certification completion state to 'Certified, before window opens' and allow cron to automatically open the window for the user."
    And I should see "Their new completion record will not be archived when they next complete the certification. To have the completion history automatically created, set the Certification completion state to 'Certified, before window opens' and allow cron to automatically update the user's state."
    # A second press is required to bring it back to the edit screen to check the values again
    When I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "Due date: 3 September 2030" in the "Transactions" "fieldset"
    And I should see "Renewal status: renewalstatus_expired" in the "Transactions" "fieldset"
    And I should see "Status: status_inprogress" in the "Transactions" "fieldset"
    And the following fields match these values:
      | In progress          | Yes         |
      | timedue[enabled]     | 1           |
      | timedue[day]         | 3           |
      | timedue[month]       | September   |
      | timedue[year]        | 2030        |
      | timedue[hour]        | 12          |
      | timedue[minute]      | 30          |
      | Certification status | In progress |
    When I follow "Return to certification"
    Then I should see "In progress" in the "fn_001 ln_001" "table_row"
    And I should see "Not certified" in the "fn_002 ln_002" "table_row"

  Scenario Outline: Confirm completion transitions work as expected
    Given I set the field "Certification completion state" to "<oldstate>"
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I set the field "Certification completion state" to "<newstate>"
    And I click on "Save changes" "button"
    Then I <see> see "You are changing the state of this completion record from <oldstate> to <newstate>."
    And I click on "Save changes" "button"
    And the field "Certification completion state" matches value "<newstate>"
    When I follow "Return to certification"
    Then I should see "<status>" in the "fn_001 ln_001" "table_row"
    And I should see "Not certified" in the "fn_002 ln_002" "table_row"

    Examples:
      | oldstate                       | newstate                       | see        | status        |
      | Newly assigned                 | Newly assigned                 | should not | Not certified |
      | Newly assigned                 | Certified, before window opens | should     | Certified     |
      | Newly assigned                 | Certified, window is open      | should     | Certified     |
      | Newly assigned                 | Expired                        | should     | Expired       |
      | Certified, before window opens | Newly assigned                 | should     | Not certified |
      | Certified, before window opens | Certified, before window opens | should not | Certified     |
      | Certified, before window opens | Certified, window is open      | should     | Certified     |
      | Certified, before window opens | Expired                        | should     | Expired       |
      | Certified, window is open      | Newly assigned                 | should     | Not certified |
      | Certified, window is open      | Certified, before window opens | should     | Certified     |
      | Certified, window is open      | Certified, window is open      | should not | Certified     |
      | Certified, window is open      | Expired                        | should     | Expired       |
      | Expired                        | Newly assigned                 | should     | Not certified |
      | Expired                        | Certified, before window opens | should     | Certified     |
      | Expired                        | Certified, window is open      | should     | Certified     |
      | Expired                        | Expired                        | should not | Expired       |
