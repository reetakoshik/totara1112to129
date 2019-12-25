@totara @totara_certification @mod @mod_facetoface @javascript
Feature: Certification completion date is based on course completion time
  The completion time of a certification
  is based on
  the course completion date

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | learner1 | Learner   | One      | learner1@example.com |
    And the following "courses" exist:
      | fullname   | shortname | format | enablecompletion | completionstartonenrol |
      | Course One | course1   | topics | 1                | 1                      |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname          | shortname | activeperiod | windowperiod | recertifydatetype |
      | Certification One | cert1     | 1 month      | 1 month      | 1                 |

  Scenario: Changing completion of face-to-face to later time does not lead to invalid state
    Given I log in as "admin"
    And I set the following administration settings values:
      | enableprogramcompletioneditor | 1       |
      | enableprograms                | Disable |
    And I click on "Certifications" in the totara menu
    And I follow "Certification One"
    And I press "Edit certification details"
    And I switch to "Content" tab
    And I click on "addcontent_ce" "button" in the "#programcontent_ce" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course One" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I click on "addcontent_rc" "button" in the "#programcontent_rc" "css_element"
    And I click on "Miscellaneous" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Course One" "link" in the "addmulticourse" "totaradialogue"
    And I click on "Ok" "button" in the "addmulticourse" "totaradialogue"
    And I wait "1" seconds
    And I press "Save changes"
    And I click on "Save all changes" "button"
    And I switch to "Assignments" tab
    And I set the field "Add a new" to "Individuals"
    And I click on "Add" "button"
    And I click on "Add individuals to program" "button"
    And I click on "Learner One" "link" in the "Add individuals to program" "totaradialogue"
    And I click on "Ok" "button" in the "Add individuals to program" "totaradialogue"
    And I wait "1" seconds
    And I click on "Save changes" "button"
    And I click on "Save all changes" "button"
    And I click on "Find Learning" in the totara menu
    And I follow "Course One"
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar One              |
      | Description | Test seminar description |
      | Completion tracking           | Show activity as complete when conditions are met |
      | completionstatusrequired[100] | 1                                                 |
    And I navigate to "Course completion" node in "Course administration"
    And I expand all fieldsets
    And I set the following fields to these values:
      | Seminar - Seminar One | 1 |
    And I press "Save changes"
    And I follow "View all events"
    And I follow "Add a new event"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -2               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | -1               |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -2               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I press "Save changes"
    And I click on "Attendees" "link"
    And I click on "Add users" "option" in the "#menuf2f-actions" "css_element"
    And I click on "Learner One, learner1@example.com" "option"
    And I press "Add"
    And I wait "1" seconds
    And I press "Continue"
    And I press "Confirm"
    And I click on "Take attendance" "link"
    And I click on "Fully attended" "option" in the "Learner One" "table_row"
    And I press "Save attendance"
    Then I should see "Successfully updated attendance"
    When I follow "Go back"
    And I click on "Edit" "link" in the ".lastrow" "css_element"
    And I click on "Edit session" "link"
    And I fill seminar session with relative date in form data:
      | sessiontimezone    | Pacific/Auckland |
      | timestart[day]     | -1               |
      | timestart[month]   | 0                |
      | timestart[year]    | 0                |
      | timestart[hour]    | -1               |
      | timestart[minute]  | 0                |
      | timefinish[day]    | -1               |
      | timefinish[month]  | 0                |
      | timefinish[year]   | 0                |
      | timefinish[hour]   | 0                |
      | timefinish[minute] | 0                |
    And I press "OK"
    And I press "Save changes"
    And I click on "Certifications" in the totara menu
    And I follow "Certification One"
    And I press "Edit certification details"
    And I switch to "Completion" tab
    And I click on "Edit completion records" "link" in the "Learner One" "table_row"
    # This obviously doesn't look like expected behaviour at this point.
    # However changing the face-to-face completion time after the certification was complete
    # is not an intended way to use this system.
    # Previously these steps result in an invalid state and stopped there.
    # So this test is ensuring we eventually get to the correct state.
    Then the following fields match these values:
      | Certification completion state | Certified, before window opens |
    When I run the scheduled task "\totara_certification\task\update_certification_task"
    Then the following fields match these values:
      | Certification completion state | Certified, before window opens |
    When I run the scheduled task "\totara_certification\task\update_certification_task"
    Then the following fields match these values:
      | Certification completion state | Certified, before window opens |
    When I run the scheduled task "\totara_certification\task\update_certification_task"
    Then the following fields match these values:
      | Certification completion state | Certified, window is open |
