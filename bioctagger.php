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
	if (preg_match_all("/
			(?<hit>\d+\s+[A-Z][a-z]{2}\.?\s+[0-9]{4})
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

$basename = basename($filename, '.json');

$output_filename = $basename . '-tagged.json';

$json = file_get_contents ($filename);

$obj = json_decode($json);

foreach ($obj->passages as &$passage)
{
	print_r($passage);
	
	tag_date($passage);
	tag_barcode($passage);
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>

