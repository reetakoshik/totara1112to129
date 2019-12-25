@totara @totara_form
Feature: Totara form checkboxes element tests
  In order to test the checkboxes element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  @javascript
  Scenario: Test basic checkboxes elements in Totara forms
    When I select "Basic checkboxes element [totara_form\form\testform\element_checkboxes]" from the "Test form" singleselect
    Then I should see "Form: Basic checkboxes element"
    And I should see the following Totara form fields having these values:
      | Basic checkboxes                    |            |
      | Required basic checkboxes           |            |
      | Checkboxes with current data        | Oh yea!    |
      | Empty frozen checkboxes             |            |
      | Frozen checkboxes with current data | true,false |
    And I should see the following Totara form fields having these values:
      | Checkboxes with current data        | yes        |
      | Frozen checkboxes with current data | 1,0        |
    And I should see the following Totara form fields having these values:
      | Frozen checkboxes with current data | false,true |
    And I should see the following Totara form fields having these values:
      | Frozen checkboxes with current data | 0,1        |
    And I should see the "Empty frozen checkboxes" Totara form field is frozen
    And I should see the "Frozen checkboxes with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic checkboxes | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkboxes_basic" row "Value" column of "form_results" table should contain "«[ ]»"
    And "checkboxes_required" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "checkboxes_with_current_data" row "Value" column of "form_results" table should contain "«[ 'yes' ]»"
    And "checkboxes_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkboxes_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkboxes_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "checkboxes_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkboxes»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic checkboxes element"
    And I should see the following Totara form fields having these values:
      | Basic checkboxes                    |                     |
      | Required basic checkboxes           |                     |
      | Checkboxes with current data        | Oh yea!             |
      | Empty frozen checkboxes             |                     |
      | Frozen checkboxes with current data | true,false          |

    When I set the following Totara form fields to these values:
      | Basic checkboxes                    | Yes,No              |
      | Required basic checkboxes           | Yes,No              |
      | Checkboxes with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is checked   | Yes,No              |
      | F is visible when test is checked   | X,y                 |
    And I should see the following Totara form fields having these values:
      | Basic checkboxes                    | Yes,No              |
      | Required basic checkboxes           | No, Yes             |
      | Checkboxes with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is checked   | Yes,No              |
      | F is visible when test is checked   | X,y                 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkboxes_basic" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "checkboxes_required" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "checkboxes_with_current_data" row "Value" column of "form_results" table should contain "«[ 'whatever' , 'yes' , 'nah' ]»"
    And "checkboxes_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkboxes_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "checkboxes_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "checkboxes_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ 'a' , 'b' , 'c' ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ 'x' , 'Y' ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkboxes»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required checkboxes elements in Totara forms without JavaScript
    When I select "Basic checkboxes element [totara_form\form\testform\element_checkboxes]" from the "Test form" singleselect
    Then I should see "Form: Basic checkboxes element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required checkboxes elements in Totara forms with JavaScript
    And I select "Basic checkboxes element [totara_form\form\testform\element_checkboxes]" from the "Test form" singleselect
    When I start watching to see if a new page loads
    Then I should see "Form: Basic checkboxes element"
    When I press "Save changes"
    And I should not see "The form has been submit"
    When I set the following Totara form fields to these values:
      | Required basic checkboxes           | Yes,No              |
    Then I should not see "Required" in the "#tfiid_checkboxes_required_totara_form_form_testform_element_checkboxes" "css_element"
    When I press "Save changes"
    And "checkboxes_required" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"

  @javascript
  Scenario: Test hidden if on checkboxes elements in Totara forms
    When I select "Basic checkboxes element [totara_form\form\testform\element_checkboxes]" from the "Test form" singleselect
    Then I should see "Form: Basic checkboxes element"
    And I should see "Expand all"
    And I click on "Expand all" "link"

    And I should see "B is visible when test is not checked"
    And I should see "C is visible when test is not checked"
    And I should see "E is visible when test is not checked"
    And I should not see "A is visible when test is checked"
    And I should not see "D is visible when test is checked"
    And I should not see "F is visible when test is checked"
    And I should not see "G is visible when required checkboxes is not checked"
    And I should see "H is visible when required checkboxes is checked"

    When I set the following Totara form fields to these values:
      | B is visible when test is not checked | 1 |
      | C is visible when test is not checked | 1 |
      | E is visible when test is not checked | Yes |
      | Required basic checkboxes  | Yes |
    And I set the following Totara form fields to these values:
      | Hidden if reference | Alpha |
    Then I should see "Form: Basic checkboxes element"
    And I should see "A is visible when test is checked"
    And I should see "F is visible when test is checked"
    And I should not see "B is visible when test is not checked"
    And I should not see "C is visible when test is not checked"
    And I should not see "D is visible when test is checked"
    And I should not see "E is visible when test is not checked"
    And I should see "G is visible when required checkboxes is not checked"
    And I should not see "H is visible when required checkboxes is checked"

    When I set the following Totara form fields to these values:
      | Required basic checkboxes | Yes |
    Then I should see "Form: Basic checkboxes element"
    And I should see "A is visible when test is checked"
    And I should see "F is visible when test is checked"
    And I should not see "B is visible when test is not checked"
    And I should not see "C is visible when test is not checked"
    And I should not see "D is visible when test is checked"
    And I should not see "E is visible when test is not checked"
    And I should see "G is visible when required checkboxes is not checked"
    And I should not see "H is visible when required checkboxes is checked"

    When I set the following Totara form fields to these values:
      | Basic checkboxes | Yes |
      | Checkboxes with current data | whatever,yes |
      | A is visible when test is checked | Yes |
      | F is visible when test is checked | X |
      | G is visible when required checkboxes is not checked | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "checkboxes_basic" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "checkboxes_required" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "checkboxes_with_current_data" row "Value" column of "form_results" table should contain "«[ 'whatever' , 'yes' ]»"
    And "checkboxes_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "checkboxes_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ 'a' ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ 'true' ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ 'false' ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ '0' ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ 'x' ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_checkboxes»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
