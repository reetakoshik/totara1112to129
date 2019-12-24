@totara @totara_coursecatalog
Feature: Check that granting view hidden capabilities allows users to see hidden learning

  @javascript
  Scenario: Check that granting view hidden capabilities allows users to see hidden learning
    Given I am on a totara site
    And I log in as "admin"
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | first1    | last1    | manager1@example.com |

    # Create category hierarchy.
    And the following "categories" exist:
      | name | category | idnumber |
      | cat1 | 0        | cat1     |
      | cat2 | cat1     | cat2     |

    # Create courses (set both types of visibility to ensure normal doesn't interfere).
    And the following "courses" exist:
      | fullname     | shortname    | category | visible | audiencevisible |
      | cat0_course1 | cat0_course1 | 0        | 0       | 2               |
      | cat0_course2 | cat0_course2 | 0        | 1       | 0               |
      | cat0_course3 | cat0_course3 | 0        | 0       | 3               |
      | cat1_course1 | cat1_course1 | cat1     | 0       | 2               |
      | cat1_course2 | cat1_course2 | cat1     | 1       | 0               |
      | cat1_course3 | cat1_course3 | cat1     | 0       | 3               |
      | cat2_course1 | cat2_course1 | cat2     | 0       | 2               |
      | cat2_course2 | cat2_course2 | cat2     | 1       | 0               |
      | cat2_course3 | cat2_course3 | cat2     | 0       | 3               |
    # 2 = COHORT_VISIBLE_ALL, 0 = COHORT_VISIBLE_ENROLLED, 3 = COHORT_VISIBLE_NOUSERS.

    # Create programs (set both types of visibility to ensure normal doesn't interfere).
    And the following "programs" exist in "totara_program" plugin:
      | fullname      | shortname     | category | visible | audiencevisible |
      | cat0_program1 | cat0_program1 | 0        | 0       | 2               |
      | cat0_program2 | cat0_program2 | 0        | 1       | 0               |
      | cat0_program3 | cat0_program3 | 0        | 0       | 3               |
      | cat1_program1 | cat1_program1 | cat1     | 0       | 2               |
      | cat1_program2 | cat1_program2 | cat1     | 1       | 0               |
      | cat1_program3 | cat1_program3 | cat1     | 0       | 3               |
      | cat2_program1 | cat2_program1 | cat2     | 0       | 2               |
      | cat2_program2 | cat2_program2 | cat2     | 1       | 0               |
      | cat2_program3 | cat2_program3 | cat2     | 0       | 3               |

    # Create certs (set both types of visibility to ensure normal doesn't interfere).
    And the following "certifications" exist in "totara_program" plugin:
      | fullname   | shortname  | category | visible | audiencevisible |
      | cat0_cert1 | cat0_cert1 | 1        | 0       | 2               |
      | cat0_cert2 | cat0_cert2 | 1        | 1       | 0               |
      | cat0_cert3 | cat0_cert3 | 1        | 0       | 3               |
      | cat1_cert1 | cat1_cert1 | 2        | 0       | 2               |
      | cat1_cert2 | cat1_cert2 | 2        | 1       | 0               |
      | cat1_cert3 | cat1_cert3 | 2        | 0       | 3               |
      | cat2_cert1 | cat2_cert1 | 3        | 0       | 2               |
      | cat2_cert2 | cat2_cert2 | 3        | 1       | 0               |
      | cat2_cert3 | cat2_cert3 | 3        | 0       | 3               |

    # Create new roles.
    And the following "roles" exist:
      | name                  | shortname             | archetype | contextlevel |
      | capmanagecategories   | capmanagecategories   |           | System       |
      | capviewhiddenlearning | capviewhiddenlearning |           | Category     |

    And the following "permission overrides" exist:
      | capability                               | permission | role                | contextlevel | reference |
      | moodle/category:manage                   | Allow      | capmanagecategories | System       |           |
      | totara/program:createprogram             | Allow      | capmanagecategories | System       |           |
      | totara/certification:createcertification | Allow      | capmanagecategories | System       |           |

    # Note that this isn't just applying these capabilities to the role, it's applying to the role IN THE SPECIFIED CONTEXT.
    And the following "permission overrides" exist:
      | capability                                    | permission | role                  | contextlevel | reference |
      | moodle/course:viewhiddencourses               | Allow      | capviewhiddenlearning | Category     | cat1      |
      | totara/program:viewhiddenprograms             | Allow      | capviewhiddenlearning | Category     | cat1      |
      | totara/certification:viewhiddencertifications | Allow      | capviewhiddenlearning | Category     | cat1      |

    # Assign manage categories capability, so manager1 can see the site admin menu item and categories.
    And the following "system role assigns" exist:
      | user     | role                |
      | manager1 | capmanagecategories |

    # Configure visibility to normal vis.
    And I set the following administration settings values:
      | audiencevisibility | 0 |

        # Log in as manager1 and check that the hidden items cannot be seen.
    Then I log out
    And I log in as "manager1"
    And I navigate to "Manage courses and categories" node in "Site administration > Courses"
    Then I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
    And I should see "Miscellaneous" in the ".course-listing" "css_element"
    And I should not see "cat0_course1" in the ".course-listing" "css_element"
    And I should see "cat0_course2" in the ".course-listing" "css_element"
    And I should not see "cat0_course3" in the ".course-listing" "css_element"
    Then I click on "cat1" "text" in the ".category-listing" "css_element"
    And I should see "cat1" in the ".course-listing" "css_element"
    And I should not see "cat1_course1" in the ".course-listing" "css_element"
    And I should see "cat1_course2" in the ".course-listing" "css_element"
    And I should not see "cat1_course3" in the ".course-listing" "css_element"
    Then I click on "cat2" "text" in the ".category-listing" "css_element"
    And I should see "cat2" in the ".course-listing" "css_element"
    And I should not see "cat2_course1" in the ".course-listing" "css_element"
    And I should see "cat2_course2" in the ".course-listing" "css_element"
    And I should not see "cat2_course3" in the ".course-listing" "css_element"
    And I press "Manage programs in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should not see "cat0_program1" in the "#moveprograms" "css_element"
    And I should see "cat0_program2" in the "#moveprograms" "css_element"
    And I should not see "cat0_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should not see "cat1_program1" in the "#moveprograms" "css_element"
    And I should see "cat1_program2" in the "#moveprograms" "css_element"
    And I should not see "cat1_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should not see "cat2_program1" in the "#moveprograms" "css_element"
    And I should see "cat2_program2" in the "#moveprograms" "css_element"
    And I should not see "cat2_program3" in the "#moveprograms" "css_element"
    And I press "Manage certifications in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should not see "cat0_cert1" in the "#moveprograms" "css_element"
    And I should see "cat0_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should not see "cat1_cert1" in the "#moveprograms" "css_element"
    And I should see "cat1_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat1_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should not see "cat2_cert1" in the "#moveprograms" "css_element"
    And I should see "cat2_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat2_cert3" in the "#moveprograms" "css_element"

        # Configure visibility to audience based vis.
    Then I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | audiencevisibility | 1 |

        # Log in as manager1 and check that the hidden items cannot be seen.
    Then I log out
    And I log in as "manager1"
    And I navigate to "Manage courses and categories" node in "Site administration > Courses"
    Then I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
    And I should see "Miscellaneous" in the ".course-listing" "css_element"
    And I should see "cat0_course1" in the ".course-listing" "css_element"
    And I should not see "cat0_course2" in the ".course-listing" "css_element"
    And I should not see "cat0_course3" in the ".course-listing" "css_element"
    Then I click on "cat1" "text" in the ".category-listing" "css_element"
    And I should see "cat1" in the ".course-listing" "css_element"
    And I should see "cat1_course1" in the ".course-listing" "css_element"
    And I should not see "cat1_course2" in the ".course-listing" "css_element"
    And I should not see "cat1_course3" in the ".course-listing" "css_element"
    Then I click on "cat2" "text" in the ".category-listing" "css_element"
    And I should see "cat2" in the ".course-listing" "css_element"
    And I should see "cat2_course1" in the ".course-listing" "css_element"
    And I should not see "cat2_course2" in the ".course-listing" "css_element"
    And I should not see "cat2_course3" in the ".course-listing" "css_element"
    And I press "Manage programs in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should see "cat0_program1" in the "#moveprograms" "css_element"
    And I should not see "cat0_program2" in the "#moveprograms" "css_element"
    And I should not see "cat0_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_program1" in the "#moveprograms" "css_element"
    And I should not see "cat1_program2" in the "#moveprograms" "css_element"
    And I should not see "cat1_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_program1" in the "#moveprograms" "css_element"
    And I should not see "cat2_program2" in the "#moveprograms" "css_element"
    And I should not see "cat2_program3" in the "#moveprograms" "css_element"
    And I press "Manage certifications in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should see "cat0_cert1" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_cert1" in the "#moveprograms" "css_element"
    And I should not see "cat1_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat1_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_cert1" in the "#moveprograms" "css_element"
    And I should not see "cat2_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat2_cert3" in the "#moveprograms" "css_element"

    # Configure visibility to normal vis.
    Then I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | audiencevisibility | 0 |

    # Assign manager1 to the capviewhiddenlearning role in the cat1 context.
    And the following "role assigns" exist:
      | user     | role                  | contextlevel | reference |
      | manager1 | capviewhiddenlearning | Category     | cat1      |

    # Log in as manager1 and check that the manager can see everything under cat1 and cat2.
    Then I log out
    And I log in as "manager1"
    And I navigate to "Manage courses and categories" node in "Site administration > Courses"
    Then I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
    And I should see "Miscellaneous" in the ".course-listing" "css_element"
    And I should not see "cat0_course1" in the ".course-listing" "css_element"
    And I should see "cat0_course2" in the ".course-listing" "css_element"
    And I should not see "cat0_course3" in the ".course-listing" "css_element"
    Then I click on "cat1" "text" in the ".category-listing" "css_element"
    And I should see "cat1" in the ".course-listing" "css_element"
    And I should see "cat1_course1" in the ".course-listing" "css_element"
    And I should see "cat1_course2" in the ".course-listing" "css_element"
    And I should see "cat1_course3" in the ".course-listing" "css_element"
    Then I click on "cat2" "text" in the ".category-listing" "css_element"
    And I should see "cat2" in the ".course-listing" "css_element"
    And I should see "cat2_course1" in the ".course-listing" "css_element"
    And I should see "cat2_course2" in the ".course-listing" "css_element"
    And I should see "cat2_course3" in the ".course-listing" "css_element"
    And I press "Manage programs in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should not see "cat0_program1" in the "#moveprograms" "css_element"
    And I should see "cat0_program2" in the "#moveprograms" "css_element"
    And I should not see "cat0_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_program1" in the "#moveprograms" "css_element"
    And I should see "cat1_program2" in the "#moveprograms" "css_element"
    And I should see "cat1_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_program1" in the "#moveprograms" "css_element"
    And I should see "cat2_program2" in the "#moveprograms" "css_element"
    And I should see "cat2_program3" in the "#moveprograms" "css_element"
    And I press "Manage certifications in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should not see "cat0_cert1" in the "#moveprograms" "css_element"
    And I should see "cat0_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_cert1" in the "#moveprograms" "css_element"
    And I should see "cat1_cert2" in the "#moveprograms" "css_element"
    And I should see "cat1_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_cert1" in the "#moveprograms" "css_element"
    And I should see "cat2_cert2" in the "#moveprograms" "css_element"
    And I should see "cat2_cert3" in the "#moveprograms" "css_element"

    # Configure visibility to audience based vis.
    Then I log out
    And I log in as "admin"
    And I set the following administration settings values:
      | audiencevisibility | 1 |

    # Log in as manager1 and check that the manager can see everything under cat1 and cat2.
    Then I log out
    And I log in as "manager1"
    And I navigate to "Manage courses and categories" node in "Site administration > Courses"
    Then I click on "Miscellaneous" "text" in the ".category-listing" "css_element"
    And I should see "Miscellaneous" in the ".course-listing" "css_element"
    And I should see "cat0_course1" in the ".course-listing" "css_element"
    And I should not see "cat0_course2" in the ".course-listing" "css_element"
    And I should not see "cat0_course3" in the ".course-listing" "css_element"
    Then I click on "cat1" "text" in the ".category-listing" "css_element"
    And I should see "cat1" in the ".course-listing" "css_element"
    And I should see "cat1_course1" in the ".course-listing" "css_element"
    And I should see "cat1_course2" in the ".course-listing" "css_element"
    And I should see "cat1_course3" in the ".course-listing" "css_element"
    Then I click on "cat2" "text" in the ".category-listing" "css_element"
    And I should see "cat2" in the ".course-listing" "css_element"
    And I should see "cat2_course1" in the ".course-listing" "css_element"
    And I should see "cat2_course2" in the ".course-listing" "css_element"
    And I should see "cat2_course3" in the ".course-listing" "css_element"
    And I press "Manage programs in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should see "cat0_program1" in the "#moveprograms" "css_element"
    And I should not see "cat0_program2" in the "#moveprograms" "css_element"
    And I should not see "cat0_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_program1" in the "#moveprograms" "css_element"
    And I should see "cat1_program2" in the "#moveprograms" "css_element"
    And I should see "cat1_program3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_program1" in the "#moveprograms" "css_element"
    And I should see "cat2_program2" in the "#moveprograms" "css_element"
    And I should see "cat2_program3" in the "#moveprograms" "css_element"
    And I press "Manage certifications in this category"
    Then I set the field "categoryid" to "Miscellaneous"
    And I should see "cat0_cert1" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert2" in the "#moveprograms" "css_element"
    And I should not see "cat0_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1"
    And I should see "cat1_cert1" in the "#moveprograms" "css_element"
    And I should see "cat1_cert2" in the "#moveprograms" "css_element"
    And I should see "cat1_cert3" in the "#moveprograms" "css_element"
    Then I set the field "categoryid" to "cat1 / cat2"
    And I should see "cat2_cert1" in the "#moveprograms" "css_element"
    And I should see "cat2_cert2" in the "#moveprograms" "css_element"
    And I should see "cat2_cert3" in the "#moveprograms" "css_element"
