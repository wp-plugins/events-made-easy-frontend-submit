=== Events Made Easy Frontend Submit ===
Contributors: liedekef
Donate link: http://www.e-dynamics.be/wordpress
Tags: events, frontend
Requires at least: 3.9
Tested up to: 4.2
Stable tag: 1.0.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple plugin that displays a form to allow people to enter events for the Events Made Easy plugin on a regular wordpress page.

== Description ==

A simple plugin that displays a form to allow people to enter events for the Events Made Easy plugin on a regular wordpress page (called "Frontend Submit").

Get The Events Made Easy plugin:
http://wordpress.org/extend/plugins/events-made-easy/

== Installation ==

1. Download the plugin archive and expand it
2. Upload the events-made-easy-frontend-submit folder to your /wp-content/plugins/ directory
3. Go to the plugins page and click 'Activate' for EME Frontend Submit
4. Navigate to the Settings section within Wordpress and change the settings appropriately.
5. Ensure the Events Made Easy plugin is installed and configured - http://wordpress.org/extend/plugins/events-made-easy/
6. Put the shortcode [emefs_submit_event_form] on a regular page to display the form


The plugin will look for form template and style files in the following paths, in that priority:

   1. ./wp-content/themes/your-current-theme/eme-frontend-submit/
   2. ./wp-content/themes/your-current-theme/events-made-easy-frontend-submit/
   3. ./wp-content/themes/your-current-theme/emefs/
   4. ./wp-content/themes/your-current-theme/events-made-easy/

The overloadable files at this moment are:

   1. form.php which controls the html form. The default version can be found in the templates subdir.
   2. style.css which controls the style loaded automatically by the plugin. The default version can be found in the templates subdir.

== Changelog ==

= 1.0.8 =
* Updated translation template file (emefs.pot)

= 1.0.7 =
* Bugfix: fix location autocomplete

= 1.0.6 =
* Bugfix: the event author was not being set correctly for logged in users

= 1.0.5 =
* Bugfix: fix javascript comment preventing it to work in emefs.js

= 1.0.4 =
* Bugfix: first day of the week needs to be an integer in the javascript code, otherwise the calendar day headers are mangled

= 1.0.3 =
* Feature: first day of week is now also respected in the datepicker
* Bugfix: fix the error "Unknown column 'localised-start-date' in 'field list'" by redoing a big part of the code

= 1.0.2 =
* Feature: added an option for the location to be always created, even if the user does not have the needed capability set in EME to create locations
* Feature: localise event date too
* Improvement: show map for new locations too
* Improvement: show entered data if the form has an error
* Bugfix: time entry fields shouldn't be read-only
* Bugfix: form.php wasn't localized correctly
* Bugfix: better 24h timeformat notation detection

= 1.0.1 =
* Bugfix: better EME dependency checks, also work for multisite now

= 1.0.0 =
Released as seperate wordpress plugin, using it's own WP settings (no config file anymore)
