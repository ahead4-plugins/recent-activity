# recent-activity
A plugin to show most recently active posts

This wordpress plugin handles the need to show posts, pages or custom post types by most recent activity.

By this we mean that it lists the posts according to the most recent update be that publishing or comment. This is similar to the way that facebook works

The plugin itself may be used either via a shortcode or via the class that builds the list.

The shortcode is [recent_activity] and will take additional parameters for example [recent_activity title="<h2>The recent activity" post_type="page" number="5" before_list="<ul>" after_list="</ul>" before_item="<li>" after_item="</li>"]

The post type can be any registered public post type on the system which makes it suitable for working with custom post types.
