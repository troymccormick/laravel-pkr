var Server = function() {
    url = 'ws://x.x.x.x:8080'; // Update this to your server IP address and port you set in your config file

	var conn = new WebSocket(url);
	var callbacks = {};

	this.bind = function(event_name, callback) {
		callbacks[event_name] = callbacks[event_name] || [];
		callbacks[event_name].push(callback);
		return this;
	};

	this.send = function(event_name, event_data) {
		var payload = JSON.stringify({event: event_name, data: event_data});
		conn.send(payload);
		return this;
	};

	conn.onmessage = function(evt) {
		if (evt.data[0] != "{") {
			json = evt.data.substring(1, evt.data.length);
		} else {
			json = evt.data;
		}
		var json = JSON.parse(json);
		dispatch(json.event, json.data);
	};

	conn.onclose = function() {
		dispatch('close', null);
	};

	conn.onopen = function() {
		dispatch('open', null);
	};

	var dispatch = function(event_name, message) {
		var chain = callbacks[event_name];
		if(typeof chain == "undefined") return;
		for(var i = 0; i < chain.length; i++) {
			chain[i](message);
		}
	};
};

$(document).ready(function() {

	var pause_showdown = false;
	var paused_render = '';

	if (!$("html").hasClass('websockets')) {
		// TODO: Modal this sucka!
		alert("You don't have websockets on this browser");
	} else {
		var socket = new Server();
		var interval = 0;
		
		$("#user-login").live("click", function(e) {
	        e.preventDefault();
	        do_login();
		});
		$("#username").keypress(function(e) {
	        if (e.which == 13) {
                e.preventDefault();
                do_login();
	        }
		});
		function do_login() {
	        // send login
	        socket.send('login', {
	                player_name: $("#username").val()
	        });
	        return false;
		}

		socket.bind('open', function() {
			$('#login-modal').modal('show').bind('shown', function() {
				$("#username").focus();
			});
			
		});

		socket.bind('close', function() {
			$('#login-modal').modal('hide');
			$('#modal').html('<div class="modal-header"><h3>OH NO!</h3></div><div class="modal-body"><p class="alert-message error">It appears the websocket server has gone down or isn\'t available. Please try refreshing in a few moments&hellip;</p></div><div class="modal-footer"><button class="btn btn-primary" id="refresh">Refresh</button></div>').modal({
                                show: true,
                        backdrop: 'static',
                        keyboard: false
                    });
			// TODO: setinterval if user has been connected before to
			// refresh page after x amount of time to retry connection with websocket
		});

		socket.bind("login_error", function(d) {
                $('#username').val('');
                $('#login-modal').modal('hide');
                $('#modal').html('<div class="modal-header"><a href="#" class="close" data-dismiss="modal">&times;</a><h3>Error</h3></div><div class="modal-body"><p class="alert alert-block alert-error"><strong>OOPS!</strong> ' + d.msg + '</p></div>').modal({
                        show: true,
                backdrop: 'static',
                keyboard: true
            }).bind('hide', function() {
                $('#login-modal').modal({show: true});
            });
        });

        socket.bind("login_success", function(d) {
        		$("#login-modal").modal('hide');
                $("#lobby-chat-txt").focus();
        });

        socket.bind("lobby_chat", function(d) {
        	/* <li><span class="user me">Player #1</span>: This is only a test</li> */
			if (d.from == "system") {
				str = '<li class="admin">' + d.msg + '</li>';
			} else {
				str = '<li><span class="user">' + d.from + '</span>: ' + d.msg + '</li>';
			}
			$("#lobby_chat ul").append(str);
			$("#lobby_chat").scrollTo('100%');
		});

		$("#lobby-chat-txt").live("keypress", function(e) {
            if (e.which == 13 && $(this).val() != "") {
                socket.send('lobby_chat', {
                    msg: $(this).val()
                });
                $(this).val('');
            }
        });

        socket.bind('user_list', function(d) {
        	str = '';
        	$.each(d, function(k, v) {
        		str += '<li>' + v.name + '</li>';
			});
			$("#online_players ul").html(str);
        });

        $("#create_game").live('click', function(e) {
        	e.preventDefault();
        	if (!$(this).hasClass("disabled")) {
				$(this).addClass("disabled");
				socket.send('create_game', {
					create_game: true
				});
			}
        });

        socket.bind('games_list', function(d) {
        	var create = $("#create-li").html();
        	if (d.length > 0) {
				str = '';
				$.each(d, function(k, v) {
					str += '<li>' + v.started_by + '\'s game <a href="#" rel="' + v.id + '" class="join_game btn btn-mini">Join</a></li>';
				});
				$("#lobby_games ul").html(str + '<li id="create-li">' + create + '</li>');
			} else {
				$('#lobby_games ul').html('<li id="create-li">' + create + '</li>');
			}
        });

        $(".join_game").live("click", function(e) {
			e.preventDefault();
			var id = $(this).attr("rel");
			socket.send('join_game', {
				game: id
			});
		});

		socket.bind('open_table', function(d) {
			// add tournament tab
			$("#main_tabs").append('<li class="pull-right"><a href="#game-' + d.table_id + '" data-toggle="tab" class="tab-' + d.table_id + '"><button class="tab_close close" type="button">&times;</button> ' + d.table_name + '</a></li>');
			$("#main_tabs_content").append('<div class="tab-pane" id="game-' + d.table_id + '"><div class="container-fluid"><div class="row-fluid"><div class="span8"><div class="table" id="game-' + d.table_id + '-table"><div class="player_text" id="game-' + d.table_id + '-player_text"></div><div class="player_cards" id="game-' + d.table_id + '-player_cards"></div><div class="community_cards" id="game-' + d.table_id + '-community_cards"></div><div class="pot" id="game-' + d.table_id + '-pot"></div><div class="opponent_text" id="game-' + d.table_id + '-opponent_text"></div><div class="opponent_cards" id="game-' + d.table_id + '-opponent_cards"></div></div><div id="game-' + d.table_id + '-game_controls" class="game_controls"></div></div><div class="span4" style="margin-top: -10px;"><ul class="nav nav-tabs" id="side_tabs"><li class="active"><a href="#game-' + d.table_id + '-play" data-toggle="tab">Game Details</a></li><li><a href="#game-' + d.table_id + '-standings" data-toggle="tab">Standings</a></li></ul><div class="tab-content"><div class="tab-pane active panel" id="game-' + d.table_id + '-play"><h3>Game Details</h3><div id="game-' + d.table_id + '-play-log" class="game_log"><ul></ul></div></div><div class="tab-pane panel" id="game-' + d.table_id + '-standings"><h3>Standings</h3><div class="standings"><p>Not yet implemented (it\'s heads up anyway!)</div></div></div><div class="panel"><h3>Player Chat</h3><div id="game-' + d.table_id + '-chat-log" class="game_log chat"><ul></ul></div><div class="form-inline" style="padding: 5px;"><input type="text" id="game-' + d.table_id + '-chat-txt" rel="' + d.table_id + '" class="game-chat-txt span12" placeholder="Enter text to send" /></div></div></div></div></div><br /></div>');
			$('.tab-' + d.table_id).tab('show');
		});
		
		socket.bind("game_render", function(d) {
            if (pause_showdown == d.game_id) {
        		paused_render = d;
        	} else {
        		$.each(d, function(i, v) {
					if (i != 'game_id') {
						$("#" + i).html(v);
					}
				});
			}
		});

		$(".game-chat-txt").live("keypress", function(e) {
            if (e.which == 13 && $(this).val() != "") {
                socket.send('game_chat', {
                    msg: $(this).val(),
                    game_id: $(this).attr('rel')
                });
                $(this).val('');
            }
        });

        socket.bind('game_chat', function(d) {
        	if (d.from == "system") {
				str = '<li class="admin">' + d.msg + '</li>';
			} else {
				str = '<li><span class="user">' + d.from + '</span>: ' + d.msg + '</li>';
			}
			$('#game-' + d.game_id + '-chat-log ul').append(str);
			$('#game-' + d.game_id + '-chat-log').scrollTo('100%');
        });

        socket.bind('game_log', function(d) {
        	var game_id = d.game_id;
    		$.each(d.msgs, function(i, v) {
                if (v.substr(0, 19) == '--- Starting Hand #' || v == '<b>*** SHOWDOWN ***</b>' || v == '<b>-- Hand Complete, No Showdown --</b>' || v.substr(0, 20) == 'The tournament owner') {
        			$('#game-' + game_id + '-play-log ul').append('<li class="start_hand">' + v + '</li>')
        		} else {
        			$('#game-' + game_id + '-play-log ul').append('<li>' + v + '</li>')
        		}
                
                $('#game-' + game_id + '-play-log').scrollTo('100%');

        	});
        });

        socket.bind('game_showdown', function(d) {
        	pause_showdown = d.game_id;
        	// pause game to show cards at showdown!
        	setTimeout(resume_game, 3000);
        });

        socket.bind('end_of_game', function(d) {
            pause_showdown = '';
            paused_render = '';
            $("#create_game").removeClass('disabled');
        });

        function resume_game() {
        	game_id = pause_showdown;
        	pause_showdown = false;
        	$.each(paused_render, function(i, v) {
				if (i != 'game_id') {
					$("#" + i).html(v);
				}
        	});
            paused_render = '';
        }

        $("#refresh").live('click', function(e) {
        	location.reload();
        });

        $(".fold_btn").live("click", function(e) {
        	e.preventDefault();
        	$(this).parent().parent().html('');
        	socket.send('fold', {
        		action: 'fold',
        		game_id: $(this).attr('rel')
        	});
        });

        $(".check_btn").live("click", function(e) {
        	e.preventDefault();
        	$(this).parent().parent().html('');
        	socket.send('check', {
        		action: 'check',
        		game_id: $(this).attr('rel')
        	});
        });
        
        $(".call_btn").live("click", function(e) {
        	e.preventDefault();
        	$(this).parent().parent().html('');
        	socket.send('call', {
        		action: 'call',
        		game_id: $(this).attr('rel')
        	});
        });

        $(".raise_btn").live("click", function(e) {
        	e.preventDefault();
        	var raise_amt = $('#game-' + $(this).attr('rel') + '-raise_amt').val();
        	
        	$(this).parent().parent().html('');
        	socket.send('raise', {
        		action: 'raise',
        		game_id: $(this).attr('rel'),
        		raise_amt: raise_amt
        	});
        });

        $(".tab_close").live('click', function() {
        	// TODO: 'are you sure?' prompt
        	socket.send('leave_game', {
        		game_id: $(id)
        	});
        	var id = $(this).parent().attr('href');
        	$("#main_tabs a[href='" + id + "']").remove();
        	$("#main_tabs_content " + id).remove();
        	$("#main_tabs a[href='#lobby']").tab('show');
        });
	}
});
