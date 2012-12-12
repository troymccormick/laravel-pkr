# Laravel Websocket Poker

## About

Laravel Websocket Poker is currently a work in progress. I am starting it as a simple game of heads up Texas Hold'em poker with the goal to allow you to host multi-table tournaments through the system with a robust admin panel and admin controls while playing the game.

I'm @mccormick_troy or you can email me directly (mccormick.troy@gmail.com) if you have any trouble getting setup!

## Current Feature Overview

- HTML5 Websocket powered (via a PHP websocket class)

    Laravel artisan task runs the server with complete logs

- Very simple heads up poker

    Any player can start a game to play against someone else

- Lobby and game chat

    Players may chat with everyone in the lobby, or just their opponent in their own game


## Installation

The installation of Laravel Websocket Poker is much the same as Laravel. Simply extract the contents of this project to your web root, point a virtual host to the public folder as you would for a new install then:

- Open application/config/application.php

Make any necessary changes (fully documented in the source)

- Open public/_resources/js/main.js

Make any necessary changes (fully documented in the source)

Finally, start the server with `php artisan server` and you are ready to play!


## Future Plans

- Minor graphic improvement like displaying chips instead of text for pot and current stake
- Complete multi-table tournaments
- Customizable blinds structure and starting chips
- Full admin interface behind the scenes and while playing
- Different implementation of Websockets (Ratchet?)
- Make the layout work for mobile devices (somewhat close now)


## Thanks

Thanks to the following who provided their code open source for my use!

- [Laravel](http://www.laravel.com) - AMAZING PHP framework
- [Twitter Bootstrap](http://twitter.github.com/bootstrap) - Dead simple responsive layouts
- [jQuery](http://www.jquery.com) - Dead simple javascripting
- [Texas Hold'em Evaluation Class](http://www.phpclasses.org/package/4573-PHP-Evaluate-a-Poker-Texas-Hold-em-hands.html) - Ugly code, but gets the job done (maybe rewrite this?)
- [Flynsarmy - PHP Websocket Class](http://www.flynsarmy.com/2012/02/php-websocket-chat-application-2-0/) - Super easy websocket server that works perfectly for me
- Card Images (I'm very sorry, but I've forgotten where I've found these card images. If you know the author, or are the author, please let me know so I may properly credit you!)
