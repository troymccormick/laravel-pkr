<?php

require_once('deck.php');
require_once('card_collection.php');

class Game
{
	var $id;
    var $started_by;
    var $dealer;
    var $community_cards;
    var $deck;
    var $state = 'WAITING';
    var $betting_round = 1;
    var $current_blind_level;
    var $blind_level_up;
    var $current_turn;
    var $game_log;
    var $hand;
    var $start_new_hand;
    var $game_over = false;

    // TODO: move this to config ???
    var $blinds = array(
        1 => array('small' => 25, 'big' => 50, 'ante' => 0, 'time' => 3),
        2 => array('small' => 50, 'big' => 100, 'ante' => 0, 'time' => 3),
        3 => array('small' => 75, 'big' => 150, 'ante' => 0, 'time' => 3),
        4 => array('small' => 100, 'big' => 200, 'ante' => 0, 'time' => 3),
        5 => array('small' => 200, 'big' => 400, 'ante' => 0, 'time' => 3),
        6 => array('small' => 300, 'big' => 600, 'ante' => 0, 'time' => 3),
        7 => array('small' => 400, 'big' => 800, 'ante' => 0, 'time' => 3),
        8 => array('small' => 500, 'big' => 1000, 'ante' => 0, 'time' => 3),
        9 => array('small' => 1000, 'big' => 2000, 'ante' => 0, 'time' => 3),
        10 => array('small' => 1500, 'big' => 3000, 'ante' => 0, 'time' => 9999)
    );
    private $start_chips = 3000;
    

    public function add_player($player, $location)
    {
        $player->init_game($this->start_chips, $this->id);
        $this->players[$location] = $player;
    }

    public function create($started_by)
    {
        $this->id = time() . '-' . $started_by;
        $this->started_by = $started_by;
        $this->deck = new Deck();
        $this->players = array();
        $this->community_cards = new Card_Collection();
        $this->dealer = array_rand(array(1, 0));
        $this->current_turn = $this->dealer;
    }

    public function start()
    {
        $this->state = 'RUNNING';
        $this->current_blind_level = 1;
        $this->blind_level_up = time() + (60 * $this->blinds[$this->current_blind_level]['time']);
        $this->game_log[] = '<b>SHUFFLE UP AND DEAL!!!</b>';
        $this->deck->shuffle();
        $this->new_hand();
    }

    public function get_player_by_seat($seat)
    {
        if (is_a($this->players[$seat], 'Player')) {
            return $this->players[$seat];
        }
        return NULL;
    }

    public function get_player_by_client_id($id)
    {
        for ($i = 0; $i < count($this->players); $i++) {
            if (is_a($this->players[$i], 'Player') && $this->players[$i]->client_id == $id) {
                return $this->players[$i];
            }
        }
        return NULL;
    }

    public function get_player_seat_by_client_id($id)
    {
        for ($i = 0; $i < count($this->players); $i++) {
            if (is_a($this->players[$i], 'Player') && $this->players[$i]->client_id == $id) {
                return $i;
            }
        }
        return NULL;
    }

    public function new_hand()
    {
        $this->hand++;

        // check for next blinds level
        if (time() > $this->blind_level_up) {
            // blind level up!
            $this->current_blind_level++;
            $this->blind_level_up = time() + (60 * $this->blinds[$this->current_blind_level]['time']);
            $update = '<b>** Blinds Up! Level ' . $this->current_blind_level . ' ($' . $this->blinds[$this->current_blind_level]['small'] . '/$' . $this->blinds[$this->current_blind_level]['big'];
            if ($this->blinds[$this->current_blind_level]['ante'] > 0) {
                $update .= ' $' . $this->blinds[$this->current_blind_level]['ante'] . ' ante';
            }
            $update .= ')</b>';
            $this->game_log[] = $update;
        }
        $this->game_log[] = '--- Starting Hand #' . $this->hand . ' ---';
        $this->game_log[] = '<b>' . $this->players[$this->dealer]->name . '</b> is dealer';
        if ($this->blinds[$this->current_blind_level]['ante'] > 0) {
            $this->game_log[] = 'All players post $' . $this->blinds[$this->current_blind_level]['ante'] . ' ante';
            $this->post_antes();
        }
        $this->post_small_blind();
        $this->post_big_blind();
        
        $this->deal_player_cards(2);
        $this->current_turn = $this->dealer;

    }

    public function make_bet($player, $amount)
    {
        if ($amount && is_a($player, 'Player') && $player->games[$this->id]['balance'] >= $amount) {
            $player->bet($amount, $this->id);
            return true;
        }
        return false;
    }

    public function post_antes()
    {
        foreach($this->players AS $player) {
            if (!$this->make_bet($player, $this->blinds[$this->current_blind_level]['ante'])) {
                // all in
                $this->game_log[] = '<b>' . $player->name . '</b> posts ante of $' . $player->games[$this->id]['balance'] . ' and is ALL IN';
                $player->games[$this->id]['status'] = 'ALLIN';
                $this->make_bet($player, $player->games[$this->id]['balance']);
            }
        }
    }

    public function post_small_blind()
    {
        if ($this->make_bet($this->players[$this->dealer], $this->blinds[$this->current_blind_level]['small'])) {
            $this->game_log[] = '<b>' . $this->players[$this->dealer]->name . '</b> posts small blind $' . $this->blinds[$this->current_blind_level]['small'];
        } else {
            // ALLIN
            $this->game_log[] = '<b>' . $this->players[$this->dealer]->name . '</b> posts small blind $' . $this->players[$this->dealer]['balance'] . " and is ALL IN";
            $this->players[$this->dealer]->games[$this->id]['status'] = 'ALLIN';
            $this->make_bet($this->players[$this->dealer], $this->players[$this->dealer]->games[$this->id]['balance']);
        }
    }

    public function post_big_blind()
    {
        $bb = ($this->dealer == 0) ? 1 : 0;
        if ($this->make_bet($this->players[$bb], $this->blinds[$this->current_blind_level]['big'])) {
            $this->game_log[] = '<b>' . $this->players[$bb]->name . '</b> posts big blind $' . $this->blinds[$this->current_blind_level]['big'];
        } else {
            // ALLIN
            $this->game_log[] = '<b>' . $this->players[$bb]->name . '</b> posts big blind $' . $this->players[$bb]['balance'] . " and is ALL IN";
            $this->players[$bb]->games[$this->id]['status'] = 'ALLIN';
            $this->make_bet($this->players[$bb], $this->players[$bb]->games[$this->id]['balance']);
        }
    }

    public function deal_player_cards($count)
    {
        for ($i = 0; $i < $count; $i++) {
            for ($j = 0; $j < count($this->players); $j++) {
                if (is_a($this->players[$j], 'Player')) {
                    $card = $this->deck->draw();
                    $this->players[$j]->add_card($card, $this->id);
                }
            }
        }
    }

    public function get_pot_value()
    {
        $total = 0;
        for ($i = 0; $i < count($this->players); $i++) {
            if (is_a($this->players[$i], 'Player')) {
                $total += $this->players[$i]->games[$this->id]['tot_stake'];
            }
        }
        return $total;
    }

    public function get_top_bet()
    {
        $top = 0;
        foreach($this->players AS $player) {
            if (is_a($player, 'Player')) {
                if ($player->games[$this->id]['cur_stake'] > $top) {
                    $top = $player->games[$this->id]['cur_stake'];
                }
            }
        }
        return $top;
    }

    public function check_for_all_in_call()
    {
        $all_in = false;
        $all_in_tot = 0;
        $called = false;
        foreach($this->players AS $player) {
            if ($player->games[$this->id]['status'] == 'ALLIN') {
                $all_in = true;
                $all_in_tot++;
            } elseif ($player->games[$this->id]['status'] == 'CALL') {
                $called = true;
            }
        }

        if ($all_in && $called) {
            return true;
        } elseif ($all_in_tot == 2) {
            return true;
        }
        return false;
    }

    public function check_for_end_of_round()
    {
        if (($this->betting_round > 1 && $this->community_cards->count() < 3) ||
            ($this->betting_round > 2 && $this->community_cards->count() < 4) ||
            ($this->betting_round > 3 && $this->community_cards->count() < 5) ||
            ($this->is_hand_over())) {
            return false;
        }

        $top_bet = $this->get_top_bet();

        foreach($this->players AS $player) {
            if (is_a($player, 'Player')) {
                $bad_status = array('ALLIN', 'FOLD', 'CHECK');
                if ($player->games[$this->id]['cur_stake'] < $top_bet && !in_array($player->games[$this->id]['status'], $bad_status)) {
                    return false;
                } else if ($player->games[$this->id]['status'] == 'WAITING') {
                    return false;
                }
            }
        }

        if ($this->check_for_all_in_call()) {
            return false;
        }

        foreach($this->players AS $player) {
            $player->games[$this->id]['status'] = 'WAITING';
        }

        $this->betting_round++;
    }

    public function fold($player)
    {
        if (!is_a($player, 'Player')) {
            return false;
        }
        $player->games[$this->id]['status'] = 'FOLD';
        $this->current_turn = ($this->current_turn == 0) ? 1 : 0;
        $this->check_for_end_of_round();
    }

    public function check($player)
    {
        if (!is_a($player, 'Player')) {
            return false;
        }
        $player->games[$this->id]['status'] = 'CHECK';
        $this->current_turn = ($this->current_turn == 0) ? 1 : 0;
        $this->check_for_end_of_round();
    }

    public function call($player)
    {
        if (!is_a($player, 'Player')) {
            return false;
        }
        $top_bet = $this->get_top_bet();
        if ($player->games[$this->id]['balance'] > $top_bet - $player->games[$this->id]['cur_stake']) {
            $this->make_bet($player, $top_bet - $player->games[$this->id]['cur_stake']);
            $player->games[$this->id]['status'] = 'CALL';
            $this->check_for_end_of_round();
        } elseif ($player->games[$this->id]['balance'] <= $top_bet - $player->games[$this->id]['cur_stake']) {
            $this->make_bet($player, $player->games[$this->id]['balance']);
            $player->games[$this->id]['status'] = 'ALLIN';
            $this->check_for_end_of_round();
        }

        $this->current_turn = ($this->current_turn == 0) ? 1 : 0;
    }

    public function raise($player, $raise_amt)
    {
        if (!is_a($player, 'Player')) {
            return false;
        }

        $top_bet = $this->get_top_bet();
        if ($player->games[$this->id]['balance'] > $top_bet + $raise_amt) {
            $this->make_bet($player, $top_bet + $raise_amt - $player->games[$this->id]['cur_stake']);
            $player->games[$this->id]['status'] = 'RAISE';
        } elseif ($player->games[$this->id]['balance'] <= $top_bet + $raise_amt) {
            $this->make_bet($player, $player->games[$this->id]['balance']);
            $player->games[$this->id]['status'] = 'ALLIN';
        }
        // grab alt seat - set status = waiting
        $alt_seat = ($this->get_player_seat_by_client_id($player->client_id) == 0) ? 1 : 0;
        $player2 = $this->get_player_by_seat($alt_seat);
        $player2->games[$this->id]['status'] = 'WAITING';

        $this->current_turn = ($this->current_turn == 0) ? 1 : 0;
    }

    public function deal_community_cards($count)
    {
        // reset player's cur_stake
        foreach($this->players AS $player) {
            $player->games[$this->id]['cur_stake'] = 0;
        }

        $this->burn_cards(1);
        for ($i = 0; $i < $count; $i++) {
            $card = $this->deck->draw();
            $this->community_cards->add($card);
        }
    }

    public function burn_cards($count)
    {
        for ($i = 0; $i < $count; $i++) {
            $card = $this->deck->draw();
        }
    }

    public function render_flop() 
    {
        return $this->community_cards->render(3);
    }

    public function render_turn()
    {
        return $this->community_cards->cards[3]->render();
    }
    
    public function render_river()
    {
        return $this->community_cards->cards[4]->render();
    }

    public function finish_hand()
    {
        // find winning hand
        if ($this->count_players_in_hand() == 1) {
            $win_player = $this->get_last_player_standing();
            $this->game_log[] = '<b>-- Hand Complete, No Showdown --</b>';
            if ($this->community_cards->count() > 0) {
                $this->game_log[] = 'Community Cards: ' . $this->render_community_cards();
            }
            $this->game_log[] = '<b>' . $win_player->name . '</b> wins $' . $this->get_pot_value() . ' (net $' . ($this->get_pot_value() - $win_player->games[$this->id]['tot_stake']) . ')';
            $win_player->games[$this->id]['balance'] += $this->get_pot_value();
        } else {
            $this->game_log[] = '<b>*** SHOWDOWN ***</b>';
            $this->game_log[] = 'Community Cards: ' . $this->render_community_cards();

            $hand_pts = array();
            $hand_txt = array();
            foreach($this->players AS $player) {
                $cards = array();
                for ($i = 0; $i < 5; $i++) {
                    if ($i == 0 || $i == 1) {
                        // get player card also!
                        $cards[] = array(
                            'value' => $player->peek_card($i, $this->id)->get_numeric_rank(),
                            'suit' => $player->peek_card($i, $this->id)->get_suit()
                        );
                    }
                    // get community card
                    $cards[] = array(
                        'value' => $this->community_cards->cards[$i]->get_numeric_rank(),
                        'suit' => $this->community_cards->cards[$i]->get_suit()
                    );
                }

                $engine = new Engine($cards);
                $hand = $engine->score($cards);
                $hand_text = $engine->readable_hand($hand);
                $this->game_log[] = '<b>' . $player->name . '</b> shows - ' . Render::player_cards($this, '', $player) . ' - ' . $hand_text;
                $hand_pts[$player->client_id] = $hand;
                $hand_txt[$player->client_id] = $hand_text;
            }
            asort($hand_pts);
            $tmp_pts = -1;
            $ways_split = 0;
            $split_seats = array();
            $lead_seat = NULL;

            foreach($hand_pts AS $seat => $pts) {
                if ($pts > $tmp_pts) {
                    $tmp_pts = $pts;
                    $split_seats[$seat] = $seat;
                    $split_pot = FALSE;
                    $lead_seat = $seat;
                } elseif ($pts == $tmp_pts) {
                    $ways_split++;
                    $split_pot = TRUE;
                    $split_seats[$seat] = $seat;
                }
            }
            if (!$split_pot) {
                $player = $this->get_player_by_client_id($lead_seat);
                $player->games[$this->id]['balance'] += $this->get_pot_value();
                $this->game_log[] = '<b>' . $player->name . '</b> wins $' . $this->get_pot_value() . ' (net $' . ($this->get_pot_value() - $player->games[$this->id]['tot_stake']) . ') with ' . $hand_txt[$lead_seat];
            } else {
                $this->game_log[] = '<b>Split Pot</b>';
                $split_amt = $this->get_pot_value() / $ways_split;
                foreach($this->players AS $player) {
                    $player->games[$this->id]['balance'] += $split_amt;
                }
                $this->game_log[] = 'Each player wins $' . $split_amt;
            }
        }
        foreach($this->players AS $player) {
            if ($player->games[$this->id]['balance'] == 0) {
                // END GAME!
                $this->game_over = true;
            } else {
                $player->games[$this->id]['cur_stake'] = 0;
                $player->games[$this->id]['tot_stake'] = 0;
                $player->games[$this->id]['status'] = 'WAITING';
                $player->cards[$this->id] = new Card_Collection();
            }
        }
        
        if (!$this->game_over) {
            // reset all cards
            $this->deck = new Deck();
            $this->deck->shuffle();
            $this->community_cards = new Card_Collection();
            $this->betting_round = 1;
                
            // move dealer button
            $this->dealer = ($this->dealer == 1) ? 0 : 1;
        }
    }

    public function count_players_in_hand()
    {
        $count = 0;
        foreach($this->players AS $player) {
            if (is_a($player, 'Player')) {
                if ($player->games[$this->id]['status'] != 'FOLD') {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function get_last_player_standing()
    {
        foreach($this->players AS $player) {
            if (is_a($player, 'Player')) {
                if ($player->games[$this->id]['status'] != 'FOLD') {
                    return $player;
                }
            }
        }
        return false;
    }

    public function render_community_cards()
    {
        if ($this->community_cards->count() > 0) {
            return $this->community_cards->render(5);
        }
        return '';
    }

    public function is_hand_over()
    {
        if ($this->betting_round > 4 || $this->count_players_in_hand() == 1) {
            return true;
        }
        return false;
    }

}