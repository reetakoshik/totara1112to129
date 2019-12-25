@totara @totara_form
Feature: Totara form onchange reload client checkbox action test suite
  In order to test the onchange_reload client action
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    When I select "Onchange reload client checkbox action test [totara_form\form\testform\clientaction_onchange_reload_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Onchange reload client checkbox action test"

  @javascript
  Scenario: Changing the value of "Checkbox without clientaction" does not reload the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Checkbox without clientaction | 1 |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "checkbox_1 unchanged"

    When I set the following Totara form fields to these values:
      | Checkbox without clientaction | 0 |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "checkbox_1 unchanged"

    When I set the following Totara form fields to these values:
      | Checkbox without clientaction | 1 |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "checkbox_1 unchanged"

    When I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "checkbox_1" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario Outline: Changing the value of a checkbox with an onchange reload reloads the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | <checkbox> | <changed> |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should see "<checkbox> <changedstatus>"

    When I set the following Totara form fields to these values:
      | <checkbox> | <default> |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should not see "<checkbox> <returnedstatus>"

    When I set the following Totara form fields to these values:
      | <checkbox> | <changed> |
    And I wait for pending js
    And I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "<checkbox>" row "Value" column of "form_results" table should contain "«<submitvalue>»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    Examples:
      | checkbox   | default | changed   | changedstatus | returnedstatus | submitvalue |
      | checkbox_2 | 0       | 1         | checked       | unchecked      | 1           |
      | checkbox_3 | 1       | 0         | unchecked     | checked        | 0           |
      | checkbox_4 | 0       | 1         | checked       | unchecked      | 1           |
      | checkbox_5 | 0       | 1         | checked       | unchecked      | banana      |
      | checkbox_6 | 1       | 0         | checked       | unchecked      | apple       |
      | checkbox_7 | 0       | 1         | unchanged     | unchecked      | banana      |