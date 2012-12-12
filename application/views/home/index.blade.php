<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>LaravelPkr</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width">

        {{ HTML::style('_resources/css/bootstrap.min.css') }}
        {{ HTML::style('_resources/css/font-awesome.css') }}
        {{ HTML::style('_resources/css/style.css') }}

        {{ HTML::script('_resources/js/vendor/modernizr-2.6.2-respond-1.1.0.min.js') }}
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="chromeframe">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">activate Google Chrome Frame</a> to improve your experience.</p>
        <![endif]-->

        <div class="container-fluid">

            <div class="row-fluid" id="header">
                <div class="span6">
                    <h1>Site Name</h1>
                </div>
                <div class="span6" style="text-align: right;">
                    <b>Contact Support:</b> <a href="#" style="color: #6b6b6b">support@pkrroom.com</a>
                </div>
            </div>

            <div class="row-fluid" id="main_contain">
                <div class="span12">
                    <ul class="nav nav-tabs" id="main_tabs">
                        <!-- <li class="pull-right"><a href="#user-prefs" data-toggle="tab">Preferences</a></li> -->
                        <li class="active pull-right"><a href="#lobby" data-toggle="tab">Main Lobby</a></li>
                    </ul>

                    <div class="tab-content" id="main_tabs_content">
                        <div class="tab-pane active" id="lobby">
                            <div class="container-fluid">
                                <div class="row-fluid">
                                    <div class="span8">
                                        <div id="lobby_chat" class="chat">
                                            <ul>
                                                <li class="admin">Welcome to the lobby!</li>
                                            </ul>
                                        </div>
                                        <div class="form-inline" style="padding: 5px;">
                                            <input type="text" id="lobby-chat-txt" class="span12" placeholder="Enter text to send" />
                                        </div>
                                    </div>

                                    <div class="span4" style="margin-top: -10px;">
                                        <ul class="nav nav-tabs" id="side_tabs">
                                            <li class="active"><a href="#lobby-players" data-toggle="tab">Online Players</a></li>
                                            <li><a href="#lobby-games" data-toggle="tab">Games</a></li>
                                        </ul>

                                        <div class="tab-content">
                                            <div class="tab-pane active" id="lobby-players">
                                                <div id="online_players">
                                                    <ul>
                                                        
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="tab-pane" id="lobby-games">
                                                <div id="lobby_games">
                                                    <ul>
                                                        <li id="create-li"><a class="btn btn-mini" id="create_game" href="#">Create New Game&hellip;</a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!--
                        <div class="tab-pane" id="user-prefs">
                            <h2>user prefs</h2>
                            <p>user prefs go here</p>
                        </div>
                        -->
                    </div>

                </div>
            </div>

        </div> <!-- /container -->

        <div id="modal" class="modal fade hide"></div>
        <div id="loading" class="modal fade hide"></div>
        <div id="login-modal" class="modal fade hide" data-backdrop="static" data-keyboard="false">
            <div class="modal-header">
                <h3>User Login</h3>
            </div>
            <div class="modal-body">
                <p>Enter a name which you would like to be known as at the tables:</p>
                <form> 
                    <fieldset>
                        <div class="clearfix"> 
                            <label for="username">Username</label> 
                            <div class="input"> 
                                <input autocomplete="off" class="xlarge" id="username" name="username" size="30" type="text" /> 
                            </div> 
                        </div>
                    </fieldset>
                </form>
            </div>
            <div class="modal-footer">
                <button id="user-login" class="btn btn-primary">Login</button>
            </div>
        </div>

        {{ HTML::script('_resources/js/vendor/jquery-1.8.3.min.js') }}

        {{ HTML::script('_resources/js/vendor/bootstrap.min.js') }}
        
        {{ HTML::script('_resources/js/plugins.js') }}
        {{ HTML::script('_resources/js/main.js') }}
        
    </body>
</html>