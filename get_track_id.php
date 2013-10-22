<?php

	//Call SoundCloud API with track URL copy and pasted from web browser.
	//Return data includes URL to get info about the URL
	$url = "http://api.soundcloud.com/resolve.json?url=https://soundcloud.com/aviciiofficial/avicii-promotional-mix-2013&client_id=51295b2530f1b1654966c0b4b64eaeca";

	$curl 	= curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($curl);
	curl_close($curl);
	$data 	= json_decode($result, TRUE);


	//Call SoundCloud API with returned URL in order to get internal Track ID.
	$curl 	= curl_init($data["location"]);
	curl_setopt($curl, CURLOPT_HEADER, 0);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($curl);
	curl_close($curl);
	$data 	= json_decode($result, TRUE);

	printf("Track ID: ".$data["id"]."\n");

?>