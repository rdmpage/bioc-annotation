<?php

// Parse a biocjson file and delete any annotations

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc_delete_annotations.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = preg_replace('/\.json$/', '', $filename);

$output_filename = $filename;

$json = file_get_contents ($filename);

$obj = json_decode($json);

foreach ($obj->passages as &$passage)
{
	if (isset($passage->annotations))
	{
		$passage->annotations = array();
	}
}

echo json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
