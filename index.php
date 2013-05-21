<?php

include 'twitterfeed.class.php';

$feed = new TwitterFeed(array(
	'username' => 'jamesbailey272',
	'cache_file' => 'C:/wamp/www/test/cache_1.txt'
));

$tweets = $feed->getAll();

var_dump($tweets);