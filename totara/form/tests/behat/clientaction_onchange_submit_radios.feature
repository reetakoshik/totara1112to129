@totara @totara_form
Feature: Totara form onchange submit client radios action test suite
  In order to test the onchange_submit client action
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    When I select "Onchange submit client radios action test [totara_form\form\testform\clientaction_onchange_submit_radios]" from the "Test form" singleselect
    Then I should see "Form: Onchange submit client radios action test"

  @javascript
  Scenario: Changing the value of "radios without clientaction" does not submit the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | radios without clientaction | 1 |
    Then a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | radios without clientaction | 0 |
    Then a new page should not have loaded since I started watching

    When I set the following Totara form fields to these values:
      | radios without clientaction | 1 |
    Then a new page should not have loaded since I started watching

    When I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "radios_1" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Changing the value of "radios" submits the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | radios_2 | 1 |
    And I wait for pending js
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "radios_2" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«0»"

  @javascript
  Scenario: Changing the value of "radios ignore empty" does not submit the form with an empty value
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | radios ignore empty | 0 |
    And I wait for pending js
    Then a new page should not have loaded since I started watching

    And I set the following Totara form fields to these values:
      | radios ignore empty | 1 |
    And I wait for pending js
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "radios_3" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«0»"
