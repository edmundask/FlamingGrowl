<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Convert a string to hex value
 */

if(!function_exists('strToHex'))
{
	function strToHex($string)
	{
		$hex = '';

		for ($i = 0; $i < strlen($string); $i++)
		{
			$hex .= dechex(ord($string[$i]));
		}

		return $hex;
	}
}

/**
 * Convert a hex value to string
 */

if(!function_exists('hexToStr'))
{
	function hexToStr($hex)
	{
		$string = '';

		for ($i = 0; $i < strlen($hex)-1; $i+=2)
		{
			$string .= chr(hexdec($hex[$i].$hex[$i+1]));
		}

		return $string;
	}
}

/* End of file str_hex.php */
/* Location: ./application/helpers/str_hex.php */