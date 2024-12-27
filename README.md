# wordpress-cookieconsent-loader
A Wordpress plugin to load CookieConsent by Orest Bida.

## Introduction
This plugin is designed to make it very easy to integrate [CookieConsent by Orest Bida](https://cookieconsent.orestbida.com/) into a Wordpress website.

## Download Plugin ZIP
Either:
- Click the **<> Code** button above, select **Download ZIP**, or
- Go to the latest release in **Releases** on the right, then download the latest **Source code (zip)**

Extract the ZIP file, then find the `cookieconsent-loader.zip` file within for installing the plugin.

## Installation
1. Go to your Wordpress site, log in as Admin
2. Go to the **Plugins** > **Add New Plugin** section
3. Click the **Upload Plugin** button
4. Select the `cookieconsent-loader.zip` file per above

## Configuration
There are three steps to configure the plugin. In Wordpress Admin, go to **Settings** > **CookieConsent**, then..:

### 1. Download CookieConsent files
This plugin is distributed *without* the CookieConsent JS and CSS files. Select the version you want to use from the dropdown, and click the **Update to Selected Release** button. This downloads the `cookieconsent.umd.js` and `cookieconsent.css` files for the selected release from Github, and serves them from your webserver.

> [!WARNING]
> This plugin may not play well with optimisation plugins, such as [Autoptimize](https://wordpress.org/plugins/autoptimize/). You may wish to exclude all files beginning with `cookieconsent.` from any optimisations if you run into problems.

### 2. Choose Display options
Choose who sees the CookieConsent banner. By default, the banner is displayed to all users. While you are refining what the banner looks like, you may want to limit it to specific (logged in) users. Once ready to publish, you can set this back to all users. After making a selection, click the **Save Display Settings** button.

### 3. Create Configuration files
A boilerplate Configuration JS file is included to get you up and running quickly, however this file has to be saved at least once before it will load. There are a huge number of [configuration options](https://cookieconsent.orestbida.com/reference/configuration-reference.html) available; you should try them out to see what works for you! If you are using Google Tag Manager to load analytics and marketing pixels, there are two `dataLayer.push()` commands in the boilerplate configuration that will help with integrating consent signals in your GTM container.

If you want to reset this back to defaults, simply delete the `cookieconsent.config.js` file from the plugin assets folder.

The Custom CSS box can be left empty, *unless* you want to customise the colours etc.. You can find examples of custom CSS in the [CookieConsent Playground](https://playground.cookieconsent.orestbida.com/) - scroll to the Themes section.
