@totara @totara_form
Feature: Totara form select element tests
  In order to test the select element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic select elements in Totara forms without javascript
    When I select "Basic select element [totara_form\form\testform\element_select]" from the "Test form" singleselect
    Then I should see "Form: Basic select element"
    And I should see the following Totara form fields having these values:
      | Basic select                           | Choose... |
      | Required basic select                  | Choose... |
      | select with current data               | Oh yea!   |
      | Empty frozen select                    | Choose... |
      | Frozen select with current data        | 1         |
      | Select with groups                     | Choose... |
    And I should see the following Totara form fields having these values:
      | Basic select                           |           |
      | Required basic select                  |           |
      | select with current data               | yes       |
      | Empty frozen select                    |           |
      | Frozen select with current data        | true      |
      | Select with groups                     |           |
    And I should see the "Empty frozen select" Totara form field is frozen
    And I should see the "Frozen select with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic select                  | Yes       |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "select_basic" row "Value" column of "form_results" table should contain "«»"
    And "select_required" row "Value" column of "form_results" table should contain "«1»"
    And "select_with_current_data" row "Value" column of "form_results" table should contain "«yes»"
    And "select_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "select_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "select_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "select_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«a»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«x»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_select»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic select element"
    And I should see the following Totara form fields having these values:
      | Basic select                           | Choose... |
      | Required basic select                  | Choose... |
      | select with current data               | Oh yea!   |
      | Empty frozen select                    | Choose... |
      | Frozen select with current data        | 1         |

    When I set the following Totara form fields to these values:
      | Basic select                           | No        |
      | Required basic select                  | No        |
      | select with current data               | Yeah?     |
      | Select with groups                     | Six       |
      | Hidden if reference                    | Charlie   |
      | A is visible when test is selected     | Yes       |
      | C is visible when test is not selected | 0         |
      | D is visible when test is selected     | UK        |
      | F is visible when test is selected     | y         |
      | G is visible when required select is not selected | No |
    And I should see the following Totara form fields having these values:
      | Basic select                           | No        |
      | Required basic select                  | No        |
      | select with current data               | Yeah?     |
      | Select with groups                     | Six       |
      | Hidden if reference                    | Charlie   |
      | A is visible when test is selected     | Yes       |
      | C is visible when test is not selected | 0         |
      | D is visible when test is selected     | UK        |
      | F is visible when test is selected     | y         |
      | G is visible when required select is not selected | No |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "select_basic" row "Value" column of "form_results" table should contain "«3»"
    And "select_required" row "Value" column of "form_results" table should contain "«3»"
    And "select_with_current_data" row "Value" column of "form_results" table should contain "«whatever»"
    And "select_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "select_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "select_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "select_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "select_grouped" row "Value" column of "form_results" table should contain "«6»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«c»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«United Kingdom»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Y»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«3»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_select»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test basic select elements in Totara forms with javascript
    When I select "Basic select element [totara_form\form\testform\element_select]" from the "Test form" singleselect
    Then I should see "Form: Basic select element"
    And I should see the following Totara form fields having these values:
      | Basic select                           | Choose... |
      | Required basic select                  | Choose... |
      | select with current data               | Oh yea!   |
      | Empty frozen select                    | Choose... |
      | Frozen select with current data        | 1         |
      | Select with groups                     | Choose... |
    And I should see the following Totara form fields having these values:
      | Basic select                           |           |
      | Required basic select                  |           |
      | select with current data               | yes       |
      | Empty frozen select                    |           |
      | Frozen select with current data        | true      |
      | Select with groups                     |           |
    And I should see the "Empty frozen select" Totara form field is frozen
    And I should see the "Frozen select with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic select                  | Yes       |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "select_basic" row "Value" column of "form_results" table should contain "«»"
    And "select_required" row "Value" column of "form_results" table should contain "«1»"
    And "select_with_current_data" row "Value" column of "form_results" table should contain "«yes»"
    And "select_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "select_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "select_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "select_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«a»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«x»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_select»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic select element"
    And I should see the following Totara form fields having these values:
      | Basic select                           | Choose... |
      | Required basic select                  | Choose... |
      | select with current data               | Oh yea!   |
      | Empty frozen select                    | Choose... |
      | Frozen select with current data        | 1         |

    When I set the following Totara form fields to these values:
      | Basic select                           | No        |
      | Required basic select                  | No        |
      | select with current data               | Yeah?     |
      | Select with groups                     | Six       |
      | Hidden if reference                    | Charlie   |
      | A is visible when test is selected     | Yes       |
      | C is visible when test is not selected | 0         |
      | D is visible when test is selected     | UK        |
      | F is visible when test is selected     | y         |
      | G is visible when required select is not selected | No |
    And I should see the following Totara form fields having these values:
      | Basic select                           | No        |
      | Required basic select                  | No        |
      | select with current data               | Yeah?     |
      | Select with groups                     | Six       |
      | Hidden if reference                    | Charlie   |
      | A is visible when test is selected     | Yes       |
      | C is visible when test is not selected | 0         |
      | D is visible when test is selected     | UK        |
      | F is visible when test is selected     | y         |
      | G is visible when required select is not selected | No |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "select_basic" row "Value" column of "form_results" table should contain "«3»"
    And "select_required" row "Value" column of "form_results" table should contain "«3»"
    And "select_with_current_data" row "Value" column of "form_results" table should contain "«whatever»"
    And "select_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "select_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "select_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "select_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "select_grouped" row "Value" column of "form_results" table should contain "«6»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«c»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«United Kingdom»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Y»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«3»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_select»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required select elements in Totara forms without JavaScript
    When I select "Basic select element [totara_form\form\testform\element_select]" from the "Test form" singleselect
    Then I should see "Form: Basic select element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required select elements in Totara forms with JavaScript
    When I select "Basic select element [totara_form\form\testform\element_select]" from the "Test form" singleselect
    Then I should see "Form: Basic select element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

  @javascript
  Scenario: Test hidden if on select elements in Totara forms
    When I select "Basic select element [totara_form\form\testform\element_select]" from the "Test form" singleselect
    Then I should see "Form: Basic select element"
    And I click on "Expand all" "link"

    And I should see "A is visible when test is selected"
    And I should see "F is visible when test is selected"
    And I should not see "B is visible when test is not selected"
    And I should not see "C is visible when test is not selected"
    And I should not see "D is visible when test is selected"
    And I should not see "E is visible when test is not selected"
    And I should not see "G is visible when required select is not selected"
    And I should see "H is visible when required select is selected"

    When I set the following Totara form fields to these values:
      | A is visible when test is selected | Yes |
      | F is visible when test is selected | y |
      | H is visible when required select is selected | Yes |
      | Required basic select  | Yes |
    When I set the following Totara form fields to these values:
      | Hidden if reference | Charlie |
    Then I should see "Form: Basic select element"
    And I should see "A is visible when test is selected"
    And I should see "C is visible when test is not selected"
    And I should see "D is visible when test is selected"
    And I should see "F is visible when test is selected"
    And I should not see "B is visible when test is not selected"
    And I should not see "E is visible when test is not selected"
    And I should see "G is visible when required select is not selected"
    And I should not see "H is visible when required select is selected"

    When I set the following Totara form fields to these values:
      | Basic select | Yes |
      | select with current data | Never! |
      | G is visible when required select is not selected | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "select_basic" row "Value" column of "form_results" table should contain "«1»"
    And "select_required" row "Value" column of "form_results" table should contain "«1»"
    And "select_with_current_data" row "Value" column of "form_results" table should contain "«nah»"
    And "select_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "select_frozen_with_current_data" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«c»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«true»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«New Zealand»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«Y»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«1»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_select»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
