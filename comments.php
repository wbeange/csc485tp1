<?php

	require_once 'php-soundcloud/Services/Soundcloud.php';

	/**
	* Get the comments from the text file and return it in an array.
	*/
	function getCommentsArray()
	{
		$comments_array = array();
		$file 			= fopen("comments.txt", "r");

		while(!feof($file))
		{
		    $line = fgets($file);

		    #remove new line, convert string to array (TRUE makes array, not object)
		    $comments_array[] = json_decode(substr($line, 0, -1), TRUE);
		}
		
		fclose($file);

		return $comments_array;
	}

	/**
	* Get the sentiment dictionary from the file and return index dictionary.
	*/
	function getSentimentsArray()
	{
		$sentiments_array 	= array();
		$file 				= fopen("AFINN-111.txt", "r");

		while(!feof($file))
		{
		    $line = fgets($file);
		    list($word, $value) = explode("\t", $line);
		    $sentiments_array[(string)$word] = (int)$value;
		}
		
		fclose($file);		

		return $sentiments_array;
	}

	/**
	* Build positive words dictionary.
	*/	
	function getPositiveWordsArray()
	{
		return getWordsArray("positive_words.txt");
	}

	/**
	* Build negative words dictionary.
	*/	
	function getNegativeWordsArray()
	{
		return getWordsArray("negative_words.txt");
	}

	/**
	* Build words dictionary.
	*/
	function getWordsArray($file)
	{
		$sentiments_array 	= array();
		$file 				= fopen($file, "r");

		while(!feof($file))
		{
		    $word = fgets($file);
		    $sentiments_array[(string)$word] = (string)$word;
		}
		
		fclose($file);		

		return $sentiments_array;		
	}

	/**
	* Clean up a phrase, make it ready for analysis.
	* Put all lower case.
	* TODO: Consider a slang translator.
	*/
	function cleanUpPhrase($phrase)
	{	
		return strtolower($phrase);
	}

	/**
	* Calculate phrase sentiment.
	* Strategy 1: Sum up sentiment scores.
	*/
	function sumPhraseSentiment($phrase, $sentiments_array)
	{
		$sentiment = 0;
		$words = explode(" ", $phrase);

		foreach($words as $word)
		{
			if(isset($sentiments_array[$word]) === TRUE)
			{
				$sentiment += $sentiments_array[$word];
			}
		}
	
		return $sentiment;
	}

	/**
	* Calculate phrase sentiment.
	* Strategy 2: Average sentiment scores.
	*/
	function averagePhraseSentiment($phrase, $sentiments_array)
	{
		$sentiment_avg 			= 0;
		$sentiment_word_count 	= 0;

		$words = explode(" ", $phrase);

		foreach($words as $word)
		{
			if(isset($sentiments_array[$word]) === TRUE)
			{
				$sentiment_word_count++;

				$sentiment_avg += $sentiments_array[$word];
			}
		}

		if($sentiment_word_count > 0)
		{
			$sentiment_avg = ceil($sentiment_avg / $sentiment_word_count);
		}
	
		return $sentiment_avg;
	}

	/**
	* Calculate phrase sentiment.
	* Strategy 3: Positive - Negative words.
	*/
	function positiveMinusNegativePhraseSentiment($phrase, $positive_words_array, $negative_words_array)
	{
		$positive_words = 0;
		$negative_words = 0;

		$words = explode(" ", $phrase);

		foreach($words as $word)
		{
			if(isset($positive_words_array[$word]) === TRUE)
			{
				$positive_words++;
			}
			elseif(isset($negative_words_array[$word]) === TRUE)
			{
				$negative_words++;
			}
		}

		return ($positive_words - $negative_words);
	}

	/**
	* Calculate the happiest comments, using a number of strategies.
	*
	* @param $comment_array 		- Track comments
	* @param $sentiments_array 		- Sentiments dictionary
	* @param $positive_words_array 	- Positive word dictionary
	* @param $negative_words_array 	- Negative word dictionary
	* @param $strategy 				- Strategy 1. Use sentiments dictionary and sum up sentiment words.
	* @param $strategy 				- Strategy 2. Use sentiments dictionary and average sentiment words.
	* @param $strategy 				- Strategy 3. Use positive and negative word dictionaries and subtract the number of negative words
	* @param $count 				-
	*
	*/
	function calculateCommentSentiment($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, $strategy=1, $count=10)
	{
		$comment_sentiments_array = array();

		#calculate each sentiment
		foreach($comments_array as $id => $comment)
		{
			if($strategy == 1)
			{
				$score = sumPhraseSentiment(cleanUpPhrase($comment["body"]), $sentiments_array);
			}
			elseif($strategy == 2)
			{
				$score = averagePhraseSentiment(cleanUpPhrase($comment["body"]), $sentiments_array);
			}
			else
			{
				$score = positiveMinusNegativePhraseSentiment(cleanUpPhrase($comment["body"]), $positive_words_array, $negative_words_array);
			}
			

			$comment_sentiments_array[] = array(
				'id'	=> $id,
				'score'	=> $score,
			);
		}		

		return $comment_sentiments_array;
	}

	/**
	* Get happiest comments.
	*/
	function getHappiestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, $strategy=1, $count=10)
	{
		$comment_sentiments_array = calculateCommentSentiment($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, $strategy, $count);

		//sort happiest desc
		usort($comment_sentiments_array, function($a, $b)
		{
			if($a['score'] > $b['score'])
			{
				return -1;
			}
			elseif($a['score'] < $b['score'])
			{
				return 1;
			}
			else
			{
				return 0;
			}
		});

		//slice array to get happiest
		$comment_sentiments_array = array_slice($comment_sentiments_array, 0, $count);

		//print output
		if($strategy == 1)
		{
			printf("*** Happiest Comments Strategy ONE: ***\n\n");
		}
		elseif($strategy == 2)
		{
			printf("*** Happiest Comments Strategy TWO: ***\n\n");
		}
		else
		{
			printf("*** Happiest Comments Strategy THREE: ***\n\n");
		}
		
		foreach($comment_sentiments_array as $data)
		{
			print($comments_array[$data['id']]["body"] . "\n");
		}
		printf("\n");
	}

	/**
	* Calculate the most negative comments.
	*/
	function getNegativestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, $strategy=1, $count=10)
	{
		$comment_sentiments_array = calculateCommentSentiment($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, $strategy, $count);

		//sort negative desc
		usort($comment_sentiments_array, function($a, $b)
		{
			if($a['score'] > $b['score'])
			{
				return 1;
			}
			elseif($a['score'] < $b['score'])
			{
				return -1;
			}
			else
			{
				return 0;
			}
		});

		//get most negative
		$comment_sentiments_array = array_slice($comment_sentiments_array, 0, $count);

		//print output
		if($strategy == 1)
		{
			printf("*** Most Negative Comments Strategy ONE: ***\n\n");
		}
		elseif($strategy == 2)
		{
			printf("*** Most Negative Comments Strategy TWO: ***\n\n");
		}
		else
		{
			printf("*** Most Negative Comments Strategy THREE: ***\n\n");
		}		

		foreach($comment_sentiments_array as $data)
		{
			print($comments_array[$data['id']]["body"] . "\n");
		}
		printf("\n");
	}

	/**
	* Who is the artist’s biggest SoundCloud fan?
	* - look at how many comments a user has posted on the track
	*/
	function getLoudestTrackFan($comments_array, $sentiments_array, $loudest_count=1)
	{
		$users_array = array();

		#sum up user comment count
		foreach($comments_array as $comment)
		{
			if(isset($users_array[$comment["user"]["id"]]) === FALSE)
			{
				$users_array[$comment["user"]["id"]] = 0;
			}

			$users_array[$comment["user"]["id"]]++;
		}

		#get user with most comments
		uasort($users_array, function($a, $b)
		{
			if($a < $b)
			{
				return 1;
			}
			elseif($a > $b)
			{
				return -1;
			}
			else
			{
				return 0;
			}
		});

		#analyze loudest users comments
		printf("\nLoudest user's comments:\n\n");
		$loudest_displayed = 0;
		
		foreach($users_array as $user_id => $count)
		{
			printf("User id ".$user_id." (".$count." comment(s)\n");
			foreach($comments_array as $comment)
			{
				if($comment["user"]["id"] == $user_id)
				{
					printf($comment["body"]."\n");
				}
			}
			$loudest_displayed++;
			if($loudest_count == $loudest_displayed)
			{
				break;
			}
			printf("\n\n");
		}
		printf("\n");
	}

	/**
	* Get the most positive track fan, as defined by the user
	* who posts the most sentimental comments (summed up)
	* And the print out those comments so that we can see what they are for
	* for further analysis.
	*/
	function getMostPositiveTrackFan($comments_array, $sentiments_array, $fan_count=1)
	{
		$users_array = array();

		#sum up user comment count
		foreach($comments_array as $comment)
		{
			if(isset($users_array[$comment["user"]["id"]]) === FALSE)
			{
				$users_array[$comment["user"]["id"]] = 0;
			}

//TODO
			//$sentiment = calculatePhraseSentiment($comment['body'], $sentiments_array);

			$users_array[$comment["user"]["id"]] += $sentiment;
		}

		#get user with most sentiment total
		uasort($users_array, function($a, $b)
		{
			if($a < $b)
			{
				return 1;
			}
			elseif($a > $b)
			{
				return -1;
			}
			else
			{
				return 0;
			}
		});

		#analyze most positive users comments
		printf("\nMost positive user's comments:\n\n");
		$displayed = 0;
		
		foreach($users_array as $user_id => $count)
		{
			printf("User id ".$user_id."\n");
			foreach($comments_array as $comment)
			{
				if($comment["user"]["id"] == $user_id)
				{
					printf($comment["body"]."\n");
				}
			}
			$displayed++;
			if($fan_count == $displayed)
			{
				break;
			}
			printf("\n\n");
		}

		printf("\n");
	}

	function getMostNegativeTrackFans($comments_array, $sentiments_array, $fan_count=1)
	{
		$users_array = array();

		#sum up user comment count
		foreach($comments_array as $comment)
		{
			if(isset($users_array[$comment["user"]["id"]]) === FALSE)
			{
				$users_array[$comment["user"]["id"]] = 0;
			}

//TODO			
			//$sentiment = calculatePhraseSentiment($comment['body'], $sentiments_array);

			$users_array[$comment["user"]["id"]] += $sentiment;
		}

		#get user with least sentiment total
		uasort($users_array, function($a, $b)
		{
			if($a < $b)
			{
				return -1;
			}
			elseif($a > $b)
			{
				return 1;
			}
			else
			{
				return 0;
			}
		});

		#analyze least positive users comments
		printf("\nMost negative user's comments:\n\n");
		$displayed = 0;
		
		foreach($users_array as $user_id => $count)
		{
			printf("User id ".$user_id."\n");
			foreach($comments_array as $comment)
			{
				if($comment["user"]["id"] == $user_id)
				{
					printf($comment["body"]."\n");
				}
			}
			$displayed++;
			if($fan_count == $displayed)
			{
				break;
			}
			printf("\n\n");
		}
		printf("\n");
	}

	/**
	* Calculate the country with the most comment participation.
	*/
	function getLoudestCountry($comments_array, $display=1)
	{
		$country_array = array();

		foreach($comments_array as $comment)
		{
			var_dump($comment);
			break;
			
			//TODO: Would need to get user data.
			//See if SoundCloud API has a primed get multi user call.
		}


		return $country_array;
	}

	function getMostPositiveCountry()
	{
		return FALSE;
	}

	/**
	* The goal of this question is to determine the most
	* popular moment in the song. As each comment correlates to a moment
	* in time, I would like to see if there is a link between song time and comment
	* frequency and sentiment. Maybe users like to comment at the beginning or the end
	* of the song. Maybe users like to comment on particular sections.
	* 
	* @param $interval_slice - I am going to divide the track into segments of this number of seconds
	*/
	function getMostPopularTrackTime($comments_array, $interval_slice=5, $display_count=1)
	{
		$client 	= new Services_Soundcloud('51295b2530f1b1654966c0b4b64eaeca', '8afd5bde51d8477ab66329b38ef29b6e');
		$data_json 	= $client->get('tracks/87274383.json', array());
		$data_array = json_decode($data_json, TRUE);

		//get track time in seconds
		$track_dur = ($data_array["duration"] / 1000);
		
		$time_array = array();

		//sort comments by their time correlation
		foreach($comments_array as $c_id => $comment)
		{
			//associated timestamp in seconds
			$ts = ($comment["timestamp"] / 1000);

			$interval = floor($ts / $interval_slice);

			if(isset($time_array[$interval]) === FALSE)
			{
				$time_array[$interval] = array(
					'count' => 0,
					'ids' 	=> array(),
				);				
			}

			$time_array[$interval]['count']++;
			$time_array[$interval]['ids'][] = $c_id;
		}

		//find most concentrated interval
		uasort($time_array, function($a, $b){
			if($a['count'] < $b['count'])
			{
				return 1;
			}
			elseif($a['count'] > $b['count'])
			{
				return -1;
			}
			else
			{
				return 0;
			}
		});

		$displayed = 0;

		printf("\nMost commented parts of the track:\n\n");
		
		foreach($time_array as $interval => $data)
		{
			$start_sec = ($interval * $interval_slice);
			$end_sec = (($interval * $interval_slice) + $interval_slice);

			$start 	= gmdate("H:i:s", $start_sec);
			$end 	= gmdate("H:i:s", $end_sec);

			print("Time: ".$start." - ".$end.", Comment count:".$data['count']."\n");

			$displayed++;
			if($display_count == $displayed)
			{
				break;
			}
		}
		printf("\n");
	}

	function getLeastPopularTrackTime()
	{

	}

	function aviciiPromoMixMostPopularTrack($comments_array)
	{
		/*
		0:00 	- Avicii feat. Linnea Henriksson - Hope There's Someone
		04:40 	- Avicii feat. Marie Orsted - Dear Boy  
		10:30 	- Avicii & Nicky Romero - I Could Be The One (Nicktim) (Vocal Mix) 
		12:00	- Avicii & Nicky Romero - I Could Be The One (Nicktim) (DubVision Remix)
		15:40	- Dave Armstrong - Make Your Move
		16:35	- Avicii & Sebastien Drums - My Feelings For You
		19:00	- Avicii feat. Audra Mae - Addicted To You
		21:20	- Adrian Lux - Teenage Crime (Axwell & Henrik B Remode)
		24:30	- Avicii feat. Aloe Blacc - Wake Me Up
		28:30	- Pryda - Shadows
		32:00	- Nina Simone - Sinnerman (Felix Da Housecat Heavenly House Mix) 
		34:00	- Avicii - Heart Upon My Sleeve
		38:00	- Avicii feat. Aloe Blacc & Mac Davis - ID (Black And Blue)
		40:30	- Avicii feat. Joakim Berg - Stars
		41:50	- ASH - Let Me Show You Love (ASH & Avicii Hype Machine Mix)
		47:40	- Avicii feat. Salem Al Fakir - You Make Me
		52:00	- Avicii feat. Audra Mae - Long Road To Hell
		54:40	- Ivan Gough & Feenixpawl feat. Georgi Kay - In My Mind (Axwell Remix)
		55:00	- Avicii feat. Dan Tyminski - Hey Brother
		*/

		//start timestamp => song name
		$avicii_promo_mix_array = array(
			0 		=> "Avicii feat. Linnea Henriksson - Hope There's Someone",
			280		=> "Avicii feat. Marie Orsted - Dear Boy ",
			630		=> "Avicii & Nicky Romero - I Could Be The One (Nicktim) (Vocal Mix)",
			720		=> "Avicii & Nicky Romero - I Could Be The One (Nicktim) (DubVision Remix)",
			940		=> "Dave Armstrong - Make Your Move",
			995		=> "Avicii & Sebastien Drums - My Feelings For You",
			1140	=> "Avicii feat. Audra Mae - Addicted To You",
			1280	=> "Adrian Lux - Teenage Crime (Axwell & Henrik B Remode)",
			1470	=> "Avicii feat. Aloe Blacc - Wake Me Up",
			1710	=> "Pryda - Shadows",
			1920	=> "Nina Simone - Sinnerman (Felix Da Housecat Heavenly House Mix)",
			2040	=> "Avicii - Heart Upon My Sleeve",
			2280	=> "Avicii feat. Aloe Blacc & Mac Davis - ID (Black And Blue)",
			2430	=> "Avicii feat. Joakim Berg - Stars",
			2510	=> "ASH - Let Me Show You Love (ASH & Avicii Hype Machine Mix)",
			2860	=> "Avicii feat. Salem Al Fakir - You Make Me",
			3120	=> "Avicii feat. Audra Mae - Long Road To Hell",
			3280	=> "Ivan Gough & Feenixpawl feat. Georgi Kay - In My Mind (Axwell Remix)",
			3300	=> "Avicii feat. Dan Tyminski - Hey Brother",
		);

		$song_count_array = array();

		//sort comments by track
		foreach($comments_array as $comment)
		{
			$ts = ($comment["timestamp"] / 1000);

			$prev_ts = FALSE;

			foreach($avicii_promo_mix_array as $start_ts => $name)
			{
				if($prev_ts === FALSE)
				{
					$prev_ts = $start_ts;
					continue;
				}
				else
				{
					if($ts <= $start_ts)
					{
						if(isset($song_count_array[$prev_ts]) === FALSE)
						{
							$song_count_array[$prev_ts] = 0;
						}
						$song_count_array[$prev_ts]++;
						break;
					}

					$prev_ts = $start_ts;
				}
			}
		}

		//display most popular desc
		//sorts by value, maintains key association, orders high to low
		arsort($song_count_array);

		printf("\nAvicii Mix New Song Ranking:\n\n");

		foreach($song_count_array as $song_id => $count)
		{
			printf("Comments: ".$count.", Song name: ".$avicii_promo_mix_array[$song_id]."\n");
		}

		printf("\n");
	}

	function main()
	{
		/*
		How consistent has activity been since the track was released?
		*/

		//Get comments in naturally index array
		$comments_array 	= getCommentsArray();

		//Get sentiments keyed dictionary
		$sentiments_array 	= getSentimentsArray();

		//Get word dictionaries
		$positive_words_array = getPositiveWordsArray();
		$negative_words_array = getNegativeWordsArray();

		//1. What are the happiest comments?
		getHappiestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 1, 10);
		#getHappiestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 2, 10);
		#getHappiestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 3, 10);

		//2. What are the most negative comments?
		getNegativestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 1, 10);
		#getNegativestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 2, 10);
		#getNegativestComments($comments_array, $sentiments_array, $positive_words_array, $negative_words_array, 3, 10);

		//3. Who is the artist’s biggest SoundCloud fan?
		#getLoudestTrackFan($comments_array, $sentiments_array, 10);
		#getMostPositiveTrackFan($comments_array, $sentiments_array, 10);		
		
		//4. Who is the artist’s biggest SoundCloud hater?
		#getMostNegativeTrackFans($comments_array, $sentiments_array, 10);

		//5. What is the most popular moment in the track?
		#getMostPopularTrackTime($comments_array, 5, 25);

		//8. The 1 hour set plays all 10 of the new Avicii tracks. Which is the most popular?
		#aviciiPromoMixMostPopularTrack($comments_array);

		//6. What country likes the artist the most?
		//What country likes the artist the most?
		//What country hates the artist the most?
		#getLoudestCountry($comments_array, 10);
		#getMostPositiveCountry();
		#getMostNegativeCountry();

		//7. How consistent has activity been since the track was released?

	}

	main();

?>