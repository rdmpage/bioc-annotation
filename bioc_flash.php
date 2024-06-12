<?php

// Add annotations to Bioc using flash search of a trie

// Can construct trie of fly, or (eventually) load existing trie, e.g. for geography

require_once(dirname(__FILE__) . '/trie.php');


//----------------------------------------------------------------------------------------
// tag an entity in the trie
function tag_trie($trie, &$passage, $tag_type = 'Species')
{
	if (1)
	{
		$passage->annotations = array();
	}

	$hits  = $trie->flash($passage->text);
	
	foreach ($hits as $hit)
	{
		$annotation = new stdclass;
		
		$annotation->text = $hit->text;
		$annotation->infons = new stdclass;
		$annotation->infons->type = $tag_type;

		$location = new stdclass;
		$location->offset = $passage->offset + $hit->offsets[0];
		$location->length = $hit->offsets[1] - $hit->offsets[0];
		
		$annotation->locations[] = $location;
		
		$passage->annotations[] = $annotation;
	}
}


//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc_flash.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.json$/', '', $filename);

$output_filename = $basename . '-flash.json';

$json = file_get_contents ($filename);

$obj = json_decode($json);

// Load terms and create trie, or load existing trie

$terms = array(
'influenza A virus',
'H5 influenza A', 
'H5N2 subtype',
'H5N2',
'mallard',
'mallards',
'wigeon',
'teal',
'red-breasted merganser',
);

$trie = new Trie();

foreach ($terms as $term)
{
	$term_obj = new stdclass;
	$term_obj->name = $term;
	$trie->add($term_obj);
}

foreach ($obj->passages as &$passage)
{
	tag_trie($trie, $passage);
	
	print_r($passage);
	
	
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>

