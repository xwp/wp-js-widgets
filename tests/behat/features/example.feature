Feature: Example

    Background:
        Given I log in as an admin

    @javascript
    Scenario: Change the site title via the customizer
        When I go to "/wp-admin/customize.php"
        And I click on "#accordion-section-title_tagline" once it appears
        And I type "Hello--World" into "#customize-control-blogname input[type=text]" once it appears
        Then I should see "Hello--World" in the ".panel-title.site-title" element
        Then I wait "100" milliseconds
        And I should see "Hello--World" in the ".site-title" element inside the preview window
        Then I wait "500" milliseconds
        And I should see "Hello—World" in the ".site-title" element inside the preview window
        # TODO: Publish changes.