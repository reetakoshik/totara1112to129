@totara @totara_form
Feature: Totara form editor element tests
  In order to test the editor element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic editor elements in Totara forms
    When I select "Basic editor element [totara_form\form\testform\element_editor]" from the "Test form" singleselect
    Then I should see "Form: Basic editor element"
    And I should see the following Totara form fields having these values:
      | Basic editor                    |               |
      | Editor with current data        | Cheerios      |
      | Frozen editor with current data | Sausage rolls |
    And I should see the "Empty frozen editor" Totara form field is frozen
    And I should see the "Frozen editor with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Basic editor          | One |
      | Required basic editor | Two |
    And I should see the following Totara form fields having these values:
      | Basic editor          | One |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«One»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"
    And "editor_with_current_data" row "Value" column of "form_results" table should contain "«Cheerios»"
    And "editor_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "editor_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_editor»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | 2016 |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«2016»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | 2016.16 |
      | Required basic editor | 2016.15.14 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«2016.16»"
    And "editor_required" row "Value" column of "form_results" table should contain "«2016.15.14»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | -70 |
      | Required basic editor | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«-70»"
    And "editor_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | True |
      | Required basic editor | False |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«True»"
    And "editor_required" row "Value" column of "form_results" table should contain "«False»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | \r |
      | Required basic editor | \n |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«\r»"
    And "editor_required" row "Value" column of "form_results" table should contain "«\n»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | <p></p> |
      | Required basic editor | <br /> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«<p></p>»"
    And "editor_required" row "Value" column of "form_results" table should contain "«<br />»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | <br> |
      | Required basic editor | <p></p> |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«<br>»"
    And "editor_required" row "Value" column of "form_results" table should contain "«<p></p>»"

  @javascript
  Scenario: Test required editor elements in Totara forms with JavaScript enabled
    When I select "Basic editor element [totara_form\form\testform\element_editor]" from the "Test form" singleselect
    Then I should see "Form: Basic editor element"
    And I should see "There are required fields in this form marked"
    And I should see the following Totara form fields having these values:
      | Basic editor                    |               |
      | Editor with current data        | Cheerios      |
      | Frozen editor with current data | Sausage rolls |
    And I should see the "Empty frozen editor" Totara form field is frozen
    And I should see the "Frozen editor with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Basic editor          | One |
      | Required basic editor | Two |
    And I should see the following Totara form fields having these values:
      | Basic editor          | One |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«One»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic editor | One |
      | Required basic editor | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic editor element"

    When I set the following Totara form fields to these values:
      | Basic editor | 1 |
      | Required basic editor | 0 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«1»"
    And "editor_required" row "Value" column of "form_results" table should contain "«0»"

  Scenario: Test required editor elements in Totara forms with JavaScript disabled
    When I select "Basic editor element [totara_form\form\testform\element_editor]" from the "Test form" singleselect
    Then I should see "Form: Basic editor element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic editor | One |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«One»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | |
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

    When I press "Reset"
    Then I should see "Form: Basic editor element"
    When I set the following Totara form fields to these values:
      | Basic editor | One |
      | Required basic editor | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic editor element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic editor | Two |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«One»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"

  @javascript
  Scenario: Test hidden if on editor elements in Totara forms
    When I select "Basic editor element [totara_form\form\testform\element_editor]" from the "Test form" singleselect
    Then I should see "Form: Basic editor element"
    And I click on "Expand all" "link"
    And I should see "Visible when 'Hidden if reference' is empty"
    And I should not see "Visible when 'Hidden if reference' is not empty"
    And I should not see "Visible when 'Hidden if reference' equals 'Behat'"
    And I should see "Visible when 'Hidden if reference' is not equal to 'Behat'"
    And I should see "Visible when 'Hidden if reference' is not filled"
    And I should not see "Visible when 'Hidden if reference' is filled"
    And I should see "Visible when 'Required basic editor' is empty"
    And I should not see "Visible when 'Required basic editor' is not empty"

    When I set the following Totara form fields to these values:
      | Visible when 'Hidden if reference' is empty | Alpha |
      | Visible when 'Hidden if reference' is not equal to 'Behat' | Beta |
      | Visible when 'Hidden if reference' is not filled           | Gamma |
      | Visible when 'Required basic editor' is empty | Delta |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Test |
    Then I should see "Form: Basic editor element"
    And I should not see "Visible when 'Hidden if reference' is empty"
    And I should see "Visible when 'Hidden if reference' is not empty"
    And I should not see "Visible when 'Hidden if reference' equals 'Behat'"
    And I should see "Visible when 'Hidden if reference' is not equal to 'Behat'"
    And I should not see "Visible when 'Hidden if reference' not is filled"
    And I should see "Visible when 'Hidden if reference' is filled"
    And I should see "Visible when 'Required basic editor' is empty"
    And I should not see "Visible when 'Required basic editor' is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | Behat |
      | Required basic editor | Test  |
    Then I should see "Form: Basic editor element"
    And I should not see "Visible when 'Hidden if reference' is empty"
    And I should see "Visible when 'Hidden if reference' is not empty"
    And I should see "Visible when 'Hidden if reference' equals 'Behat'"
    And I should not see "Visible when 'Hidden if reference' is not equal to 'Behat'"
    And I should not see "Visible when 'Hidden if reference' not is filled"
    And I should see "Visible when 'Hidden if reference' is filled"
    And I should not see "Visible when 'Required basic editor' is empty"
    And I should see "Visible when 'Required basic editor' is not empty"

    When I set the following Totara form fields to these values:
      | Basic editor | One |
      | Required basic editor | Two |
      | Editor with current data | Three |
      | Visible when 'Hidden if reference' is not empty | Four |
      | Visible when 'Hidden if reference' equals 'Behat' | Five |
      | Visible when 'Hidden if reference' is filled | Six |
      | Visible when 'Required basic editor' is not empty | Seven |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "editor_basic" row "Value" column of "form_results" table should contain "«One»"
    And "editor_required" row "Value" column of "form_results" table should contain "«Two»"
    And "editor_with_current_data" row "Value" column of "form_results" table should contain "«Three»"
    And "editor_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "editor_frozen_with_current_data" row "Value" column of "form_results" table should contain "«Sausage rolls»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«Behat»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«Four»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«Alpha»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«Beta»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«Five»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«Gamma»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Six»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«Seven»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«Delta»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_editor»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
