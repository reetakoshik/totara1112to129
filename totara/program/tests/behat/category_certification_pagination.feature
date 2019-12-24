@totara @totara_program
Feature: Navigate to all certifications in a category with sub-categories
  In order to view all certifications in a category with sub-categories
  As an admin
  I need to lbe able to navigate to all certification in a category with sub-categories

  @javascript
  Scenario: Can navigate to all certifications in a category with sub-categories
    Given I am on a totara site
    And I log in as "admin"
    And the following config values are set as admin:
      | config | value |
      | coursesperpage | 5 |

    And the following "categories" exist:
      | name       | category | idnumber |
      | Category 1 | 0        | CAT1     |
      | Sub 1      | CAT1     | SUB1     |

    And the following "certifications" exist in "totara_program" plugin:
      | fullname   | shortname  | category |
      | cert1      | cert1      | 2        |
      | cert2      | cert2      | 2        |
      | cert3      | cert3      | 2        |
      | cert4      | cert4      | 2        |
      | cert5      | cert5      | 2        |
      | cert6      | cert6      | 2        |
      | cert7      | cert7      | 2        |
      | cert8      | cert8      | 2        |
      | cert9      | cert9      | 2        |
      | cert10     | cert10     | 2        |
      | cert11     | cert11     | 2        |
      | cert12     | cert12     | 2        |

    When I navigate to "Manage certifications" node in "Site administration > Courses"
    And I follow "Category 1"
    And I follow "cert1"
    And I follow "Category 1"
    Then I should see "cert1"
    And I should see "cert2"
    And I should see "cert3"
    And I should see "cert4"
    And I should see "cert5"
    And I should not see "cert8"
    And I should not see "cert11"
    And I should see "1" in the ".paging" "css_element"
    And I should see "2" in the ".paging" "css_element"
    And I should see "3" in the ".paging" "css_element"
    And I should not see "4" in the ".paging" "css_element"

    When I click on "3" "link" in the ".paging" "css_element"
    Then I should see "cert11"
    And I should see "cert12"
    And I should not see "cert4"
    And I should not see "cert9"

    When I click on "Previous" "link" in the ".paging" "css_element"
    Then I should see "cert6"
    And I should see "cert7"
    And I should see "cert8"
    And I should see "cert9"
    And I should see "cert10"
    And I should not see "cert4"
    And I should not see "cert12"

