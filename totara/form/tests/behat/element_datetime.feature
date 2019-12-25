@totara @totara_form
Feature: Totara form datetime element tests
  In order to test the datetime element
  As an admin
  I use the test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic datetime elements in Totara forms
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"
    And I should see the following Totara form fields having these values:
      | Basic datetime                    |                  |
      | Required basic datetime           |                  |
      | datetime with current data        | 2016/03/08 06:05 |
      | Frozen datetime with current data | 2016/06/23 19:27 |
    And I should see the "Empty frozen datetime" Totara form field is frozen
    And I should see the "Frozen datetime with current data" Totara form field is frozen
    And I should see "There are required fields in this form marked"

    When I set the following Totara form fields to these values:
      | Basic datetime                    | 2015-07-26 12:34 |
      | Required basic datetime           | 2018-09-09T09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"
    And "datetime_with_current_data" row "Value" column of "form_results" table should contain "«1457388300 (2016/03/08 06:05 Australia/Perth)»"
    And "datetime_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "datetime_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1466681220 (2016/06/23 19:27 Australia/Perth)»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_datetime»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test required datetime elements in Totara forms with JavaScript enabled
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"
    And I should see the following Totara form fields having these values:
      | Basic datetime                    |                  |
      | Required basic datetime           |                  |
      | datetime with current data        | 2016/03/08 06:05 |
      | Frozen datetime with current data | 2016/06/23 19:27 |
    And I should see the "Empty frozen datetime" Totara form field is frozen
    And I should see the "Frozen datetime with current data" Totara form field is frozen
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic datetime                    | 2015/07/26 12:34 |
      | Required basic datetime           | 2018/09/09 09:09 |
    And I should see the following Totara form fields having these values:
      | Basic datetime                    | 2015/07/26 12:34 |
      | Required basic datetime           | 2018/09/09 09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"
    And "datetime_with_current_data" row "Value" column of "form_results" table should contain "«1457388300 (2016/03/08 06:05 Australia/Perth)»"

    When I press "Reset"
    Then I should see "Form: Basic datetime element"
    When I set the following Totara form fields to these values:
      | Basic datetime | |
      | Required basic datetime | 2018/09/09 09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"

    When I press "Reset"
    Then I should see "Form: Basic datetime element"
    When I start watching to see if a new page loads
    And I set the following Totara form fields to these values:
      | Basic datetime | 2015/07/26 12:34 |
      | Required basic datetime | |
    And I press "Save changes"
    And a new page should not have loaded since I started watching
    Then I should not see "The form has been submit"
    And I should see "Form: Basic datetime element"

    When I set the following Totara form fields to these values:
      | Basic datetime | 2018/09/09 09:09 |
      | Required basic datetime | 2015/07/26 12:34 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"

  Scenario: Test required datetime elements in Totara forms with JavaScript disabled
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"
    And I should see "There are required fields in this form marked"
    When I set the following Totara form fields to these values:
      | Basic datetime | 2015/07/26 12:34 |
      | Required basic datetime | 2018/09/09 09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"

    When I press "Reset"
    Then I should see "Form: Basic datetime element"
    When I set the following Totara form fields to these values:
      | Basic datetime | |
      | Required basic datetime | 2018/09/09 09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"

    When I press "Reset"
    Then I should see "Form: Basic datetime element"
    When I set the following Totara form fields to these values:
      | Basic datetime | 2015/07/26 12:34 |
      | Required basic datetime | |
    And I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"
    And I should see "Form: Basic datetime element"
    And I should see "Required"

    When I set the following Totara form fields to these values:
      | Required basic datetime | 2018/09/09 09:09 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1536455340 (2018/09/09 09:09 Australia/Perth)»"

  @javascript
  Scenario: Test hidden if on datetime elements in Totara forms
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"
    And I click on "Expand all" "link"
    And I should see "Visible when 'Testing Hiddenif' is empty"
    And I should not see "Visible when 'Testing Hiddenif' is not empty"
    And I should see "Visible when 'Testing Hiddenif' is not filled"
    And I should not see "Visible when 'Testing Hiddenif' is filled"
    And I should see "Visible when 'Required basic datetime' is empty"
    And I should not see "Visible when 'Required basic datetime' is not empty"

    When I set the following Totara form fields to these values:
      | Visible when 'Testing Hiddenif' is empty | 1987/12/31 11:59 |
      | Visible when 'Testing Hiddenif' is not filled           | 2000-01-01 12:00 |
      | Visible when 'Required basic datetime' is empty | 2000-02-28 01:00 |
    When I set the following Totara form fields to these values:
      | Hidden if reference | 2020/08/06 |
    Then I should see "Form: Basic datetime element"
    And I should not see "Visible when 'Testing Hiddenif' is empty"
    And I should see "Visible when 'Testing Hiddenif' is not empty"
    And I should not see "Visible when 'Testing Hiddenif' not is filled"
    And I should see "Visible when 'Testing Hiddenif' is filled"
    And I should see "Visible when 'Required basic datetime' is empty"
    And I should not see "Visible when 'Required basic datetime' is not empty"

    When I set the following Totara form fields to these values:
      | Hidden if reference | 2016/06/23T17:00 |
      | Required basic datetime | 2020/08/06  |
    Then I should see "Form: Basic datetime element"
    And I should not see "Visible when 'Testing Hiddenif' is empty"
    And I should see "Visible when 'Testing Hiddenif' is not empty"
    And I should not see "Visible when 'Testing Hiddenif' not is filled"
    And I should see "Visible when 'Testing Hiddenif' is filled"
    And I should not see "Visible when 'Required basic datetime' is empty"
    And I should see "Visible when 'Required basic datetime' is not empty"

    When I set the following Totara form fields to these values:
      | Basic datetime | 2015/07/26 12:34 |
      | Required basic datetime | 2020/08/06 |
      | datetime with current data | 1999/12/01 7:56 |
      | Visible when 'Testing Hiddenif' is not empty | 17/6/5 4:3 |
      | Visible when 'Testing Hiddenif' is filled | 2000/03/31 01:00 |
      | Visible when 'Required basic datetime' is not empty | 2000 02 28 01:00 |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "datetime_basic" row "Value" column of "form_results" table should contain "«1437885240 (2015/07/26 12:34 Australia/Perth)»"
    And "datetime_required" row "Value" column of "form_results" table should contain "«1596699900 (2020/08/06 15:45 Australia/Perth)»"
    And "datetime_with_current_data" row "Value" column of "form_results" table should contain "«944006160 (1999/12/01 07:56 Australia/Perth)»"
    And "datetime_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "datetime_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1466681220 (2016/06/23 19:27 Australia/Perth)»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1466658000 (2016/06/23 13:00 Australia/Perth)»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1496606580 (2017/06/05 04:03 Australia/Perth)»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«567921540 (1987/12/31 11:59 Australia/Perth)»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«946699200 (2000/01/01 12:00 Australia/Perth)»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«954435600 (2000/03/31 01:00 Australia/Perth)»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«951670800 (2000/02/28 01:00 Australia/Perth)»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«951670800 (2000/02/28 01:00 Australia/Perth)»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_datetime»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test relative datetime elements in Totara forms without javascript
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"

    When I set the following Totara form fields to these values:
      | Basic datetime | +P1D |
      | Required basic datetime | -P1D |
    And I press "Save changes"
    Then I should see "The form has been submit"

  @javascript
  Scenario: Test relative datetime elements in Totara forms with javascript
    When I select "Basic datetime element [totara_form\form\testform\element_datetime]" from the "Test form" singleselect
    Then I should see "Form: Basic datetime element"

    When I set the following Totara form fields to these values:
      | Basic datetime | +P1D |
      | Required basic datetime | -P1D |
    And I press "Save changes"
    Then I should see "The form has been submit"
