<?php

// Try different tokenisation approaches

// e.g., tokenise on spaces, include punctuation, etc.

// See https://medium.com/@bradneysmith/tokenization-llms-from-scratch-1-cedc9f72de4e 
// for example where tokens can be stored along offsets, so we can still recreate the 
// original string (handy if we don't split solely on spaces)

/*
[('this', (0, 4)),
 ('sentence', (5, 13)),
 ("'", (13, 14)),
 ('s', (14, 15)),
 ('content', (16, 23)),
 ('includes', (24, 32)),
 (':', (32, 33)),
 ('characters', (34, 44)),
 (',', (44, 45)),
 ('spaces', (46, 52)),
 (',', (52, 53)),
 ('and', (54, 57)),
 ('punctuation', (58, 69)),
 ('.', (69, 70))]

*/

//----------------------------------------------------------------------------------------
// Tokenise on spaces and punctuation
function tokenise_string($string)
{
	$tokens = array();
	
	// https://stackoverflow.com/a/43877532

	preg_match_all("/(\w\S+\w)|(\w+)|(\s*\.{3}\s*)|(\s*[^\w\s]\s*)|\s+/u", 
		$string, 
		$matches, 
		PREG_OFFSET_CAPTURE);

	$encoding = mb_detect_encoding($string);
	
	$tokens = array();
	
	foreach ($matches[0] as $tok)
	{
		if ($tok[0] != " ")
		{
			$token = new stdclass;
			$token->text = $tok[0];
			$token->pos = array();
			
			// https://stackoverflow.com/a/72665203/9684
			$token->pos[0]= mb_strlen(substr($string, 0, $tok[1]), $encoding);
			
			// regexp generates " (" as output, which will cause problems so
			// trim space and adjust position accordingly
			if (preg_match('/^\s(.)$/', $token->text, $m))
			{
				$token->text = $m[1];
				$token->pos[0]++;
			}			
			
			// regexp generates ") " as output, which will cause problems so
			// trim space and adjust position accordingly
			if (preg_match('/^(.)\s$/', $token->text, $m))
			{
				$token->text = $m[1];
			}				
			
			$length = mb_strlen($token->text, $encoding);
			
			$token->pos[1] = $token->pos[0] + $length;
			
			// debugging
			//$token->actual = mb_substr($string, $token->pos[0] , $length, $encoding);
	
			$tokens[] = $token;
		}
	
	
	}
	
	return $tokens;
}

if (0)
{
	
	$string = 'Phylogenetic analyses of five chloroplast regions (psbA-trnH, trnL-F, matK, rbcL, and atpB-rbcL; ca. 4.2 kb, 70 accessions) also unambiguously placed Meiogyne kwangtungensis in the Pseuduvaria clade (PP = 1.00, ML BS = 99%).';
	
	$string = 'Phylogenetic (analysås PGV) of fæive ';
	
	$tokens = tokenise_string($string);
	
	print_r($tokens);
}
      
?>
