=== Email Essentials ===
Contributors: acato
Donate link: https://acato.nl/donate
Tags: email, smtp, dkim, smime, debugging, history, html, css, outgoing, deliverability
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 6.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin to make WordPress outgoing emails better and less likely to be marked as spam.

== Description ==

Email Essentials vastly reduces the chances of your emails being marked as spam or being rejected. Originally a debugging tool, it has grown into a full-fledged email enhancement plugin.

Please note that this plugin is not a "we support any type of transport" Email plugin. For other protocols than SMTP, but rather to enhance the email sending capabilities of WordPress.
If you need to send emails with other protocols than SMTP, this plugin is not for you. You might want to look at plugins like Post SMTP (not affiliated).

And since version 6.0.0, after more than 10 years of development, this plugin is now a FOSS plugin, meaning it is free to use, modify and distribute under the GPLv2 license.

In return, we ask you to support the development of this plugin by contributing to the codebase, reporting bugs, and helping others in the community.

**Responsible disclosure:**
If you find a vulnerability, please email us at [responsibledisclosure@acato.nl](mailto:responsibledisclosure@acato.nl).

== Features ==

* Set a good From name and email address, automatically correcting it if needed. For example, a contact form is sent from the visitors email address, resulting in an invalid Sender address. This plugin will correct it to a valid email address. This plugin automatically corrects it.
* Correct envelope-from address; often forgotten, but important for deliverability.
* Reformat as HTML with plain text alternative; will detect the use of HTML ensures that emails are sent as HTML with a plain text alternative.
* Process shortcodes in your email content.
* UTF8 recoding, to ensure that special characters are correctly encoded in the email.
* Email Essentials allows for adding CSS, header, footer, and body template using filters, see below.
* Convert CSS to inline styles for better support in email clients
* SMTP configuration
* Send emails to multiple addressees as separate emails
* S/MIME signing, using a supplied certificate, to ensure the authenticity of the email.
* DKIM signing, and providing all information needed to set up DKIM signing for your domain.
* Allow redirecting emails sent to the administrator to other email addresses based on the email subject.
* Allow redirecting emails sent to the moderators (e.g., for comments).
* Keep a history of outgoing emails (debugging, cleared on deactivation)
* Email receipt tracking (for investigative purposes only, see GDPR note)
* Re-send button for failed emails
* Allow sending emails delayed, to prevent sending too many emails at once. (Beta feature)

== Important Note ==

This tool is for users who understand email delivery. If unsure, ask for help.

*Under GDPR, storing and tracking emails is prohibited. The history feature is for investigative purposes only!*

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/email-essentials` directory, or install via the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure settings as needed.

== Frequently Asked Questions ==

= Is this a replacement for plugins that use other transport than SMTP? =
No, it enhances WordPress email sending but only supports SMTP. You CAN use both plugins, but you must be careful to configure them properly to not conflict with each other.

= Is it free? =
Email Essentials has always been free, but has never been open source. Since version 6.0.0, it is now a FOSS plugin, meaning it is free to use, modify and distribute under the GPLv2 license.

= How do I report a vulnerability? =
Email responsibledisclosure@acato.nl.

= How can I contribute? =
You can contribute by reporting bugs, helping others in the community, or contributing code. See our [GitHub repository](https://github.com/acato-plugins/email-essentials) for more information.
Please consider forking the repository and submitting pull requests for any improvements or bug fixes you make.

= How do I get support? =
If you need support, please visit our [support forum](https://wordpress.org/support/plugin/email-essentials/) or check the [GitHub issues page](https://github.com/acato-plugins/email-essentials/issues). (If you post the same issue on both, please cross-link your posts.) Acato customers can also contact us through regular channels.

== Screenshots ==

1. Email Essentials settings page.
2. Outgoing email history with re-send option.

== WordPress Filters ==

* `email_essentials_settings` — Filter plugin settings.
* `email_essentials_defaults` — Filter default settings.
* `email_essentials_body` — Filter HTML body of the email.
* `email_essentials_head` — Filter HEAD section of HTML email.
* `email_essentials_css` — Filter CSS for the email.
* `email_essentials_subject` — Filter email subject.
* `email_essentials_ip_services` — Define custom IP services for accuarely determining the sender's IP address.

== Changelog ==

= 6.0.0 =
* GOING FOSS! First release as a FOSS plugin.

= 5.5.0 =
* i18n text domain changed to `email-essentials`.

= 5.4.6 =
* Small bugfixes on the resend interface.

= 5.4.5 =
* Translation fixes.

= 5.4.4 =
* Added a re-send button for emails in the history.

= 5.4.3 =
* New tag because tag 5.4.2 is broken.

= 5.4.2 =
* Default SMTP port is now 465 or 587 for SSL or TLS.

= 5.4.1 =
* Added more logging during sending/processing the email.

= 5.4.0 =
* Critical bugfix for alternative admins.

= 5.3.0 =
* More features for Multisite and email testing.

= 5.2.5 =
* PHP 8 compatibility improvements.

= 5.2.4 =
* Code improvements for PHP 8.0 compatibility.

= 5.2.3 =
* Added filter to disable GravityForms HTML envelope.

== Upgrade Notice ==

= 6.0.0 =
First public FOSS release. Please test before updating live sites.

== Arbitrary section ==

For advanced configuration and custom IP services, see the plugin documentation or source code.
