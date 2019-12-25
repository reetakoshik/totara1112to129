@totara @totara_certification @totara_completion_upload @javascript @_file_upload
Feature: Certification history can be imported as long as records are considered unique
  Uploaded history records respect the certification history uniqueness rules
  Allowing an admin to create valid data

  Scenario: Certification history records are added where due date matches and completion does not
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Learner    | One       | learner1@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | idnumber |
      | Certification One | cert1     | 1        |
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_history_similar_records.csv" file to "Choose certification file to upload" filemanager
    And I set the field "Import action" to "Save to history"
    And I click on "Upload" "button" in the "#mform2" "css_element"
    Then I should see "CSV import completed"
    And I should see "2 Records successfully imported as certifications"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Certifications" tab
    And I click on "2" "link" in the "Certification One" "table_row"
    And the following should exist in the "plan_certifications_history" table:
      | Certification name  | Current?  | Completion date | Expiration date |
      | Certification One   | Yes       |                 |                 |
      | Certification One   | No        | 16 Jun 2015     | 12 Feb 2031     |
      | Certification One   | No        | 15 May 2015     | 12 Feb 2031     |

    # Follow-up test. This test window opening rather than completion import itself, but is mostly an issue for
    # data that would have been imported, so is useful to test here.
    # We create a current completion where the window will open on next cron run and ensure that none of the
    # completion dates are overwritten
    When I set the following administration settings values:
      | enableprogramcompletioneditor | 1       |
    And I navigate to "Manage certifications" node in "Site administration > Certifications"
    And I follow "Miscellaneous"
    And I click on "Settings" "link" in the "Certification One" "table_row"
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Learner One" "table_row"
    And I set the following fields to these values:
      | Certification completion state | Certified, before window opens |
    And I set the following fields to these values:
      | timecompleted[day]      | 17       |
      | timecompleted[month]    | July     |
      | timecompleted[year]     | 2015     |
      | timecompleted[hour]     | 12       |
      | timecompleted[minute]   | 30       |
      | timewindowopens[day]    | 18       |
      | timewindowopens[month]  | July     |
      | timewindowopens[year]   | 2017     |
      | timewindowopens[hour]   | 12       |
      | timewindowopens[minute] | 30       |
      | timeexpires[day]        | 12       |
      | timeexpires[month]      | February |
      | timeexpires[year]       | 2031     |
      | timeexpires[hour]       | 00       |
      | timeexpires[minute]     | 00       |
    And I click on "Save changes" "button"
    And I click on "Save changes" "button"
    And I run the scheduled task "\totara_certification\task\update_certification_task"
    And I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Certifications" tab
    And I click on "3" "link" in the "Certification One" "table_row"
    Then the following should exist in the "plan_certifications_history" table:
      | Certification name  | Current?  | Completion date | Expiration date |
      | Certification One   | Yes       |                 |                 |
      | Certification One   | No        | 16 Jun 2015     | 12 Feb 2031     |
      | Certification One   | No        | 15 May 2015     | 12 Feb 2031     |
      | Certification One   | No        | 17 Jul 2015     | 12 Feb 2031     |

  Scenario: Only one history record is added when the due date, completion, certification and user all match
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Learner    | One       | learner1@example.com |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | idnumber |
      | Certification One | cert1     | 1        |
    Given I log in as "admin"
    When I navigate to "Upload Completion Records" node in "Site administration > Courses > Upload Completion Records"
    And I upload "totara/completionimport/tests/behat/fixtures/certification_completion_history_matching_records.csv" file to "Choose certification file to upload" filemanager
    And I set the field "Import action" to "Save to history"
    And I click on "Upload" "button" in the "#mform2" "css_element"
    Then I should see "CSV import completed"

    When I navigate to "Browse list of users" node in "Site administration > Users"
    And I follow "Learner One"
    And I click on "Record of Learning" "link" in the ".profile_tree" "css_element"
    And I switch to "Certifications" tab
    And I click on "1" "link" in the "Certification One" "table_row"
    And the following should exist in the "plan_certifications_history" table:
      | Certification name  | Current?  | Completion date | Expiration date |
      | Certification One   | Yes       |                 |                 |
      | Certification One   | No        | 16 Jun 2015     | 12 Feb 2031     |
