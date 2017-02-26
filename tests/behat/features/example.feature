Feature: Example

    Background:
        Given I log in as an admin

    Scenario: Create and publish a blog post
        When I go to "/wp-admin/post-new.php"
        And I fill in "post_title" with a random string
        And I press "publish"
        Then print current URL
        And I should see "Post published."

        When I go to "/blog/"
        And I should see the random string
