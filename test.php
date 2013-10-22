<?php

require_once 'php-soundcloud/Services/Soundcloud.php';

$url = "https://api.soundcloud.com/tracks/87274383/comments.json?client_id=51295b2530f1b1654966c0b4b64eaeca&offset=8128&limit=10"

//create a client object with access token
$client = new Services_Soundcloud('51295b2530f1b1654966c0b4b64eaeca', '8afd5bde51d8477ab66329b38ef29b6e');

$comments_array = array();

$limit 			= 190;
$offset 		= 0;
$call_limit 	= 8000;
$sleep_time 	= 1;

$file = fopen("comments.txt","w");

for($i=0; $i<$call_limit; $i++)
{
	$data = $client->get('tracks/87274383/comments.json', array('limit' => $limit, 'offset' => $offset));

	if(empty($data) === TRUE)
	{
		break;
	}

	$data_decoded = json_decode($data);

	foreach($data_decoded as $item)
	{
		$comments_array[] = $item;

		$json = json_encode($item) . "\n";
		
		fwrite($file, $json);
	}

	echo("pulled $limit comments from offset $offset.   \n");

	$offset += $limit;

	//just so that soundcloud doesn't hate me for calling their api over and over
	sleep($sleep_time);
}

fclose($file);

?>