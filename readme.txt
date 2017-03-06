=== Easy Digital Downloads Github ===
Contributors: nwoetzel
Tags: edd, easy digital downloads, github
Requires at least: 4.6
Tested up to: 4.7.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This pluging integrates github release files as downloads.

== Description ==

This plugin requires that you have installed:
* [Easy digital downloads](https://wordpress.org/plugins/easy-digital-downloads/) - tested for version 2.7.4

It adds meta settings to a download where the github user, repo and accesstoken need to be added.
From the latest release, all asset files are retrieved and provided as downloads.

== Installation ==

Download the latest release from github as zip and install it through wordpress.
Or use [wp-cli](http://wp-cli.org/) with the latest release:
<pre>
wp-cli.phar plugin install https://github.com/nwoetzel/edd-github/archive/1.0.1.zip --activate
</pre>

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 1.0.1 =
* cache github release api queries in transient
* display the version of the latest release in the downloads metabox

= 1.0.0 =
* Initial release
