@totara @totara_plan
Feature: Learner creates a learning plan and adds an image to description

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | firstname1 | lastname1 | learner1@example.com |
      | manager2 | firstname2 | lastname2 | manager2@example.com |
    And the following job assignments exist:
      | user     | fullname       | manager  |
      | learner1 | jobassignment1 | manager2 |
    And the following "plans" exist in "totara_plan" plugin:
      | user     | name                   |
      | learner1 | learner1 Learning Plan |

  @javascript
  Scenario: Learner add image to plan description.
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "totara/plan/tests/fixtures/pic1.png" file to "Files" filemanager
    And I click on "Save changes" "button"

    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I click on "Image" "button" in the "#fitem_id_description_editor" "css_element"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link"
    And I click on "pic1.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "It's a picture"
    And I click on "Save image" "button"
    And I press "Create plan"
    And I should see "My Learning Plan"
    And "//img[contains(@src, 'pic1.png')]" "xpath_element" should exist
    And I should see image with alt text "It's a picture"

  @javascript
  Scenario: Learner adds image to Objective within a plan
    Given I log in as "learner1"
    And I click on "Dashboard" in the totara menu
    And I press "Customise this page"
    And I add the "Private files" block
    And I follow "Manage private files..."
    And I upload "totara/plan/tests/fixtures/pic1.png" file to "Files" filemanager
    And I press "Save changes"

    And I click on "Dashboard" in the totara menu
    And I click on "Learning Plans" "link"
    And I press "Create new learning plan"
    And I set the field "Plan name" to "My Learning Plan"
    And I press "Create plan"
    And I follow "Objectives"
    And I press "Add new objective"
    And I set the field "Objective Title" to "My test Objective"
    And I click on "Image" "button" in the "#fitem_id_description_editor" "css_element"
    And I click on "Browse repositories..." "button"
    And I click on "Private files" "link"
    And I click on "pic1.png" "link"
    And I click on "Select this file" "button"
    And I set the field "Describe this image for someone who cannot see it" to "It's a picture"
    And I click on "Save image" "button"
    And I press "Add objective"
    And I should see "My test Objective"
    And I follow "My test Objective"
    And "//img[contains(@src, 'pic1.png')]" "xpath_element" should exist
    And I should see image with alt text "It's a picture"
