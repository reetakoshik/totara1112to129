@totara @totara_catalog @javascript
Feature: Admin can configure grid catalog search using configuration forms
  As an administrator
  I need to be able to use the catalog configuration forms
  In order to configure the catalog search

  Background:
    Given I am on a totara site
    And I log in as "admin"
    And I navigate to "Courses > Configure catalogue" in site administration
    And I wait for pending js

  Scenario: Forms are showing correct default values and sections
    Then I should see the following Totara form fields having these values:
      | Include in catalogue | Courses,Certifications,Programs |
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    # Check Undo button
    When I set the "Include in catalogue" Totara form field to "Courses"
    And I press "Undo changes"
    Then I should see the following Totara form fields having these values:
      | Include in catalogue | Courses,Certifications,Programs |
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    When I follow "General"
    Then I should see the following Totara form fields having these values:
      | View options             | Tile and list    |
      | Items per 'load more'    | 20               |
      | Browse menu              | Category         |
      | Featured learning        | 0                |
    And I should see the "browse_by_custom" Totara form field is frozen
    And I should see the "featured_learning_source" Totara form field is frozen
    And I should see the "featured_learning_value" Totara form field is frozen
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    When I follow "Templates"
    Then I should see Totara form section "Item content placeholders"
    And I should see Totara form section "Detail content placeholders"
    And I should see the following Totara form fields having these values:
      | Image                            | 1       |
      | Hero data type                   | None    |
      | item_description_enabled         | 0       |
      | item_additional_text_count       | 2       |
      | item_additional_icons_enabled    | 0       |
      | Progress bar                     | 0       |
      | details_title_enabled            | 1       |
      | Rich text content                | 1       |
      | details_description_enabled      | 0       |
      | details_additional_text_count    | 2       |
      | details_additional_icons_enabled | 0       |
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    When I follow "Item"
    Then I should see Totara form section "Title"
    And I should see Totara form section "Additional text field(s)"
    And I should not see Totara form section "Icon sources"
    And I should see the following Totara form fields having these values:
      | item_title__course                           | Full name     |
      | item_title__program                          | Full name     |
      | item_title__certification                    | Full name     |
      | item_additional_text__course__0              | Learning type |
      | item_additional_text_label__course__0        | 0             |
      | item_additional_text__course__1              | Category      |
      | item_additional_text_label__course__1        | 0             |
      | item_additional_text__program__0             | Learning type |
      | item_additional_text_label__program__0       | 0             |
      | item_additional_text__program__1             | Category      |
      | item_additional_text_label__program__1       | 0             |
      | item_additional_text__certification__0       | Learning type |
      | item_additional_text_label__certification__0 | 0             |
      | item_additional_text__certification__1       | Category      |
      | item_additional_text_label__certification__1 | 0             |
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    When I follow "Details"
    Then I should see Totara form section "Title"
    And I should see Totara form section "Rich text content"
    And I should see Totara form section "Additional text field(s)"
    And I should not see Totara form section "Icon sources"
    Then I should see the following Totara form fields having these values:
      | details_title__course                           | Full name |
      | details_title__program                          | Full name |
      | details_title__certification                    | Full name |
      | rich_text__course                               |           |
      | rich_text__program                              |           |
      | rich_text__certification                        |           |
      | details_additional_text__course__0              |           |
      | details_additional_text_label__course__0        | 0         |
      | details_additional_text__course__1              |           |
      | details_additional_text_label__course__1        | 0         |
      | details_additional_text__program__0             |           |
      | details_additional_text_label__program__0       | 0         |
      | details_additional_text__program__1             |           |
      | details_additional_text_label__program__1       | 0         |
      | details_additional_text__certification__0       |           |
      | details_additional_text_label__certification__0 | 0         |
      | details_additional_text__certification__1       |           |
      | details_additional_text_label__certification__1 | 0         |
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

    When I follow "Filters"
    Then I should see "Learning type" in the "Learning type" "table_row"
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen

  Scenario: Forms unfreeze submit buttons on change and display warning when trying to change tab
    When I set the "Include in catalogue" Totara form field to "Courses"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen

    When I start watching to see if a new page loads
    And I follow "General"
    # Cancel the warning.
    # If this step doesn't work for all browsers, we may have to remove testing this.
    And I dismiss the currently displayed dialog
    And a new page should not have loaded since I started watching
    When I follow "General"
    # Accept the warning.
    # If this step doesn't work for all browsers, we may have to remove testing this.
    And I accept the currently displayed dialog
    Then a new page should have loaded since I started watching

    When I set the "View options" Totara form field to "List only"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen
    When I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Templates"
    And I set the "Image" Totara form field to "0"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen
    And I press "Save"
    And I should see "Changes have been saved. View catalogue."
    And I set the "Image" Totara form field to "1"

    When I start watching to see if a new page loads
    And I follow "Item"
    And I accept the currently displayed dialog
    Then a new page should have loaded since I started watching

    When I set the "item_title__course" Totara form field to "Short name"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen

    When I follow "Details"
    And I accept the currently displayed dialog
    And I set the "details_additional_text_label__course__1" Totara form field to "1"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen

    When I follow "Filters"
    And I accept the currently displayed dialog
    When I set the "Add another..." Totara form field to "Course Type"
    Then I should see the "submitbutton" Totara form field is not frozen
    And I should see the "cancelbutton" Totara form field is not frozen
    When I follow "Details"
    And I accept the currently displayed dialog
    Then I should see Totara form section "Title"

  Scenario: Form is saving changes and reflects them correctly
    When I set the following Totara form fields to these values:
      | Include in catalogue     | |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."
    And I should see "'Include in catalogue' has no selections and will result in an empty catalogue."
    And I should see the "submitbutton" Totara form field is frozen
    And I should see the "cancelbutton" Totara form field is frozen
    When I follow "General"
    And I follow "Contents"
    Then I should see the following Totara form fields having these values:
      | Include in catalogue     | |
    # We have to activate all included providers again. Use this opportunity to test the success notification show/hide along the way.
    When I set the "Include in catalogue" Totara form field to "Courses"
    And I press "Save"
    Then I should see "Changes have been saved and will appear as soon as the processing is complete."
    When I set the "Include in catalogue" Totara form field to "Courses,Certifications,Programs"
    Then I should not see "Changes have been saved"
    And I press "Save"
    Then I should see "Changes have been saved and will appear as soon as the processing is complete."

    When I follow "General"
    And I set the following Totara form fields to these values:
      | View options             | List only     |
      | Items per 'load more'    | 40            |
      | Browse menu              | none          |
      | Featured learning        | 1             |
    And I wait for pending js
    And I set the following Totara form fields to these values:
      | featured_learning_source | Category      |
      | featured_learning_value  | Miscellaneous |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    # Navigate to another tab and come back.
    When I follow "Templates"
    And I follow "General"
    Then I should see the following Totara form fields having these values:
      | View options             | List only     |
      | Items per 'load more'    | 40            |
      | Browse menu              | none          |
      | Featured learning        | 1             |
      | featured_learning_source | Category      |
      | featured_learning_value  | Miscellaneous |

    When I follow "Templates"
    And I set the following Totara form fields to these values:
      | Image                            | 0       |
      | Hero data type                   | Icon    |
      | item_description_enabled         | 1       |
      | item_additional_text_count       | 3       |
      | item_additional_icons_enabled    | 1       |
      | Progress bar                     | 1       |
      | details_title_enabled            | 0       |
      | Rich text content                | 0       |
      | details_description_enabled      | 1       |
      | details_additional_text_count    | 3       |
      | details_additional_icons_enabled | 1       |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    # Navigate to another tab and come back.
    When I follow "Item"
    And I follow "Templates"
    Then I should see the following Totara form fields having these values:
      | Image                            | 0       |
      | Hero data type                   | Icon    |
      | item_description_enabled         | 1       |
      | item_additional_text_count       | 3       |
      | item_additional_icons_enabled    | 1       |
      | Progress bar                     | 1       |
      | details_title_enabled            | 0       |
      | Rich text content                | 0       |
      | details_description_enabled      | 1       |
      | details_additional_text_count    | 3       |
      | details_additional_icons_enabled | 1       |

    # Change some values to set up Items and Details forms to show all possible fields.
    And I set the following Totara form fields to these values:
      | Hero data type                | Text    |
      | details_title_enabled         | 1       |
      | Rich text content             | 1       |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Item"
    And I set the following Totara form fields to these values:
      | item_title__course                           | Short name      |
      | item_title__program                          | Short name      |
      | item_title__certification                    | Short name      |
      | hero_data_text__course                       | Editing Trainer |
      | hero_data_text__program                      | Short name      |
      | hero_data_text__certification                | Time created    |
      | item_description__course                     | Language        |
      | item_description__program                    | Short name      |
      | item_description__certification              | Available Until |
      | item_additional_text__course__0              | Editing Trainer |
      | item_additional_text_label__course__0        | 1               |
      | item_additional_text__course__2              | Format          |
      | item_additional_text_label__course__2        | 1               |
      | item_additional_text__program__1             | Short name      |
      | item_additional_text_label__program__1       | 0               |
      | item_additional_text__program__2             | Short name      |
      | item_additional_text_label__program__2       | 0               |
      | item_additional_text__certification__1       | Available From  |
      | item_additional_text_label__certification__1 | 0               |
      | item_additional_text__certification__2       | ID              |
      | item_additional_text_label__certification__2 | 1               |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Details"
    And I follow "Item"
    Then I should see the following Totara form fields having these values:
      | item_title__course                           | Short name      |
      | item_title__program                          | Short name      |
      | item_title__certification                    | Short name      |
      | hero_data_text__course                       | Editing Trainer |
      | hero_data_text__program                      | Short name      |
      | hero_data_text__certification                | Time created    |
      | item_description__course                     | Language        |
      | item_description__program                    | Short name      |
      | item_description__certification              | Available Until |
      | item_additional_text__course__0              | Editing Trainer |
      | item_additional_text_label__course__0        | 1               |
      | item_additional_text__course__1              | Category        |
      | item_additional_text_label__course__1        | 0               |
      | item_additional_text__course__2              | Format          |
      | item_additional_text_label__course__2        | 1               |
      | item_additional_text__program__0             | Learning type   |
      | item_additional_text_label__program__0       | 0               |
      | item_additional_text__program__1             | Short name      |
      | item_additional_text_label__program__1       | 0               |
      | item_additional_text__program__2             | Short name      |
      | item_additional_text_label__program__2       | 0               |
      | item_additional_text__certification__1       | Available From  |
      | item_additional_text_label__certification__1 | 0               |
      | item_additional_text__certification__2       | ID              |
      | item_additional_text_label__certification__2 | 1               |

    When I follow "Details"
    And I set the following Totara form fields to these values:
      | details_title__course                           | Short name      |
      | details_title__program                          | Short name      |
      | details_title__certification                    | Short name      |
      | rich_text__course                               | Summary         |
      | rich_text__program                              | Summary         |
      | rich_text__certification                        | Summary         |
      | details_description__course                     | Language        |
      | details_description__program                    | Short name      |
      | details_description__certification              | Active Period   |
      | details_additional_text__course__0              | Editing Trainer |
      | details_additional_text_label__course__0        | 1               |
      | details_additional_text__course__1              |                 |
      | details_additional_text_label__course__1        | 0               |
      | details_additional_text__course__2              | Format          |
      | details_additional_text_label__course__2        | 1               |
      | details_additional_text__program__0             |                 |
      | details_additional_text_label__program__0       | 0               |
      | details_additional_text__program__1             | Short name      |
      | details_additional_text_label__program__1       | 0               |
      | details_additional_text__program__2             | Short name      |
      | details_additional_text_label__program__2       | 0               |
      | details_additional_text__certification__0       | Category        |
      | details_additional_text_label__certification__0 | 0               |

    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Filters"
    And I follow "Details"
    Then I should see the following Totara form fields having these values:
      | details_title__course                           | Short name      |
      | details_title__program                          | Short name      |
      | details_title__certification                    | Short name      |
      | rich_text__course                               | Summary         |
      | rich_text__program                              | Summary         |
      | rich_text__certification                        | Summary         |
      | details_description__course                     | Language        |
      | details_description__program                    | Short name      |
      | details_description__certification              | Active Period   |
      | details_additional_text__course__0              | Editing Trainer |
      | details_additional_text_label__course__0        | 1               |
      | details_additional_text__course__1              |                 |
      | details_additional_text_label__course__1        | 0               |
      | details_additional_text__course__2              | Format          |
      | details_additional_text_label__course__2        | 1               |
      | details_additional_text__program__0             |                 |
      | details_additional_text_label__program__0       | 0               |
      | details_additional_text__program__1             | Short name      |
      | details_additional_text_label__program__1       | 0               |
      | details_additional_text__program__2             | Short name      |
      | details_additional_text_label__program__2       | 0               |
      | details_additional_text__certification__0       | Category        |
      | details_additional_text_label__certification__0 | 0               |

  Scenario: Panel filters configuration page
    When I follow "Filters"
    And I set the "Add another..." Totara form field to "Course Type"
    And I wait for pending js
    Then I should see "Course Type" in the "Course Type" "table_row"
    When I set the "Add another..." Totara form field to "Category"
    And I wait for pending js
    Then I should see "Category" in the "Category" "table_row"
    When I set the "Add another..." Totara form field to "Format"
    And I wait for pending js
    Then I should see "Format" in the "Format" "table_row"
    When I set the following fields to these values:
      | Course Type | Typeadjusted     |
      | Category    | Categorymodified |
      | Format      | Formatchanged    |
    And I click on "Move filter up" "link" in the "Format" "table_row"
    And I wait for pending js
    Then "Format" "table_row" should appear before "Category" "table_row"
    And "Course Type" "table_row" should appear before "Format" "table_row"

    When I click on "Move filter down" "link" in the "Course Type" "table_row"
    And I wait for pending js
    Then "Format" "table_row" should appear before "Course Type" "table_row"
    And "Course Type" "table_row" should appear before "Category" "table_row"

    When I click on "Delete filter" "link" in the "Course Type" "table_row"
    And I wait for pending js
    Then the following fields match these values:
      | Format    | Formatchanged    |
      | Category  | Categorymodified |
    And I should not see "Course Type" in the ".totara_catalog-matrix table" "css_element"
    When I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Details"
    And I follow "Filters"
    Then the following fields match these values:
      | Format    | Formatchanged    |
      | Category  | Categorymodified |
    And I should not see "Course Type" in the ".totara_catalog-matrix table" "css_element"

  Scenario: Icon sources multiple select elements
    When I follow "Templates"
    And I set the following Totara form fields to these values:
      | item_additional_icons_enabled    | 1       |
      | details_additional_icons_enabled | 1       |
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Item"
    And I set the "item_additional_icons__course" multiple select Totara form field to "Activity types"
    And I set the "item_additional_icons__course" multiple select Totara form field to "Course icon"
    And I set the "item_additional_icons__course" multiple select Totara form field to "Course Type"
    And I set the "item_additional_icons__certification" multiple select Totara form field to "Program icon"
    And I set the "item_additional_icons__program" multiple select Totara form field to "Program icon"
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Details"
    And I set the "details_additional_icons__course" multiple select Totara form field to "Course icon"
    And I set the "details_additional_icons__course" multiple select Totara form field to "Activity types"
    And I set the "details_additional_icons__course" multiple select Totara form field to "Course Type"
    Then I should see the "details_additional_icons__course" Totara form field has value "Course icon,Activity types,Course Type"

    When I click on "Delete" "link" in the "Course Type" "list_item"
    Then I should see the "details_additional_icons__course" Totara form field has value "Course icon,Activity types"
    When I set the "details_additional_icons__course" multiple select Totara form field to "Course Type"
    Then "Course icon" "list_item" should appear before "Course Type" "list_item"
    When I click on "Move down" "link" in the "Course icon" "list_item"
    And I click on "Move up" "link" in the "Course Type" "list_item"
    Then "Course Type" "list_item" should appear before "Course icon" "list_item"

    When I click on "Delete" "link" in the "Course icon" "list_item"
    And I set the "details_additional_icons__program" multiple select Totara form field to "Program icon"
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."

    When I follow "Item"
    Then I should see the following Totara form fields having these values:
      | item_additional_icons__course        | Activity types,Course icon,Course Type |
      | item_additional_icons__certification | Program icon                    |
      | item_additional_icons__program       | Program icon                    |

    When I follow "Details"
    Then I should see the following Totara form fields having these values:
      | details_additional_icons__course        | Activity types,Course Type |
      | details_additional_icons__certification |                     |
      | details_additional_icons__program       | Program icon        |

  Scenario: Dependencies between form elements
    When I set the "Include in catalogue" Totara form field to "Programs"
    And I press "Save"
    And I should see "Changes have been saved. View catalogue."

    And I follow "Templates"
    And I set the following Totara form fields to these values:
      | Hero data type                   | Text    |
      | item_description_enabled         | 1       |
      | details_description_enabled      | 1       |
      | item_additional_icons_enabled    | 1       |
      | details_additional_icons_enabled | 1       |
    And I press "Save"
    And I should see "Changes have been saved. View catalogue."

    And I follow "Item"
    Then I should see Totara form section "Icon sources"
    And I should see the following Totara form fields having these values:
      | item_title__program                    | Full name     |
      | hero_data_text__program                |               |
      | item_description__program              |               |
      | item_additional_text__program__0       | Learning type |
      | item_additional_text_label__program__0 | 0             |
      | item_additional_text__program__1       | Category      |
      | item_additional_text_label__program__1 | 0             |
    And I should see Totara form label "Programs"
    And I should not see Totara form label "Courses"
    And I should not see Totara form label "Certifications"

    And I follow "Details"
    Then I should see Totara form section "Icon sources"
    And I should see the following Totara form fields having these values:
      | details_title__program                    | Full name |
      | rich_text__program                        |           |
      | details_description__program              |           |
      | details_additional_text__program__0       |           |
      | details_additional_text_label__program__0 | 0         |
      | details_additional_text__program__1       |           |
      | details_additional_text_label__program__1 | 0         |
    And I should see Totara form label "Programs"
    And I should not see Totara form label "Courses"
    And I should not see Totara form label "Certifications"

    When I follow "General"
    And I set the following Totara form fields to these values:
      | Browse menu              | custom   |
      | Featured learning        | 1        |
    And I wait for pending js
    And I set the following Totara form fields to these values:
      | browse_by_custom         |          |
      | featured_learning_source |          |
      | featured_learning_value  |          |
    And I press "Save"
    Then I should see "Form could not be submitted, validation failed"
    And I should see "A source and a value must be selected when 'Featured learning' is enabled."
    And I should see "A custom browse menu must be selected when 'Custom' is checked."

  Scenario: Link for saved success message takes me to grid catalogue
    When I set the "Include in catalogue" Totara form field to "Programs"
    And I press "Save"
    Then I should see "Changes have been saved. View catalogue."
    When I click on "View catalogue." "link" in the ".alert-message" "css_element"
    Then I should see the "totara" catalog page
