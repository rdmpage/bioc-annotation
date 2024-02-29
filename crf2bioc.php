<?php

// CRF output to passages

//----------------------------------------------------------------------------------------
function new_passage($text)
{
	$passage = new stdclass;
	$passage->text = $text;
	$passage->infons = new stdclass;
	$passage->infons->type = "paragraph";
	$passage->offset = 0; 
	$passage->annotations = array();
	
	return $passage;
}

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: crf2biocf.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = basename($filename, '.data');
$output_filename = $basename . '-annotated.json';


$bioc = new stdclass;
$bioc->passages = array();

$text = array();
$label = array();


$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
	
	if (!feof($file_handle))
	{
	
		if ($line == "")
		{
			$passage = new_passage(join(' ', $text));
		
			$n = count($text);
		
			$annotation = null;
		
			$offset = 0;
			for ($i = 0; $i < $n; $i++)
			{
				// New annotation?
				if (preg_match('/^B-(.*)/', $label[$i], $m))
				{
					if ($annotation)
					{
						$passage->annotations[] = $annotation;					
						$annotation = null;
					
					}
				
					$annotation = new stdclass;
					$annotation->text = $text[$i];
					$annotation->infons = new stdclass;
					$annotation->infons->type = mb_convert_case($m[1], MB_CASE_TITLE);
					$annotation->locations = array();
					$location = new stdclass;
					$location->offset = $offset;
					$location->length = mb_strlen($text[$i], mb_detect_encoding($text[$i]));
					$annotation->locations[] = $location;				
				}
				// extending existing annotation?
				else if (preg_match('/^I-(.*)/', $label[$i]))
				{
					$annotation->text .= ' ' . $text[$i];
					$annotation->locations[0]->length += mb_strlen($text[$i], mb_detect_encoding($text[$i])) + 1;
				}
				// no annotation
				else
				{
					if ($annotation)
					{
						$passage->annotations[] = $annotation;	
					}
					$annotation = null;
				}
		
				$offset += mb_strlen($text[$i], mb_detect_encoding($text[$i]));
				$offset++;
			}
		
			$bioc->passages[] = $passage;
	
			$text = array();
			$label = array();
		}
		else
		{
			$values = preg_split('/\s+/', $line);
			$tag = array_pop($values);

			$text[] = $values[0];
			$label[] = $tag;	
		}
	}
	
}	

print_r($bioc);

file_put_contents($output_filename, json_encode($bioc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
