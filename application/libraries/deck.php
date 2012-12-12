<?php

require_once('card_collection.php');
require_once('card.php');

class Deck extends Card_Collection
{
	
	function __construct()
	{
		parent::__construct();
		foreach (Card::get_ranks() AS $rank) {
			foreach (Card::get_suits() AS $suit) {
				$this->add(new Card($rank, $suit));
			}
		}
	}
}