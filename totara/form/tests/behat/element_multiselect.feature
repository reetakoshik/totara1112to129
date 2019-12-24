@totara @totara_form
Feature: Totara form multiselect element tests
  In order to test the multiselect element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic multiselect elements in Totara forms without javaScript
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    |            |
      | Required basic multiselect           |            |
      | Multiselect with current data        | Oh yea!    |
      | Empty frozen multiselect             |            |
      | Frozen multiselect with current data | true,false |
    And I should see the following Totara form fields having these values:
      | Multiselect with current data        | yes        |
      | Frozen multiselect with current data | 1,0        |
    And I should see the following Totara form fields having these values:
      | Frozen multiselect with current data | false,true |
    And I should see the following Totara form fields having these values:
      | Frozen multiselect with current data | 0,1 |
    And I should see the "Empty frozen multiselect" Totara form field is frozen
    And I should see the "Frozen multiselect with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic multiselect | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "«[ ]»"
    And "multiselect_required" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "«[ 'yes' ]»"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_multiselect»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic multiselect element"
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    |            |
      | Required basic multiselect           |            |
      | Multiselect with current data        | Oh yea!    |
      | Empty frozen multiselect             |            |
      | Frozen multiselect with current data | true,false |

    When I set the following Totara form fields to these values:
      | Basic multiselect                    | Yes,No              |
      | Required basic multiselect           | Yes,No              |
      | Multiselect with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is selected   | Yes,No              |
      | F is visible when test is selected   | X,y                 |
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    | Yes,No              |
      | Required basic multiselect           | No, Yes             |
      | Multiselect with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is selected   | Yes,No              |
      | F is visible when test is selected   | X,y                 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "multiselect_required" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "«[ 'whatever' , 'yes' , 'nah' ]»"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ 'a' , 'b' , 'c' ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ 'x' , 'Y' ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_multiselect»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test basic multiselect elements in Totara forms with javaScript
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    |            |
      | Required basic multiselect           |            |
      | Multiselect with current data        | Oh yea!    |
      | Empty frozen multiselect             |            |
      | Frozen multiselect with current data | true,false |
    And I should see the following Totara form fields having these values:
      | Multiselect with current data        | yes        |
      | Frozen multiselect with current data | 1,0        |
    And I should see the following Totara form fields having these values:
      | Frozen multiselect with current data | false,true |
    And I should see the following Totara form fields having these values:
      | Frozen multiselect with current data | 0,1 |
    And I should see the "Empty frozen multiselect" Totara form field is frozen
    And I should see the "Frozen multiselect with current data" Totara form field is frozen

    When I set the following Totara form fields to these values:
      | Required basic multiselect | 1 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "«[ ]»"
    And "multiselect_required" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "«[ 'yes' ]»"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_multiselect»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic multiselect element"
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    |            |
      | Required basic multiselect           |            |
      | Multiselect with current data        | Oh yea!    |
      | Empty frozen multiselect             |            |
      | Frozen multiselect with current data | true,false |

    When I set the following Totara form fields to these values:
      | Basic multiselect                    | Yes,No              |
      | Required basic multiselect           | Yes,No              |
      | Multiselect with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is selected   | Yes,No              |
      | F is visible when test is selected   | X,y                 |
    And I should see the following Totara form fields having these values:
      | Basic multiselect                    | Yes,No              |
      | Required basic multiselect           | No, Yes             |
      | Multiselect with current data        | whatever,yes,nah    |
      | Hidden if reference                 | Alpha,Bravo,Charlie |
      | A is visible when test is selected   | Yes,No              |
      | F is visible when test is selected   | X,y                 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "multiselect_required" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "«[ 'whatever' , 'yes' , 'nah' ]»"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "multiselect_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "multiselect_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ 'a' , 'b' , 'c' ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ '1' , '3' ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ 'x' , 'Y' ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_multiselect»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required multiselect elements in Totara forms without JavaScript
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required multiselect elements in Totara forms with JavaScript
    When I start watching to see if a new page loads
    And I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    When I press "Save changes"
    Then I should not see "The form has been submit"

  @javascript
  Scenario: Test hidden if on multiselect elements in Totara forms
    When I select "Basic multiselect element [totara_form\form\testform\element_multiselect]" from the "Test form" singleselect
    Then I should see "Form: Basic multiselect element"
    And I should see "Expand all"
    And I click on "Expand all" "link"

    And I should see "B is visible when test is not selected"
    And I should see "C is visible when test is not selected"
    And I should see "E is visible when test is not selected"
    And I should not see "A is visible when test is selected"
    And I should not see "D is visible when test is selected"
    And I should not see "F is visible when test is selected"
    And I should not see "G is visible when required multiselect is not checked"
    And I should see "H is visible when required multiselect is checked"

    When I set the following Totara form fields to these values:
      | B is visible when test is not selected | 1 |
      | C is visible when test is not selected | 1 |
      | E is visible when test is not selected | Yes |
      | Required basic multiselect  | Yes |
    And I set the following Totara form fields to these values:
      | Hidden if reference | Alpha |
    Then I should see "Form: Basic multiselect element"
    And I should see "A is visible when test is selected"
    And I should see "F is visible when test is selected"
    And I should not see "B is visible when test is not selected"
    And I should not see "C is visible when test is not selected"
    And I should not see "D is visible when test is selected"
    And I should not see "E is visible when test is not selected"
    And I should see "G is visible when required multiselect is not checked"
    And I should not see "H is visible when required multiselect is checked"

    When I set the following Totara form fields to these values:
      | Required basic multiselect | Yes |
    Then I should see "Form: Basic multiselect element"
    And I should see "A is visible when test is selected"
    And I should see "F is visible when test is selected"
    And I should not see "B is visible when test is not selected"
    And I should not see "C is visible when test is not selected"
    And I should not see "D is visible when test is selected"
    And I should not see "E is visible when test is not selected"
    And I should see "G is visible when required multiselect is not checked"
    And I should not see "H is visible when required multiselect is checked"

    When I set the following Totara form fields to these values:
      | Basic multiselect | Yes |
      | Multiselect with current data | whatever,yes |
      | A is visible when test is selected | Yes |
      | F is visible when test is selected | X |
      | G is visible when required multiselect is not checked | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "multiselect_basic" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "multiselect_required" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "multiselect_with_current_data" row "Value" column of "form_results" table should contain "«[ 'whatever' , 'yes' ]»"
    And "multiselect_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "multiselect_frozen_with_current_data" row "Value" column of "form_results" table should contain "«[ 'true' , 'false' ]»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«[ 'a' ]»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«[ 'true' ]»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«[ 'false' ]»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«[ ]»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«[ '0' ]»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«[ 'x' ]»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«[ '1' ]»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«[ ]»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_multiselect»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
