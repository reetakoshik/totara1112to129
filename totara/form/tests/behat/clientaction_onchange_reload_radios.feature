@totara @totara_form
Feature: Totara form onchange reload client radios action test suite
  In order to test the onchange_reload client action
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"
    When I select "Onchange reload client radios action test [totara_form\form\testform\clientaction_onchange_reload_radios]" from the "Test form" singleselect
    Then I should see "Form: Onchange reload client radios action test"

  @javascript
  Scenario: Changing the value of "radios without clientaction" does not reload the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | radios without clientaction | two |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "radios_1 unchanged"

    When I set the following Totara form fields to these values:
      | radios without clientaction | 0 |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "radios_1 unchanged"

    When I set the following Totara form fields to these values:
      | radios without clientaction | 1 |
    Then a new page should not have loaded since I started watching
    And I wait for pending js
    And I should see "radios_1 unchanged"

    When I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "radios_1" row "Value" column of "form_results" table should contain "«1»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario Outline: Changing the value of a radios with an onchange reload reloads the form
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | <radios> | <changed> |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should see "<radios> <changedstatus>"

    When I set the following Totara form fields to these values:
      | <radios> | <default> |
    And I wait for pending js
    Then a new page should not have loaded since I started watching
    And I should not see "<radios> <returnedstatus>"

    When I set the following Totara form fields to these values:
      | <radios> | <changed> |
    And I wait for pending js
    And I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should see "The form has been submit"
    And "<radios>" row "Value" column of "form_results" table should contain "«<submitvalue>»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    Examples:
      | radios   | default | changed   | changedstatus | returnedstatus | submitvalue |
      | radios_2 | one     | two       | changed       | changed        | 1           |
      | radios_3 | one     | two       | changed       | unchanged      | 1           |
      | radios_4 | one     | two       | changed       | changed        | b           |
      | radios_5 | two     | one       | unchanged     | changed        | a           |
      | radios_6 | one     | two       | changed       | unchanged      | b           |