@totara @totara_form
Feature: Totara form checkbox element tests
  In order to test the checkbox element
  As an admin
  I use the test form to confirm behaviour

# NOTE: commented out steps do not work in Totara 10 yet, devs will get coding exception for now

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic checkbox elements in Totara forms without JavaScript
    When I select "Basic checkbox element [totara_form\form\testform\element_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Basic checkbox element"
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 0 |
      | Required basic checkbox           | 0 |
      | Checkbox with current data        | 1 |
      | Empty frozen checkbox             | 0 |
      | Frozen checkbox with current data | 1 |
    And I should see the "Empty frozen checkbox" Totara form field is frozen
    And I should see the "Frozen checkbox with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic checkbox           | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkbox_basic" row "Value" column of "form_results" table should contain "«0»"
    And "checkbox_required" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_with_current_data" row "Value" column of "form_results" table should contain "«yes»"
    And "checkbox_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkbox_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkbox_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "checkbox_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«false»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«NO»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«United Kingdom»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«0»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkbox»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic checkbox element"
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 0 |
      | Required basic checkbox           | 0 |
      | Checkbox with current data        | 1 |
      | Empty frozen checkbox             | 0 |
      | Frozen checkbox with current data | 1 |

    When I set the following Totara form fields to these values:
      | Basic checkbox                    | 1 |
      | Required basic checkbox           | 1 |
#      | Checkbox with current data        | 0 |
      | Hidden if reference               | 1 |
      | A is visible when test is checked | 1 |
#      | D is visible when test is checked | 1 |
#      | F is visible when test is checked | 1 |
      | G is visible when required checkbox is checked | 1 |
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 1 |
      | Required basic checkbox           | 1 |
      | Empty frozen checkbox             | 0 |
#      | Checkbox with current data        | 0 |
      | Frozen checkbox with current data | 1 |
      | Hidden if reference               | 1 |
      | A is visible when test is checked | 1 |
#      | D is visible when test is checked | 1 |
#      | F is visible when test is checked | 1 |
      | G is visible when required checkbox is checked | 1 |
    And I should see the "Empty frozen checkbox" Totara form field is frozen
    And I should see the "Frozen checkbox with current data" Totara form field is frozen

    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkbox_basic" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_required" row "Value" column of "form_results" table should contain "«1»"
# Goutte supports checkboxes with 1 value only
#    And "checkbox_with_current_data" row "Value" column of "form_results" table should contain "«no»"
    And "checkbox_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkbox_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkbox_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "checkbox_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«false»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«NO»"
#    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
#    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«0»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkbox»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test basic checkbox elements in Totara forms with JavaScript
    When I select "Basic checkbox element [totara_form\form\testform\element_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Basic checkbox element"
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 0 |
      | Required basic checkbox           | 0 |
      | Checkbox with current data        | 1 |
      | Empty frozen checkbox             | 0 |
      | Frozen checkbox with current data | 1 |
    And I should see the "Empty frozen checkbox" Totara form field is frozen
    And I should see the "Frozen checkbox with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic checkbox           | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkbox_basic" row "Value" column of "form_results" table should contain "«0»"
    And "checkbox_required" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_with_current_data" row "Value" column of "form_results" table should contain "«yes»"
    And "checkbox_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkbox_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkbox_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "checkbox_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«false»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«NO»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«United Kingdom»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«0»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkbox»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic checkbox element"
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 0 |
      | Required basic checkbox           | 0 |
      | Checkbox with current data        | 1 |
      | Empty frozen checkbox             | 0 |
      | Frozen checkbox with current data | 1 |

    When I set the following Totara form fields to these values:
      | Basic checkbox                    | 1 |
      | Required basic checkbox           | 1 |
      | Checkbox with current data        | 0 |
      | Hidden if reference               | 1 |
      | A is visible when test is checked | 1 |
      | D is visible when test is checked | 1 |
      | F is visible when test is checked | 1 |
      | G is visible when required checkbox is checked | 1 |
    And I should see the following Totara form fields having these values:
      | Basic checkbox                    | 1 |
      | Required basic checkbox           | 1 |
      | Empty frozen checkbox             | 0 |
      | Checkbox with current data        | 0 |
      | Frozen checkbox with current data | 1 |
      | Hidden if reference               | 1 |
      | A is visible when test is checked | 1 |
      | D is visible when test is checked | 1 |
      | F is visible when test is checked | 1 |
      | G is visible when required checkbox is checked | 1 |
    And I should see the "Empty frozen checkbox" Totara form field is frozen
    And I should see the "Frozen checkbox with current data" Totara form field is frozen

    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkbox_basic" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_required" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_with_current_data" row "Value" column of "form_results" table should contain "«no»"
    And "checkbox_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkbox_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkbox_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "checkbox_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«false»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«NO»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«0»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkbox»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required checkbox elements in Totara forms without JavaScript
    When I select "Basic checkbox element [totara_form\form\testform\element_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Basic checkbox element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required checkbox elements in Totara forms with JavaScript
    When I select "Basic checkbox element [totara_form\form\testform\element_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Basic checkbox element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "Form could not be submitted, validation failed"
    And I should not see "The form has been submit"
    And I should see "Form: Basic checkbox element"

  @javascript
  Scenario: Test hidden if on checkbox elements in Totara forms
    When I select "Basic checkbox element [totara_form\form\testform\element_checkbox]" from the "Test form" singleselect
    Then I should see "Form: Basic checkbox element"
    And I should see "Expand all"
    And I click on "Expand all" "link"

    And I should see "B is visible when test is not checked"
    And I should see "C is visible when test is not checked"
    And I should see "E is visible when test is not checked"
    And I should not see "A is visible when test is checked"
    And I should not see "D is visible when test is checked"
    And I should not see "F is visible when test is checked"
    And I should not see "G is visible when required checkbox is checked"
    And I should see "H is visible when required checkbox is not checked"

    When I set the following Totara form fields to these values:
      | B is visible when test is not checked              | 1 |
      | C is visible when test is not checked              | 1 |
      | E is visible when test is not checked              | 1 |
      | H is visible when required checkbox is not checked | 1 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | 1 |
    Then I should see "Form: Basic checkbox element"
    And I should see "A is visible when test is checked"
    And I should see "D is visible when test is checked"
    And I should see "F is visible when test is checked"
    And I should not see "B is visible when test is not checked"
    And I should not see "C is visible when test is not checked"
    And I should not see "E is visible when test is not checked"
    And I should not see "G is visible when required checkbox is checked"
    And I should see "H is visible when required checkbox is not checked"

    When I set the following Totara form fields to these values:
      | Required basic checkbox | 1 |
    Then I should see "Form: Basic checkbox element"
    And I should see "A is visible when test is checked"
    And I should see "D is visible when test is checked"
    And I should see "F is visible when test is checked"
    And I should not see "B is visible when test is not checked"
    And I should not see "C is visible when test is not checked"
    And I should not see "E is visible when test is not checked"
    And I should see "G is visible when required checkbox is checked"
    And I should not see "H is visible when required checkbox is not checked"

    When I set the following Totara form fields to these values:
      | Basic checkbox                    | 1 |
      | A is visible when test is checked | 1 |
      | D is visible when test is checked | 1 |
      | F is visible when test is checked | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkbox_basic" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_required" row "Value" column of "form_results" table should contain "«1»"
    And "checkbox_with_current_data" row "Value" column of "form_results" table should contain "«yes»"
    And "checkbox_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkbox_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«YES»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«1»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkbox»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
