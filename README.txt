This module provides a way to transparently log users into the web site when
they follow a link. The primary use is for mass email where users are send
individualized URL's which take them to a page on the site, in a "logged in"
state. Every effort has been made to minimize the potential security risks.


USE CASE
========

 * The main use case is for mass emailing where it is unlikely that the
   recipients will go through the hassle of creating accounts and passwords.
   Assuming an account has been created for them, email to them contains a
   customized link which will automatically log them in and take them to a
   target page.
 * This permission would only be given to accounts with "low value" privileges
   (e.g. no admin, financial or access to confidential data).
 * The reason for logging users into the site would typically be so that they
   can do activities such as:
    o sign up for events
    o comment, rate, "like", or otherwise interact with the site
    o unsubscribe from email or change email preferences
 * Usage tracking
    o Widely used mass email tools such as MailChimp and Campaign Monitor allow
      the sender to track which links in the email recipients have clicked on.
    o Using this module, Drupal's own tracking system can be used to accomplish
      this, providing a far more integrated solution than provided by third-
      party mailers.

FEATURES
========

 * Security
    o The login access link is encoded with a high level of security based on
      sha256
    o All encryption/decryption functions are encapsulated in a separate file
      so that an alternative can easily be dropped in as a replacement
    o All currently issued access links may be instantly "expired" by simply
      changing an administration setting, giving full control over the active
      lifetime of the links.
    o All access failures are logged together with the reason for the failure
    o The main security weakness would be from emails going astray or being
      intercepted. This can be mitigated by:
       - giving access links a short lifetime
       - limiting the permissions of users who are allowed this mode of access.

 * Design Features
    o The module is designed to scale to over 100,000 users, (although only
      tested with 15,000).
    o Both users and spam detectors are suspicious of long URL's, so every
      effort has been make to keep the login link as short as possible
    o For this reason, the embedding login string is only 11 characters long.
       - e.g. http://example.com/l/zjIR0AeOzef/blog/myarticle
    o base64URL encoding has been used to avoid problems
    o The link can take the user directly to any page on the site for which
      they have permission
    o The module is integrated with persistent login

DIFFERENCES FROM OTHER SIMILAR MODULES
======================================

The most similar module is easylogin although the similarities are only
superficial because easylogin has an entirely different use case, as described
below.

 _____________________________________________________________________________
|            |URLLOGIN                       |EASYLOGIN                       |
|____________|_______________________________|________________________________|
|            |                               |allowing a small number of      |
|Use case    |mass emailing                  |individual users to manage their|
|            |                               |access using a URL string       |
|____________|_______________________________|________________________________|
|            |                               |All users have an extra         |
|Architecture|All encryption/decryption done |"password" added to the         |
|            |on the fly                     |database. User can log in by    |
|            |                               |putting this "password" in a URL|
|____________|_______________________________|________________________________|
|            | * Highly secure               |                                |
|            | * Large number of users can be|                                |
|Architecture|   managed easily              |Detailed individual-level       |
|strengths   | * No database tables need to  |control possible                |
|            |   be created/maintained       |                                |
|            | * mass download of access     |                                |
|            |   strings to csv file         |                                |
|____________|_______________________________|________________________________|
|            |                               | * Security: access strings are |
|            |                               |   stored unencrypted in the    |
|            |                               |   database                     |
|Architecture| * No individual control       | * No way of making access      |
|weaknesses  |   (except by permission)      |   strings have an expiry date  |
|            | * no way of re-setting an     | * methodology does not scale to|
|            |   individual access string    |   a large number of users      |
|            |                               | * no mass download of access   |
|            |                               |   strings possible             |
|____________|_______________________________|________________________________|
Another similar module is One-time login links which is a very minimal utility
module that simply re-creates the link that a user would get had they forgotten
their password and needed to re-create it.

 * This module has a very limited number of use cases because the landing page
   is (of necessity) the user's account where they need to create a password.
 * This limits the use of the module to those cases where users are willing to
   create themselves a password as the first step to viewing the site.
 * There is no way of expiring the links if the user has not logged in before.
   They last indefinitely--a security risk.
 * It is not suitable for sending in mass email because experience shows that
   email recipients will often go back to the original email to re-gain access
   to the site. Drupal's one-time link mechanism that this module utilizes will
   not allow this behaviour.

CONFIGURATION
=============

1. Set a passphrase and validation numbers on the urllogin administration page
   (/admin/settings/urllogin)
2. Give the "login via URL" permission to users who are allowed to log in with
   this module
3. Generate login strings (can be downloaded as a CSV file). Login strings are
   in the form:
    o http://example.com/l/11CHARSTRNG/my_blog_page where the '/l/' and the 11
      character access string have been inserted into the URL.
4. Before sending the URL to real people, it can be tested by using
   '/l_test/' instead of '/l/' in the URL.

POSSIBLE FUTURE DEVELOPMENT
===========================

 * Integration into simplemail

SUPPORT
=======

If you experience a problem with urllogin or have a problem, file a request or
issue on the urllogin queue at http://drupal.org/project/issues/1076736. DO NOT
POST IN THE FORUMS. Posting in the issue queues is a direct line of
communication with the module authors.

No guarantee is provided with this software, no matter how critical your
information, module authors are not responsible for damage caused by this
software or obligated in any way to correct problems you may experience.


SPONSORS
========

The urllogin module is sponsored by Corporate Finance Associates
( http://cfaw.ca ).

Licensed under the GPL 2.0.
http://www.gnu.org/licenses/gpl-2.0.txt

