# wordpress-cookieconsent-loader
A Wordpress plugin to load CookieConsent by Orest Bida.

## Introduction
This plugin is designed to make it very easy to integrate [CookieConsent by Orest Bida](https://cookieconsent.orestbida.com/) into a Wordpress website.

## Download Plugin ZIP
- Click the **cookieconsent-loader.zip** file above, or navigate to [cookieconsent-loader.zip](https://github.com/codewithzac/wordpress-cookieconsent-loader/blob/main/cookieconsent-loader.zip)
- Click the download button at the top-right (next to the pencil icon)

## Installation
1. Go to your Wordpress site, log in as Admin
2. Go to the **Plugins** > **Add New Plugin** section
3. Click the **Upload Plugin** button
4. Select the `cookieconsent-loader.zip` file per above

> [!CAUTION]
> If you are using this process to upgrade from an earlier version, you need to make a copy of your configuration files and take note of your settings _before_ you upgrade! The upgrade process resets your configuration to defaults.

## Configuration
There are three steps to configure the plugin. In Wordpress Admin, go to **Settings** > **CookieConsent**, then..:

### 1. Download CookieConsent files
This plugin is distributed *without* the CookieConsent JS and CSS files. Select the version you want to use from the dropdown, and click the **Update to Selected Release** button. This downloads the `cookieconsent.umd.js` and `cookieconsent.css` files for the selected release from Github, and serves them from your webserver.

> [!WARNING]
> This plugin may not play well with optimisation plugins, such as [Autoptimize](https://wordpress.org/plugins/autoptimize/). You may wish to exclude all files beginning with `cookieconsent.` from any optimisations if you run into problems.

### 2. Choose Display options
Choose who sees the CookieConsent banner. By default, the banner is displayed to all users. While you are refining what the banner looks like, you may want to limit it to specific (logged in) users. Once ready to publish, you can set this back to all users. After making a selection, click the **Save Display Settings** button.

### 3. Create Configuration files
A boilerplate Configuration JS file is included to get you up and running quickly, however this file has to be saved at least once before it will load. There are a huge number of [configuration options](https://cookieconsent.orestbida.com/reference/configuration-reference.html) available; you should try them out to see what works for you!

If you want to reset this back to defaults, simply delete the `cookieconsent.config.js` file from the plugin assets folder.

The Custom CSS box can be left empty, *unless* you want to customise the colours etc.. You can find examples of custom CSS in the [CookieConsent Playground](https://playground.cookieconsent.orestbida.com/) - scroll to the Themes section.

### 4. Optional integrations
This plugin optionally integrates with Google Tag Manager and the WP Consent API. There are additional recommended plugins to enable these features in Wordpress:
* [GTM4WP](https://gtm4wp.com/)
* [WP Consent API](https://wpconsentapi.org/)

To integrate the consent banner with these services, the boilerplate configuration contains two callback functions that trigger: a) when the user first saves their consent preferences, and b) on every consent change.

If you are using Google Tag Manager to load analytics and marketing pixels, there are two `dataLayer.push()` commands in these callback functions that will help with integrating consent signals in your GTM container.

If you are using the WP Consent API to send consent signals to WP Consent API-aware Wordpress plugins (among others: Google Site Kit, WooCommerce, WP Statistics), there are two `updateWPConsent()` commands that will help with integrating consent signals in Wordpress. Note that this integration does *not* enable bi-directional sync of consent signals; the plugin assumes it is the only thing setting consent. 
