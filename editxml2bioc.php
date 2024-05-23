<?php

// Convert simple XML that we manual edit to Bioc JSON

//----------------------------------------------------------------------------------------
// Recursively traverse DOM and process tags, add passages and annotations as we go
function dive($dom, $node)
{	
	global $count;
	global $depth;
	
	global $doc;
			
	switch ($node->nodeName)
	{				
		case 'passage':
			$passage = new stdclass;			
			$passage->text = '';
			$passage->offset = $count;
			$passage->infons = new stdclass;
			$passage->infons->type = "paragraph";
			
			$passage->annotations = array();
			
			$doc->passages[] = $passage;
			
			$doc->stack[] = $passage;
			
			$doc->current = $passage;
			
			/*
			$depth++;	
			echo str_pad('', (2 * $depth), ' ');			
			echo "push " . $node->nodeName . ' [' . count($doc->stack) . "]\n";
			*/
			
			break;
			
		// annotations
		
		// mark (default)
		case 'mark':		
			// echo '[' . $count . '] ' . $node->nodeValue . "\n";
			
			$annotation = new stdclass;
			$annotation->text = $node->nodeValue;
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'mark';
			$annotation->locations = array();
			
			$location = new stdclass;
			$location->offset = $count;
			$location->length = mb_strlen($node->nodeValue, mb_detect_encoding($node->nodeValue));
			$annotation->locations[] = $location;
						
			if ($doc->current)
			{
				$doc->current->annotations[] = $annotation;
			}						
			break;
		
			
		case '#text':
			// echo '#text=|' . $node->nodeValue . "|\n";
			if ($doc->current)
			{
				$doc->current->text .= $node->nodeValue;
				$count += mb_strlen($node->nodeValue, mb_detect_encoding($node->nodeValue));
			}
			break;
						
		default:
			break;
	
	
	}
	
	// Visit any children of this node
	if ($node->hasChildNodes())
	{
		foreach ($node->childNodes as $children) {
			dive($dom, $children);
		}
	}
	
	// leaving
	
	$depth--;
	echo str_pad('', (2 * $depth), ' ');
	
	switch ($node->nodeName)
	{
	
		case 'passage':
			array_pop($doc->stack);
						
			/*
			$depth--;	
			echo str_pad('', (2 * $depth), ' ');			
			echo "pop " . $node->nodeName . ' [' . count($doc->stack) . "]\n";
			*/
			
			$stack_count = count($doc->stack);
			if ($stack_count > 0)
			{
				$doc->current = $doc->stack[$stack_count - 1];
			}
			else
			{
				$doc->current = null;
			}
			
			break;
						
		default:
			break;
	
	
	}
	
	
}

$filename = 'z.xml';

$basename = basename($filename, '.xml');

$output_filename = $basename . '.json';


$xml = file_get_contents($filename);

// load XML and XPATH
$dom= new DOMDocument;
$dom->preserveWhiteSpace = false; // need this for tp:name to work
$dom->loadXML($xml, LIBXML_NOCDATA); // So we get text wrapped in <![CDATA[ ... ]]>
$xpath = new DOMXPath($dom);

// store output in $bioc
$bioc = new stdclass;
$bioc->id = null;

// infons (units of information)
$bioc->infons = array();

// passages
$bioc->passages = array();

// accessions
$bioc->accessions = array();

$count = 0;
$depth = 0;

$doc = new stdclass;
$doc->stack = array();
$doc->current = null;
$doc->title_depth = 0;
$doc->passages = array();

foreach ($xpath->query('//text') as $node) {
    dive($dom, $node);
}


$bioc->passages = $doc->passages;

print_r($bioc);

file_put_contents($output_filename, json_encode($bioc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


?>
