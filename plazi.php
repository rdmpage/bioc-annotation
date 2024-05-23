<?php


//----------------------------------------------------------------------------------------
// Recursively traverse DOM and process tags, add passages and annotations as we go
function dive($dom, $node)
{	
	global $count;
	global $depth;
	
	global $doc;
			
	switch ($node->nodeName)
	{				
		case 'paragraph':
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
		
		// taxonomic names
		case 'taxonomicName':		
			// echo '[' . $count . '] ' . $node->nodeValue . "\n";
			
			$annotation = new stdclass;
			$annotation->text = $node->nodeValue;
			$annotation->infons = new stdclass;
			$annotation->infons->type = 'Species';
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
			
			// tagged
		case 'collectingDate':
		
		case 'collectorName':
		
		case 'collectingCountry':
		case 'collectingCounty':
		case 'collectingRegion':
		case 'collectingMunicipality':

		case 'collectionCode':
		case 'specimenCode':
		case 'specimenCount':
				
		case 'geoCoordinate':
		case 'location':

		case 'quantity':
		case 'typeStatus':
		
		case 'collectionCode':
		case 'specimenCode':
		case 'specimenCount':
		
		case 'bibRefCitation':
			
			$attributes = array();
			if ($node->hasAttributes()) 
			{ 
				$attrs = $node->attributes; 
	
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
			}
			
			switch ($node->nodeName)
			{
				case 'collectingCountry':
				case 'collectingCounty':
				case 'collectingRegion':
				case 'collectingMunicipality':
				case 'location':
					$type = 'Locality';
					break;
			
				case 'collectingDate':
					$type = 'Date';
					break;
					
				case 'geoCoordinate':
					$type= 'Geo';
					break;

				case 'collectorName':
					$type= 'Person';
					break;

				case 'collectionCode':
				case 'specimenCode':
				case 'specimenCount':
					$type = 'Specimen';
					break;
					
				case 'bibRefCitation':
					$type = 'Cite';
					break;
					
				case 'quantity':
					$type = 'Quantity';
					break;

				case 'typeStatus':
					$type = 'Type';
					break;					
					
				default:
					$type = 'Unknown';
					break;
			}

			$annotation = new stdclass;
			$annotation->text = $node->nodeValue;
			$annotation->infons = new stdclass;
			$annotation->infons->type = $type;
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
			
		// external links
		case 'ext-link':		
			echo '[' . $count . '] |' . $node->nodeValue . "|\n";
			
			
			$attributes = array();
			if ($node->hasAttributes()) 
			{ 
				$attrs = $node->attributes; 
	
				foreach ($attrs as $i => $attr)
				{
					$attributes[$attr->name] = $attr->value; 
				}
			}
			
			$type = 'Unknown';
			
			print_r($attributes);
			//exit();

			if (isset($attributes['ext-link-type']))
			{
				switch ($attributes['ext-link-type'])
				{
					case 'gen':
						$type = 'Gene';
						break;

					default:
						break;
				}
		    }	
		    		
			$annotation = new stdclass;
			$annotation->text = $node->nodeValue;
			$annotation->infons = new stdclass;
			$annotation->infons->type = $type;
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
	
		case 'paragraph':
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

$filename = 'AB9508DD-9B19-52AC-AB50-564EF306D1BF.xml';

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

// //body
// //app-group
// //back
// //front

// Handle the various parts of an article


//$doc->passages[] = $front_passage;

foreach ($xpath->query('//treatment') as $node) {
    dive($dom, $node);
}


$bioc->passages = $doc->passages;

print_r($bioc);

file_put_contents($output_filename, json_encode($bioc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));


?>
