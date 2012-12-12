<?php
set_time_limit(0);
date_default_timezone_set('America/Los_Angeles');

use Laravel\CLI as Console;

class Server_Task {

	public $server;
	public $games;

	public function run($args)
	{
		$this->server = new Websocket();
		$this->games = array();

		$that = $this; // for use in the closures
		$this->server->bind('open', function($client_id) use($that) {
			$ip = long2ip($that->server->wsClients[$client_id][6]);
			$that->output('+ ' . $ip . ' (' . $client_id . ') connected', 'green');
		});

		$this->server->bind('message', function($client_id, $msg, $msg_length, $binary) use($that) {
			$ip = long2ip($that->server->wsClients[$client_id][6]);

			if ($msg_length == 0) {
				$that->server->wsClose($client_id);
				return;
			}

			$action = json_decode($msg);
			switch($action->event) {
				case 'login':
					$username = str_replace(" ", "", trim($action->data->player_name));
					$bad_usernames = array('system', 'admin', 'root');
					if (in_array($username, $bad_usernames)) {
						$that->send_to_one($client_id, 'login_error', array('msg' => 'That is an invalid username'));
					} elseif (substr($username, 0, 1) == '@') {
						$that->send_to_one($client_id, 'login_error', array('msg' => 'Twitter users are not yet implemented'));
					} elseif (strlen($username) < 2 || strlen($username) > 15) {
						$that->send_to_one($client_id, 'login_error', array('msg' => 'Username must be 2-15 characters in length'));
					} else {
						// Check if username is taken
						// and build players array to send as user_list on the front end
						$taken = false;
						$players = array();
						foreach($that->server->wsClients AS $id => $client) {
							$name = $client[12]->name;
							if ($name == $username) {
								$taken = true;
								break;
							}
							if ($name != NULL) {
								$players[] = array('name' => $name);
							}
						}
						if ($taken) {
							$that->send_to_one($client_id, 'login_error', array('msg' => 'That username is already in use'));
						} else {
							$players[] = array('name' => $username, 'client_id' => $client_id);

							$that->server->wsClients[$client_id][12]->name = $username;
							$that->output('+ ' . $ip . ' (' . $client_id . ') identified as ' . $username, 'green');
							$that->send_to_one($client_id, 'login_success', array('msg' => true));
							if (count($that->games) > 0) {
								$games_send = array();
								foreach($that->games AS $key => $game) {
									if ($game->state == 'WAITING') {
										$games_send[] = array('id' => $game->id, 'started_by' => $game->started_by);
									}
								}
								$that->send_to_one($client_id, 'games_list', $games_send);
							}
							// tell all players that this person is here
							$that->send_to_all('lobby_chat', array('from' => 'system', 'msg' => $username . ' joined'));
							$that->send_to_all('user_list', $players);
						}
					}
					break;
				case 'lobby_chat':
					// TODO: add admin commands here...general commands too (uptime, etc)
					$that->send_to_all('lobby_chat', array('from' => $that->server->wsClients[$client_id][12]->name, 'msg' => HTML::entities($action->data->msg)));
					break;
				case 'create_game':
					if (!$that->server->wsClients[$client_id][12]->started_game) {
						$g = new Game();
						$g->create($that->server->wsClients[$client_id][12]->name);
						$g->add_player($that->server->wsClients[$client_id][12], 0);

						$that->games[$g->id] = $g;

						$that->send_game_list();

						$that->output('+ Game ' . $g->id . ' created!', 'green');
						
						$that->send_to_all('lobby_chat', array('from' => 'system', 'msg' => $g->started_by . ' started a new game!'));
					} else {
						$that->send_to_one('lobby_chat', array('from' => 'system', 'msg' => 'You already have an open game!'));
					}
					break;
				case 'join_game':
					$game_id = $action->data->game;
					
					$g = $that->games[$game_id];

					$player_1 = $g->get_player_by_seat(0);

					if ($player_1->name == $that->server->wsClients[$client_id][12]->name) {
						$that->send_to_one($client_id, 'lobby_chat', array('from' => 'system', 'msg' => 'You are not allowed to join your own game'));
					} else {
						$g->add_player($that->server->wsClients[$client_id][12], 1);
						
						$that->send_to_game($g, 'open_table', array('table_id' => $g->id, 'table_name' => $g->started_by . "'s Tournament"));

						$g->game_log = array();
						$g->start();

						$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => $g->game_log));

						$g->game_log = array();

						$that->render_game($g, true);

						$that->games[$game_id] = $g;

						$that->send_game_list();

						$that->output('+ Game ' . $g->id . ' started!', 'white', true, 'green');

					}
					break;
				case 'game_chat':
					$game_id = $action->data->game_id;
					
					$g = $that->games[$game_id];
					// TODO: add admin commands here...general commands too (uptime, etc)
					if ($g->started_by == $that->server->wsClients[$client_id][12]->name) {
						// allow admin commands from the game owner
						
						if (substr($action->data->msg, 0, 6) == '/admin') {
							// do admin stuff
							$task = explode(' ', $action->data->msg);
							$task = $task[1];
							switch($task) {
								case 'pause':
									$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array('The tournament owner has paused this tournament')));
									$that->output('+ Game ' . $g->id . ' paused', 'white', true, 'yellow');
									
									$that->render_game($g, false, 0);

									$g->paused_at = time();
									$g->status = 'PAUSED';
									break;
								case 'resume':
									$new_level_up = $g->blind_level_up + (time() - $g->paused_at);
									$g->blind_level_up = $new_level_up;
									$g->status = 'RUNNING';
									
									$that->render_game($g, false, true);

									$that->output('+ Game ' . $g->id . ' resumed', 'white', true, 'green');
									$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array('The tournament owner has resumed this tournament!')));
									break;
							}
							break;
						}
					}
					$that->send_to_game($g, 'game_chat', array('game_id' => $action->data->game_id, 'from' => $that->server->wsClients[$client_id][12]->name, 'msg' => HTML::entities($action->data->msg)));
					break;
				case 'fold':
				case 'check':
				case 'call':
				case 'raise':
					$game_id = $action->data->game_id;

					$g = $that->games[$game_id];

					if ($g->get_player_seat_by_client_id($client_id) == $g->current_turn && $g->state == 'RUNNING') {
						switch($action->event) {
							case 'fold':
								$g->fold($g->get_player_by_client_id($client_id));

								$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array($g->get_player_by_client_id($client_id)->name . ' folds')));
								break;
							case 'check':
								$g->check($g->get_player_by_client_id($client_id));

								$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array($g->get_player_by_client_id($client_id)->name . ' checks')));
								break;
							case 'call':
								$call_amt = $g->get_top_bet() - $g->get_player_by_client_id($client_id)->games[$g->id]['cur_stake'];
								$g->call($g->get_player_by_client_id($client_id));

								$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array($g->get_player_by_client_id($client_id)->name . ' calls $' . $call_amt)));
								break;
							case 'raise':
								$replace = array('Bet $', 'Raise $');
								$raise_amt = str_replace($replace, '', $action->data->raise_amt);
								$all_in = '';
								if ($raise_amt == 'ALLIN') {
									$all_in = ' and is ALL IN!';
									$raise_amt = $g->get_player_by_client_id($client_id)->games[$g->id]['balance'];
								}
								$g->raise($g->get_player_by_client_id($client_id), $raise_amt);

								$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => array($g->get_player_by_client_id($client_id)->name . ' raises $' . $raise_amt . $all_in)));
								break;
						}

						$g->start_new_hand = false;

						if ($g->check_for_all_in_call()) {
							// TODO: slow roll flop, turn, river as necessary with cards exposed
							if ($g->community_cards->count() < 3) {
								$g->deal_community_cards(3);
								$g->game_log[] = '<b>- Dealing Flop: </b> ' . $g->render_flop();
							}

							if ($g->community_cards->count() < 4) {
								$g->deal_community_cards(1);
								$g->game_log[] = '<b>- Dealing Turn: </b> ' . $g->render_turn();
							}

							if ($g->community_cards->count() < 5) {
								$g->deal_community_cards(1);
								$g->game_log[] = '<b>- Dealing River: </b> ' . $g->render_river();
							}

							// reveal all cards
							$i = 0;
							foreach($g->players AS $player) {
								$alt_seat = ($i == 0) ? 1 : 0;

								$render = array(
									'game_id' => $g->id,
									'game-' . $g->id . '-player_cards' => Render::player_cards($g, '', $player),
									'game-' . $g->id . '-player_text' => Render::player_text($g, '', $player),
									'game-' . $g->id . '-opponent_cards' => Render::player_cards($g, '', $g->players[$alt_seat]),
									'game-' . $g->id . '-opponent_text' => Render::player_text($g, '', $g->players[$alt_seat]),
									'game-' . $g->id . '-community_cards' => Render::community_cards($g),
									'game-' . $g->id . '-pot' => '<b>Pot: $' . $g->get_pot_value() . '</b>'
								);

								$that->send_to_one($player->client_id, 'game_render', $render);
								$that->send_to_one($player->client_id, 'game_showdown', array('game_id' => $g->id, 'showdown' => true));
								$i++;
							}

							$g->finish_hand();

							if (!$g->game_over) {
								$g->start_new_hand = true;
								$g->new_hand();
							} else {
								$that->send_to_game($g, 'end_of_game', array('game_id' => $g->id, 'end_of_game' => true));
								$g->game_log[] = '<b>** TOURNAMENT COMPLETE **</b>';
							}
						} elseif ($g->betting_round > 1 && $g->community_cards->count() < 3) {
							$g->current_turn = ($g->dealer == 0) ? 1 : 0;
							$g->deal_community_cards(3);
							$g->game_log[] = '<b>- Dealing Flop: </b>' . $g->render_flop();
						} elseif ($g->betting_round > 2 && $g->community_cards->count() < 4) {
							$g->current_turn = ($g->dealer == 0) ? 1 : 0;
							$g->deal_community_cards(1);
							$g->game_log[] = '<b>- Dealing Turn: </b>' . $g->render_turn();
						} elseif ($g->betting_round > 3 && $g->community_cards->count() < 5) {
							$g->current_turn = ($g->dealer == 0) ? 1 : 0;
							$g->deal_community_cards(1);
							$g->game_log[] = '<b>- Dealing River: </b>' . $g->render_river();
						} elseif ($g->is_hand_over()) {
							$g->start_new_hand = true;

							// reveal all cards...IF players in hand > 1
							if ($g->count_players_in_hand() > 1) {
								$i = 0;
								foreach($g->players AS $player) {
									$alt_seat = ($i == 0) ? 1 : 0;

									$render = array(
										'game_id' => $g->id,
										'game-' . $g->id . '-player_cards' => Render::player_cards($g, '', $player),
										'game-' . $g->id . '-player_text' => Render::player_text($g, '', $player),
										'game-' . $g->id . '-opponent_cards' => Render::player_cards($g, '', $g->players[$alt_seat]),
										'game-' . $g->id . '-opponent_text' => Render::player_text($g, '', $g->players[$alt_seat]),
										'game-' . $g->id . '-community_cards' => Render::community_cards($g),
										'game-' . $g->id . '-pot' => '<b>Pot: $' . $g->get_pot_value() . '</b>'
									);


									$that->send_to_one($player->client_id, 'game_render', $render);
									$that->send_to_one($player->client_id, 'game_showdown', array('game_id' => $g->id, 'showdown' => true));
									$i++;
								}
							}

							$g->finish_hand();
						
							if (!$g->game_over) {
								$g->new_hand();
							} else {
								$that->send_to_game($g, 'end_of_game', array('game_id' => $g->id, 'end_of_game' => true));
								$g->game_log[] = '<b>** TOURNAMENT COMPLETE **</b>';
							}
						}

						// send game log and render (?)
						$that->send_to_game($g, 'game_log', array('game_id' => $g->id, 'msgs' => $g->game_log));
						if (!$g->game_over) {
							$i = 0;
							foreach($g->players AS $player) {
								$alt_seat = ($i == 0) ? 1 : 0;
								if ($g->start_new_hand) {
									$that->send_to_one($player->client_id, 'game_log', array('game_id' => $g->id, 'msgs' => array('<b>You were dealt:</b> ' . Render::player_cards($g, $i, $player))));
								}
								$render = array(
									'game_id' => $g->id,
									'game-' . $g->id . '-player_cards' => Render::player_cards($g, '', $player),
									'game-' . $g->id . '-player_text' => Render::player_text($g, '', $player),
									'game-' . $g->id . '-opponent_cards' => Render::player_cards($g, $alt_seat),
									'game-' . $g->id . '-opponent_text' => Render::player_text($g, '', $g->players[$alt_seat]),
									'game-' . $g->id . '-community_cards' => Render::community_cards($g),
									'game-' . $g->id . '-pot' => '<b>Pot: $' . $g->get_pot_value() . '</b>'
								);
								if ($g->current_turn == $i) {
									$render['game-' . $g->id . '-game_controls'] = Render::game_actions($g, $player);
								}
								$that->send_to_one($player->client_id, 'game_render', $render);
								$i++;
							}
						}

						$g->start_new_hand = false;
						$g->game_log = array();

					} else {
						// TODO: throw error to user that it is either not their turn yet or the game isn't in status RUNNING
					}
					break;
			}
		});

		$this->server->bind('close', function($client_id, $status) use($that) {
			$ip = long2ip($that->server->wsClients[$client_id][6]);
			
			$that->output('- ' . $ip . ' (' . $client_id . ') disconnected', 'red');

			if ($that->server->wsClients[$client_id][12]->name) {
				$that->send_to_all('lobby_chat', array('from' => 'system', 'msg' => $that->server->wsClients[$client_id][12]->name . ' left'));
			}
		});

		// START 'ER UP!
		$this->output('**************************************', 'light_purple', false);
		$this->output('* Laravel Websocket Poker            *', 'light_purple', false);
		$this->output('*                 Version: Pre-ALPHA *', 'light_purple', false);
		$this->output('**************************************', 'light_purple', false);
		$this->output('** Server started', 'white', true, 'green');
		
		$this->server->wsStartServer(Config::get('application.lwp_server_ip'), Config::get('application.lwp_server_port'));
	}

	public function send_to_one($client_id, $event, $msg)
	{
		$msg = json_encode(array('event' => $event, 'data' => $msg));
		$this->server->wsSend($client_id, $msg);
	}

	public function send_to_all($event, $msg)
	{
		$msg = json_encode(array('event' => $event, 'data' => $msg));
		foreach($this->server->wsClients AS $id => $client) {
			$this->server->wsSend($id, $msg);
		}
	}

	public function output($msg, $color = 'white', $date = true, $bgcolor = NULL)
	{
		if ($date) {
			$msg = date('Y-m-d H:i:s') . '| ' . $msg;
		}
		Console::write($msg, $color, $bgcolor);
	}

	public function send_to_game($game, $event, $msg)
	{
		foreach ($game->players AS $player) {
			$this->send_to_one($player->client_id, $event, $msg);
		}
	}

	public function render_game($game, $start_hand = false, $controls = true)
	{
		$i = 0;
		foreach($game->players AS $player) {
			$alt_seat = ($i == 0) ? 1 : 0;
			$render = array(
				'game_id' => $game->id,
				'game-' . $game->id . '-player_cards' => Render::player_cards($game, '', $player),
				'game-' . $game->id . '-player_text' => Render::player_text($game, '', $player),
				'game-' . $game->id . '-opponent_cards' => Render::player_cards($game, $alt_seat),
				'game-' . $game->id . '-opponent_text' => Render::player_text($game, $alt_seat),
				'game-' . $game->id . '-community_cards' => Render::community_cards(),
				'game-' . $game->id . '-pot' => '<b>Pot: $' . $game->get_pot_value() . '</b>'
			);
			if ($game->current_turn == $i && $controls) {
				$render['game-' . $game->id . '-game_controls'] = Render::game_actions($game, $player);
			}
			$this->send_to_one($player->client_id, 'game_render', $render);
			if ($start_hand) {
				// send their cards to the game log
				$this->send_to_one($player->client_id, 'game_log', array('game_id' => $game->id, 'msgs' => array('<b>You were dealt:</b> &nbsp;' . Render::player_cards($game, $i, $player))));
			}
			$i++;
		}
	}

	public function send_game_list()
	{
		$games_send = array();
		foreach($this->games AS $key => $game) {
			if ($game->state == 'WAITING') {
				$games_send[] = array('id' => $game->id, 'started_by' => $game->started_by);
			}
		}
		$this->send_to_all('games_list', $games_send);
	}

}