@totara @totara_certification @javascript
Feature: Certification editing tool history
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
    And I click on "Add history" "button"
    And I set the field "Certification completion state" to "Certified, before window opens"
    And I set the following fields to these values:
      | timecompleted[day]      | 1       |
      | timecompleted[month]    | January |
      | timecompleted[year]     | 2001    |
      | timecompleted[hour]     | 1       |
      | timecompleted[minute]   | 30      |
    And I click on "Save changes" "button"

  Scenario: Confirm that you can not save a historical invalid state
    Given I click on "Add history" "button"
    And I set the field "Certification completion state" to "Invalid state - select a valid state"
    Then the "In progress" "field" should be disabled
    And the "Certification status" "field" should be disabled
    And the "Renewal status" "field" should be disabled
    And the "Certification path" "field" should be disabled
    And the "Save changes" "button" should be disabled

  Scenario: Confirm that a historical newly assigned state is saved correctly
    Given I click on "Add history" "button"
    And I set the field "Certification completion state" to "Newly assigned"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Not certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Not due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Certification"
    And I set the field "In progress" to "Yes"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "1 January 2001" in the "Certified, before window opens" "table_row"
    When I click on "Edit" "link" in the "Newly assigned" "table_row"
    And the following fields match these values:
      | In progress          | Yes         |
      | Certification status | In progress |

  Scenario: Confirm that a historical Certified, before window opens state is saved correctly
    # There is a negative test that uses Expired so set that to something else
    Given I click on "Edit" "link" in the "Certified, before window opens" "table_row"
    And I set the field "Certification completion state" to "Certified, window is open"
    And I click on "Save changes" "button"
    # Now do the real test
    Given I click on "Add history" "button"
    And I set the field "Certification completion state" to "Certified, before window opens"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Not due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Recertification"
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
    Then I should not see "Window open date must be after Completion date when user is certified and recertification window is open."
    And I should not see "Expiry date should not be before Window open date when user is certified and recertification window is open."
    And I should see "1 January 2001" in the "Certified, window is open" "table_row"
    When I click on "Edit" "link" in the "Certified, before window opens" "table_row"
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

  Scenario: Confirm that a historical Certified, window is open state is saved correctly
    Given I click on "Add history" "button"
    And I set the field "Certification completion state" to "Certified, window is open"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Certified"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Due for renewal"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Recertification"
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
    And I should see "1 January 2001" in the "Certified, before window opens" "table_row"
    When I click on "Edit" "link" in the "Certified, window is open" "table_row"
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

  Scenario: Confirm that a historical Expired state is saved correctly
    And I click on "Add history" "button"
    And I set the field "Certification completion state" to "Expired"
    And the "Certification status" "field" should be disabled
    And the field "Certification status" matches value "Expired"
    And the "Renewal status" "field" should be disabled
    And the field "Renewal status" matches value "Renewal expired"
    And the "Certification path" "field" should be disabled
    And the field "Certification path" matches value "Certification"
    And I set the field "In progress" to "Yes"
    And I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"
    And I should see "1 January 2001" in the "Certified, before window opens" "table_row"
    When I click on "Edit" "link" in the "Expired" "table_row"
    And the following fields match these values:
      | In progress          | Yes         |
      | Certification status | In progress |
