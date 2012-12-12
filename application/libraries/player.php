<?php

class Player
{
	
	var $name;
	var $client_id;
	var $started_game = NULL;
	var $games = array();
	var $cards = array();

	function __construct($client_id)
	{
        $this->client_id = $client_id;
    }

    public function init_game($start_chips, $game_id)
    {
    	$this->games[$game_id] = array(
            'cur_stake' => 0,
            'tot_stake' => 0,
            'status' => 'WAITING',
            'balance' => $start_chips
        );
        $this->cards[$game_id] = new Card_Collection;
    }

    public function bet($amount, $game_id)
    {
    	if($amount <= $this->games[$game_id]['balance']) {
            $this->games[$game_id]['cur_stake'] += $amount;
            $this->games[$game_id]['tot_stake'] += $amount;
            $this->games[$game_id]['balance'] -= $amount;
        }
    }

    public function add_card($card, $game_id)
    {
        return $this->cards[$game_id]->add($card);
    }

    public function peek_card($i, $game_id) {

        return $this->cards[$game_id]->peek($i);
    }

    public function render_cards($game_id, $face_down = false)
    {
    	if ($face_down) {
            $count = $this->cards[$game_id]->count();
            if ($count == 2) {
                return Card::render_card_back() . Card::render_card_back();
            } elseif ($count == 1) {
                return Card::render_card_back() . Card::render_card_space();
            } else {
                return Card::render_card_space() . Card::render_card_space();
            }
        } else {
            return $this->cards[$game_id]->render(2);
        }
    }
}