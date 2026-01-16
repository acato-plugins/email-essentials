# Email Essentials
A plugin to make WordPress outgoing emails better.

# Introduction:

The main purpose is to vastly reduce the chances of your emails being marked as spam or being rejected.

This plugin started as a debugging tool to find out why emails were not sent, but has grown into a full-fledged email enhancement plugin.

Please note that this plugin is not a "we support any type of transport" Email plugin. For other protocols than SMTP, but rather to enhance the email sending capabilities of WordPress.
If you need to send emails with other protocols than SMTP, this plugin is not for you. You might want to look at plugins like Post SMTP (not affiliated).

And since version 6.0.0, after more than 10 years of development, this plugin is now a FOSS plugin, meaning it is free to use, modify and distribute under the GPLv2 license.

In return, we ask you to support the development of this plugin by contributing to the codebase, reporting bugs, and helping others in the community.

## Responsible disclosure

If you find a vulnerability, please email us at [responsibledisclosure@acato.nl](mailto:responsibledisclosure@acato.nl).

# BREAKING CHANGES:

From version 4.1.0 on, the plugin is fully WP Coding standards compliant and fully Namespaced.
The side effect is that While versions 4.0.0 - 4.0.2 are backwards compatible; version 4.1.0 is NOT -- IF -- you access the WP_Email_Essentials methods directly. In version 6.0.0 the namespace is changed to `Acato\Email_Essentials` but backward compatibility is available.

Please TEST your website with the latest version of WPES locally or on a test-server _BEFORE_ you update your live website.

# This plugin offers your WP-site...
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

# Important note:
This tool is created for people that know what to do and why they do it. If you don't know what a feature does, ask for help :)

*1) Under GDPR, storing and tracking emails is prohibited, the history feature is meant for investigative purposes only!

# WordPress Filters:

## Plugin Settings

`acato_email_essentials_settings`

Parameters:
- (array) `$settings` The current settings of the plugin.

Expected return:
- (array) The new settings of the plugin.

---

`acato_email_essentials_defaults`

Parameters:
- (array) `$defaults` The current default settings of the plugin.

Expected return:
- (array) The new default settings of the plugin.

---

## Email Content

`acato_email_essentials_subject`

Parameters:
- (string) `$the_subject` Subject for the email.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The (altered) Subject.

---

`acato_email_essentials_body`

Parameters:
- (string) `$should_be_html` A text that should be html, but might not yet be, your job to make a nice HTML body.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) A text that should be html.

---

`acato_email_essentials_head`

Parameters:
- (string) `$the_head_section` HTML that is the HEAD section of the HTML email.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The altered HEAD section of the HTML email.

---

`acato_email_essentials_css`

Parameters:
- (string) `$the_css` CSS for the email (empty by default).
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The (altered) CSS.

---

`acato_email_essentials_minify_css`

Parameters:
- (string) `$css` CSS to be minified.

Expected return:
- (string) The minified CSS.

---

## Mail Throttling

`acato_email_essentials_mail_is_throttled`

Parameters:
- (bool) `$is_throttled` Whether the mail is currently throttled.
- (string) `$ip` The sender's IP address.
- (int) `$mails_recently_sent` Number of mails recently sent from this IP.

Expected return:
- (bool) Whether the mail should be throttled.

---

`acato_email_essentials_mail_throttle_time_window`

Parameters:
- (int) `$time_window` Time window in seconds for counting sent emails.

Expected return:
- (int) The (altered) time window in seconds.

---

`acato_email_essentials_mail_throttle_max_count_per_time_window`

Parameters:
- (int) `$count` Maximum number of emails allowed per time window.

Expected return:
- (int) The (altered) maximum count.

---

`acato_email_essentials_mail_throttle_batch_size`

Parameters:
- (int) `$size` Number of emails to send in a single batch.

Expected return:
- (int) The (altered) batch size.

---

## IP Detection

`acato_email_essentials_ip_services`

Parameters:
- (array) `$services` The current list of IP services used to determine the sender's IP address.

The services must be keyed with `ipv4`, `ipv6` and `dual-stack`. The values must be URLs that return the IP address in plain text.
The dual-stack service should return an IPv6 address if available, otherwise an IPv4 address, never both.

You can set-up your own service like this;

- You will need a webserver that can run PHP, and you need a DNS service that allows you to manually add records.
- You will need three webspaces, for example; ipv4.myservice.com, ipv6.myservice.com and dual-stack.myservice.com.
    - You could use the same webspace for all three, but you will still need three subdomains on the service.
- For the ipv4 subdomain, ONLY register an A record, pointing to the webserver's IP address.
- For the ipv6 subdomain, ONLY register an AAAA record, pointing to the webserver's IPv6 address.
- For the dual-stack subdomain, register both an A and an AAAA record, pointing to the webserver's IP addresses.
- Create a file called `index.php` in each of the webspaces with the following content:

```php
<?php
header('Content-Type: text/plain');
print $_SERVER['REMOTE_ADDR'];
```

That's it. You can now use these services in the plugin settings like this;

```php
add_filter('acato_email_essentials_ip_services', 'my_custom_ip_services');
function my_custom_ip_services($services) {
    // Add your custom services here
    $services['ipv4'] = 'https://ipv4.myservice.com';
    $services['ipv6'] = 'https://ipv6.myservice.com';
    $services['dual-stack'] = 'https://dual-stack.myservice.com';
    return $services;
}
```

Expected return:
- (array) The (altered) list of IP services.

---

`acato_email_essentials_ip_service`

Parameters:
- (string) `$service` The URL of the IP service for the given type.
- (string) `$type` The type of IP service ('ipv4', 'ipv6', or 'dual-stack').

Expected return:
- (string) The (altered) IP service URL.

Filter to modify individual IP service URLs based on type.

---

`acato_email_essentials_website_root_path`

Parameters:
- (string) `$path` The current website root path.

Expected return:
- (string) The (possibly altered) website root path.

Filter to supply the correct website root path in case of non-standard setups.

---

`acato_email_essentials_development_tlds`

Parameters:
- (array) `$tlds` Array of top-level domains considered as development environments.

Expected return:
- (array) The (altered) array of development TLDs.

Filter to modify which TLDs are treated as development/local environments. Default values are 'local' and 'test'.

# Scripts/styles:

in the `public/scripts` and `public/styles` folder you find the JS and CSS files used in the plugin admin area.
These files are processed with Webpack, just so it works in all recent browsers. Script is nearly identical to the source.

You can find the source files in the `assets/scripts` and `assets/styles` folders.

If you feel the need to modify these files, you can change them there, and run `npm install ; npm run build` to create the production files.

# Translation files:

You can use `npm run i18n` to generate the POT file for translation, update the PO files in the `languages` folder, and compile to MO/php files.
This is a one-task-does-all; run it, change the translations, run it again. Done.
See package.json for more details or individual commands.

# Tools:

In the `tools` folder you will find a script to generate DKIM keys, should you want to use DKIM signing.
rename to remove the .txt extension and run it in a shell.

Tools are provided as-is, without support. Use at your own risk. Read the scripts before using them.

You DO NOT HAVE TO USE these scripts, you can generate DKIM keys with any tool you like.

# Changelog:

6.0.0: GOING FOSS! This is the first release of Email Essentials as a FOSS plugin. After months of preparation, we're finally public! If you want more, come check us out on [Acato.nl](https://www.acato.nl).

5.5.3: More review feedback from WordPress.org, code hardening, no functional changes.

5.5.2: Additional output escaping, breaking functionality, but added source panel for restoring part of that so outgoing emails can still be scrutinized.

5.5.1: Review feedback from WordPress.org handled. This brings no functional changes except for;
Updated the email viewer interface for more user-friendly viewing of emails and their debug information.

5.4.7: i18n text domain changed to `email-essentials` to match future plugin slug, another step towards FOSS,
       Bugfix: add missing PHPMailer Exception class.

5.4.6: Small bugfixes on the resend interface

5.4.5: Translation fixes - nothing critical changed.

5.4.4: Added a re-send button for emails in the history, so you can re-send an email that failed to send.

5.4.3: New tag because tag 5.4.2 is broken - No changes here - Identical code to 5.4.2.

5.4.2: Default SMTP port is now 465 or 587 for SSL or TLS, instead of relying on the server to switch automatically on port 25.

5.4.1: Added more logging during sending/processing the email, you can find the extra log in the debug field, in the history viewable with an alt-click. Also added a note about false-positive SPF match when using an Admin Email that is on a domain on the same server.

5.4.0: Critical bugfix warranted the new minor release, gathering subjects for the alternative admins was broken since version 4.0.0.

5.3.0: Move E-mail testing to the bottom and add more features, In Multisite, also check the Site Admin for candidate for replacement (Alternative Admins).

5.2.5: More improvements for PHP 8 compatibility, code optimisation and generic bugfixing

5.2.4: Code improvements for PHP 8.0 compatibility

5.2.3: Added filter to disable the HTML envelope set by GravityForms, so we can use our own.
