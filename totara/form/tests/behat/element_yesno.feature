@totara @totara_form
Feature: Totara form yesno element tests
  In order to test the yesno element
  As an admin
  I use the yes/no test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  @javascript
  Scenario: Test basic yesno elements in Totara forms
    When I select "Basic yesno element [totara_form\form\testform\element_yesno]" from the "Test form" singleselect
    Then I should see "Form: Basic yesno element"

    When I set the following Totara form fields to these values:
      | Required basic yesno | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "yesno_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "yesno_required" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "yesno_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "yesno_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_yesno»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic yesno element"

    When I click on "Expand all" "link"
    And I set the following Totara form fields to these values:
      | Basic yesno | Yes |
      | Required basic yesno | Yes |
      | yesno with current data | No |
      | Hidden if reference | Yes |
      | A is visible when hiddenif reference is yes | Yes |
      | D is visible when hiddenif reference is yes | Yes |
      | F is visible when hiddenif reference is yes | No |
      | G is visible when required yesno is not empty (yes) | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "yesno_basic" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_required" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_with_current_data" row "Value" column of "form_results" table should contain "«0»"
    And "yesno_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "yesno_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "yesno_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_yesno»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required yesno elements in Totara forms without JavaScript
    When I select "Basic yesno element [totara_form\form\testform\element_yesno]" from the "Test form" singleselect
    Then I should see "Form: Basic yesno element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required yesno elements in Totara forms with JavaScript
    When I select "Basic yesno element [totara_form\form\testform\element_yesno]" from the "Test form" singleselect
    Then I should see "Form: Basic yesno element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

  @javascript
  Scenario: Test hidden if on yesno elements in Totara forms
    When I select "Basic yesno element [totara_form\form\testform\element_yesno]" from the "Test form" singleselect
    Then I should see "Form: Basic yesno element"
    And I click on "Expand all" "link"
    And I should not see "A is visible when hiddenif reference is yes"
    And I should see "B is visible when hiddenif reference is no"
    And I should see "C is visible when hiddenif reference is no"
    And I should see "E is visible when hiddenif reference is no"
    And I should see "H is visible when required yesno is empty (no, not selected)"
    And I should not see "D is visible when hiddenif reference is yes"
    And I should not see "F is visible when hiddenif reference is yes"
    And I should not see "G is visible when required yesno is not empty (yes)"

    When I set the following Totara form fields to these values:
      | B is visible when hiddenif reference is no | Yes |
      | C is visible when hiddenif reference is no | Yes |
      | E is visible when hiddenif reference is no | Yes |
      | H is visible when required yesno is empty (no, not selected) | Yes |
      | Required basic yesno  | Yes |
      | Hidden if reference | Yes |
    Then I should see "Form: Basic yesno element"
    And I should see "A is visible when hiddenif reference is yes"
    And I should see "D is visible when hiddenif reference is yes"
    And I should see "F is visible when hiddenif reference is yes"
    And I should see "G is visible when required yesno is not empty (yes)"
    And I should not see "B is visible when hiddenif reference is no"
    And I should not see "C is visible when hiddenif reference is no"
    And I should not see "E is visible when hiddenif reference is no"
    And I should not see "H is visible when required yesno is empty (no, not selected)"

    When I set the following Totara form fields to these values:
      | Basic yesno | Yes |
      | yesno with current data | No |
      | A is visible when hiddenif reference is yes | No |
      | D is visible when hiddenif reference is yes | No |
      | F is visible when hiddenif reference is yes | Yes |
      | G is visible when required yesno is not empty (yes) | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "yesno_basic" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_required" row "Value" column of "form_results" table should contain "«1»"
    And "yesno_with_current_data" row "Value" column of "form_results" table should contain "«0»"
    And "yesno_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "yesno_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«1»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_yesno»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
