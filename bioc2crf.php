<?php

// BioC to encoding of annotations for CRF

require_once (dirname(__FILE__) . '/encode.php');


//----------------------------------------------------------------------------------------
function passage_to_tokens($passage)
{
	$tokens = new stdclass;
	$tokens->text = array();
	$tokens->classification = array();

	$char_to_token = array();

	// We want to map string coordinates to tokens (which are split on spaces)
	// so we can apply any annotations to the string
	$num_chars = mb_strlen($passage->text, mb_detect_encoding($passage->text));

	$token_counter = 0;
	$tokens->classification[$token_counter] = 'O';
	$tokens->text[0] = '';

	for ($i = 0; $i < $num_chars; $i++)
	{
		$char = mb_substr($passage->text, $i, 1);
	
		if ($char == ' ')
		{
			$token_counter++;
			$tokens->classification[$token_counter] = 'O';
			$tokens->text[$token_counter] = '';
			$char_to_token[$i] = -1;
		}
		else
		{
			$char_to_token[$i] = $token_counter;
			$tokens->text[$token_counter] .= $char;
		}
	}

	foreach ($passage->annotations as $annotation)
	{
		foreach ($annotation->locations as $location)
		{
			$start = $location->offset - $passage->offset;
			$end = $start + $location->length;
		
			$token_begin = $char_to_token[$start];
		
			$class = 'B-' . strtoupper($annotation->infons->type);
		
			for ($j = $start; $j < $end; $j++)
			{
				// echo mb_substr($passage->text, $j, 1) . "\n";
			
				$token_number = $char_to_token[$j];
			
				if ($token_number != -1)
				{			
					if ($token_number != $token_begin)
					{
						$class = 'I-' . strtoupper($annotation->infons->type);
					}
			
					$tokens->classification[$token_number] = $class;
				}			
			}
		}
	}	
	
	return $tokens;

}


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

$basename = basename($filename, '.json');

$output_filename = $basename . '.data';
$template_filename = $basename . '.template';

$json = file_get_contents ($filename);

$obj = json_decode($json);

$output = '';
$template = '';

$result = null;

foreach ($obj->passages as $passage)
{
	$tokens = passage_to_tokens($passage);
	
	print_r($tokens);
	
	$result = encode($tokens->text, $tokens->classification);	
	
	$output .= join("\n", $result->features) . "\n\n";
	
	//print_r($result);
	


}

file_put_contents($output_filename, $output);
//file_put_contents($template_filename, $template);


?>
