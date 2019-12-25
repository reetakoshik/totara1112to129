@totara @totara_form
Feature: Totara form JavaScript based element compilation tests
  In order to test a compilation of elements using just JavaScript
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  @javascript
  Scenario: Test a compilation of elements in a Totara form
    When I select "Compilation of elements loaded in JS [totara_form\form\testform\element_compilation_js]" from the "Test form" singleselect
    Then I should see "Form: Compilation of elements loaded in JS"
    And I should see "Static HTML test"

    When I start watching to see if a new page loads
    And I press "Save changes"
    Then I should see "The following form values were submit"
    And I should not see "The form has been submit"
    And a new page should not have loaded since I started watching
    And "checkbox" row "Value" column of "form_results" table should contain "«empty»"
    And "checkboxes" row "Value" column of "form_results" table should contain "«[ ]»"
    And "datetime" row "Value" column of "form_results" table should contain "«--null--»"
    And "editor" row "Value" column of "form_results" table should contain "«»"
    And "email" row "Value" column of "form_results" table should contain "«»"
    And "hidden" row "Value" column of "form_results" table should contain "«Invisible»"
    And "multiselect" row "Value" column of "form_results" table should contain "«[ ]»"
    And "number" row "Value" column of "form_results" table should contain "«»"
    And "passwordunmask" row "Value" column of "form_results" table should contain "«»"
    And "radios" row "Value" column of "form_results" table should contain "«--null--»"
    And "select" row "Value" column of "form_results" table should contain "«apple»"
    And "tel" row "Value" column of "form_results" table should contain "«»"
    And "text" row "Value" column of "form_results" table should contain "«»"
    And "textarea" row "Value" column of "form_results" table should contain "«»"
    And "url" row "Value" column of "form_results" table should contain "«»"
    And "utc10date" row "Value" column of "form_results" table should contain "«--null--»"
    And "yesno" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_compilation_js»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Compilation of elements loaded in JS"
    And a new page should have loaded since I started watching

    When I set the following Totara form fields to these values:
      | Checkbox          | 1                    |
      | Checkboxes        | Yes,No               |
      | Date and time     | 1985-03-07 22:05     |
      | Editor            | I am some rich text  |
      | Email             | admin@example.com    |
      | Multiselect       | Orange,Green         |
      | Number            | 2016                 |
      | Password unmask   | freedom              |
      | Radios            | Agree                |
      | Select            | Raspberry            |
      | Tel               | *123                 |
      | Text              | Test 123             |
      | Textarea          | Take off your shoes  |
      | Web URL           | http://totaralms.com |
      | UTC10 Date        | 1985-03-07           |
      | Yes or No         | 1                    |
    And I start watching to see if a new page loads
    And I press "Save changes"
    Then I should see "The following form values were submit"
    And I should not see "The form has been submit"
    And a new page should not have loaded since I started watching
    And "checkbox" row "Value" column of "form_results" table should contain "«checked»"
    And "checkboxes" row "Value" column of "form_results" table should contain "«[ '1' , '0' ]»"
    And "datetime" row "Value" column of "form_results" table should contain "1985/03/07 22:05"
    And "editor" row "Value" column of "form_results" table should contain "«I am some rich text»"
    And "email" row "Value" column of "form_results" table should contain "«admin@example.com»"
    And "hidden" row "Value" column of "form_results" table should contain "«Invisible»"
    And "multiselect" row "Value" column of "form_results" table should contain "«[ 'orange' , 'green' ]»"
    And "number" row "Value" column of "form_results" table should contain "«2016»"
    And "passwordunmask" row "Value" column of "form_results" table should contain "«freedom»"
    And "radios" row "Value" column of "form_results" table should contain "«true»"
    And "select" row "Value" column of "form_results" table should contain "«raspberry»"
    And "tel" row "Value" column of "form_results" table should contain "«*123»"
    And "text" row "Value" column of "form_results" table should contain "«Test 123»"
    And "textarea" row "Value" column of "form_results" table should contain "«Take off your shoes»"
    And "url" row "Value" column of "form_results" table should contain "«http://totaralms.com»"
    And "utc10date" row "Value" column of "form_results" table should contain "1985/03/07"
    And "yesno" row "Value" column of "form_results" table should contain "«1»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_compilation_js»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

