@totara @totara_catalog @javascript
Feature: Course catalog filters and featured learning
  Background:
    Given I am on a totara site
    And the following "custom course fields" exist in "totara_core" plugin:
      | shortname | fullname | param1         | datatype | defaultdata |
      | colour    | colour   | red/green/blue | menu     |             |
      | checkbox  | checkbox |                | checkbox | 1           |
    And the following "custom program fields" exist in "totara_core" plugin:
      | shortname | fullname | param1            | datatype | defaultdata |
      | colour    | colour   | orange/red/yellow | menu     |             |
      | checkbox  | checkbox |                   | checkbox | 1           |
    And the following "courses" exist:
      | fullname | shortname | category | customfield_colour | customfield_checkbox |
      | course1  | course1   | 0        | 0                  | 1                    |
      | course2  | course2   | 0        | 1                  | 0                    |
      | course3  | course3   | 0        | 2                  | 1                    |
    And the following "programs" exist in "totara_program" plugin:
      | fullname | shortname | category |
      | program1 | program1  | 0        |
    And the following "certifications" exist in "totara_program" plugin:
      | fullname | shortname | category |
      | cert1    | cert1     | 0        |
    And I log in as "admin"
    And I navigate to "Courses > Configure catalogue" in site administration
    And I follow "Filters"
    And I set the field "Add another..." to "colour"
    And I set the field "Add another..." to "checkbox"
    And I click on "Save" "button"

    # Editing the custom field for program
    And I click on "Find Learning" in the totara menu
    And I follow "program1"
    And I follow "Edit program details"
    And I follow "Details"
    And I follow "Custom fields"
    And I set the field "colour" to "1"
    And I click on "Save changes" "button"

    And I click on "Find Learning" in the totara menu
    And I follow "cert1"
    And I follow "Edit certification details"
    And I follow "Details"
    And I follow "Custom fields"
    And I set the field "colour" to "0"
    And I click on "Save changes" "button"

  Scenario: User is filtering course catalog to find the match courses
    Given I am on homepage
    And I click on "Find Learning" in the totara menu
    # Click on Checkbox Yes
    When I click on "Yes" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should not see "course2"
    And I should see "cert1"
    And I should see "course1"
    And I should see "course3"
    And I should see "program1"

    # Click on Checkbox No
    When I click on "No" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should see "5 items"

    And I follow "Clear all"
    Then I should see "5 items"

    When I click on "red" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should see "course1"
    And I should see "program1"
    And I should see "2 items"

    And I follow "Clear all"
    And I should see "5 items"
    When I click on "orange" "link" in the "section.tw-selectRegionPanel" "css_element"
    And I click on "green" "link" in the "section.tw-selectRegionPanel" "css_element"
    And I should see "2 items"
    And I should see "course2"
    And I should see "cert1"

    # Only cert 1 is appearing on the fitlered result ? This is probably the operator between
    # two different type of fitler is `AND`. Since cert1 has the orange color and it also has
    # checkbox checked by default
    When I click on "Yes" "link" in the "section.tw-selectRegionPanel" "css_element"
    Then I should see "cert1"
    And I should see "1 items"

  Scenario: Enable featured learning on custom field to see the match
    Given I am on homepage
    And I navigate to "Courses > Configure catalogue" in site administration
    And I follow "General"
    And I set the following Totara form fields to these values:
      | Featured learning | 1 |
    And I wait for pending js
    And I set the following Totara form fields to these values:
      | tfiid_featured_learning_source_general | colour |
      | tfiid_featured_learning_value_general  | red    |
    And I click on "Save" "button"
    # Since bouth course1 and program1 has the value of red for the customfield. Therefore, these
    # learning items should have the flag of `Featured`
    When I click on "Find Learning" in the totara menu
    Then "Featured" "text" should exist in the "a[title='program1']" "css_element"
    And "Featured" "text" should exist in the "a[title='course1']" "css_element"
    And I follow "Configure catalogue"
    And I follow "General"
    And I set the following Totara form fields to these values:
      | tfiid_featured_learning_source_general | checkbox |
      | tfiid_featured_learning_value_general  | 1        |
    And I click on "Save" "button"
    And I click on "Find Learning" in the totara menu
    And I should see "5 items"
    And "Featured" "text" should exist in the "a[title='program1']" "css_element"
    And "Featured" "text" should exist in the "a[title='cert1']" "css_element"
    And "Featured" "text" should exist in the "a[title='course1']" "css_element"
    And "Featured" "text" should exist in the "a[title='course3']" "css_element"
