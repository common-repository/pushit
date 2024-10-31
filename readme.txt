=== Pushit ===
Contributors: d0pp13r, voituk, qmikali, sulo, ante13, Nadai, NamedSue 
Homepage: http://handshake.se/pushit/
Tags: pushit, push, sms, mobilstart, mobile, mobility
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: trunk

Pushit is a mobile mash-up that enables SMS recommendations from your Wordpress installation!

== Description ==

Pushit is a marvel within sharing technology! Not only is it capable of sending a of a blog post to a friend via SMS or e-mail, it also gives your blog a universal mobile interface! We wouldn’t be too surprised if you could substitute a couple of old plug-ins with Pushit and still have more features and a faster site.

Here are the three core features:

* Pushit adds support for registration with cell phones. This means that you can verify your blog visitors and send them their password in a text message (SMS). Bye bye, spam comments and captcha plug-ins.

* Pushit adds post recommendations via SMS, e-mail and social bookmarks like Facebook, Twitter and Bloggy. As a bonus, Pushit also gives you a neat print function and a nice set of related RSS links for a particular post.

* The third major feature is the super compact mobile viewport for your web site! The mobile version is light and adaptable, with support for custom fields, logging in, comments and search.

PLEASE NOTE: THE MOBILE VERSION WILL APPEAR APRIL 30th 2009. BEFORE THIS DATE, PUSHIT IS DISTRIBUTED WITHOUT A MOBILE VIEWPORT.

== Installation ==

PLEASE NOTE, THE INSTALLATION INSTRUCTIONS BELOW ARE NOT VALID DURING THE PRIVATE BETA PERIOD. IF YOU WANT TO TRY PUSHIT AT YOUR BLOG,  [CONTACT US](http://handshake.se/contact/ "Contact") TO GET YOUR COUPON CODE AND CALLERID ACTIVATION.

1. Backup your Wordpress installation
1. Upload the Pushit folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Wordpress Admin Settings
* enter your [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") credentials and save
* check out the Pushit PHP hooks that can be used in your theme
1. Pushit should now work on your Wordpress-powered web site!

== Screenshots ==

1. This screenshot shows the pop-up overlay that appears when you press the Pushit icon on a Pushit-powered home page. A box with two entry fields will appear - one for SMS, and one for e-mail. You will also have the ability to click a social bookmark or microblog icon, Browse post specific RSS feeds or print the article from here. The print function calls an adjustable print CSS with support for custom fields.

2. This screenshot shows the Admin Settings area for Pushit. Here, you can enter your [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") credentials just like you would enter your API key in a Google Analytics plugin. Here you can also control a number of other settings, and find information about the PHP hooks that can be used in Wordpress themes.

== Licence ==

The code is under GPLv3, but in order to work properly with CallerID support, Pushit requires a working account at [Mobilstart.se](http://mobilstart.se "Mobilstart official web page"). During the pre-beta and beta periods, you can apply for an account and a coupon code by [contacting us](http://handshake.se/contact/ "Contact").

This page is where you will find information about the four licence alternatives for the release (post-beta) version of Pushit. 

== Frequently Asked Questions ==

= Why on earth is it called Pushit? =
Pushit refers to the act of forwarding a message. If you recommend a news article or a blog post to a friend, you’re “pushing” the message forward to your friend.

= Why do i need it? =
Pushit offers a sweet set of mobile features in one single plugin! We wouldn't be too surprised if you could substitute a couple of old plugins with Pushit and still have more features and a faster site. Check out the three core features [here](http://wordpress.org/extend/plugins/pushit/ "Pushit description")!

= What do I need to install Pushit on my blog? =
You need admin rights and FTP/shell access to a functioning Wordpress 2.7 installation. You will also need a [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") account, which is free of cost and can be obtained from within the Wordpress admin interface of Pushit. It's really no more difficult than entering a so called "API key", which is very common for other plugins. During the private beta period, you can get a special coupon code and you will also have to send a request for CallerID activation (see below). Without this, Pushit will not work, so [contact us](http://handshake.se/contact/ "Contact"). 

= What if I don't use Wordpress? =
Tell us what CMS platform you think we should support. We will port Pushit to other platforms like Drupal if there is demand!

= How do I install Pushit? =
Simple as pie! It's no different than installing a regular Wordpress plugin with an API/activation code. Check out the basic steps [here](http://wordpress.org/extend/plugins/pushit/installation/ "Installation of Pushit"). During the private beta period, you can get a special coupon code and you will also have to send a request for CallerID activation (see below). Without this, Pushit will not work, so [contact us](http://handshake.se/contact/ "Contact"). 

= What is FTP permissions? =
Various accounts on a server can have different rights to alter certain files in certain catalogues. The root user has permissions to all types of modifications. If your rights to modify a certain file or catalogue is blocked, you should contact the admin (root user) to get write permissions. 

If you want your Pushit installation to work properly, make sure that the following files can be altered: 

* LOGIN `/wp-login.php`
* TEMPLATE `/wp-admin/includes/template.php`
* EDIT USER `/wp-admin/user-edit.php`
* NEW USER`/wp-admin/user-new.php`

Make sure to check permissions **before activating** pushit plugin and **before deactivating** it. Once the Pushit plugin is installed, there is a link in the Plugins listing that will check the permissions state on your Wordpress installation for the four affected files. 

Just go to `/wp-admin/plugins.php` and scroll down to "Pushit". Click the permissions check link in the description text. In normal cases, there should be no problems with permissions. 

If you have persistent problems with write permissions, you should know that this is not related to Pushit, and that it affects the entire site, including other plug-ins and their functionality.

= What about compatibility with the other funky plugs on my web site? =
A rule of thumb is to have as little plugins as possible. Few plugins = few problems.  Pushit is not designed and tested with all the possible Wordpress plugins in mind, but has relatively few reported compatibility issues so far. If you have other plugins that somehow alter the login and registration functions, you should consider deactivating them first.

= Will you support Pushit? What if I have problems after the installation? =
Pushit does not have a general 24/7 support line or e-mail, but we will offer personal support to the users during the private beta period. During the public beta, an extended FAQ and a moderated discussion group will be provided via Getsatisfaction.

= What happens to my user database after i install Pushit? =
Nothing happens to the existing fields. Pushit adds a table for the phone number, but that's about it. There are no other changes to the user database.

= Is Pushit compatible with OpenID? =
You bet. Don't forget to deactivate your existing OpenID plugin, if you have one running already!

= What about oAuth, FBConnect and others? =
Not yet, but if we see a demand, we will consider this too.

= What is CallerID and how is it integrated in Pushit? =
[CallerID](http://en.wikipedia.org/wiki/Caller_id "CallerID") allows the receiving handset to recognise the sending number. If a user is logged in at your web site, he will be able to use Pushit for sending a text message with a recommendation link. Since he registered with his mobile, the cell phone number is already confirmed. This means that a an SMS sent via Pushit can have the correct sender number! If the phone supports CallerID correctly, and the incoming number is in the address book (very likely), the actual name of the sender will be shown in the receiving handset. As a result, the receiver can in turn reply directly to the senders cell phone by simply clicking "Reply". Bye bye, anonymous sender numbers!

= Can CallerID be compromised in Pushit? =
Sure, there are evil people doing evil things out there. Still, the site admin will need a working [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") account, so we see the risk of misuse as minimal. If someone will be sending text messages from the "wrong" number, it will be very easy to stop further misuse.

= Why does CallerID not work properly for all handsets? =
Unfortunately, all phones are not built equal. Some phones are more equal than others. On some phones, the incoming SMS will be shown as a regular phone number. There is not much to do about this other than buying the good phones and avoid the bad ones. That should send a message to the makers.

= Are there any personal integrity related "issues" with Pushit? =
A log with all SMS traffic will be created at your [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") account. As the site admin, you will be able to log in and see this log. In the log, you can see that a certain phone number has sent a recommendation message of a certain web page on your blog to a certain receiver phone.  You will also have access to all the cell phone numbers of your blog visitors directly from the admin interface in Wordpress. These issues are regulated in the User Agreement of Pushit.

= Is Pushit compatible with the Swedish PUL? =
Yes. Collecting so-called "User Identifiable Data", such as mobile phone numbers is regulated by the Swedish Law. Wordpress does not comply to PUL if it starts to store potentially sensitive data, such as cell phone numbers. To resolve this, Pushit adds a user agreement to. 

= How much does it cost? =
At this moment, it's free to install and free to use. In the future, there will be four versions of Pushit with various pricing strategies. There will always be one free (ad sponsored) version of Pushit. Standard operator rates apply when users are surfing the mobile web after clicking a link in a Pushit generated SMS.

= So who pays for the SMS sent with Pushit? =
During the private beta period, [Mobilstart.se](http://mobilstart.se "Mobilstart official web page") pays for the SMS traffic.

= What's the business logic behind the concept? Is there a plan? =
You bet. Pushit relies on four income streams to be implemented in the future.

= Pushit killed my web site! =
Not very likely, but sure - you never know, it's still beta software. Like stated in the README file distributed with Pushit, the plugin is distributed under GPL v3 and no warranty is given for its use or possible misuse. We recommend site admins to backup **before** installation, *not*  afterwards. Installing and using Pushit is at your own risk.

= I want to change the look and feel of the mobile web site. Is there anything i can do about it? =
The current mobile page is very rudimentary. It was not our goal to create the ultimate killer web at this stage, but we promise that this is our #1 priority in March 2009 and onwards. We are working on the mobile version and plan to update it at least once per week throughout spring 2009. You can submit your ideas for the mobile web site to us, and we will consider implementing them in the next version.

= Is Pushit Open Source? =
Sure, the source code is distributed under GPL v3. Grab yourself a copy at the public SVN repository and check out the code - we would love to have your [feedback](http://handshake.se/contact "Send feedback using our contact form")!