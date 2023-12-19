# LinkedEvents integration plugin

This plugin offers an abstraction to LinkedEvents API.

## Installation

This plugin will setup virtual pages for stores. Content for single store pages are loaded from the LinkedEvents API.

Plugin will create an url rewrite:
´/event/abc:123/eventname´

And we will rewrite this to the page called `LinkedEvents`. That page then loads the content from the API.

NOTE! You need to manually create the page called `LinkedEvents` AND set it to use the event template to get this working.

## Settings

Available at Dashboard > Linked events.

- Insert 'TPREK_ID' to limit the api query to certain location.
- Check 'Rewrite WP titles', if wp_title should be filtered to include event title

## Contributors
Juha Lehtonen, Jaakko Alajoki

## License
GPLv2 or later License URI: [http://www.gnu.org/licenses/gpl-2.0.html](http://www.gnu.org/licenses/gpl-2.0.html)
