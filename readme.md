# Simple Facebook Instant Articles

Add support for Facebook Instant Articles to your WordPress site. This plugin creates a new articles endpoint, and a feed to give to Facebook with links to those articles.

## Installation

1. Upload `simple-fb-instant-articles` to the `/wp-content/plugins/` directory
2. Or, use the installer in plugins menu.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Setup the plugin with Facebook. More info can be found [here](https://developers.facebook.com/docs/instant-articles/publishing).
5. As part of the installation process with Facebook, a URL to the feed is provided. If you are using pretty permalinks, you can find the RSS feed at `domain.com/feed/fb`.
5. If you want to test the layout of the articles, you can do by appending `/fb-instant` to the end of single posts.

## Changelog

### 0.5.3

* Fixed an issue that led to filters on the `simple_fb_posts_per_rss` not working.
* Better README for Github. (You are reading it right now. [How meta...])

### 0.5.2
* MOAR filters.
* Code styling fixes.

### 0.5.1
* Adding upstream changes from the Human Made team, props dashaluna, mattheu, jetlej, and AramZS.
* Extended the HumanMade branch into a universally usable plugin, maintaining backwards compatibility with previous versions. [#39](https://github.com/whyisjake/Simple-Instant-Articles-for-Facebook/pull/39) - props [AramZS](https://github.com/AramZS)
*Allow for endpoint on article links to be used for query vars or not at all enhancement [#37](https://github.com/whyisjake/Simple-Instant-Articles-for-Facebook/pull/39) - props [AramZS](https://github.com/AramZS)
* Updated format for kicker [#35](https://github.com/whyisjake/Simple-Instant-Articles-for-Facebook/pull/35) - props [jetlej](https://profiles.wordpress.org/jetlej)
* Better README [#41](https://github.com/whyisjake/Simple-Instant-Articles-for-Facebook/pull/41)
* Add the reaction stuff to images. [#40](https://github.com/whyisjake/Simple-Instant-Articles-for-Facebook/pull/40)

### 0.5.0
* Initial Release
