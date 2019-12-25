@totara @totara_coursecatalog @totara_program @totara_certification
Feature: Check that searching for hidden programs when toggling course catalog works as expected
  Background:
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username   | firstname  | lastname    | email                  |
      | user1      | user1      | user1       | user1@example.com      |
      | sysmanager | sysmanager | sysmanager  | sysmanager@example.com |
      | catmanager | catmanager | catmanager  | catmanager@example.com |

    # Create category hierarchy.
    And the following "categories" exist:
      | name | category | idnumber |
      | top  | 0        | top      |
      | cat1 | 0        | cat1     |
      | cat2 | cat1     | cat2     |

    # Create programs (set both types of visibility to ensure normal doesn't interfere).
    And the following "programs" exist in "totara_program" plugin:
      | fullname      | shortname     | category | visible | audiencevisible |
      | cat0_program1 | cat0_program1 | top      | 0       | 2               |
      | cat0_program2 | cat0_program2 | top      | 1       | 0               |
      | cat0_program3 | cat0_program3 | top      | 0       | 3               |
      | cat1_program1 | cat1_program1 | cat1     | 0       | 2               |
      | cat1_program2 | cat1_program2 | cat1     | 1       | 0               |
      | cat1_program3 | cat1_program3 | cat1     | 0       | 3               |
      | cat2_program1 | cat2_program1 | cat2     | 0       | 2               |
      | cat2_program2 | cat2_program2 | cat2     | 1       | 0               |
      | cat2_program3 | cat2_program3 | cat2     | 0       | 3               |
    # 2 = COHORT_VISIBLE_ALL, 0 = COHORT_VISIBLE_ENROLLED, 3 = COHORT_VISIBLE_NOUSERS.

    # Create certs (set both types of visibility to ensure normal doesn't interfere).
    And the following "certifications" exist in "totara_program" plugin:
      | fullname   | shortname  | category | visible | audiencevisible |
      | cat0_cert1 | cat0_cert1 | 2        | 0       | 2               |
      | cat0_cert2 | cat0_cert2 | 2        | 1       | 0               |
      | cat0_cert3 | cat0_cert3 | 2        | 0       | 3               |
      | cat1_cert1 | cat1_cert1 | 3        | 0       | 2               |
      | cat1_cert2 | cat1_cert2 | 3        | 1       | 0               |
      | cat1_cert3 | cat1_cert3 | 3        | 0       | 3               |
      | cat2_cert1 | cat2_cert1 | 4        | 0       | 2               |
      | cat2_cert2 | cat2_cert2 | 4        | 1       | 0               |
      | cat2_cert3 | cat2_cert3 | 4        | 0       | 3               |
    # 2 = COHORT_VISIBLE_ALL, 0 = COHORT_VISIBLE_ENROLLED, 3 = COHORT_VISIBLE_NOUSERS.

    # Create new roles.
    And the following "roles" exist:
      | name                  | shortname             | archetype | contextlevel |
      | capmanagecategories   | capmanagecategories   |           | System       |
      | capviewhiddenlearning | capviewhiddenlearning |           | Category     |

    And the following "permission overrides" exist:
      | capability                                    | permission | role                 | contextlevel | reference |
      | moodle/category:manage                        | Allow      | capmanagecategories  | System       |           |
      | totara/program:createprogram                  | Allow      | capmanagecategories  | System       |           |
      | totara/certification:createcertification      | Allow      | capmanagecategories  | System       |           |
      | totara/program:viewhiddenprograms             | Allow      | capmanagecategories  | System       |           |
      | totara/certification:viewhiddencertifications | Allow      | capmanagecategories  | System       |           |

    # Note that this isn't just applying these capabilities to the role, it's applying to the role IN THE SPECIFIED CONTEXT.
    And the following "permission overrides" exist:
      | capability                                    | permission | role                  | contextlevel | reference |
      | totara/program:viewhiddenprograms             | Allow      | capviewhiddenlearning | Category     | cat1      |
      | totara/certification:viewhiddencertifications | Allow      | capviewhiddenlearning | Category     | cat1      |

    # Assign manage categories capability, so manager1 can see the site admin menu item and categories.
    And the following "system role assigns" exist:
      | user       | role                  |
      | sysmanager | capmanagecategories   |

    # Assign catmanager to the capviewhiddenlearning role in the cat1 context.
    And the following "role assigns" exist:
      | user       | role                  | contextlevel | reference |
      | catmanager | capviewhiddenlearning | Category     | cat1      |

  @javascript
  Scenario: Search for hidden programs in the old catalog
    # Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0      |
      | catalogtype        | moodle |
    And I log out

    # Seeing the program catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Programs" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_program1"
    And I should see "cat0_program2"
    And I should see "cat0_program3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_program1"
    And I should see "cat1_program2"
    And I should see "cat1_program3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_program1"
    And I should see "cat2_program2"
    And I should see "cat2_program3"

    When I set the field "coursesearchbox" to "cat0_program3"
    And I press "Go"
    Then I should see "cat0_program3" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat0_program2"
    And I press "Go"
    Then I should see "cat0_program2" in the ".course-search-result" "css_element"
    And I log out

    # Seeing the program catalog as user1.
    When I log in as "user1"
    And I click on "Programs" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_program2"
    And I should not see "cat0_program1"
    And I should not see "cat0_program3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_program2"
    And I should not see "cat1_program1"
    And I should not see "cat1_program3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_program2"
    And I should not see "cat2_program1"
    And I should not see "cat2_program3"

    When I set the field "coursesearchbox" to "cat0_program3"
    And I press "Go"
    Then I should see "No programs were found with the words 'cat0_program3'"
    When I set the field "coursesearchbox" to "cat0_program2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat0_program2" in the ".course-search-result" "css_element"
    And I log out

     # Seeing the program catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Programs" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_program2"
    And I should not see "cat0_program1"
    And I should not see "cat0_program3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_program2"
    And I should see "cat1_program1"
    And I should see "cat1_program3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_program2"
    And I should see "cat2_program1"
    And I should see "cat2_program3"

    When I set the field "coursesearchbox" to "cat0_program3"
    And I press "Go"
    Then I should see "No programs were found with the words 'cat0_program3'"
    When I set the field "coursesearchbox" to "cat1_program1"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_program1" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat1_program2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_program2" in the ".course-search-result" "css_element"

  @javascript
  Scenario: Search for hidden programs in the enhanced catalog
  # Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0        |
      | catalogtype        | enhanced |
    And I log out

  # Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Programs" in the totara menu
    Then I should see "cat0_program1"
    And I should see "cat0_program2"
    And I should see "cat0_program3"
    And I should see "cat1_program1"
    And I should see "cat1_program2"
    And I should see "cat1_program3"
    And I should see "cat2_program1"
    And I should see "cat2_program2"
    And I should see "cat2_program3"

    When I set the following fields to these values:
      | Search by | cat0_program3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program3" in the "#catalogprograms" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_program2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program2" in the "#catalogprograms" "css_element"
    And I log out

  # Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Programs" in the totara menu
    Then I should see "cat0_program2"
    And I should see "cat1_program2"
    And I should see "cat2_program2"
    And I should not see "cat0_program1"
    And I should not see "cat0_program3"
    And I should not see "cat1_program1"
    And I should not see "cat1_program3"
    And I should not see "cat2_program1"
    And I should not see "cat2_program3"

    When I set the following fields to these values:
      | Search by | cat0_program3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_program2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program2" in the "#catalogprograms" "css_element"
    And I log out

  # Seeing the enhanced catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Programs" in the totara menu
    Then I should see "cat0_program2"
    And I should see "cat1_program1"
    And I should see "cat1_program2"
    And I should see "cat1_program3"
    And I should see "cat2_program1"
    And I should see "cat2_program2"
    And I should see "cat2_program3"
    And I should not see "cat0_program1"
    And I should not see "cat0_program3"

    When I set the following fields to these values:
      | Search by | cat0_program3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat1_program1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_program1" in the "#catalogprograms" "css_element"
    When I set the following fields to these values:
      | Search by | cat1_program2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_program2" in the "#catalogprograms" "css_element"
    And I log out

  @javascript
  Scenario: Search for hidden programs in the enhanced catalog with audience visibility on
    # Configure visibility audience visibility.
    Given I set the following administration settings values:
      | audiencevisibility | 1        |
      | catalogtype        | enhanced |
    And I log out

    # Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Programs" in the totara menu
    Then I should see "cat0_program1"
    And I should see "cat0_program2"
    And I should see "cat0_program3"
    And I should see "cat1_program1"
    And I should see "cat1_program2"
    And I should see "cat1_program3"
    And I should see "cat2_program1"
    And I should see "cat2_program2"
    And I should see "cat2_program3"

    When I set the following fields to these values:
      | Search by | cat0_program3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program3" in the "#catalogprograms" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_program1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program1" in the "#catalogprograms" "css_element"
    And I log out

    # Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Programs" in the totara menu
    Then I should see "cat0_program1"
    And I should see "cat1_program1"
    And I should see "cat2_program1"
    And I should not see "cat0_program2"
    And I should not see "cat0_program3"
    And I should not see "cat1_program2"
    And I should not see "cat1_program3"
    And I should not see "cat2_program2"
    And I should not see "cat2_program3"

    When I set the following fields to these values:
      | Search by | cat0_program2 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_program1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_program1" in the "#catalogprograms" "css_element"
    And I log out

  @javascript
  Scenario: Search for hidden certifications in the old catalog
    # Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0      |
      | catalogtype        | moodle |
    And I log out

    # Seeing the certification catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Certifications" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_cert1"
    And I should see "cat0_cert2"
    And I should see "cat0_cert3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_cert1"
    And I should see "cat1_cert2"
    And I should see "cat1_cert3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_cert1"
    And I should see "cat2_cert2"
    And I should see "cat2_cert3"

    When I set the field "coursesearchbox" to "cat0_cert3"
    And I press "Go"
    Then I should see "cat0_cert3" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat0_cert2"
    And I press "Go"
    Then I should see "cat0_cert2" in the ".course-search-result" "css_element"
    And I log out

    # Seeing the certifications catalog as user1.
    When I log in as "user1"
    And I click on "Certifications" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_cert2"
    And I should not see "cat0_cert1"
    And I should not see "cat0_cert3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_cert2"
    And I should not see "cat1_cert1"
    And I should not see "cat1_cert3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_cert2"
    And I should not see "cat2_cert1"
    And I should not see "cat2_cert3"

    When I set the field "coursesearchbox" to "cat0_cert3"
    And I press "Go"
    Then I should see "No programs were found with the words 'cat0_cert3'"
    When I set the field "coursesearchbox" to "cat0_cert2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat0_cert2" in the ".course-search-result" "css_element"
    And I log out

     # Seeing the certifications catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Certifications" in the totara menu
    Then I should see "top" in the ".subcategories" "css_element"
    And I should see "cat1" in the ".subcategories" "css_element"

    When I click to expand category "top" in the course catalog
    Then I should see "cat0_cert2"
    And I should not see "cat0_cert1"
    And I should not see "cat0_cert3"

    When I click to expand category "cat1" in the course catalog
    Then I should see "cat1_cert2"
    And I should see "cat1_cert1"
    And I should see "cat1_cert3"

    When I click to expand category "cat2" in the course catalog
    Then I should see "cat2_cert2"
    And I should see "cat2_cert1"
    And I should see "cat2_cert3"

    When I set the field "coursesearchbox" to "cat0_cert3"
    And I press "Go"
    Then I should see "No programs were found with the words 'cat0_cert3'"
    When I set the field "coursesearchbox" to "cat1_cert1"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_cert1" in the ".course-search-result" "css_element"
    When I set the field "coursesearchbox" to "cat1_cert2"
    And I click on "Go" "link_or_button" in the "#coursesearch2" "css_element"
    Then I should see "cat1_cert2" in the ".course-search-result" "css_element"

  @javascript
  Scenario: Search for hidden certifications in the enhanced catalog
# Configure visibility to normal vis.
    Given I set the following administration settings values:
      | audiencevisibility | 0        |
      | catalogtype        | enhanced |
    And I log out

# Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Certifications" in the totara menu
    Then I should see "cat0_cert1"
    And I should see "cat0_cert2"
    And I should see "cat0_cert3"
    And I should see "cat1_cert1"
    And I should see "cat1_cert2"
    And I should see "cat1_cert3"
    And I should see "cat2_cert1"
    And I should see "cat2_cert2"
    And I should see "cat2_cert3"

    When I set the following fields to these values:
      | Search by | cat0_cert3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert3" in the "#catalogcertifications" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_cert2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert2" in the "#catalogcertifications" "css_element"
    And I log out

# Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Certifications" in the totara menu
    Then I should see "cat0_cert2"
    And I should see "cat1_cert2"
    And I should see "cat2_cert2"
    And I should not see "cat0_cert1"
    And I should not see "cat0_cert3"
    And I should not see "cat1_cert1"
    And I should not see "cat1_cert3"
    And I should not see "cat2_cert1"
    And I should not see "cat2_cert3"

    When I set the following fields to these values:
      | Search by | cat0_cert3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_cert2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert2" in the "#catalogcertifications" "css_element"
    And I log out

# Seeing the enhanced catalog as catmanager.
    When I log in as "catmanager"
    And I click on "Certifications" in the totara menu
    Then I should see "cat0_cert2"
    And I should see "cat1_cert1"
    And I should see "cat1_cert2"
    And I should see "cat1_cert3"
    And I should see "cat2_cert1"
    And I should see "cat2_cert2"
    And I should see "cat2_cert3"
    And I should not see "cat0_cert1"
    And I should not see "cat0_cert3"

    When I set the following fields to these values:
      | Search by | cat0_cert3 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat1_cert1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_cert1" in the "#catalogcertifications" "css_element"
    When I set the following fields to these values:
      | Search by | cat1_cert2 |
    And I press "toolbarsearchbutton"
    Then I should see "cat1_cert2" in the "#catalogcertifications" "css_element"
    And I log out

  @javascript
  Scenario: Search for hidden programs in the enhanced catalog with audience visibility on
    # Configure visibility audience visibility.
    Given I set the following administration settings values:
      | audiencevisibility | 1        |
      | catalogtype        | enhanced |
    And I log out

    # Seeing the enhanced catalog as sys manager.
    When I log in as "sysmanager"
    And I click on "Certifications" in the totara menu
    Then I should see "cat0_cert1"
    And I should see "cat0_cert2"
    And I should see "cat0_cert3"
    And I should see "cat1_cert1"
    And I should see "cat1_cert2"
    And I should see "cat1_cert3"
    And I should see "cat2_cert1"
    And I should see "cat2_cert2"
    And I should see "cat2_cert3"

    When I set the following fields to these values:
      | Search by | cat0_cert3 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert3" in the "#catalogcertifications" "css_element"
    When I set the following fields to these values:
      | Search by | cat0_cert1 |
    And I press "toolbarsearchbutton"
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert1" in the "#catalogcertifications" "css_element"
    And I log out

    # Seeing the enhanced catalog as user1.
    When I log in as "user1"
    And I click on "Certifications" in the totara menu
    Then I should see "cat0_cert1"
    And I should see "cat1_cert1"
    And I should see "cat2_cert1"
    And I should not see "cat0_cert2"
    And I should not see "cat0_cert3"
    And I should not see "cat1_cert2"
    And I should not see "cat1_cert3"
    And I should not see "cat2_cert2"
    And I should not see "cat2_cert3"

    When I set the following fields to these values:
      | Search by | cat0_cert2 |
    And I press "toolbarsearchbutton"
    Then I should see "There are no records that match your selected criteria"
    When I set the following fields to these values:
      | Search by | cat0_cert1 |
    And I press "toolbarsearchbutton"
    Then I should see "cat0_cert1" in the "#catalogcertifications" "css_element"
    And I log out
