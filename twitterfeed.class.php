<?php

/**
 * Load tweets from a twitter account.
 *
 * Features: caching.
 */
class TwitterFeed
{
	private $username = false;
	private $tweets = array();
	
	// The full path to the cache file.
	// If false, caching will be disabled.
	private $cache_file = false;
	
	// The number of hours a cache file should remain valid for.
	// After the cache file passes this age mark, the file will
	// be refreshed with the latest tweets.
	private $cache_file_age = 24; 
	
	/**
	 * Class constructor.
	 */
	public function __construct($args=array())
	{
		if (is_array($args) && !empty($args)) {
			foreach ($args as $name => $value) {
				if (property_exists($this, $name)) {
					$this->$name = $value;
				}
			}	
		}

		if (!$this->loadFromCache()) {		
			// Load tweets via Twitter XML.
			$this->load();
		}
	}
	
	/**
	 * Save current tweets to cache.
	 *
	 * @return bool
	 */
	public function saveToCache()
	{
		// Nothing to cache.
		if (empty($this->tweets)) { return false; }
		
		// No cache file defined.
		if (!$this->cache_file) { return false; }
		
		$file_content = serialize($this->tweets);
		
		$fp = fopen($this->cache_file,'w');
		fputs($fp, $file_content);
		fclose($fp);
		
		return true;
	}
	
	/**
	 * Load tweets from cache file into tweets array. 
	 *  
	 * @return bool
	 */
	public function loadFromCache()
	{
		// Cache file set?
		if (!$this->cache_file) { return false; }
	
		// Cache file doesn't exist or is not readable.
		if (!file_exists($this->cache_file) || !is_readable($this->cache_file)) { return false; }
		
		// Cache file has expired?
		// I.e. the cache file is older than the maximum defined cache file age.
		if ($this->cache_file_age > 0) 
		{
			$file_update_time = filemtime($this->cache_file);
			$diff = time() - $file_update_time;
			$diff_hours = (($diff/60)/60);
			
			if ($diff_hours > $this->cache_file_age) {
				return false;
			}
		}
		
		$saved_tweets = unserialize(trim(file_get_contents($this->cache_file)));
		
		$this->tweets = $saved_tweets;
		
		return true;
	}
	
	/**
	 * Uses XML to load the users tweets into the tweets array field.
	 *
	 * @return bool Returns false if no tweets were found.
	 */
	public function load() 
	{
		if (!$this->username) { return false; }
		
		$url = 'https://api.twitter.com/1/statuses/user_timeline.rss?screen_name='.$this->username;
		$xml = @simplexml_load_string(file_get_contents($url));
		if (!$xml) { return false; }
		
		if (empty($xml->channel->item)) {
			// No tweets.
			return false;
		}
		
		foreach ($xml->channel->item as $tweet) {
			$this->tweets[] = array(
				'content' => str_replace("\n", '', $tweet->title),
				'timestamp' => strtotime($tweet->pubDate),
				'status_url' => (string) $tweet->guid
			);
		}
		
		$this->saveToCache();
		
		return true;
	}
	
	/**
	 * Return a selected number of tweets.
	 *
	 * @param int $how_many The number of tweets to obtain.
	 * @param string $date_order The tweets will be in asc-ending or desc-ending order.
	 * @return bool|array Returns false if no tweets loaded.
	 */
	public function get($how_many=3, $date_order='asc') 
	{
		$tweets = $this->tweets;
		if (empty($tweets)) { return false; }
		if (!is_numeric($how_many)) { return false; }
		
		if ($date_order == 'desc') {
			$tweets = array_reverse($tweets);
		}

		if ($how_many > count($tweets)) { $how_many = count($tweets); }
		
		return array_slice($tweets, 0, $how_many);
	}
	
	/**
	 * Get all loaded tweets.
	 *
	 * @return bool|array If no tweets loaded, false is returned.
	 */
	public function getAll() 
	{
		if (empty($this->tweets)) { return false; }
		
		return $this->tweets;
	}

} // End of TwitterFeed class.
