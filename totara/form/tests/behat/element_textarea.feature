@totara @totara_form
Feature: Totara form textarea element tests
  In order to test the textarea element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic textarea elements in Totara forms
    When I select "Basic textarea element [totara_form\form\testform\element_textarea]" from the "Test form" singleselect
    Then I should see "Form: Basic textarea element"

    When I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«One»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"
    And "textarea_with_current_data" row "Value" column of "form_results" table should contain "«Cheerios»"
    And "textarea_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "textarea_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_textarea»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | 2016 |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | 2016.16 |
      | Required basic textarea | 2016.15.14 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«2016.16»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«2016.15.14»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | -70 |
      | Required basic textarea | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«-70»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | True |
      | Required basic textarea | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«True»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | \r |
      | Required basic textarea | \n |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«\r»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«\n»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | <p></p> |
      | Required basic textarea | <br /> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«<p></p>»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«<br />»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | <br> |
      | Required basic textarea | <p></p> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«<br>»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«<p></p>»"

  @javascript
  Scenario: Test required textarea elements in Totara forms with JavaScript enabled
    When I select "Basic textarea element [totara_form\form\testform\element_textarea]" from the "Test form" singleselect
    Then I should see "Form: Basic textarea element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«One»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic textarea element"

    When I set the following Totara form fields to these values:
      | Basic textarea | 1 |
      | Required basic textarea | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«1»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«0»"

  Scenario: Test required textarea elements in Totara forms with JavaScript disabled
    When I select "Basic textarea element [totara_form\form\testform\element_textarea]" from the "Test form" singleselect
    Then I should see "Form: Basic textarea element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«One»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | |
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic textarea element"
    When I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic textarea element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic textarea | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«One»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"

  @javascript
  Scenario: Test hidden if on textarea elements in Totara forms
    When I select "Basic textarea element [totara_form\form\testform\element_textarea]" from the "Test form" singleselect
    Then I should see "Form: Basic textarea element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals 'Behat'"
    And I should see "Visible when test is not equal to 'Behat'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required textarea is empty"
    And I should not see "Visible when required textarea is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | Alpha |
      | Visible when test is not equal to 'Behat' | Beta |
      | Visible when test is not filled           | Gamma |
      | Visible when required textarea is empty | Delta |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Test |
    Then I should see "Form: Basic textarea element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals 'Behat'"
    And I should see "Visible when test is not equal to 'Behat'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required textarea is empty"
    And I should not see "Visible when required textarea is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | Behat |
      | Required basic textarea | Test  |
    Then I should see "Form: Basic textarea element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals 'Behat'"
    And I should not see "Visible when test is not equal to 'Behat'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required textarea is empty"
    And I should see "Visible when required textarea is not empty"

    When I set the following Totara form fields to these values:
      | Basic textarea | One |
      | Required basic textarea | Two |
      | textarea with current data | Three |
      | Visible when test is not empty | Four |
      | Visible when test equals 'Behat' | Five |
      | Visible when test is filled | Six |
      | Visible when required textarea is not empty | Seven |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "textarea_basic" row "Value" column of "form_results" table should contain "«One»"
    And "textarea_required" row "Value" column of "form_results" table should contain "«Two»"
    And "textarea_with_current_data" row "Value" column of "form_results" table should contain "«Three»"
    And "textarea_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "textarea_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«Behat»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«Four»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«Alpha»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«Beta»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«Five»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«Gamma»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Six»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«Seven»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«Delta»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_textarea»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
