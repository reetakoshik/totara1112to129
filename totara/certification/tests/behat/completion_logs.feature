@totara @totara_certification @javascript
Feature: Completion logs are created
  In order to see that the logs are created
  I need to step a user through the certification process

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1       |
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user001  | fn_001    | ln_001   | user001@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname |
      | Cert 1   | cert1     |
    And the following "program assignments" exist in "totara_program" plugin:
      | program | user    |
      | cert1   | user001 |

  Scenario: The certification window open and expired messages are sent
    Given I click on "Certifications" in the totara menu
    And I follow "Cert 1"
    And I press "Edit certification details"
    And I click on "Completion" "link" in the ".tabtree" "css_element"
    And I click on "Edit completion records" "link" in the "fn_001 ln_001" "table_row"
    When I set the following fields to these values:
      | Certification completion state | Certified, before window opens |
      | timecompleted[day]             | 3                              |
      | timecompleted[month]           | September                      |
      | timecompleted[year]            | 2011                           |
      | timecompleted[hour]            | 12                             |
      | timecompleted[minute]          | 30                             |
      | timewindowopens[day]           | 2                              |
      | timewindowopens[month]         | September                      |
      | timewindowopens[year]          | 2012                           |
      | timewindowopens[hour]          | 12                             |
      | timewindowopens[minute]        | 30                             |
      | timeexpires[day]               | 1                              |
      | timeexpires[month]             | September                      |
      | timeexpires[year]              | 2013                           |
      | timeexpires[hour]              | 12                             |
      | timeexpires[minute]            | 30                             |
    And I click on "Save changes" "button"
    Then I should see "You are changing the state of this completion record from Newly assigned to Certified, before window opens"
    When I click on "Save changes" "button"
    Then I should see "Completion changes have been saved"
    When I run the scheduled task "\totara_certification\task\update_certification_task"
    Then I should see "Completion manually edited"
    And I should see "Certification completion copied to new completion history"
    And I should see "Window opened, current certification completion archived, certif_completion updated (step 1 of 2)"
    And I should see "Window opened, prog_completion updated, course and activity completion will be archived (step 2 of 2)"
    And I should see "Certification expired, changed to primary certification path"
