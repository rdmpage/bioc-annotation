<?php

// Encode a list of tokens for CRF, and generate a template file

//----------------------------------------------------------------------------------------
// polyfill from https://www.php.net/manual/en/function.mb-str-split.php#125429
if (!function_exists('mb_str_split'))
{
	function mb_str_split($string, $split_length = 1, $encoding = null)
	{
		if (null !== $string && !\is_scalar($string) && !(\is_object($string) && \method_exists($string, '__toString'))) {
			trigger_error('mb_str_split(): expects parameter 1 to be string, '.\gettype($string).' given', E_USER_WARNING);
			return null;
		}
		if (null !== $split_length && !\is_bool($split_length) && !\is_numeric($split_length)) {
			trigger_error('mb_str_split(): expects parameter 2 to be int, '.\gettype($split_length).' given', E_USER_WARNING);
			return null;
		}
		$split_length = (int) $split_length;
		if (1 > $split_length) {
			trigger_error('mb_str_split(): The length of each segment must be greater than zero', E_USER_WARNING);
			return false;
		}
		if (null === $encoding) {
			$encoding = mb_internal_encoding();
		} else {
			$encoding = (string) $encoding;
		}
	
		if (! in_array($encoding, mb_list_encodings(), true)) {
			static $aliases;
			if ($aliases === null) {
				$aliases = [];
				foreach (mb_list_encodings() as $encoding) {
					$encoding_aliases = mb_encoding_aliases($encoding);
					if ($encoding_aliases) {
						foreach ($encoding_aliases as $alias) {
							$aliases[] = $alias;
						}
					}
				}
			}
			if (! in_array($encoding, $aliases, true)) {
				trigger_error('mb_str_split(): Unknown encoding "'.$encoding.'"', E_USER_WARNING);
				return null;
			}
		}
	
		$result = [];
		$length = mb_strlen($string, $encoding);
		for ($i = 0; $i < $length; $i += $split_length) {
			$result[] = mb_substr($string, $i, $split_length, $encoding);
		}
		return $result;
	}
}


//----------------------------------------------------------------------------------------



function encode($token_list, $token_classification = array())
{
	// Typical templates

	$template_one = 'UCOUNT:%x[0,FID]';

	$template_1ab = 'UCOUNT:%x[-1,FID]
UCOUNT:%x[0,FID]
UCOUNT:%x[1,FID]';

	$template_2ab = 'UCOUNT:%x[-2,FID]
UCOUNT:%x[-1,FID]
UCOUNT:%x[0,FID]
UCOUNT:%x[1,FID]
UCOUNT:%x[2,FID]';

	$result = new stdclass;
	$result->features = array();
	$result->templates = array();	
	
	$num_tokens = count($token_list);
	
	for ($i = 0; $i < $num_tokens; $i++)
	{
		$features = array();
		
		$result->templates = array(); // recreate this with each loop
		
		$word = $token_list[$i];
	
		$wordNP = $word; // no punctuation
		$wordNP = preg_replace('/[^\\p{L}|\d]/u', '', $wordNP);
		if (preg_match('/^\s*$/u', $wordNP))
		{
			$wordNP = "EMPTY";
		}
		
		// word lowercase no punctuation
		$wordLCNP = mb_strtolower($wordNP);
		if (preg_match('/^\s*$/u', $wordLCNP))
		{
			$wordLCNP = "EMPTY";
		}
	
		$features[] 			= $word;		
		$result->templates[] 	= $template_2ab;
	
		$features[] 			= $wordLCNP; // lowercased word, no punct
		$result->templates[] 	= $template_2ab;	
	
		// capitalization
		$ortho = 'others';
		if (preg_match('/^\p{Lu}$/u', $wordNP))
		{
			$ortho = "singleCap";
		} 
		else if (preg_match('/^\p{Lu}+$/u', $wordNP))
		{
			$ortho = "AllCap";
		} 
		else if (preg_match('/^\p{Lu}\p{L}+/u', $wordNP))
		{
			$ortho = "InitCap";
		}
	
		$features[] 			= $ortho;  
		$result->templates[] 	= $template_2ab;	
	
		// split word into characters
		if (function_exists('mb_str_split'))
		{
			$chars = mb_str_split($word);
		}
		else
		{
			$chars = my_mb_str_split($word);
		}
	
		// print_r($chars);
	
		$numchars = count($chars);
	
		$lastChar = $chars[$numchars - 1];
		if (preg_match('/\p{L}/u', $lastChar))
		{
			$lastChar = 'a';
		}
		if (preg_match('/\p{Lu}/u', $lastChar))
		{
			$lastChar = 'A';
		}
		if (preg_match('/\d/u', $lastChar))
		{
			$lastChar = '0';
		}
	
		$features[] 			= $lastChar;
		$result->templates[] 	= $template_2ab;
	
		$features[] 			= $chars[0];
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, 0, 2)); // 3 = first 2 chars
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, 0, 3)); // 4 = first 3 chars
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, 0, 4)); // 5 = first 4 chars
		$result->templates[] 	= $template_2ab;

	
		$features[] 			= $chars[$numchars - 1]; // first char
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, $numchars - 2)); // last 2 chars
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, $numchars - 3)); // last 3 chars
		$result->templates[] 	= $template_2ab;

		$features[] 			= join("", array_slice($chars, $numchars - 4)); // last 4 chars
		$result->templates[] 	= $template_2ab;
	
		
		// numbers
		$num = 'nonNum';	
		if (preg_match('/^(17|18|19|20)[0-9][0-9][a-z]?$/u', $wordNP))
		{
			$num = 'possibleYear';
		}
		else if (preg_match('/[^\d](17|18|19|20)[0-9][0-9][a-z]?$/u', $wordNP))
		{
			$num = 'possibleYear';
		}
		else if (preg_match('/[0-9][\-|–][0-9]/u', $word))
		{
			$num = 'possibleRange';
		}
		else if (preg_match('/^[0-9]$/', $wordNP))
		{
			$num = '1dig';
		}
		else if (preg_match('/^[0-9][0-9]$/', $wordNP))
		{
			$num = '2dig';
		}
		else if (preg_match('/^[0-9][0-9][0-9]$/', $wordNP))
		{
			$num = '3dig';
		}
		else if (preg_match('/^[0-9]+$/', $wordNP))
		{
			$num = '4+dig';
		}
		else if (preg_match('/^[0-9]+(th|st|nd|rd)$/', $wordNP))
		{
			$num = 'ordinal';
		}
			
		$features[] 			= $num;
		$result->templates[] 	= $template_2ab;
	
		// punctuation
		$punct = 'noPunct';
		if (preg_match('/^\(/', $word))
		{
			$punct = 'leadParenthesis';
		}
		else if (preg_match('/\)[,|\.]?$/', $word))
		{
			$punct = 'endParenthesis';
		}	
		else if (preg_match('/^[\"\'\`\‘]/', $word))
		{
			$punct = 'leadQuote';
		}
		else if (preg_match('/[\"\'\`\’][^s]?$/', $word))
		{
			$punct = 'endQuote';
		}
		else if (preg_match('/\-.*\-/', $word))
		{
			$punct = 'multiHyphen';
		}
		else if (preg_match('/[\-\,\:\;–]$/u', $word))
		{
			$punct = 'contPunct';
		}
		else if (preg_match('/[\!\?\.\"\']$/', $word))
		{
			$punct = 'stopPunct';
		}
		else if (preg_match('/^[\(\[\{\<].+[\)\]\}\>].?$/', $word))
		{
			$punct = 'braces';
		}

		$features[] 			= $punct;
		$result->templates[] 	= $template_2ab;

		$degree = 'noDegree';
		if (preg_match('/[°|º]/u', $word))
		{
			$degree = 'degree';
		}

		$features[] 			= $degree;
		$result->templates[] 	= $template_2ab;
		
		if (isset($token_classification[$i]))
		{
			$features[] = $token_classification[$i];
		}
	

		$result->features[] = join(' ', $features);
		
	}
	
	return $result;

}


if (0)
{
	// test
	$text = "Holotype female: San Jose, 12°23'N, 121°04'E, Mindoro Island, Philippines, III.1945, E.S. Ross (CAS 9031787).";

	$text = "China. Hainan: Bao-ting Hisen, Xing-long, 25 Jul. 1935, F. C. How 73305 (holotype: IBSC! [IBSC0003357]; isotypes, A [A00066602, photo!], IBK![IBK00190122], SN!).";
	
	
	// split text into "words"
	$tokens = preg_split('/\s+/u', $text);
	
	$result = encode($tokens);
	
	print_r($result);
	


}




?>

