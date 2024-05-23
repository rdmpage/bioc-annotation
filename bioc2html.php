<?php

// Parse a biocjson file and generate simple HTML, loosely modelled on PubTator output

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc2html.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = basename($filename, '.json');

$output_filename = $basename . '.html';

$json = file_get_contents ($filename);

$obj = json_decode($json);


$html = '<html>
<head>
<style>
body {
	padding:1em;
	margin:1em;
	font-family: sans-serif;
	font-size: 1em;
	line-height: 1.8em;
}

h1 {
	font-size: 2em;
	line-height: 2em;
}
</style>

<link rel="stylesheet" href="pubtator.css">
<link rel="stylesheet" href="extra.css">

</head>
<body>';

foreach ($obj->passages as $passage)
{
	// echo $passage->text . "\n";
	
	// get annotation coordinates so we can add to the text
	
	$open = array();
	$close = array();
	
	foreach ($passage->annotations as $annotation)
	{
		foreach ($annotation->locations as $location)
		{
			$start = $location->offset - $passage->offset;
			$end = $start + $location->length - 1;
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
	
	switch ($passage->infons->type)
	{
		case 'front':
		case 'title_0':
			$html .= '<h1>';
			break;

		case 'abstract_title_1':
			$html .= '<h3>';
			break;

		case 'title':		
		case 'title_1':
			$html .= '<h2>';
			break;
			
		case 'title_2':
			$html .= '<h3>';
			break;

		case 'title_3':
			$html .= '<h4>';
			break;			
	
		default:
			$html .= '<p>';
			break;
	}
	
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
						$html .= '<span class="' . $type . '">';
						break;
				}
			}		
		}
		
		$html .= $char;
	
		if (isset($close[$i]))
		{
			foreach ($close[$i] as $type)
			{
				switch ($type)
				{
					default:
						$html .= '</span>';
						break;
				}
			}		
		}
	
	}
	
	switch ($passage->infons->type)
	{
		case 'front':
		case 'title_0':			
			$html .= '</h1>';
			break;

		case 'abstract_title_1':
			$html .= '</h3>';
			break;

		case 'title':
		case 'title_1':
			$html .= '</h2>';
			break;
			
		case 'title_2':
			$html .= '</h3>';
			break;

		case 'title_3':
			$html .= '</h4>';
			break;			
	
		case 'paragraph':
		default:
			$html .= '</p>';
			break;
	}	
	
}

$html .= '</body>
</html>';

file_put_contents($output_filename, $html);

?>
