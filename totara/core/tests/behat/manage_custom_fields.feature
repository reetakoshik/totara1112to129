@javascript @totara @totara_core @totara_customfield
Feature: Test new custom field capabilities
  In order to check that new manage custom fields capabilities
  working correctly
  As admin and users with different roles
  I need to assign these capabilities and check that they work
  as designed

  Background:
    Given I am on a totara site
      # Create roles with right access
    And the following "roles" exist:
      | name    | shortname | archetype |
      | Face    | face      | manager   |
      | Course  | course    | manager   |
      | Prog    | prog      | manager   |
      | Comp    | comp      | manager   |
      | Goal    | goal      | manager   |
      | Pos     | pos       | manager   |
      | Org     | org       | manager   |
      | Posface | posface   | manager   |

    # Capabilities for roles are explicitly set below (grouped by capability for readability)
    # Role "manager" have all capabilities but modconfig (that required by seminar).
    And the following "permission overrides" exist:
      | capability                       | permission | role    | contextlevel | reference |
      | totara/core:modconfig            | Allow      | manager | System       |           |
      | totara/core:modconfig            | Allow      | face    | System       |           |
      | totara/core:modconfig            | Allow      | posface | System       |           |

    # Seminar
    And the following "permission overrides" exist:
      | capability                       | permission | role    | contextlevel | reference |
      | mod/facetoface:managecustomfield | Allow      | face    | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | course  | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | prog    | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | comp    | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | goal    | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | org     | System       |           |
      | mod/facetoface:managecustomfield | Prevent    | pos     | System       |           |
      | mod/facetoface:managecustomfield | Allow      | posface | System       |           |

    # Courses
    And the following "permission overrides" exist:
      | capability                          | permission | role    | contextlevel | reference |
      | totara/core:coursemanagecustomfield | Prevent    | face    | System       |           |
      | totara/core:coursemanagecustomfield | Allow      | course  | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | prog    | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | comp    | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | goal    | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | org     | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | pos     | System       |           |
      | totara/core:coursemanagecustomfield | Prevent    | posface | System       |           |

    # Programs/Certifications
    And the following "permission overrides" exist:
      | capability                           | permission | role    | contextlevel | reference |
      | totara/core:programmanagecustomfield | Prevent    | face    | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | course  | System       |           |
      | totara/core:programmanagecustomfield | Allow      | prog    | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | comp    | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | goal    | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | org     | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | pos     | System       |           |
      | totara/core:programmanagecustomfield | Prevent    | posface | System       |           |

    # Competencies
    And the following "permission overrides" exist:
      | capability                                   | permission | role    | contextlevel | reference |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | face    | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | course  | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | prog    | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Allow      | comp    | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | goal    | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | org     | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | pos     | System       |           |
      | totara/hierarchy:competencymanagecustomfield | Prevent    | posface | System       |           |

    # Goals
    And the following "permission overrides" exist:
      | capability                             | permission | role    | contextlevel | reference |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | face    | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | course  | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | prog    | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | comp    | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Allow      | goal    | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | org     | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | pos     | System       |           |
      | totara/hierarchy:goalmanagecustomfield | Prevent    | posface | System       |           |

    # Organisations
    And the following "permission overrides" exist:
      | capability                                     | permission | role    | contextlevel | reference |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | face    | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | course  | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | prog    | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | comp    | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | goal    | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Allow      | org     | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | pos     | System       |           |
      | totara/hierarchy:organisationmanagecustomfield | Prevent    | posface | System       |           |

    # Positions
    And the following "permission overrides" exist:
      | capability                                 | permission | role    | contextlevel | reference |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | face    | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | course  | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | prog    | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | comp    | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | goal    | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Prevent    | org     | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Allow      | pos     | System       |           |
      | totara/hierarchy:positionmanagecustomfield | Allow      | posface | System       |           |

    And the following "users" exist:
      | username    |
      | learneruser |
      | sitemanuser |
      | faceuser    |
      | courseuser  |
      | proguser    |
      | compuser    |
      | goaluser    |
      | orguser     |
      | posuser     |
      | posfaceuser |

    And the following "system role assigns" exist:
      | user        | role    |
      | sitemanuser | manager |
      | faceuser    | face    |
      | courseuser  | course  |
      | proguser    | prog    |
      | compuser    | comp    |
      | goaluser    | goal    |
      | orguser     | org     |
      | posuser     | pos     |
      | posfaceuser | posface |

    And I log in as "admin"
    # Create testing Hierarchy types
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | Competency Test Type |
    And I press "Save changes"
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I press "Add a new company goal type"
    And I set the following fields to these values:
      | Type full name | Goal Test Type |
    And I press "Save changes"
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | Organisations Test Type |
    And I press "Save changes"
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I press "Add a new type"
    And I set the following fields to these values:
      | Type full name | Positions Test Type |
    And I press "Save changes"
    And I log out

  Scenario: Check each role that they has access only relevant to their capabilities

    # Check all access of Site Manager
    When I log in as "sitemanuser"
    # Seminar: allowed
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I should see "Create a new custom field"
    # Course: allowed
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I should see "Create a new custom field"
    # Program: allowed
    And I follow "Programs / Certifications"
    And I should see "Create a new custom field"
    # Competency: allowed
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should see "Competency Test Type" in the "td a" "css_element"
    And I follow "Competency Test Type"
    And I should see "Create a new custom field"
    # Goals: allowed
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type" in the "td a" "css_element"
    And I follow "Goal Test Type"
    And I should see "Create a new custom field"
    # Organisation: allowed
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type" in the "td a" "css_element"
    And I follow "Organisations Test Type"
    And I should see "Create a new custom field"
    # Positions: allowed
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type" in the "td a" "css_element"
    And I follow "Positions Test Type"
    And I should see "Create a new custom field"
    And I log out

    # Check seminar managecustomfield access
    When I log in as "faceuser"
    # Seminar: allowed
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I should see "Create a new custom field"
    # Avoid seeing Custom fields in the seminar section
    And I click on "Seminar" "text" in the "Administration" "block"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should see "Competency Test Type"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check course managecustomfield access
    When I log in as "courseuser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: allowed
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I should see "Create a new custom field"
    # Program: prevented
    And I should not see "Programs / Certifications"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should see "Competency Test Type"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check program managecustomfield access
    When I log in as "proguser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: prevented
    And I navigate to "Custom fields" node in "Site administration > Courses"
    And I should not see "Create a new custom field"
    # Program: allowed
    And I follow "Programs / Certifications"
    And I should see "Create a new custom field"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should see "Competency Test Type"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check competency managecustomfield access
    When I log in as "compuser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: allowed
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should see "Competency Test Type" in the "td a" "css_element"
    And I follow "Competency Test Type"
    And I should see "Create a new custom field"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check goal managecustomfield access
    When I log in as "goaluser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: allowed
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type" in the "td a" "css_element"
    And I follow "Goal Test Type"
    And I should see "Create a new custom field"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check organisation managecustomfield access
    When I log in as "orguser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: allowed
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should see "Organisations Test Type" in the "td a" "css_element"
    And I follow "Organisations Test Type"
    And I should see "Create a new custom field"
    # Positions: prevented
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type"
    And I should not see "Positions Test Type" in the "td a" "css_element"
    And I log out

    # Check position managecustomfield access
    When I log in as "posuser"
    # Seminar: prevented
    And I expand "Site administration" node
    And I expand "Plugins" node
    And I should not see "Activity modules"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: allowed
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type" in the "td a" "css_element"
    And I follow "Positions Test Type"
    And I should see "Create a new custom field"
    And I log out

    # Check position and seminar managecustomfield access
    When I log in as "posfaceuser"
    # Seminar: allowed
    And I navigate to "Custom fields" node in "Site administration > Seminars"
    And I should see "Create a new custom field"
    # Avoid seeing Custom fields in the seminar section
    And I click on "Seminar" "text" in the "Administration" "block"
    # Course: prevented & Program: prevented
    And I click on "Courses" "text" in the "Administration" "block"
    Then I should not see "Custom fields" in the "Administration" "block"
    # Competency: prevented
    And I navigate to "Manage types" node in "Site administration > Competencies"
    And I should not see "Competency Test Type" in the "td a" "css_element"
    # Goals: prevented
    And I navigate to "Manage company goal types" node in "Site administration > Goals"
    And I should see "Goal Test Type"
    And I should not see "Goal Test Type" in the "td a" "css_element"
    # Organisation: prevented
    And I navigate to "Manage types" node in "Site administration > Organisations"
    And I should not see "Organisations Test Type" in the "td a" "css_element"
    # Positions: allowed
    And I navigate to "Manage types" node in "Site administration > Positions"
    And I should see "Positions Test Type" in the "td a" "css_element"
    And I follow "Positions Test Type"
    And I should see "Create a new custom field"
    And I log out
