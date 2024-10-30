=== bookTuner ===
Contributors: silversteelwolf
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=SilverSteelWolf%40gmail%2ecom&lc=US&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: artwork, book, cover, goodreads, reading, sidebar
Requires at least: 2.7
Tested up to: 3.0.4
Stable tag: 1.1.2

bookTuner displays books from Goodreads.com in a customizable format.

== Description ==

bookTuner pulls book information from one of your shelves on Goodreads.com. Title, author, jacket art, and a review snippet can all be displayed on your site with the plugin's configurable settings and simple tags. The plugin is an adaptation of fmTuner by Collin Allen.

= Features =
*   Displays books from currently-reading, to-read, read, or a custom shelf
*   Limit how many books are shown
*   Configure how often the book list is updated
*   Customize book appearence with HTML and tags
*   Set length of review preview
*   Sort list by a number of criteria including author, title, rating, and date read

= Requirements =
*   A Goodreads.com account with books added to a shelf
*   Wordpress 2.7 or newer
*   PHP 5 or newer

== Installation ==

Installation is pretty straightforward, although PHP 5 or newer is required.

1. Upload `booktuner.php` to the `/wp-content/plugins/` directory, within a subdirectory like `booktuner`.
1. Ensure `/wp-content/plugins/booktuner` is writable by your webserver (`chmod 755 booktuner`).
1. Activate the plugin through the "Plugins" page in the Wordpress admin.
1. Set your booktuner preferences in the "Settings" menu in the Wordpress admin. Be sure to enter your Goodreads user ID (not your username)
1. Place `<?php if(function_exists('booktuner')) { booktuner(); } ?>` in the desired place in your template. 

== Frequently Asked Questions ==



= How does bookTuner work? =


bookTuner pulls your latest books from Goodreads.com according to the settings page in the WordPress administration area.  Books get pulled from Goodreads.com when a visitor comes to your site, and are then cached for future visits. If the cache has expired (that is, the cache's age has passed the update frequency you've chosen), it gets pulled again, and your page is updated. Book information is displayed using HTML and bookTuner Tags, also in the settings page. 



= What are bookTuner Tags? =
bookTuner tags are simple placeholders that can be sprinkled among HTML to customize the display format used for each book. Tags can be used more than once, or completely left out, depending on your preferences.  A simple example is provided when you install bookTuner, so you won't be left in the dark if you have even basic HTML knowledge.


* `[::title::]` Title of the book

* `[::author::]` Author name

* `[::image::]` Jacket artwork address (small, medium, or large size)
 
* `[::number::]` Book number within the bookTuner set (for a numbered list)

* `[::review::]` The first few characters of the review (configurable length)

* `[::url::]` Goodreads.com book address
* `[::rating::]` Book rating (assigned by user)
Using CSS and JavaScript, you can do even more, limited only by your skills and imagination!



= Can I customize the HTML around the displayed tracks? =


Absolutely! While the customizable display format and bookTuner Tags are used for each track, you can place any additional HTML around the `<?php if(function_exists ('booktuner')) { booktuner(); } ?>` call.



= How many books can I display? =


The number of books to be displayed can be set in the bookTuner Settings page in  the WordPress administration area.  Between 1 and 10 is recommended, just to keep things looking sane.


= I don't want to have to paste code into my template. Is there a widget? =

I know it's a pain, but there's not a widget right now. If I get the time to learn how to do it I may try.

= My books aren't displaying! =
First, make sure you have your Goodreads.com user ID number entered correctly. You can find this by viewing your profile. It's the number at the end of this: `http://www.goodreads.com/user/show/1234567`. Also make sure you have the code pasted into your template - it should work just about anywhere.

== Screenshots ==

1. bookTuner Settings screen
2. bookTuner in action - just one of many possibilities!

== Changelog ==

= 1.1.2 =
* Fixed a bug with fetching book lists.

= 1.1.1 =
* Added tag for user rating.

= 1.1 =
* Reworked use of Goodreads API and added shelf sorting.

= 1.0.1 =
* Fixed a bug in the [::url::] tag.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.2 =
*Fixed a bug where fetching a book list would fail if not done manually.

= 1.1.1 =
*Added a tag to insert the user rating for a book as a number.

= 1.1 =
*Reworked how the plugin uses the Goodreads API and added the ability to sort shelves by a number of different criteria.

= 1.0.1 =
*Fixes a bug in the [::url::] tag that caused unecessary text to be prepended to the link.