@totara @totara_form
Feature: Totara form onchange submit client checkbox action test suite
  In order to test the onchange_submit client action
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    When I select "Onchange submit client checkbox action test [totara_form\form\testform\clientaction_onchange_submit_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Onchange submit client checkbox action test"

  @javascript
  Scenario: Changing the value of "Checkbox without clientaction" does not submit the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Checkbox without clientaction | 1 |
    Then a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Checkbox without clientaction | 0 |
    Then a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Checkbox without clientaction | 1 |
    Then a new page should not have loaded since I started watching

    When I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "checkbox_1" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Changing the value of "Checkbox" submits the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | checkbox_2 | 1 |
    And I wait for pending js
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "checkbox_2" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«0»"

  @javascript
  Scenario: Changing the value of "Checkbox ignore empty" does not submit the form with an empty value
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Checkbox ignore empty | 0 |
    And I wait for pending js
    Then a new page should not have loaded since I started watching

    And I set the following Totara form fields to these values:
      | Checkbox ignore empty | 1 |
    And I wait for pending js
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "checkbox_3" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«0»"
