@mod @mod_scorm @_file_upload @_switch_frame @javascript @totara
Feature: Scorm my learning overview
  In order to let students know what to do
  As a student
  I need to be able to see overview in My Learning

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | Course 1 | C1        | 0        | 1                |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "student1"
    And I press "Customise this page"
    And I add the "Course overview" block
    And I log out
    And I log in as "teacher1"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "label" to section "1" and I fill the form with:
      | Label text | Overview testing |
    And I delete "Overview testing" activity
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name | Test SCORM package |
      | Description | Description |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager

  Scenario: Scorm my learning overview - no completion tracking
    Given I set the following fields to these values:
      | Completion tracking    | Do not indicate activity completion |
      | Display attempt status | My learning and entry page          |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should not see "You have SCORM packages that need attention"

  Scenario: Scorm my learning overview - no display attempt status
    Given I set the following fields to these values:
      | Completion tracking        | Show activity as complete when conditions are met |
      | Display attempt status     | No                                                |
      | Require view               | 1                                                 |
      | id_completionscoredisabled | 0                                                 |
      | completionscorerequired    | 80                                                |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should not see "You have SCORM packages that need attention"

  Scenario: Scorm my learning overview - activity availability specified in future
    Given I set the following fields to these values:
      | Completion tracking        | Show activity as complete when conditions are met |
      | Display attempt status     | My learning and entry page                        |
      | timeopen[enabled]          | 1                                                 |
      | timeopen[year]             | 2035                                              |
      | Require view               | 1                                                 |
      | id_completionscoredisabled | 0                                                 |
      | completionscorerequired    | 80                                                |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should not see "You have SCORM packages that need attention"

  Scenario: Scorm my learning overview - activity availability specified in past
    Given I set the following fields to these values:
      | Completion tracking        | Show activity as complete when conditions are met |
      | Display attempt status     | My learning and entry page                        |
      | timeopen[enabled]          | 1                                                 |
      | timeopen[year]             | 2015                                              |
      | Require view               | 1                                                 |
      | id_completionscoredisabled | 0                                                 |
      | completionscorerequired    | 80                                                |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should see "You have SCORM packages that need attention"

  Scenario: Scorm my learning overview - display attempt status
    Given I set the following fields to these values:
      | Completion tracking        | Show activity as complete when conditions are met |
      | Display attempt status     | My learning and entry page                        |
      | Require view               | 1                                                 |
      | id_completionscoredisabled | 0                                                 |
      | completionscorerequired    | 80                                                |
    And I click on "Save and display" "button"
    And I log out
    And I log in as "student1"
    When I click on "Dashboard" in the totara menu
    Then I should see "You have SCORM packages that need attention"

    Given I follow "Course 1"
    And I follow "Test SCORM package"
    And I press "Enter"
    And I wait "2" seconds
    And I switch to "scorm_object" iframe
    And I set the following fields to these values:
      | key0b0 | 1 |
      | key1b0 | 1 |

    When I click on "submitB" "button"
    # Must wait here to let it save results, otherwise alert may popup.
    And I wait "2" seconds
    And I am on site homepage
    And I click on "Dashboard" in the totara menu
    Then I should see "You have SCORM packages that need attention"

    Given I follow "Course 1"
    And I follow "Test SCORM package"
    And I press "Enter"
    And I wait "2" seconds
    And I switch to "scorm_object" iframe
    And I set the following fields to these values:
      | key0b0 | 1 |
      | key1b0 | 1 |
      | key2b0 | 1 |
      | key3b0 | 1 |
      | key4b0 | 1 |

    When I click on "submitB" "button"
    # Must wait here to let it save results, otherwise alert may popup.
    And I wait "2" seconds
    And I am on site homepage
    And I click on "Dashboard" in the totara menu
    Then I should not see "You have SCORM packages that need attention"
