<?php

// BioC to simple XML intended for editing. Note that we use tags to markup
// annotations, and we ensure tags include whole token by extending annotation to
// before and after whitespace. Tags derived from JATS XML or other sources may
// match a part of a token (for example by excluding punctuation).


$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc2crf.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.json$/', '', $filename);

$output_filename = $basename . '.xml';

$json = file_get_contents ($filename);

$obj = json_decode($json);

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<data>
';


foreach ($obj->passages as $passage)
{
	// get annotation coordinates so we can add to the text
	
	$open = array();
	$close = array();
	
	$passage_length = mb_strlen($passage->text);
	
	foreach ($passage->annotations as $annotation)
	{
		foreach ($annotation->locations as $location)
		{
			$start = $location->offset - $passage->offset;
			$end = $start + $location->length - 1;
			
			// extend span because we want to split on spaces (to match tokenisation)
			if (1)
			{
				$before = $start;
				while ($before > 1 && mb_substr($passage->text, $before - 1, 1) != ' ')
				{
					$before--;
				}
				$start = $before;
				
				$after = $end;
				while ($after < ($passage_length - 1) && mb_substr($passage->text, $after + 1, 1) != ' ')
				{
					$after++;
				}
				
				$end = $after;
			}
			
			
			if (!isset($open[$start]))
			{
				$open[$start] = array();
			}
			
			$open[$start][] = $annotation->infons->type;

			if (!isset($close[$end]))
			{
				$close[$end][] = $annotation->infons->type;
			}
		}
	
	}
	
	// print_r($open);
	//print_r($close);
	
	// output
	$xml .= '<passage>';
	
	
	$content_length = mb_strlen($passage->text);
	
	for ($i = 0; $i < $content_length; $i++)
	{
		$char = mb_substr($passage->text, $i, 1); 
		
		if (isset($open[$i]))
		{
			foreach ($open[$i] as $type)
			{
				switch ($type)
				{
					default:
						$xml .= '<mark>';
						break;
				}
			}		
		}
		
		$xml .= htmlspecialchars($char, ENT_XML1 | ENT_COMPAT, 'UTF-8');
	
		if (isset($close[$i]))
		{
			foreach ($close[$i] as $type)
			{
				switch ($type)
				{
					default:
						$xml .= '</mark>';
						break;
				}
			}		
		}
	
	}
	
	$xml .= '</passage>';
	$xml .= "\n";
	
}

$xml .= '</data>';
$xml .= "\n";

echo $xml;

// file_put_contents($output_filename, $output);




?>
