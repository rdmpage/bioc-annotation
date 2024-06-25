<?php

// Add annotations to Bioc using simple taggers (e.g., regular expressions)
// Idea is to explore these taggers, and also to generate training data for more 
// sophisticated tools

/*
1,500–1,700 m elevation
840 m
400–500 m
738 m
3275–3350 m
1200 m alt.
alt. 549 m
*/

//----------------------------------------------------------------------------------------
// text is complete passage, highlight is bit being tagged, last_pos is last position 
// we tagged in this passage, offset is offset with respect to larger document
function annotation_location($text, $highlight, &$last_pos, $offset = 0)
{
	$flanking_length = 32;
	
	$location = new stdclass;
	
	// position
	$start = mb_strpos($text, $highlight, $last_pos, mb_detect_encoding($text));
	$length = mb_strlen($highlight, mb_detect_encoding($highlight));
	$end = $start + $length - 1;
		
	// bioc-style
	$location->offset = $start + $offset;
	$location->length = $length;
	
	// text loc
	$pre_length = min($start, $flanking_length);
	$pre_start = $start - $pre_length;
	
	$location->pre = mb_substr($text, $pre_start, $pre_length, mb_detect_encoding($text)); 
	
	$post_length = 	min(mb_strlen($text, mb_detect_encoding($text)) - $end, $flanking_length);		
			
	$location->post = mb_substr($text, $end + 1, $post_length, mb_detect_encoding($text)); 
			
	$last_pos = $end;
	
	return $location;
}

//----------------------------------------------------------------------------------------
// tag a date
function tag_date(&$passage)
{
	// 18 Sept. 2000
	// 22 Aug. 2009
	if (preg_match_all("/
			(?<hit>\d+\s+[A-Z][a-z]{2,3}\.?\s+[0-9]{4})
		/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		print_r($matches);
		
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match['hit'];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Date';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
			
			$passage->annotations[] = $annotation;
		}	
	}
	
	// 1 viii 2018
	if (preg_match_all("/
			(?<hit>\d+\s+[ivx]+\.?\s+[0-9]{4})
		/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		print_r($matches);
		
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match['hit'];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Date';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
			
			$passage->annotations[] = $annotation;
		}	
	}
	
}


//----------------------------------------------------------------------------------------
// tag a specimen barcode
function tag_barcode(&$passage)
{
	if (preg_match_all("/
			(?<hit>[A-Z]{1,4}\s*\d+\!?)
		/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		print_r($matches);
		
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match['hit'];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Specimen';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
			
			$passage->annotations[] = $annotation;
		}	
	}
}

//----------------------------------------------------------------------------------------
/**
 * @brief Convert degrees, minutes, seconds to a decimal value
 *
 * @param degrees Degrees
 * @param minutes Minutes
 * @param seconds Seconds
 * @param hemisphere Hemisphere (optional)
 *
 * @result Decimal coordinates
 */
function degrees2decimal($degrees, $minutes=0, $seconds=0, $hemisphere='N')
{
	$result = $degrees;
	$result += $minutes/60.0;
	$result += $seconds/3600.0;
	
	//echo "seconds=$seconds|<br/>";
	
	if ($hemisphere == 'S')
	{
		$result *= -1.0;
	}
	if ($hemisphere == 'W')
	{
		$result *= -1.0;
	}
	// Spanish
	if ($hemisphere == 'O')
	{
		$result *= -1.0;
	}
	// Spainish OCR error
	if ($hemisphere == '0')
	{
		$result *= -1.0;
	}
	
	return $result;
}

//----------------------------------------------------------------------------------------
function toPoint($matches)
{
	$feature = new stdclass;
	$feature->type = "Feature";
	$feature->geometry = new stdclass;
	$feature->geometry->type = "Point";
	$feature->geometry->coordinates = array();
			
	$degrees = $minutes = $seconds = 0;		
		
	if (isset($matches['latitude_seconds']))
	{
		$seconds = $matches['latitude_seconds'];
		
		if ($seconds == '')
		{
			$seconds = 0;
		}
		
	}
	$minutes = $matches['latitude_minutes'];
	$degrees = $matches['latitude_degrees'];
	
	$feature->geometry->coordinates[1] = degrees2decimal($degrees, $minutes, $seconds, $matches['latitude_hemisphere']);

	$degrees = $minutes = $seconds = 0;	
	
	if (isset($matches['longitude_seconds']))
	{
		$seconds = $matches['longitude_seconds'];
		
		if ($seconds == '')
		{
			$seconds = 0;
		}
	}
	$minutes = $matches['longitude_minutes'];
	$degrees = $matches['longitude_degrees'];
	
	$feature->geometry->coordinates[0] = degrees2decimal($degrees, $minutes, $seconds, $matches['longitude_hemisphere']);
	
	// ensures that JSON export treats coordinates as an array
	ksort($feature->geometry->coordinates);
	
	return $feature;
}

//----------------------------------------------------------------------------------------
// tag a date
function tag_geo(&$passage)
{

	$DEGREES_SYMBOL 		=  '[˚|°|º]';
	$MINUTES_SYMBOL			= '(\'|’|\′|\´)';
	$SECONDS_SYMBOL			= '("|\'\'|’’|”|\′\′|\´\´|″)';
	
	$INTEGER				= '\d+';
	$FLOAT					= '\d+([\.|,]\d+)?';
	
	$LATITUDE_DEGREES 		= '[0-9]{1,2}';
	$LONGITUDE_DEGREES 		= '[0-9]{1,3}';
	
	$LATITUDE_HEMISPHERE 	= '[N|S]';
	$LONGITUDE_HEMISPHERE 	= '[W|E]';
	
	$ES_LATITUDE_HEMISPHERE 	= '[N|S]';
	$ES_LONGITUDE_HEMISPHERE 	= '[O|E]';
	
	
	$flanking_length = 50;
	
	$results = array();
		
	if (preg_match_all("/
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<latitude_minutes>$FLOAT)
		\s*
		$MINUTES_SYMBOL?
		\s*
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		,?
		(\s+-)?
		;?
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL
		\s*
		(?<longitude_minutes>$FLOAT)
		\s*
		$MINUTES_SYMBOL?
		\s*
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		
	/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match[0];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Geo';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
				
			$annotation->infons->geojson = toPoint($match);				
			
			$passage->annotations[] = $annotation;
		}	
	}
	
	// 29.6° N, 101.8° E
	if (preg_match_all("/
		(?<latitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		\s*
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		,
		\s+
		(?<longitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		\s*
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)		
	/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match[0];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Geo';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
			
			$annotation->infons->geojson = toPoint($match);

			$passage->annotations[] = $annotation;
		}	
	}
	
	
	// N27.21234º, E098.69601º
	if (preg_match_all("/
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		(?<latitude_degrees>$FLOAT)
		$DEGREES_SYMBOL
		,
		\s+
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		(?<longitude_degrees>$FLOAT)
		$DEGREES_SYMBOL		
	/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		//print_r($matches);
		
		$last_pos = 0;
		
		foreach ($matches as $match)
		{
			$last_pos = 0;
	
			foreach ($matches as $match)
			{
				$annotation = new stdclass;
			
				$annotation->text = $match[0];
				$annotation->infons = new stdclass;
				$annotation->infons->type = 'Geo';

				$annotation->locations[] = annotation_location(
					$passage->text, 
					$annotation->text, 
					$last_pos,
					$passage->offset
					);

				$annotation->infons->geojson = toPoint($match);
			
				$passage->annotations[] = $annotation;
			}	
		}
	}
	
	// N25°59', E98°40'
	if (preg_match_all("/
		(?<latitude_hemisphere>$LATITUDE_HEMISPHERE)
		\s*
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<latitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
		,
		\s+
		(?<longitude_hemisphere>$LONGITUDE_HEMISPHERE)
		\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL		
		(?<longitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
	/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		//print_r($matches);
		
		$last_pos = 0;
		
		foreach ($matches as $match)
		{
			$last_pos = 0;
	
			foreach ($matches as $match)
			{
				$annotation = new stdclass;
			
				$annotation->text = $match[0];
				$annotation->infons = new stdclass;
				$annotation->infons->type = 'Geo';

				$annotation->locations[] = annotation_location(
					$passage->text, 
					$annotation->text, 
					$last_pos,
					$passage->offset
					);
			
				$annotation->infons->geojson = toPoint($match);

				$passage->annotations[] = $annotation;
			}	
		}
	}
	
	// Spanish https://doi.org/10.21068/c2018.v19s1a11
	// 4°19´44”N y 71°43´54.1”O
	if (preg_match_all("/
		(?<latitude_degrees>$LATITUDE_DEGREES)
		$DEGREES_SYMBOL
		(?<latitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		\s*
		(
		(?<latitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?
		\s*
		(?<latitude_hemisphere>$ES_LATITUDE_HEMISPHERE)		
		\s*y\s*
		(?<longitude_degrees>$LONGITUDE_DEGREES)
		$DEGREES_SYMBOL		
		(?<longitude_minutes>$INTEGER)
		$MINUTES_SYMBOL
		\s*
		(
		(?<longitude_seconds>$FLOAT)
		$SECONDS_SYMBOL
		)?		
		\s*
		(?<longitude_hemisphere>$ES_LONGITUDE_HEMISPHERE)
	/xu",  $passage->text, $matches, PREG_SET_ORDER))
	{
		$last_pos = 0;
	
		foreach ($matches as $match)
		{
			$annotation = new stdclass;
			
			$annotation->text = $match[0];
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Geo';

			$annotation->locations[] = annotation_location(
				$passage->text, 
				$annotation->text, 
				$last_pos,
				$passage->offset
				);
				
			$annotation->infons->geojson = toPoint($match);
			
			$passage->annotations[] = $annotation;
		}	
	}	
	
	
}


//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc2tagger.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.json$/', '', $filename);

$output_filename = $basename . '-tagged.json';

$json = file_get_contents ($filename);

$obj = json_decode($json);

foreach ($obj->passages as &$passage)
{
	print_r($passage);
	
	tag_date($passage);
	tag_barcode($passage);
	tag_geo($passage);
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>

