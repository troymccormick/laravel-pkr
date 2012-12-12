<?php

use Laravel\CLI as Console;

class Card_Collection
{
	
	var $cards;

	public function __construct() {
		$this->cards = array();
	}

	public function render($count = 0) {
		if ($count) {
			$return = '';
			for($i = 0; $i < $count; $i++) {
				if (array_key_exists($i, $this->cards)) {
					if (is_a($this->cards[$i], 'Card')) {
						$return .= $this->cards[$i]->render();
					} else {
						$return .= Card::render_card_space();
					}
				} else {
					$return .= Card::render_card_space();
				}
			}
		} else {
			foreach($this->cards AS $card) {
				$return .= $card->render();
			}
		}
		return $return;
	}

	public function add($card) {
		if (is_a($card, 'Card')) {
			$this->cards[] = $card;
		}
	}

	public function shuffle() {
		$min = 0;
		$max = count($this->cards);
		$quota = RandomNum::quota();
		if ($quota[0] >= 1612) {
			// quota is large enough to request a new random deck
			// Console::write('+ Random.org Quota: ' . $quota[0], 'green');
			$random_deck = RandomNum::get_deck();
			$new_deck = array();
			foreach($random_deck AS $k => $p) {
				$new_deck[] = $this->cards[$p];
			}
			$this->cards = $new_deck;
		} else {
			// not enough quota to fulfil a new random deck...use shuffle :(
			// Console::write('- Random.org quota has been exceeded', 'red');
			shuffle($this->cards);
		}
	}

	public function count() {
		return count($this->cards);
	}

	public function draw() {
		return array_pop($this->cards);
	}

	public function peek($i = 0) {
		return $this->cards[$i];
	}

}