@totara @totara_customfield @javascript
Feature: Administrators can add a custom multi-select field to complete during course creation
  In order for the custom field to appear during course creation
  As admin
  I need to select the multi-select custom field and add the relevant settings

  Scenario: Create a custom multi-select
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"
    And "multiselectitem[3][option]" "field" should not be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[3][option]" "field" should be visible

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |
      | multiselectitem[3][option]  | Option Four               |
    And I click on "Make selected by default" "link" in the "#fgroup_id_multiselectitem_2" "css_element"
    And I click on "Delete" "link" in the "#fgroup_id_multiselectitem_1" "css_element"
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should not see "Option Two"
    And I should see "Option Three"
    And I should see "Option Four"
    And the following fields match these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 1 |
      | customfield_custommultiselect[2] | 0 |

    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
      | customfield_custommultiselect[0]  | 1          |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_custommultiselect[0] | 1 |
      | customfield_custommultiselect[1] | 1 |
      | customfield_custommultiselect[2] | 0 |

    When I set the following fields to these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 0 |
      | customfield_custommultiselect[2] | 1 |
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    Then the following fields match these values:
      | customfield_custommultiselect[0] | 0 |
      | customfield_custommultiselect[1] | 0 |
      | customfield_custommultiselect[2] | 1 |

  Scenario: Check locked setting on large custom multi-select
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"

    When I click on "Add another option" "link"
    Then "multiselectitem[3][option]" "field" should be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[4][option]" "field" should be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[5][option]" "field" should be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[6][option]" "field" should be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[7][option]" "field" should be visible

    When I click on "Add another option" "link"
    Then "multiselectitem[8][option]" "field" should be visible

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |
      | multiselectitem[3][option]  | Option Four               |
      | multiselectitem[4][option]  | Option Five               |
      | multiselectitem[5][option]  | Option Six                |
      | multiselectitem[6][option]  | Option Seven              |
      | multiselectitem[7][option]  | Option Eight              |
      | multiselectitem[8][option]  | Option Nine               |
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"
    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should see "Option Two"
    And I should see "Option Seven"
    And I should see "Option Eight"
    And I should see "Option Nine"

    And the "customfield_custommultiselect[0]" "checkbox" should be enabled
    And the "customfield_custommultiselect[1]" "checkbox" should be enabled
    And the "customfield_custommultiselect[6]" "checkbox" should be enabled
    And the "customfield_custommultiselect[7]" "checkbox" should be enabled
    And the "customfield_custommultiselect[8]" "checkbox" should be enabled

    When I navigate to "Custom fields" node in "Site administration > Courses"

    And I click on "Edit" "link"
    And I set the field "Is this field locked?" to "Yes"
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I should see the "Categories" management page
    And I click on category "Miscellaneous" in the management interface
    When I click on "edit" action for "Course One" in management course listing
    Then I should see "Edit course settings"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should see "Option Two"
    And I should see "Option Seven"
    And I should see "Option Eight"
    And I should see "Option Nine"

    And the "id_customfield_custommultiselect_0" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_1" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_6" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_7" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_8" "checkbox" should be disabled

  Scenario: Check locked setting on small custom multi-select
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"
    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
    And I press "Save and display"
    Then I should see "Course One" in the page title

    When I navigate to "Edit settings" node in "Course administration"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should see "Option Two"
    And I should see "Option Three"
    And the "customfield_custommultiselect[0]" "checkbox" should be enabled
    And the "customfield_custommultiselect[1]" "checkbox" should be enabled
    And the "customfield_custommultiselect[2]" "checkbox" should be enabled

    When I navigate to "Custom fields" node in "Site administration > Courses"

    And I click on "Edit" "link"
    And I set the field "Is this field locked?" to "Yes"
    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I should see the "Categories" management page
    And I click on category "Miscellaneous" in the management interface
    When I click on "edit" action for "Course One" in management course listing
    Then I should see "Edit course settings"

    When I expand all fieldsets
    Then I should see "Custom Multi-Select Field"
    And I should see "Option One"
    And I should see "Option Two"
    And I should see "Option Three"
    And the "id_customfield_custommultiselect_0" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_1" "checkbox" should be disabled
    And the "id_customfield_custommultiselect_2" "checkbox" should be disabled

  Scenario: Check custom field multi-select when required setting is on
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | This field is required      | Yes                       |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |

    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
    And I press "Save and display"
    Then I should see "This field is required"

    When I set the following fields to these values:
      | customfield_custommultiselect[0]  | 1  |
    And I press "Save and display"
    Then I should not see "This field is required"
    And I should see "Course One" in the page title

  Scenario: Check custom field multi-select when required setting is off
    Given I log in as "admin"
    When I navigate to "Custom fields" node in "Site administration > Courses"
    Then I should see "Create a new custom field"

    When I set the field "datatype" to "Multi-select"
    Then I should see "Editing custom field: Multi-select"

    When I set the following fields to these values:
      | fullname                    | Custom Multi-Select Field |
      | shortname                   | custom_multiselect        |
      | This field is required      | No                        |
      | multiselectitem[0][option]  | Option One                |
      | multiselectitem[1][option]  | Option Two                |
      | multiselectitem[2][option]  | Option Three              |

    And I press "Save changes"
    Then I should see "Custom Multi-Select Field"

    When I go to the courses management page
    And I click on "Create new course" "link"
    Then I should see "Add a new course"

    When I set the following fields to these values:
      | fullname                          | Course One |
      | shortname                         | course1    |
    And I press "Save and display"
    Then I should not see "This field is required"
    And I should see "Course One" in the page title
