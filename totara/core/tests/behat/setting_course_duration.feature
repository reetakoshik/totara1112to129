@totara @totara_core @javascript
Feature: Test course duration in the course default settings
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Course default settings" node in "Site administration >  Courses"

  Scenario: Set a valid course duration
    Given I set the field "Course duration" to "100"
    And I set the field with xpath "//select[@name='s_moodlecourse_courseduration[u]']" to "hours"
    When I press "Save changes"
    Then the field "Course duration" matches value "100"
    Then the field with xpath "//select[@name='s_moodlecourse_courseduration[u]']" matches value "hours"
    Given I set the field "Course duration" to "2880"
    And I set the field with xpath "//select[@name='s_moodlecourse_courseduration[u]']" to "minutes"
    When I press "Save changes"
    Then the field "Course duration" matches value "2"
    Then the field with xpath "//select[@name='s_moodlecourse_courseduration[u]']" matches value "days"

  Scenario: Set an invalid course duration results in an error
    Given I set the field "Course duration" to "0"
    When I press "Save changes"
    Then I should see "Some settings were not changed due to an error."
    Then I should see "Duration has to be at least one hour." in the "#admin-courseduration" "css_element"
    Given I set the field "Course duration" to "3599"
    And I set the field with xpath "//select[@name='s_moodlecourse_courseduration[u]']" to "seconds"
    When I press "Save changes"
    Then I should see "Some settings were not changed due to an error."
    Then I should see "Duration has to be at least one hour." in the "#admin-courseduration" "css_element"

  Scenario: Set a non-numeric course duration results in an error
    Given I set the field "Course duration" to "forty-two"
    When I press "Save changes"
    Then I should see "Some settings were not changed due to an error."
    Then I should see "The value has to be a number." in the "#admin-courseduration" "css_element"

  Scenario: Enable/disable course end date
    Given I set the field "Course end date enabled by default" to "0"
    When I press "Save changes"
    Then the field "Course end date enabled by default" matches value "0"
    And I navigate to "Courses and categories" node in "Site administration >  Courses"
    When I follow "Create new course"
    Then the field with xpath "//input[@name='enddate[enabled]']" matches value "0"

    And I navigate to "Course default settings" node in "Site administration >  Courses"
    Given I set the field "Course end date enabled by default" to "1"
    When I press "Save changes"
    Then the field "Course end date enabled by default" matches value "1"
    And I navigate to "Courses and categories" node in "Site administration >  Courses"
    When I follow "Create new course"
    Then the field with xpath "//input[@name='enddate[enabled]']" matches value "1"
