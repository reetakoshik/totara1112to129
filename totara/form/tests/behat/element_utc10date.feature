@totara @totara_form
Feature: Totara form utc10date element tests
  In order to test the utc10date element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic utc10date elements in Totara forms without JavaScript
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I should see the following Totara form fields having these values:
      | Basic utc10date                    |            |
      | Required basic utc10date           |            |
      | utc10date with current data        | 2016/03/07 |
      | Frozen utc10date with current data | 2016/06/23 |
    And I should see the "Empty frozen utc10date" Totara form field is frozen
    And I should see the "Frozen utc10date with current data" Totara form field is frozen
    And I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Basic utc10date          | 2015-07-26 |
      | Required basic utc10date | 2018-09-09 |
    And I should see the following Totara form fields having these values:
      | Basic utc10date          | 2015-07-26 |
      | Required basic utc10date | 2018-09-09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"
    And "utc10date_with_current_data" row "Value" column of "form_results" table should contain "«1457344800 (2016/03/07)»"
    And "utc10date_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "utc10date_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1466676000 (2016/06/23)»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_utc10date»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1"

  @javascript
  Scenario: Test required utc10date elements in Totara forms with JavaScript enabled
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I should see the following Totara form fields having these values:
      | Basic utc10date                    |            |
      | Required basic utc10date           |            |
      | utc10date with current data        | 2016/03/07 |
      | Frozen utc10date with current data | 2016/06/23 |
    And I should see the "Empty frozen utc10date" Totara form field is frozen
    And I should see the "Frozen utc10date with current data" Totara form field is frozen
    And I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Basic utc10date          | 2015-07-26 |
      | Required basic utc10date | 2018-09-09 |
    And I should see the following Totara form fields having these values:
      | Basic utc10date          | 2015-07-26 |
      | Required basic utc10date | 2018-09-09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"

    When I press "Reset"
    Then I should see "Form: Basic utc10date element"
    When I set the following Totara form fields to these values:
      | Basic utc10date | |
      | Required basic utc10date | 2018/09/09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"

    When I press "Reset"
    Then I should see "Form: Basic utc10date element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic utc10date | 2015/07/26 |
      | Required basic utc10date | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic utc10date element"

    When I set the following Totara form fields to these values:
      | Basic utc10date | 2018/09/09 |
      | Required basic utc10date | 2015/07/26 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"

  Scenario: Test required utc10date elements in Totara forms with JavaScript disabled
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic utc10date | 2015/07/26 |
      | Required basic utc10date | 2018/09/09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"

    When I press "Reset"
    Then I should see "Form: Basic utc10date element"
    When I set the following Totara form fields to these values:
      | Basic utc10date | |
      | Required basic utc10date | 2018/09/09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"

    When I press "Reset"
    Then I should see "Form: Basic utc10date element"
    When I set the following Totara form fields to these values:
      | Basic utc10date | 2015/07/26 |
      | Required basic utc10date | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic utc10date element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic utc10date | 2018/09/09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1536487200 (2018/09/09)»"

  @javascript
  Scenario: Test hidden if on utc10date elements in Totara forms
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I click on "Expand all" "link"
    And I should see "Visible when 'Testing Hiddenif' is empty"
    And I should not see "Visible when 'Testing Hiddenif' is not empty"
    And I should see "Visible when 'Testing Hiddenif' is not filled"
    And I should not see "Visible when 'Testing Hiddenif' is filled"
    And I should see "Visible when 'Required basic utc10date' is empty"
    And I should not see "Visible when 'Required basic utc10date' is not empty"

    When I set the following Totara form fields to these values:
      | Visible when 'Testing Hiddenif' is empty | 1987/12/31 |
      | Visible when 'Testing Hiddenif' is not filled           | 2000-01-01 |
      | Visible when 'Required basic utc10date' is empty | 2000-02-28 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | 2020/08/06 |
    Then I should see "Form: Basic utc10date element"
    And I should not see "Visible when 'Testing Hiddenif' is empty"
    And I should see "Visible when 'Testing Hiddenif' is not empty"
    And I should not see "Visible when 'Testing Hiddenif' not is filled"
    And I should see "Visible when 'Testing Hiddenif' is filled"
    And I should see "Visible when 'Required basic utc10date' is empty"
    And I should not see "Visible when 'Required basic utc10date' is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | 2016/06/23 |
      | Required basic utc10date | 2020/08/06  |
    Then I should see "Form: Basic utc10date element"
    And I should not see "Visible when 'Testing Hiddenif' is empty"
    And I should see "Visible when 'Testing Hiddenif' is not empty"
    And I should not see "Visible when 'Testing Hiddenif' not is filled"
    And I should see "Visible when 'Testing Hiddenif' is filled"
    And I should not see "Visible when 'Required basic utc10date' is empty"
    And I should see "Visible when 'Required basic utc10date' is not empty"

    When I set the following Totara form fields to these values:
      | Basic utc10date | 2015/07/26 |
      | Required basic utc10date | 2020/08/06 |
      | utc10date with current data | 1999/12/01 |
      | Visible when 'Testing Hiddenif' is not empty | 17/6/5 |
      | Visible when 'Testing Hiddenif' is filled | 2000/03/31 |
      | Visible when 'Required basic utc10date' is not empty | 2000 02 28 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "utc10date_basic" row "Value" column of "form_results" table should contain "«1437904800 (2015/07/26)»"
    And "utc10date_required" row "Value" column of "form_results" table should contain "«1596708000 (2020/08/06)»"
    And "utc10date_with_current_data" row "Value" column of "form_results" table should contain "«944042400 (1999/12/01)»"
    And "utc10date_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "utc10date_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1466676000 (2016/06/23)»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1466676000 (2016/06/23)»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1496656800 (2017/06/05)»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«567943200 (1987/12/31)»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«946720800 (2000/01/01)»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«954496800 (2000/03/31)»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«951732000 (2000/02/28)»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«951732000 (2000/02/28)»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_utc10date»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test relative date in utc10date elements in Totara forms without javascript
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic utc10date | +P1D |
      | Required basic utc10date | -P1D |
    And I press "Save changes"
    Then I should see "The form has been submit"

  @javascript
  Scenario: Test relative date in utc10date elements in Totara forms with javascript
    When I select "Basic utc10date element [totara_form\form\testform\element_utc10date]" from the "Test form" singleselect
    Then I should see "Form: Basic utc10date element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic utc10date | +P1D |
      | Required basic utc10date | -P1D |
    And I press "Save changes"
    Then I should see "The form has been submit"
