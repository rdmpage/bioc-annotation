<?php

// Crude JATS XML to BioC converter, extracts mark up and adds as annotations.

// https://www.ncbi.nlm.nih.gov/pmc/utils/oa/oa.fcgi?id=PMC6982432

//----------------------------------------------------------------------------------------
// Extract details from references
function ref($node, &$passage)
{
	global $xpath;
	
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)', $node) as $n)
	{
		$passage->infons->unstructured = $n->textContent;
	}	
	
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/person-group/name', $node) as $n)
	{
		$parts = array();
	
		$ncc = $xpath->query ('given-names', $n);
		foreach($ncc as $nc)
		{
			$given = $nc->firstChild->nodeValue;
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = trim($given);
			
			$parts[] = $given;
		}
		$ncc = $xpath->query ('surname', $n);
		foreach($ncc as $nc)
		{
			$family = $nc->firstChild->nodeValue;
			
			$parts[] = $family;
		}
		
		$passage->infons->author[] = join(' ', $parts);
	}
	
	// PLoS is flatter
	foreach($xpath->query('mixed-citation/name', $node) as $n)
	{
		$parts = array();
	
		$ncc = $xpath->query ('given-names', $n);
		foreach($ncc as $nc)
		{
			$given = $nc->firstChild->nodeValue;
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = preg_replace('/([A-Z])([A-Z])/u', '$1 $2', $given);
			$given = trim($given);
			
			$parts[] = $given;
		}
		$ncc = $xpath->query ('surname', $n);
		foreach($ncc as $nc)
		{
			$family = $nc->firstChild->nodeValue;
			
			$parts[] = $family;
		}
		
		$passage->infons->author[] = join(' ', $parts);
	}
	
	
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/article-title', $node) as $n)
	{
		$passage->infons->title = $n->textContent;
	}	

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/source', $node) as $n)
	{
		$passage->infons->source = $n->firstChild->nodeValue;
	}	
		
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/volume', $node) as $n)
	{
		$passage->infons->volume = $n->firstChild->nodeValue;
	}	

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/issue', $node) as $n)
	{
		$passage->infons->issue = $n->firstChild->nodeValue;
	}	

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/fpage', $node) as $n)
	{
		$passage->infons->fpage = $n->firstChild->nodeValue;
	}	
	
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/lpage', $node) as $n)
	{
		$passage->infons->lpage = $n->firstChild->nodeValue;
	}	

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/year', $node) as $n)
	{
		$passage->infons->year = $n->firstChild->nodeValue;
	}		

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/ext-link[@ext-link-type="doi"]/@xlink:href', $node) as $n)
	{
		$passage->infons->doi = $n->firstChild->nodeValue;
	}	

	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/pub-id[@pub-id-type="doi"]/@xlink:href', $node) as $n)
	{
		$passage->infons->doi = $n->firstChild->nodeValue;
	}	
	
	foreach($xpath->query('(element-citation|mixed-citation|nlm-citation)/ext-link[@ext-link-type="uri"]/@xlink:href', $node) as $n)
	{
		$passage->infons->url = $n->firstChild->nodeValue;
	}	
	
}

//----------------------------------------------------------------------------------------
// Recursively traverse DOM and process tags, add passages and annotations as we go
function dive($dom, $node, $passage = null)
{	
	global $count;
	global $depth;
	
	global $doc;
	
	//echo $doc->title_depth . "\n";
	
	$tag_name = $node->nodeName;
	
	// Tags for references can include <title> which gets conflated with section titles.
	if ($node->parentNode->nodeName == 'mixed-citation')
	{
		$tag_name = '';
	}
	
	// echo "tag_name = $tag_name\n";
		
	switch ($tag_name)
	{		
		case 'sec':
			$doc->title_depth++;
			break;
		
		//case 'label': // labels can't be passages as they break Pensoft
		case 'article-title':
		case 'title':
		case 'p':
		case 'tp:taxon-treatment':
		case 'ref':
			if (!$passage)
			{		
				$passage = new stdclass;			
				$passage->text = '';
				$passage->offset = $count;
				$passage->infons = new stdclass;
				$passage->annotations = array();
			}
			
			switch ($node->nodeName)
			{		
				case 'article-title':
				case 'title':
					$passage->infons->type = "title" . "_" . $doc->title_depth;
					break;
					
				case 'ref':					
					$passage->infons->section_type = "REF";
					$passage->infons->type = "ref";
					ref($node, $passage);
					break;
					
				case 'p':
				case 'tp:taxon-treatment':
				case 'label':
				default:
					$passage->infons->type = "paragraph";
					break;
			}
			
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
		case 'tp:taxon-name':	
			// echo '[' . $count . '] ' . $node->nodeValue . "\n";
			
			// These need special treatment as Pensoft can include additional tags
			// such as object-id which make processing tricky
			
			/*
				<tp:taxon-name><object-id content-type="arpha">3F003351-4A01-5C0A-BDF5-35CC77D4FC5A</object-id>
				  <tp:taxon-name-part taxon-name-part-type="genus" reg="Lappula">Lappula</tp:taxon-name-part>
				  <tp:taxon-name-part taxon-name-part-type="species" reg="effusa">effusa</tp:taxon-name-part>
				  <object-id content-type="ipni" xlink:type="simple">urn:lsid:ipni.org:names77343947-1</object-id>
				</tp:taxon-name>
			*/
			
			if (1)
			{				
				$taxon_name_parts = array();
				$start = 0;
				
				$children = $node->childNodes;
				foreach ($children as $child)
				{
					switch ($child->nodeName)
					{
						// skip over these
						case 'object-id':
							$count += mb_strlen($child->nodeValue, mb_detect_encoding($child->nodeValue));
							$count += 1; // add one for space
							break;
							
						case 'tp:taxon-name-part':
							//echo ">|" . $child->nodeValue . "|\n";
							
							if (trim($child->nodeValue) != '')
							{
								if ($start == 0)
								{
									$start = $count;								
								}
								$taxon_name_parts[] = $child->nodeValue;
							}
							break;
							
						default:
							break;
					}
				}
				
				//echo "start=$start\n";
				//print_r($a);
				
				if (count($taxon_name_parts) > 0)
				{
					
					$taxon_name = join(' ', $taxon_name_parts);
					
					$annotation = new stdclass;
					$annotation->text = $taxon_name;
					$annotation->infons = new stdclass;
					$annotation->infons->type = 'Species';
					$annotation->locations = array();
					
					$location = new stdclass;
					$location->offset = $start;
					$location->length = mb_strlen($taxon_name, mb_detect_encoding($taxon_name));
					$annotation->locations[] = $location;
									
					if ($doc->current)
					{
						$doc->current->annotations[] = $annotation;
					}
				}	
			}
			else
			{	
				// this works for most tp:taxon-name but not those with UUIDs and other 
				// identifiers				
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
			}					
			break;
			
		// named-content
		case 'named-content':		
			// echo '[' . $count . '] ' . $node->nodeValue . "\n";
						
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
			
			// print_r($attributes);
			//exit();
			
			$go = true;

			if (isset($attributes['content-type']))
			{
				switch ($attributes['content-type'])
				{
					// if we have both dwc:verbatimCoordinates and geo-json
					// we get two annotations with an identical span, and that breaks
					// our ability to render them in HTML
					case 'dwc:verbatimCoordinates':
						$type = 'Geo';
						$go = true;
						break;					
				
					case 'geo-json':
						$type = 'Geo';
						$go = false;
						break;
						
					case 'dwc:institutional_code':
					case 'institution':
						$type = 'Organisation';
						break;
						
					case 'kingdom':
					case 'order':
					case 'family':
						$type = 'Species';
						break;
						
					default:
						break;
				}
		    }	
		    		
		    if ($go)
		    {
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
			}			
						
			break;
			
		// external links
		case 'ext-link':		
			//echo '[' . $count . '] |' . $node->nodeValue . "|\n";			
			
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
			
			//print_r($attributes);
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
			//echo '#text=|' . $node->nodeValue . "|\n";
			if ($doc->current)
			{
				$doc->current->text .= $node->nodeValue . ' ';
				$count += mb_strlen($node->nodeValue, mb_detect_encoding($node->nodeValue));
				$count += 1;
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
	//echo str_pad('', (2 * $depth), ' ');
	
	switch ($tag_name)
	{
		case 'sec':
			$doc->title_depth--;
			break;
	
		//case 'label': // labels can't be passages as they break Pensoft
		case 'article-title':
		case 'title':
		case 'p':
		case 'tp:taxon-treatment':
		case 'ref':
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


//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: jats2bioc.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.xml$/', '', $filename);

$output_filename = $basename . '.json';


$xml = file_get_contents($filename);

// load XML and XPATH
$dom= new DOMDocument;
$dom->preserveWhiteSpace = true; // need this for tp:name to work
$dom->preserveWhiteSpace = false;
$dom->loadXML($xml, LIBXML_NOCDATA); // So we get text wrapped in <![CDATA[ ... ]]>
$xpath = new DOMXPath($dom);

// store output in $bioc
$bioc = new stdclass;
$bioc->id = null;

// first passage is the "front"

$front_passage = new stdclass;
$front_passage->infons = new stdclass;
$front_passage->infons->type = 'front';
$front_passage->offset = 0;
$front_passage->text = "";
$front_passage->annotations = array();

// pmcid
foreach($xpath->query('//front/article-meta/article-id[@pub-id-type="pmc"]') as $node)
{
    $bioc->pmcid = $node->firstChild->nodeValue;
    $front_passage->infons->pmcid = $bioc->pmcid;
    if ($bioc->id)
    {
    	$bioc->id = $bioc->pmcid;
    }
}

// pmid
foreach($xpath->query('//front/article-meta/article-id[@pub-id-type="pmid"]') as $node)
{
    $bioc->pmid = (Integer)$node->firstChild->nodeValue;
    $front_passage->infons->pmid = $bioc->pmid;
    if (!$bioc->id)
    {
    	$bioc->id = $bioc->pmid;
    }
}

// doi
foreach($xpath->query('//front/article-meta/article-id[@pub-id-type="doi"]') as $node)
{
    $bioc->doi = $node->firstChild->nodeValue;
    $front_passage->infons->doi = $bioc->doi;
    if (!$bioc->id)
    {
    	$bioc->id = $bioc->doi;
    }
}

// title (non-standard but will likely help)
foreach($xpath->query('//front/article-meta/title-group/article-title') as $node)
{
    $bioc->title = $node->textContent;    
    //$front_passage->text = $bioc->title;
}

// infons (units of information)
$bioc->infons = array();

// passages
$bioc->passages = array();

// accessions
$bioc->accessions = array();

// hacks for basic information about document (following PubTator)

// journal
foreach($xpath->query('//front/journal-meta/journal-title-group/journal-title') as $node)
{
    $bioc->journal = $node->firstChild->nodeValue;
}

// year

// @epub
foreach($xpath->query('//front/article-meta/pub-date[@pub-type="epub"]/year') as $node)
{
    $bioc->year = $node->firstChild->nodeValue;
}

// if we don't have an @epub date just grab the first year we can
if (!isset($bioc->year))
{
	foreach($xpath->query('//front/article-meta/pub-date/year') as $node)
	{
		$bioc->year = $node->firstChild->nodeValue;
	}
}

// authors
$bioc->authors = array();
foreach($xpath->query('//front/article-meta/contrib-group/contrib[@contrib-type="author"]') as $node)
{
	$name_parts = array();
	
	foreach($xpath->query('name/given-names', $node) as $subnode)
	{
		$name_parts[] = $subnode->firstChild->nodeValue;
	}
		
	foreach($xpath->query('name/surname', $node) as $subnode)
	{
		$name_parts[] = $subnode->firstChild->nodeValue;
	}
	
	$bioc->authors[] = join(" ", $name_parts);
}

// process document

// get text

$count = mb_strlen($front_passage->text, mb_detect_encoding($front_passage->text));
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

foreach ($xpath->query('//front/article-meta/title-group/article-title') as $node) {
    dive($dom, $node, $front_passage);
}


foreach ($xpath->query('//front/article-meta/abstract') as $node) {
    dive($dom, $node);
}

foreach ($xpath->query('//body') as $node) {
    dive($dom, $node);
}

foreach ($xpath->query('//back') as $node) {
    dive($dom, $node);
}

$bioc->passages = $doc->passages;

//print_r($bioc);

file_put_contents($output_filename, json_encode($bioc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
