<?php

class Render
{
        
	function player_cards($game = NULL, $position = '', $player = NULL)
	{
		if ($game == null) {
            // we are not in an active game
			return Card::render_card_space() . Card::render_card_space();
		}
		if(is_a($player, 'Player')) {
			return $player->render_cards($game->id);
		} else {
			return Card::render_card_back() . Card::render_card_back();
		}
		
	}

	public function game_actions($game = NULL, $player = NULL)
	{
		if (is_a($game, 'Game')) {
			if (is_a($player, 'Player')) {
				$big = $game->blinds[$game->current_blind_level]['big'];

				$raise_slider = '<select class="raise_amt" id="game-' . $game->id . '-raise_amt">';
				for($i = $big; $i <= ($player->games[$game->id]['balance']); $i += $big) {
					$raise_slider .= '<option value="' . $i . '">$' . $i . '</option>';
				}
				$raise_slider .= '<option value="ALLIN">ALL IN!</option></select>';
				if ($player->games[$game->id]['cur_stake'] == $game->get_top_bet()) {
					$cf = '<a href="#" rel="' . $game->id . '" class="check_btn btn danger">Check</a>';
					$call = '<span class="span4" style="text-align: center;">&nbsp;</span>';
					$call_amt = 0;
					$bet_txt = 'Bet';
				} else {
					$cf = '<a href="#" rel="' . $game->id . '" class="fold_btn btn danger">Fold</a>';
					$call_amt = $game->get_top_bet() - $player->games[$game->id]['cur_stake'];
					if ($call_amt > $player->games[$game->id]['balance']) {
						$call = '<span class="span4" style="text-align: center;"><a href="#" rel="' . $game->id . '" class="call_btn btn primary">Call $' . $player->games[$game->id]['balance'] . ' (All In)</a></span>';
					} else {
						$call = '<span class="span4" style="text-align: center;"><a href="#" rel="' . $game->id . '" class="call_btn btn primary">Call $' . $call_amt . '</a></span>';
					}
					$bet_txt = 'Raise';
				}
				$raise_btn = '<a href="#" rel="' . $game->id . '" class="raise_btn btn success">' . $bet_txt . ':</a>';

				if ($player->games[$game->id]['balance'] <= $call_amt) {
					$raise_slider = '&nbsp;';
					$raise_btn = '&nbsp;';
				}

				$return = '<div class="row-fluid"><span class="span4" style="text-align: center;">' . $cf . '</span>' . $call . '<span class="span4" style="text-align: center;">' . $raise_btn . ' ' . $raise_slider . '</span></div>';
				return $return;
			}
		}
		return false;
	}

	public function community_cards($game = NULL)
	{
		if (is_a($game, 'Game')) {
			return $game->render_community_cards();
		} else {
			return Card::render_card_space() . Card::render_card_space() . Card::render_card_space() . Card::render_card_space() . Card::render_card_space();
		}
	}

	public function player_text($game = NULL, $seat = '', $player = NULL)
	{
		if ($game == NULL) {
			// TODO: throw exception
		} elseif ($seat === '') {
			if (is_a($player, 'Player')) {
				$name = $player->name;
				$balance = $player->games[$game->id]['balance'];
				$cur_stake = $player->games[$game->id]['cur_stake'];
				$tot_stake = $player->games[$game->id]['tot_stake'];
			} else {
				$name = "-";
				$balance = 0;
				$stake = 0;
				$tot_stake = 0;
			}
		} else {
			if (is_a($game, 'Game')) {
				$player = $game->get_player_by_seat($seat);
				if (is_a($player, 'Player')) {
					$name = $player->name;
					$balance = $player->games[$game->id]['balance'];
					$cur_stake = $player->games[$game->id]['cur_stake'];
					$tot_stake = $player->games[$game->id]['tot_stake'];
					$is_dealer = ($seat == $game->dealer);
				} else {
					$name = "Empty";
					$balance = 0;
					$stake = 0;
					$tot_stake = 0;
					$is_dealer = false;
				}
			}
		}
		if ($seat === '') {
			return '<b>' . $name . ' $' . $balance . '</b><br />Cur Stake: $' . $cur_stake . '<br />Tot Stake: $' . $tot_stake;
		} else {
			$return = '<b>' . $name . ' $' . $balance . '</b><br />Cur Stake: $' . $cur_stake . '<br />Tot Stake: $' . $tot_stake;
			return $return;
		}
	}
}