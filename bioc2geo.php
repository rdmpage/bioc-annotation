<?php

// Parse a biocjson file and generate simple HTML, loosely modelled on PubTator output

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: bioc2geo.php <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$basename = basename($filename, '.json');

$output_filename = $basename . '-geo.json';

$json = file_get_contents ($filename);

$obj = json_decode($json);

$geojson = new stdclass;
$geojson->type = "FeatureCollection";
$geojson->features = array();


$feature = new stdclass;
$feature->type = "Feature";
$feature->geometry = new stdclass;
$feature->geometry->type = "MultiPoint";
$feature->geometry->coordinates = array();
$feature->properties = new stdclass;

foreach ($obj->passages as $passage)
{
	foreach ($passage->annotations as $annotation)
	{
		foreach ($annotation->infons as $k => $v)
		{
			if ($k == 'geojson')
			{
				$feature->geometry->coordinates [] = $annotation->infons->geojson->geometry->coordinates;
			}
		}
	}
}

$geojson->features[] = $feature;

echo json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

file_put_contents($output_filename, json_encode($geojson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

?>
