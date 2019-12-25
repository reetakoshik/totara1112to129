@totara @core @core_course
Feature: A course type can be selected for a course
  To test course types
  As an admin
  I will create a course and change its type

  @javascript
  Scenario: I can select a type for a course and then change it
    Given I am on a totara site
    And I log in as "admin"
    And I create a course with:
      | Course full name  | Course 1 |
      | Course short name | C1 |
    When I navigate to "Edit settings" node in "Course administration"
    Then I should see "Course Type"
    When I set the field "Course Type" to "E-learning"
    Then the following fields match these values:
     | Course Type | E-learning |

    When I set the field "Course Type" to "Blended"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    Then the following fields match these values:
      | Course Type | Blended |

    When I set the field "Course Type" to "Seminar"
    And I press "Save and display"
    And I navigate to "Edit settings" node in "Course administration"
    Then the following fields match these values:
      | Course Type | Seminar |