@totara @totara_catalog @javascript
Feature: Course catalog item and details content
  Background:
    Given I am on a totara site
    And the following "custom course fields" exist in "totara_core" plugin:
      | shortname | fullname | param1 | datatype |
      | input     | input    |        | text     |
    And the following "custom program fields" exist in "totara_core" plugin:
      | shortname | fullname | param1 | datatype |
      | input     | input    |        | text     |
    And the following "users" exist:
      | firstname | lastname    | username  |
      | jongsuk   | lee         | jongsuk   |
      | shinhye   | park        | shinhye   |
      | sarang    | kim         | sarang    |
      | joe       | notenrolled | notenrold |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion | customfield_input         |
      | course1  | course1   | 0        | 1                | This is course input test |
    And the following "course enrolments" exist:
      | user    | course  | role    |
      | jongsuk | course1 | student |
      | shinhye | course1 | student |
      | sarang  | course1 | teacher |
    And the following "programs" exist in "totara_program" plugin:
      | fullname | shortname | category |
      | program1 | program1  | 0        |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | category |
      | cert1    | cert1     | 0        |

  Scenario: User viewing course catalog item and content
    When I log in as "admin"
    And I navigate to "Courses > Configure catalogue" in site administration
    And I follow "Templates"
    And I set the following Totara form fields to these values:
      | Hero data type                                | icon |
      | Progress bar                                  | 1    |
      | tfiid_item_additional_icons_enabled_templates | 1    |
    And I click on "Save" "button"
    And I follow "Details"
    And I set the following Totara form fields to these values:
      | details_additional_text__course__0  | Trainer |
      | details_additional_text__course__1  | input   |
      | details_additional_text__program__0 | input   |
    And I click on "Save" "button"
    And I follow "Item"
    And I set the following Totara form fields to these values:
      | item_additional_text__course__0                  | Trainer |
      | item_additional_text__course__1                  | input   |
      | item_additional_text__program__1                 | input   |
      | tfiid_item_additional_icons__course_item_addicon | icon    |
    And I click on "Save" "button"

    # Changing the custom field input for program
    And I click on "Find Learning" in the totara menu
    And I follow "program1"
    And I follow "Edit program details"
    And I follow "Details"
    And I follow "Custom fields"
    And I set the field "input" to "This is program input test"
    And I click on "Save changes" "button"

    # Checking whether those texts are appearing in catalog page
    When I click on "Find Learning" in the totara menu
    Then "sarang kim" "text" should exist in the "a[title='course1']" "css_element"
    And "This is course input test" "text" should exist in the "a[title='course1']" "css_element"
    And "This is program input test" "text" should exist in the "a[title='program1']" "css_element"

    # As admin, i should not be able to see progress bar here
    And I should not see "0%"
    And I should not see "100%"

    # Viewing course details
    When I follow "course1"
    And I should see "sarang kim" exactly "2" times
    And I should see "This is course input test" exactly "2" times

    # Viewing program details
    When I follow "program1"
    And I should see "This is program input test" exactly "2" times
    And I am on "course1" course homepage with editing mode on
    And I add the "Self completion" block
    And I navigate to "Course completion" node in "Course administration"
    And I follow "Condition: Manual self completion"
    And I set the field "criteria_self_value" to "1"
    And I click on "Save changes" "button"
    And I log out
    And I log in as "jongsuk"

    # Viewing progress bar as a learner without completion
    When I click on "Find Learning" in the totara menu
    Then I should see "0%"
    And I am on "course1" course homepage
    And I click on "Complete course" "link"
    And I click on "Yes" "button"

    # Viewing progress bar as a learner with completion
    When I click on "Find Learning" in the totara menu
    Then I should see "100%"

    And I log out
    And I log in as "shinhye"
    When I click on "Find Learning" in the totara menu
    Then I should see "0%"

  Scenario: User without enrolment should still get a link to course page
    When I log in as "notenrold"
    And I click on "Find Learning" in the totara menu
    And I follow "course1"
    Then I should see "You are not enrolled in this course"

    When I follow "Go to course"
    Then I should see "You can not enrol yourself in this course."
