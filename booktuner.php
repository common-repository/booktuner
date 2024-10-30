<?php
	
	/*
	Plugin Name: bookTuner
	Version: 1.1.2
	Plugin URI: http://wordpress.org/extend/plugins/booktuner/
	Description: Displays books from a <a href="http://www.goodreads.com" target="_blank">Goodreads.com</a> bookshelf in a <a href="options-general.php?page=booktuner/booktuner.php">customizable format</a>. Based on <a href="http://wordpress.org/extend/plugins/fmtuner/">fmTuner</a> by <a href="http://www.command-tab.com/">Collin Allen</a>. 
	Author: Michael Castello
	Author URI: http://www.mistypedURL.com
	*/
	
	
	/*
	Copyright (c) 2008, 2010 Michael Castello (http://mistypedURL.com/)
	and Collin Allen (http://www.command-tab.com/).

	Permission is hereby granted, free of charge, to any person obtaining
	a copy of this software and associated documentation files (the
	"Software"), to deal in the Software without restriction, including
	without limitation the rights to use, copy, modify, merge, publish,
	distribute, sublicense, and/or sell copies of the Software, and to
	permit persons to whom the Software is furnished to do so, subject to
	the following conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
	MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
	LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
	OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
	WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
	*/
	
	
	// Display Goodreads.com books, no duplicates
	function booktuner()
	{
		if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
		{
			// Fetch options from WordPress DB and set up variables
			$iCacheTime = get_option('booktuner_update_frequency');
			$sCachePath = get_option('booktuner_cachepath');
			$iBookLimit = get_option('booktuner_limit');
			$sBaseUrl = 'http://www.goodreads.com/review/list_rss/';
			$sUserID = get_option('booktuner_userid');
			$sApiKey = '3mVq2aLmYmh0GlFVVlKrwQ';
			$sShelf = get_option('booktuner_shelf');
			$sSort = get_option('booktuner_sort');
			$aSortOrder = get_option('booktuner_sort_order');		
			$sDisplayFormat = get_option('booktuner_display_format');
			$sCoverImage = get_option('booktuner_image_size');
			$sReviewLength = get_option('booktuner_review_length');
			$sApiUrl = "{$sBaseUrl}{$sUserID}.xml?key={$sApiKey}&v=2&shelf={$sShelf}&sort={$sSort}&per_page={$iBookLimit}&order={$sSortOrder}";
			//test url with defaults http://www.goodreads.com/review/list_rss/1874313.xml?key=3mVq2aLmYmh0GlFVVlKrwQ&v=2&shelf=read&sort=d&per_page=2&order=date_read
					
			// Run only if a user ID is set
			if ($sUserID)
			{
				// If the cached XML exists on disk
				if (file_exists($sCachePath))
				{
					// Compare file modification time against update frequency
					if (time() - filemtime($sCachePath) > $iCacheTime)
					{
						// Cache miss
						$sTracksXml = booktuner_fetch($sApiUrl);
						file_put_contents($sCachePath, $sTracksXml);
					}
					else
					{
						// Cache hit
						$sTracksXml = file_get_contents($sCachePath);
					}
				}
				else
				{
					// Fetch the XML for the first time
					$sTracksXml = booktuner_fetch($sApiUrl);
					file_put_contents($sCachePath, $sTracksXml);
				}
				
				// Parse the XML
				$xTracksXml = simplexml_load_string($sTracksXml);
				$aItems = array();
				$iTotal = 1;
				
				// If we have any parsed tracks
				if ($xTracksXml)
				{
					// Tell it where to look
					{
							$xItems = $xTracksXml->channel->item;
					}
					
					// Loop over each book, outputting it in the desired format
					foreach($xItems as $oItem)
					{
                        // Get the correct image size
                        $sImage = $oItem->$sCoverImage;					
												
						// Retreive only the first x words of the book review
						{
							$sLongReview = $oItem->user_review;
							$sReviewLength = abs((int)$sReviewLength);						
							if(strlen($sLongReview) > $sReviewLength) {
						   		$sReview = preg_replace("/^(.{1,$sReviewLength})(\s.*|$)/s", '\\1', $sLongReview);
							}
							else {
								$sReview = $sLongReview;
							}
						}
						
						// Store each Book in $aItems, and check it every iteration so as not to output duplicates
						$sKey = $oItem->isbn;
						
						// If the current book is not in $aItems and we haven't hit the book limit
						if (!in_array($sKey, $aItems) && $iTotal <= $iBookLimit)
						{
							// Shove the current track into $aItems to be checked for next time around
							$aItems[] = $sKey;
							
							// Dump out the blob of HTML with data embedded
							$aTags = array(
								'[::review::]',
								'[::author::]',
								'[::image::]',
								'[::number::]',
								'[::title::]',
								'[::url::]',
								'[::rating::]'
							);
							$aData = array(
								$sReview,
								$oItem->author_name,
								$sImage,
								$iTotal,
								$oItem->title,
								$oItem->link,
								$oItem->user_rating
							);
							
							// Clean up data, prevent XSS, etc.
                            foreach ($aData as $iKey => $sValue)
                                $aData[$iKey] = trim(strip_tags(htmlspecialchars($sValue)));
							
							// Merge $aTags and $aData
							echo str_replace($aTags, $aData, $sDisplayFormat);
							
							// Increment the counter so we can check the track limit next time around
							$iTotal++;
						}
					} // end foreach loop
				} // end if (any parsed tracks)
			}
			else
			{
				echo 'Please <a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=booktuner/booktuner.php">set bookTuner options</a> in your WordPress administration panel.';
			} // end if (UserID)
		}
		else
		{
			echo 'bookTuner requires PHP version 5 or greater.  Please contact your web host for more information.';
		} // end PHP5 check
	} // end booktuner()
	
	
	
	// Fetch a given URL using file_get_contents or cURL
	function booktuner_fetch($sApiUrl)
	{
		// Check if file_get_contents will work
		if ( ini_get('allow_url_fopen') && function_exists('file_get_contents') && $sApiUrl )
		{
			// Use file_get_contents
			return file_get_contents($sApiUrl);
		}
		elseif ( function_exists('curl_init') && $sApiUrl )
		{
			// Fall back to cURL
			$hCurl = curl_init();
			$iTimeout = 5;
			curl_setopt($hCurl, CURLOPT_URL, $sApiUrl);
			curl_setopt($hCurl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($hCurl, CURLOPT_CONNECTTIMEOUT, $iTimeout);
			$sFileContents = curl_exec($hCurl);
			curl_close($hCurl);
			return $sFileContents;
		}
		else
		{
			return false;
		}
	}
	
	
	
	// Add default options to the DB
	function add_booktuner_options()
	{
		add_option('booktuner_cachepath', dirname(__FILE__).'/booktuner_cache.xml'); // Default cache location
		add_option('booktuner_userid', ''); // Default to ''
		add_option('booktuner_shelf', 'read'); // Default to 'read' shelf
		add_option('booktuner_sort', 'date_read'); // Default to sorting by date read
		add_option('booktuner_sort_order', 'd'); //Default to descending order
		add_option('booktuner_update_frequency', 3600); // Default to 'Every hour'
		add_option('booktuner_limit', 2); // Default to max of 2 books
		add_option('booktuner_display_format', '<li>[::author::] - [::title::]<img src="[::image::]" alt="[::title::] by [::author::]" /></li>'); // Default format
		add_option('booktuner_image_size', 'book_medium_image_url'); // Default to medium image size
		add_option('booktuner_review_length', 100); // Default to first 100 characters of review
	}
	
	
	
	// Delete the cache file and options stored in the DB
	function delete_booktuner_options()
	{
		$sCachePath = get_option('booktuner_cachepath');
		if (file_exists($sCachePath))
			unlink($sCachePath);
		
		delete_option('booktuner_cachepath');
		delete_option('booktuner_userid');
		delete_option('booktuner_shelf');
		delete_option('booktuner_sort');
		delete_option('booktuner_sort_order');
		delete_option('booktuner_update_frequency');
		delete_option('booktuner_limit');
		delete_option('booktuner_display_format');
		delete_option('booktuner_image_size');
		delete_option('booktuner_review_length');
	}
	
	
	
	// Add the options page to the admin area under Settings when called
	function setup_booktuner_options()
	{
		add_options_page('bookTuner Settings', 'bookTuner', 1, __FILE__, 'booktuner_options');
	}
	
	
	// Add a Settings link to the plugin actions list
    function setup_booktuner_settings_link($aActionLinks)
    {
        $sSettingsLink = '<a class="edit" title="Change bookTuner settings" href="options-general.php?page=' . plugin_basename(__FILE__) . '">Settings</a>';
        array_unshift($aActionLinks, $sSettingsLink); 
        return $aActionLinks; 
    }
	
	
	
	// Hook into WordPress to add a Settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'setup_booktuner_settings_link');
	
	
	
	// Register bookTuner plugin activation/deactivation hooks
	register_activation_hook(__FILE__, 'add_booktuner_options');
	register_deactivation_hook(__FILE__, 'delete_booktuner_options');
	
	
	
	// Hook into WordPress to call setup_booktuner_options() when the admin menu is loaded
	add_action('admin_menu', 'setup_booktuner_options');
	
	
	
	// Display the options page in wp-admin
	function booktuner_options()
	{ ?>
		<div id="booktuner_body">
			<div class="wrap">
<?php
		if (function_exists('simplexml_load_string') && function_exists('file_put_contents'))
		{
			// Fetch XML again, since key options (user id, track type) may have changed
			$iBookLimit = get_option('booktuner_limit');
			$sBaseUrl = 'http://www.goodreads.com/review/list_rss/';
			$sShelf = get_option('booktuner_shelf');
			$sUserID = get_option('booktuner_userid');
			$sApiKey = '3mVq2aLmYmh0GlFVVlKrwQ';
			$sSort = get_option('booktuner_sort');
			$sSortOrder = get_option('booktuner_sort_order');
			$sDisplayFormat = get_option('booktuner_display_format');
			$sCoverImage = get_option('booktuner_image_size');
			$sReviewLength = get_option('booktuner_review_length');
			$sApiUrl = "{$sBaseUrl}{$sUserID}.xml?key={$sApiKey}&v=2&shelf={$sShelf}&sort={$sSort}&per_page={$iBookLimit}&order={$sSortOrder}";
			if ($sUserID != '')
			{
				$sTracksXml = booktuner_fetch($sApiUrl);
				file_put_contents(get_option('booktuner_cachepath'), $sTracksXml);
			}
		}
		else
		{
?>
			<div class="error" style="padding: 5px; font-weight: bold;">bookTuner requires PHP version 5 or greater. Please contact your web host for more information.</div>
<?php
		}
?>
				<div class="icon32" id="icon-options-general"><br /></div>
                <h2>bookTuner Settings</h2>
				<form action="options.php" method="post">
					<?php wp_nonce_field('update-options'); // Protect against XSS ?>
					<table class="form-table">
						<tbody>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_userid">Goodreads.com User ID </label>
								</th>
								<td>
									<input type="text" size="25" value="<?php echo get_option('booktuner_userid'); ?>" id="booktuner_userid" name="booktuner_userid" />
									<span class="description">Enter your <a href="http://www.goodreads.com/" target="_blank">Goodreads.com</a> user ID number (NOT your username)</span>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">Shelf Type</th>
								<td>
									<input type="text" size="25" value="<?php echo get_option('booktuner_shelf'); ?>" id="booktuner_shelf" name="booktuner_shelf" />
                                    <br />Enter the ID of the bookshelf you want to use
								</td>
							</tr>
                            <tr valign="top">
								<th scope="row">
									<label for="booktuner_sort">Sorting Method</label>
								</th>
								<td>
									
                                    <select id="booktuner_sort" name="booktuner_sort">
										<?php $iSortMethod = get_option('booktuner_sort'); ?>
                                        <option <?php if ($iSortMethod == 'author') { echo 'selected="selected" '; } ?> value="author">Author</option>
                                        <option <?php if ($iSortMethod == 'avg_rating') { echo 'selected="selected" '; } ?> value="avg_rating">Average rating</option>
                                        <option <?php if ($iSortMethod == 'date_added') { echo 'selected="selected" '; } ?> value="date_added">Date added to shelf</option>
                                        <option <?php if ($iSortMethod == 'date_pub') { echo 'selected="selected" '; } ?> value="date_pub">Date published</option>
                                        <option <?php if ($iSortMethod == 'date_read') { echo 'selected="selected" '; } ?> value="date_read">Date read</option>
                                        <option <?php if ($iSortMethod == 'date_started') { echo 'selected="selected" '; } ?> value="date_started">Date you started reading the book</option>
                                        <option <?php if ($iSortMethod == 'isbn') { echo 'selected="selected" '; } ?> value="isbn">ISBN</option>
                                        <option <?php if ($iSortMethod == 'isbn13') { echo 'selected="selected" '; } ?> value="isbn13">ISBN-13, if ISBN isn't sufficient</option>
                                        <option <?php if ($iSortMethod == 'position') { echo 'selected="selected" '; } ?> value="position">Position on shelf</option>
                                        <option <?php if ($iSortMethod == 'rating') { echo 'selected="selected" '; } ?> value="rating">User rating</option>
                                        <option <?php if ($iSortMethod == 'random') { echo 'selected="selected" '; } ?> value="random">Random (selected by Goodreaads)</option>
                                        <option <?php if ($iSortMethod == 'read_count') { echo 'selected="selected" '; } ?> value="read_count">Read count</option>
                                        <option <?php if ($iSortMethod == 'title') { echo 'selected="selected" '; } ?> value="title">Title</option>
									</select>
                                    
                                </td>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_sort">Sort Order</label>
								</th>
								<td>                                   
                                    <select id="booktuner_sort_order" name="booktuner_sort_order">
										<?php $iSortOrder = get_option('booktuner_sort_order'); ?>	
                                        <option <?php if ($iSortOrder == 'a') { echo 'selected="selected" '; } ?> value="a">Ascending</option>
                                        <option <?php if ($iSortOrder == 'd') { echo 'selected="selected" '; } ?> value="d">Descending</option>
									</select>
								</td>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_limit">Book Limit</label>
								</th>
								<td>
									Show <input type="text" size="3" value="<?php echo get_option('booktuner_limit'); ?>" id="booktuner_limit" name="booktuner_limit" /> books at most.
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_update_frequency">Update Frequency</label>
								</th>
								<td>
									
									<select id="booktuner_update_frequency" name="booktuner_update_frequency">
										<?php $iUpdateFrequency = get_option('booktuner_update_frequency'); ?>
										<option <?php if ($iUpdateFrequency == 900) { echo 'selected="selected" '; } ?> value="900">Every 15 minutes</option>
										<option <?php if ($iUpdateFrequency == 1800) { echo 'selected="selected" '; } ?> value="1800">Every 30 minutes</option>
										<option <?php if ($iUpdateFrequency == 3600) { echo 'selected="selected" '; } ?> value="3600">Every hour</option>
										<option <?php if ($iUpdateFrequency == 86400) { echo 'selected="selected" '; } ?> value="86400">Every day</option>
									</select>
								
                                </td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_image_size">Image Size</label>
								</th>
								<td>
									
									<select id="booktuner_image_size" name="booktuner_image_size">
										<?php $iImageSize = get_option('booktuner_image_size'); ?>
										<option <?php if ($iImageSize == 'book_small_image_url') { echo 'selected="selected" '; } ?> value="book_small_image_url">Small (39px x 60px)</option>
										<option <?php if ($iImageSize == 'book_medium_image_url') { echo 'selected="selected" '; } ?> value="book_medium_image_url">Medium (92px x 140px)</option>
										<option <?php if ($iImageSize == 'book_large_image_url') { echo 'selected="selected" '; } ?> value="book_large_image_url">Large (311px x 475px)</option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_review_length">Review Length</label>
								</th>
								<td>
									Show the first <input type="text" size="3" value="<?php echo get_option('booktuner_review_length'); ?>" id="booktuner_review_length" name="booktuner_review_length" /> characters of the book review (trimmed to nearest full word).
								</td>
							<tr valign="top">
								<th scope="row">
									<label for="booktuner_display_format">Display Format</label>
								</th>
								<td>
									<p>
										<label for="booktuner_display_format">The bookTuner tags below can be used among standard <abbr title="HyperText Markup Language">HTML</abbr> to customize the book display format.  Tags can be used more than once, or completely left out, depending on your preferences.
											<ul style="margin: 0px; padding: 0px; list-style: none;">
												<li><code>[::review::]</code> Review excerpt. Retrieves the first characters of the book review (set length above).</li>
												<li><code>[::author::]</code> Author name</li>
												<li><code>[::image::]</code> Book cover image address.</li>
												<li><code>[::number::]</code> Book number within the booktuner set (e.g. for a numbered list)</li>
												<li><code>[::title::]</code> Book title</li>
												<li><code>[::url::]</code> Goodreads.com book address</li>
                                                <li><code>[::rating::]</code> Book rating (assigned by user)</li>
											</ul>
										</label>
									</p>
									<p>
										<textarea class="code" style="width: 98%; font-size: 12px;" id="booktuner_display_format" rows="8" cols="60" name="booktuner_display_format"><?php echo get_option('booktuner_display_format'); ?></textarea>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="hidden" name="action" value="update" />
						<input type="hidden" name="page_options" value="booktuner_userid,booktuner_shelf,booktuner_sort,booktuner_sort_order,booktuner_update_frequency,booktuner_image_size,booktuner_limit,booktuner_review_length,booktuner_display_format" />
						<input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
					</p>
				</form>
			</div>
		</div>
			
		<?php
	}	
?>