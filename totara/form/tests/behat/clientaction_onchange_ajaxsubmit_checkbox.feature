@totara @totara_form
Feature: Totara form onchange ajaxsubmit client checkbox action test suite
  In order to test the onchange_ajaxsubmit client action
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    When I select "Onchange ajaxsubmit client checkbox action test [totara_form\form\testform\clientaction_onchange_ajaxsubmit_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Onchange ajaxsubmit client checkbox action test"

  @javascript
  Scenario: Changing the value of "Checkbox without clientaction" does not ajax submit the form
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
  Scenario: Changing the value of "Checkbox" submits the form via ajax
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | checkbox_2 | 1 |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should see "Checkbox 2 submit via ajax"
    And I should see "Success!"

    When I set the following Totara form fields to these values:
      | checkbox_2 | 0 |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should not see "Checkbox 2 submit via ajax"
    And I should not see "Success!"

    When I set the following Totara form fields to these values:
      | checkbox_2 | 1 |
    And I wait for pending js
    And I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "checkbox_2" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
