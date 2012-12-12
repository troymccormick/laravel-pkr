<?php
/*
    Based on:
    	phpRandDotOrg - a PHP client for random.org.
    	Jonathon Reinhart - 2008
    	http://software.onthefive.com/RandDotOrg/

*/
use Laravel\CLI as Console;

class RandomNum
{
	const BASE_URL = 'http://www.random.org/';

	public function get_deck() {
		
		$url = self::BASE_URL . 'sequences/?&min=0&max=51&col=1&format=plain&rnd=new';

		if (function_exists('curl_exec')) {
			$curl_ch = curl_init();
			$user_agent = 'Laravel Websocket Poker : Development';
			curl_setopt($curl_ch, CURLOPT_URL, $url);
            curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl_ch, CURLOPT_USERAGENT, $user_agent);
			$raw_data = trim(curl_exec($curl_ch));
            curl_close($curl_ch);
		} else {
			$raw_data = trim(file_get_contents($url));
		}

		if ( strpos($raw_data, 'Error:') !== FALSE ) {
            $error = substr($raw_data, 7);      // Remove the 'Error: ' from the beginning.
            throw new Exception('Random Num Error: ' . $error);
        }

        $raw_data = rtrim($raw_data);               // Remove newline from end
        $parsed_data = explode("\n", $raw_data);    // Separate the data by newline.

		return $parsed_data;
	}

	public function quota() {
		$url = self::BASE_URL . 'quota/?&col=1&format=plain&rnd=new';

		if (function_exists('curl_exec')) {
			$curl_ch = curl_init();
			$user_agent = 'Laravel Websocket Poker : Development';
			curl_setopt($curl_ch, CURLOPT_URL, $url);
            curl_setopt($curl_ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl_ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl_ch, CURLOPT_USERAGENT, $user_agent);
			$raw_data = trim(curl_exec($curl_ch));
            curl_close($curl_ch);
		} else {
			$raw_data = trim(file_get_contents($url));
		}

		if ( strpos($raw_data, 'Error:') !== FALSE ) {
            $error = substr($raw_data, 7);      // Remove the 'Error: ' from the beginning.
            throw new Exception('Random Num Error: ' . $error);
        }

        $raw_data = rtrim($raw_data);               // Remove newline from end
        $parsed_data = explode("\n", $raw_data);    // Separate the data by newline.

		return $parsed_data;

	}

}