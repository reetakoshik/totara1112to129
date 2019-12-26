@totara @totara_cohort
Feature: Test dynamic audience with user profile choice based custom text fields.
  In order to compute the members of a cohort with dynamic membership
  As an admin
  I should be able to use menu custom field values for filter rules

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname |
      | u0       | User      | 0        |
      | u1       | User      | 1        |
      | u2       | User      | 2        |
      | u3       | User      | 3        |
      | u4       | User      | 4        |
      | u5       | User      | 5        |
    And the following "custom profile fields" exist in "totara_core" plugin:
      | datatype | shortname | name    | param1     | defaultdata |
      | checkbox | upck      | upck    |            | 0           |
      | menu     | upmenu    | upmenu  | IT/Fin/Unk | Unk         |
      | text     | uptxt     | uptxt   |            | uptxt       |
    And the following "custom profile field assignments" exist in "totara_core" plugin:
      | username | fieldname | value     |
      | u0       | upck      | 1         |
      | u0       | upmenu    | 0         |
      | u0       | uptxt     | uptxt_usr |
      | u1       | upck      | 1         |
      | u1       | upmenu    | 1         |
      | u1       | uptxt     | uptxt_usr |
      | u2       | upck      | 0         |
      | u2       | upmenu    | 2         |
      | u2       | uptxt     | uptxt     |
      | u3       | upck      | 0         |
      | u3       | upmenu    | 2         |
      | u3       | uptxt     |           |
      | u4       | upck      | 0         |
      | u4       | upmenu    | 2         |
      | u4       | uptxt     | aaa       |
    And the following "cohorts" exist:
      | name         | idnumber | cohorttype |
      | TestAudience | D1       | 2          |

    Given I log in as "admin"
    # Unfortunately new custom fields are popping up in auth plugin settings.
    And I confirm new default admin settings
    And I navigate to "Audiences" node in "Site administration > Audiences"
    And I follow "TestAudience"
    And I switch to "Rule sets" tab
    And I set the field "addrulesetmenu" to "uptxt (Choose)"


  @javascript
  Scenario: cohort_userprofile_text_choice_01: "equals" custom text field specific value
    When I set the field "equal" to "Equal to"
    And I set the field "listofvalues[]" to "uptxt_usr"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "User 0" in the "#cohort_members" "css_element"
    And I should see "User 1" in the "#cohort_members" "css_element"
    And I should not see "User 2" in the "#cohort_members" "css_element"
    And I should not see "User 3" in the "#cohort_members" "css_element"
    And I should not see "User 4" in the "#cohort_members" "css_element"
    And I should not see "User 5" in the "#cohort_members" "css_element"


  @javascript
  Scenario: cohort_userprofile_text_choice_02: "equals" custom text field default value
    When I set the field "equal" to "Equal to"
    And I set the field "listofvalues[]" to "uptxt"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "User 0" in the "#cohort_members" "css_element"
    And I should not see "User 1" in the "#cohort_members" "css_element"
    And I should see "User 2" in the "#cohort_members" "css_element"
    And I should not see "User 3" in the "#cohort_members" "css_element"
    And I should not see "User 4" in the "#cohort_members" "css_element"
    And I should see "User 5" in the "#cohort_members" "css_element"


  @javascript
  Scenario: cohort_userprofile_text_choice_03: "equals" multiple, specific custom text field values
    When I set the field "equal" to "Equal to"
    And I set the field "listofvalues[]" to "uptxt_usr,aaa"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "User 0" in the "#cohort_members" "css_element"
    And I should see "User 1" in the "#cohort_members" "css_element"
    And I should not see "User 2" in the "#cohort_members" "css_element"
    And I should not see "User 3" in the "#cohort_members" "css_element"
    And I should see "User 4" in the "#cohort_members" "css_element"
    And I should not see "User 5" in the "#cohort_members" "css_element"


  @javascript
  Scenario: cohort_userprofile_text_choice_04: "not equals" multiple, specific custom text field values
    When I set the field "equal" to "Not equal to"
    And I set the field "listofvalues[]" to "uptxt_usr,aaa"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "User 0" in the "#cohort_members" "css_element"
    And I should not see "User 1" in the "#cohort_members" "css_element"
    And I should see "User 2" in the "#cohort_members" "css_element"
    And I should see "User 3" in the "#cohort_members" "css_element"
    And I should not see "User 4" in the "#cohort_members" "css_element"
    And I should see "User 5" in the "#cohort_members" "css_element"


  @javascript
  Scenario: cohort_userprofile_text_choice_05: "not equals" custom text field default value
    When I set the field "equal" to "Not equal to"
    And I set the field "listofvalues[]" to "uptxt"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should see "User 0" in the "#cohort_members" "css_element"
    And I should see "User 1" in the "#cohort_members" "css_element"
    And I should not see "User 2" in the "#cohort_members" "css_element"
    And I should see "User 3" in the "#cohort_members" "css_element"
    And I should see "User 4" in the "#cohort_members" "css_element"
    And I should not see "User 5" in the "#cohort_members" "css_element"


  @javascript
  Scenario: cohort_userprofile_text_choice_06: "not equals" specific custom text field value
    When I set the field "equal" to "Not equal to"
    And I set the field "listofvalues[]" to "uptxt_usr"
    And I click on "Save" "button" in the "Add rule" "totaradialogue"
    Then I should see "Audience rules changed"

    When I press "Approve changes"
    And I switch to "Members" tab
    Then I should not see "User 0" in the "#cohort_members" "css_element"
    And I should not see "User 1" in the "#cohort_members" "css_element"
    And I should see "User 2" in the "#cohort_members" "css_element"
    And I should see "User 3" in the "#cohort_members" "css_element"
    And I should see "User 4" in the "#cohort_members" "css_element"
    And I should see "User 5" in the "#cohort_members" "css_element"
