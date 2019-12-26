@totara @totara_form
Feature: Totara form radios element tests
  In order to test the radios element
  As an admin
  I use the radios test form to confirm behaviour

  Background:
    Given I log in as "admin"
    And I navigate to the Totara test form
    And I should see "Form acceptance testing page"

  Scenario: Test basic radios elements in Totara forms without JavaScript
    When I select "Basic radios element [totara_form\form\testform\element_radios]" from the "Test form" singleselect
    Then I should see "Form: Basic radios element"
    And I should see the following Totara form fields having these values:
      | Basic radios                    | $@NULL@$ |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | $@NULL@$ |
      | Radios with current data        | Yes      |
      | Empty frozen radios             | $@NULL@$ |
      | Frozen radios with current data | Yes      |
    And I should see the following Totara form fields having these values:
      | Radios with current data        | 1        |
      | Frozen radios with current data | 1        |
    And I should see the following Totara form fields frozen:
      | Empty frozen radios             |
      | Frozen radios with current data |

    When I set the following Totara form fields to these values:
      | Required basic radios           | Yes      |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "radios_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_required" row "Value" column of "form_results" table should contain "«1»"
    And "radios_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "radios_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_radios»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic radios element"
    And I should see the following Totara form fields having these values:
      | Basic radios                    | $@NULL@$ |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | $@NULL@$ |
      | Radios with current data        | Yes      |
      | Empty frozen radios             | $@NULL@$ |
      | Frozen radios with current data | Yes      |
    And I should see the following Totara form fields having these values:
      | Radios with current data        | 1        |
      | Frozen radios with current data | 1        |
    And I should see the following Totara form fields frozen:
      | Empty frozen radios             |
      | Frozen radios with current data |

    When I set the following Totara form fields to these values:
      | Basic radios                    | Yes      |
      | Required basic radios           | 1        |
      | Radios with current data        | No       |
      | Hidden if reference             | Yes      |
      | A is visible when hiddenif reference is yes          | No    |
      | D is visible when hiddenif reference is yes          | Bravo |
      | F is visible when hiddenif reference is yes          | Yes   |
      | G is visible when required radios is not empty (yes) | No    |
    And I should see the following Totara form fields having these values:
      | Basic radios                    | 1        |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | 1        |
      | Radios with current data        | 0        |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "radios_basic" row "Value" column of "form_results" table should contain "«1»"
    And "radios_required" row "Value" column of "form_results" table should contain "«1»"
    And "radios_with_current_data" row "Value" column of "form_results" table should contain "«0»"
    And "radios_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "radios_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«bravo»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_radios»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  @javascript
  Scenario: Test basic radios elements in Totara forms with JavaScript
    When I select "Basic radios element [totara_form\form\testform\element_radios]" from the "Test form" singleselect
    Then I should see "Form: Basic radios element"
    And I should see the following Totara form fields having these values:
      | Basic radios                    | $@NULL@$ |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | $@NULL@$ |
      | Radios with current data        | Yes      |
      | Empty frozen radios             | $@NULL@$ |
      | Frozen radios with current data | Yes      |
    And I should see the following Totara form fields having these values:
      | Radios with current data        | 1        |
      | Frozen radios with current data | 1        |
    And I should see the following Totara form fields frozen:
      | Empty frozen radios             |
      | Frozen radios with current data |

    When I set the following Totara form fields to these values:
      | Required basic radios           | Yes      |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "radios_basic" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_required" row "Value" column of "form_results" table should contain "«1»"
    And "radios_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "radios_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_radios»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

    When I press "Reset"
    Then I should see "Form: Basic radios element"
    And I should see the following Totara form fields having these values:
      | Basic radios                    | $@NULL@$ |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | $@NULL@$ |
      | Radios with current data        | Yes      |
      | Empty frozen radios             | $@NULL@$ |
      | Frozen radios with current data | Yes      |
    And I should see the following Totara form fields having these values:
      | Radios with current data        | 1        |
      | Frozen radios with current data | 1        |
    And I should see the following Totara form fields frozen:
      | Empty frozen radios             |
      | Frozen radios with current data |

    When I set the following Totara form fields to these values:
      | Basic radios                    | Yes      |
      | Required basic radios           | 1        |
      | Radios with current data        | No       |
      | Hidden if reference             | Yes      |
      | A is visible when hiddenif reference is yes          | No    |
      | D is visible when hiddenif reference is yes          | Bravo |
      | F is visible when hiddenif reference is yes          | Yes   |
      | G is visible when required radios is not empty (yes) | No    |
    And I should see the following Totara form fields having these values:
      | Basic radios                    | Yes      |
      | Horizontal radios               | $@NULL@$ |
      | Required basic radios           | Yes      |
      | Radios with current data        | No       |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "radios_basic" row "Value" column of "form_results" table should contain "«1»"
    And "radios_required" row "Value" column of "form_results" table should contain "«1»"
    And "radios_with_current_data" row "Value" column of "form_results" table should contain "«0»"
    And "radios_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_frozen_empty" row "Post data" column of "form_results" table should contain "No post data"
    And "radios_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "radios_frozen_with_current_data" row "Post data" column of "form_results" table should contain "No post data"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«bravo»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«--null--»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«--null--»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_radios»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"

  Scenario: Test required radios elements in Totara forms without JavaScript
    When I select "Basic radios element [totara_form\form\testform\element_radios]" from the "Test form" singleselect
    Then I should see "Form: Basic radios element"
    When I press "Save changes"
    Then I should not see "The form has been submit"
    And I should see "Form could not be submitted, validation failed"

  @javascript
  Scenario: Test required radios elements in Totara forms with JavaScript
    When I select "Basic radios element [totara_form\form\testform\element_radios]" from the "Test form" singleselect
    Then I should see "Form: Basic radios element"
    When I start watching to see if a new page loads
    And I press "Save changes"
    Then a new page should not have loaded since I started watching
    And I should not see "The form has been submit"

  @javascript
  Scenario: Test hidden if on radios elements in Totara forms
    When I select "Basic radios element [totara_form\form\testform\element_radios]" from the "Test form" singleselect
    Then I should see "Form: Basic radios element"
    And I click on "Expand all" "link"
    And I should not see "A is visible when hiddenif reference is yes"
    And I should see "B is visible when hiddenif reference is no"
    And I should see "C is visible when hiddenif reference is no"
    And I should see "E is visible when hiddenif reference is no"
    And I should see "H is visible when required radios is empty (no, not selected)"
    And I should not see "D is visible when hiddenif reference is yes"
    And I should not see "F is visible when hiddenif reference is yes"
    And I should not see "G is visible when required radios is not empty (yes)"

    When I set the following Totara form fields to these values:
      | B is visible when hiddenif reference is no | United Kingdom |
      | C is visible when hiddenif reference is no | Empty |
      | E is visible when hiddenif reference is no | No |
      | H is visible when required radios is empty (no, not selected) | No |
      | Required basic radios  | Yes |
      | Hidden if reference | Yes |
    Then I should see "Form: Basic radios element"
    And I should see "A is visible when hiddenif reference is yes"
    And I should see "D is visible when hiddenif reference is yes"
    And I should see "F is visible when hiddenif reference is yes"
    And I should see "G is visible when required radios is not empty (yes)"
    And I should not see "B is visible when hiddenif reference is no"
    And I should not see "C is visible when hiddenif reference is no"
    And I should not see "E is visible when hiddenif reference is no"
    And I should not see "H is visible when required radios is empty (no, not selected)"

    When I set the following Totara form fields to these values:
      | Basic radios | Yes |
      | Radios with current data | No |
      | A is visible when hiddenif reference is yes | No |
      | D is visible when hiddenif reference is yes | Charlie |
      | F is visible when hiddenif reference is yes | Yes |
      | G is visible when required radios is not empty (yes) | Yes |
    And I press "Save changes"
    Then I should see "The form has been submit"
    And "radios_basic" row "Value" column of "form_results" table should contain "«1»"
    And "radios_required" row "Value" column of "form_results" table should contain "«1»"
    And "radios_with_current_data" row "Value" column of "form_results" table should contain "«0»"
    And "radios_frozen_empty" row "Value" column of "form_results" table should contain "«--null--»"
    And "radios_frozen_with_current_data" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_primary" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_secondary_b" row "Value" column of "form_results" table should contain "«UK»"
    And "hiddenif_secondary_c" row "Value" column of "form_results" table should contain "«»"
    And "hiddenif_secondary_d" row "Value" column of "form_results" table should contain "«charlie»"
    And "hiddenif_secondary_e" row "Value" column of "form_results" table should contain "«0»"
    And "hiddenif_secondary_f" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_a" row "Value" column of "form_results" table should contain "«1»"
    And "hiddenif_required_b" row "Value" column of "form_results" table should contain "«0»"
    And "form_select" row "Value" column of "form_results" table should contain "«totara_form\form\testform\element_radios»"
    And "submitbutton" row "Value" column of "form_results" table should contain "«1»"
