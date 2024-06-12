<?php

// Add annotations to Bioc for scientific names 

//----------------------------------------------------------------------------------------
function get($url, $format = '')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	switch ($info['http_code'])
	{
		case 404:
			echo "$url Not found\n";
			//exit();
			break;
			
		case 429:
			echo "Blocked\n";
			exit();
			break;
	
		default:
			break;
	}
	
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
// tag a taxonomic name
function tag_name(&$passage)
{
	if (1)
	{
		$passage->annotations = array();
	}

	$url = 'http://localhost:3000/api/find?text=' . urlencode($passage->text);
	
	$result_json = get($url);
	
	// echo $result_json . "\n";
	
	$result = json_decode($result_json, true);
	
	// print_r($result);
	
	if ($result)
	{
		foreach ($result as $hit)
		{
			$matched = $hit['name'];
		
			if (isset($hit['original']))
			{
				$matched = $hit['original'];		
			}
		
			$annotation = new stdclass;
			
			$annotation->text = $matched;
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Species';

			$location = new stdclass;
			$location->offset = $passage->offset + $hit['offsets'][0];
			$location->length = $hit['offsets'][1] - $hit['offsets'][0];
			
			$annotation->locations[] = $location;
			
			$passage->annotations[] = $annotation;
		}
	}

}

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc_name_tagger.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.json$/', '', $filename);

$output_filename = $basename . '-name-tagged.json';

$json = file_get_contents ($filename);

$obj = json_decode($json);

foreach ($obj->passages as &$passage)
{
	tag_name($passage);
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>

