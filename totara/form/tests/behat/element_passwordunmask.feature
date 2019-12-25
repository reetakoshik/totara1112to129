@totara @totara_form
Feature: Totara form password unmask element tests
  In order to test the password unmask element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic passwordunmask elements in Totara forms
    When I select "Basic passwordunmask element [totara_form\form\testform\element_passwordunmask]" from the "Test form" singleselect
    Then I should see "Form: Basic passwordunmask element"

    When I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«One»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"
    And "passwordunmask_with_current_data" row "Value" column of "form_results" table should contain "«Cheerios»"
    And "passwordunmask_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "passwordunmask_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_passwordunmask»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | 2016 |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | 2016.16 |
      | Required basic passwordunmask | 2016.15.14 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«2016.16»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«2016.15.14»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | -70 |
      | Required basic passwordunmask | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«-70»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | True |
      | Required basic passwordunmask | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«True»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | \r |
      | Required basic passwordunmask | \n |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«\r»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«\n»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | <p></p> |
      | Required basic passwordunmask | <br /> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«<p></p>»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«<br />»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | <br> |
      | Required basic passwordunmask | <p></p> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«<br>»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«<p></p>»"

  @javascript
  Scenario: Test required passwordunmask elements in Totara forms with JavaScript enabled
    When I select "Basic passwordunmask element [totara_form\form\testform\element_passwordunmask]" from the "Test form" singleselect
    Then I should see "Form: Basic passwordunmask element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«One»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic passwordunmask element"

    When I set the following Totara form fields to these values:
      | Basic passwordunmask | 1 |
      | Required basic passwordunmask | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«1»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«0»"

  Scenario: Test required passwordunmask elements in Totara forms with JavaScript disabled
    When I select "Basic passwordunmask element [totara_form\form\testform\element_passwordunmask]" from the "Test form" singleselect
    Then I should see "Form: Basic passwordunmask element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«One»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | |
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic passwordunmask element"
    When I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic passwordunmask element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic passwordunmask | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«One»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"

  @javascript
  Scenario: Test hidden if on passwordunmask elements in Totara forms
    When I select "Basic passwordunmask element [totara_form\form\testform\element_passwordunmask]" from the "Test form" singleselect
    Then I should see "Form: Basic passwordunmask element"
    And I click on "Expand all" "link"
    And I should see "Visible when test is empty"
    And I should not see "Visible when test is not empty"
    And I should not see "Visible when test equals 'Behat'"
    And I should see "Visible when test is not equal to 'Behat'"
    And I should see "Visible when test is not filled"
    And I should not see "Visible when test is filled"
    And I should see "Visible when required passwordunmask is empty"
    And I should not see "Visible when required passwordunmask is not empty"

    When I set the following Totara form fields to these values:
      | Visible when test is empty | Alpha |
      | Visible when test is not equal to 'Behat' | Beta |
      | Visible when test is not filled           | Gamma |
      | Visible when required passwordunmask is empty | Delta |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Test |
    Then I should see "Form: Basic passwordunmask element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should not see "Visible when test equals 'Behat'"
    And I should see "Visible when test is not equal to 'Behat'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should see "Visible when required passwordunmask is empty"
    And I should not see "Visible when required passwordunmask is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | Behat |
      | Required basic passwordunmask | Test  |
    Then I should see "Form: Basic passwordunmask element"
    And I should not see "Visible when test is empty"
    And I should see "Visible when test is not empty"
    And I should see "Visible when test equals 'Behat'"
    And I should not see "Visible when test is not equal to 'Behat'"
    And I should not see "Visible when test not is filled"
    And I should see "Visible when test is filled"
    And I should not see "Visible when required passwordunmask is empty"
    And I should see "Visible when required passwordunmask is not empty"

    When I set the following Totara form fields to these values:
      | Basic passwordunmask | One |
      | Required basic passwordunmask | Two |
      | passwordunmask with current data | Three |
      | Visible when test is not empty | Four |
      | Visible when test equals 'Behat' | Five |
      | Visible when test is filled | Six |
      | Visible when required passwordunmask is not empty | Seven |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "passwordunmask_basic" row "Value" column of "form_results" table should contain "«One»"
    And "passwordunmask_required" row "Value" column of "form_results" table should contain "«Two»"
    And "passwordunmask_with_current_data" row "Value" column of "form_results" table should contain "«Three»"
    And "passwordunmask_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "passwordunmask_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«Behat»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«Four»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«Alpha»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«Beta»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«Five»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«Gamma»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Six»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«Seven»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«Delta»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_passwordunmask»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
