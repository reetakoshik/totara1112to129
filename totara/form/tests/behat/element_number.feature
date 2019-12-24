@totara @totara_form
Feature: Totara form number element tests
  In order to test the number element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic number elements in Totara forms
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"

    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"
    And "number_with_current_data" row "Value" column of "form_results" table should contain "«300»"
    And "number_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "number_frozen_with_current_data" row "Value" column of "form_results" table should contain "«-300»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_number»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | -1620 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«-1620»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | -70 |
      | Required basic number | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«-70»"
    And "number_required" row "Value" column of "form_results" table should contain "«0»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

  @javascript
  Scenario: Test invalid values in number elements in Totara forms
    Given I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | \r |
      | Required basic number | 42 |
    Then I should not see "\r"
    When I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«42»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | <p></p> |
      | Required basic number | 42 |
    Then I should not see "\r"
    When I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«42»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | <br> |
      | Required basic number | 42 |
    Then I should not see "\r"
    When I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«42»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | -0 |
      | Required basic number | 0 |
    And I press "Save changes"
    Then I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test decimals block submission in number elements in Totara forms
    Given I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    And I start watching to see if a new page loads
    When I set the following Totara form fields to these values:
      | Basic number | 3.14 |
      | Required basic number | 42 |
    When I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

    When I set the following Totara form fields to these values:
      | Basic number | .14 |
      | Required basic number | 42 |
    When I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

    When I set the following Totara form fields to these values:
      | Basic number | -0.72 |
      | Required basic number | 42 |
    When I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

    # Now exploit a bug in Chromes HTML5 number validation.
    When I set the following Totara form fields to these values:
      | Basic number | -.72 |
      | Required basic number | 42 |
    When I press "Save changes"
    Then a new page should have loaded since I started watching
    And I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  Scenario: Test decimals fail validation for number elements in Totara forms
    Given I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | 3.14 |
      | Required basic number | 42 |
    And I press "Save changes"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic number element"

    When I set the following Totara form fields to these values:
      | Basic number | .14 |
      | Required basic number | 42 |
    And I press "Save changes"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic number element"

    When I set the following Totara form fields to these values:
      | Basic number | -5.72 |
      | Required basic number | 42 |
    And I press "Save changes"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic number element"

  @javascript
  Scenario: Test required number elements in Totara forms with JavaScript enabled
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic number element"

    When I set the following Totara form fields to these values:
      | Basic number | 1 |
      | Required basic number | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«1»"
    And "number_required" row "Value" column of "form_results" table should contain "«0»"

  Scenario: Test required number elements in Totara forms with JavaScript disabled
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | |
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

    When I press "Reset"
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic number element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic number | 3141592658979323 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"

  @javascript
  Scenario: Test hidden if on number elements in Totara forms
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals '-273'"
    And I should see "Visible when test is not equal to '-273'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required number is empty"
    And I should not see "Visible when required number is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | 73 |
      | Visible when test is not equal to '-273' | 1098 |
      | Visible when test is not filled           | 10 |
      | Visible when required number is empty | 123456789 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | 66 |
    Then I should see "Form: Basic number element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals '-273'"
    And I should see "Visible when test is not equal to '-273'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required number is empty"
    And I should not see "Visible when required number is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | -273 |
      | Required basic number | 88  |
    Then I should see "Form: Basic number element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals '-273'"
    And I should not see "Visible when test is not equal to '-273'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required number is empty"
    And I should see "Visible when required number is not empty"

    When I set the following Totara form fields to these values:
      | Basic number | 2016 |
      | Required basic number | 3141592658979323 |
      | number with current data | -20 |
      | Visible when test is not empty | -1998 |
      | Visible when test equals '-273' | 987654321 |
      | Visible when test is filled | 0 |
      | Visible when required number is not empty | 8 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "number_required" row "Value" column of "form_results" table should contain "«3141592658979323»"
    And "number_with_current_data" row "Value" column of "form_results" table should contain "«-20»"
    And "number_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "number_frozen_with_current_data" row "Value" column of "form_results" table should contain "«-300»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«-273»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«-1998»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«73»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«1098»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«987654321»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«10»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«8»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«123456789»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_number»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test min and max in number elements in Totara forms
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Required basic number | 123 |
      | Positive number | 66 |
      | Positive or zero number | 0 |
      | Negative number | -77 |
      | Negative or zero number | 0 |

    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_positive" row "Value" column of "form_results" table should contain "«66»"
    And "number_positive_or_zero" row "Value" column of "form_results" table should contain "«0»"
    And "number_negative" row "Value" column of "form_results" table should contain "«-77»"
    And "number_negative_or_zero" row "Value" column of "form_results" table should contain "«0»"

  @javascript
  Scenario: Test step in number elements in Totara forms
    When I select "Basic number element [totara_form\form\testform\element_number]" from the "Test form" singleselect
    Then I should see "Form: Basic number element"
    When I set the following Totara form fields to these values:
      | Required basic number | 123 |
      | Number with step three from minus 1 | 2 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "number_step_three" row "Value" column of "form_results" table should contain "«2»"
