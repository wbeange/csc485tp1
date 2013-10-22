<?php

	$client_id		= "51295b2530f1b1654966c0b4b64eaeca";
	$track_id		= 87274383;

	$url = "https://api.soundcloud.com/tracks/".$track_id.".json?client_id=".$client_id;

	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($curl);
	curl_close($curl);
	$data 	= json_decode($result, TRUE);	

	//Most results from our API are returned as a collection. 
	//The number of items in the collection returned is limited to 50 by default. 
	//Most endpoints support limit and offset parameters that allow your app to page through collections. 
	//When you receive 0 items in a response, you can assume that you've reached the end of the collection. 
	//The maximum value is 200 for limit and 8000 for offset.
	$comment_count 	= min($data["comment_count"], 8000);
	$comment_total 	= 0;

	$limit 			= 200;
	$offset 		= 0;

	$call_limit 	= ceil($comment_count / $limit);
	$sleep_time 	= 0.5;

	$file = fopen("comments.txt","w");

	for($i=0; $i<$call_limit; $i++)
	{
		$url = "https://api.soundcloud.com/tracks/".$track_id."/comments.json?client_id=".$client_id."&offset=".$offset."&limit=".$limit;

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		$result = curl_exec($curl);
		curl_close($curl);
		$data 	= json_decode($result, TRUE);	

		foreach($data as $item)
		{
			$json = json_encode($item) . "\n";			
			fwrite($file, $json);

			$comment_total++;

			if($comment_total >= $comment_count)
			{
				printf("done!\n");
				break 2;
			}
		}

		echo("pulled $limit comments from offset $offset... total comments: $comment_total / $comment_count \n");

		$offset += $limit;

		//just so that soundcloud doesn't hate me for calling their api over and over
		sleep($sleep_time);
	}

	fclose($file);

?>