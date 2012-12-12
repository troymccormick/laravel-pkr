<?php

class Card
{
	private $rank;
	private $suit;

	function __construct($rank, $suit) {
		$this->set_rank($rank);
		$this->set_suit($suit);
	}

	public static function render_card_space() {
		return ' ';
    }

    public static function render_card_back()
    {
        return '<span class="card back">&nbsp;</span> ';
    }

    public function render()
    {
        $color = $this->get_color();
        $suit = substr($this->suit, 0, 1);
        $entity = $this->get_entity();
        return '<span class="card ' . $color . ' x' . strtolower($this->rank . $suit) . '">' . $this->rank . $entity . '</span> ';
    }

    public function set_rank($rank)
    {
        if(in_array($rank, Card::get_ranks())) {
            $this->rank = $rank;
        }
    }

    public function get_long_rank($rank = '') {
        if ($rank == '') {
            $rank = $this->rank;
        }
        switch ($rank) {
            case 'J':
                return 'jack';
                break;
            case 'Q':
                return 'queen';
                break;
            case 'K':
                return 'king';
                break;
            case 'A':
                return 'ace';
                break;
            default:
                return $rank;
        }
    }

    public function get_numeric_rank($rank = '') {
        if ($rank == '') {
            $rank = $this->rank;
        }
        switch($rank) {
            case 'T':
                return 10;
                break;
            case 'J':
                return 11;
                break;
            case 'Q':
                return 12;
                break;
            case 'K':
                return 13;
                break;
            case 'A':
                return 14;
                break;
            default:
                return $rank;
        }
    }

    public function get_rank()
    {
        return $this->rank;
    }

    public function set_suit($suit)
    {
        if (in_array($suit, Card::get_suits())) {
            $this->suit = $suit;
        }
    }

    public function get_suit()
    {
        return $this->suit;
    }

    public static function get_suits()
    {
        return array('clubs', 'diamonds', 'spades', 'hearts');
    }

    public static function get_ranks()
    {
        $ret = range(2,9);
        $ret[] = 'T';
        $ret[] = 'J';
        $ret[] = 'Q';
        $ret[] = 'K';
        $ret[] = 'A';
        return $ret;
    }

    public function get_color($suit = '')
    {
        if ($suit == '') {
            $suit = $this->suit;
        }
        if ($suit == "clubs" || $suit == "spades") {
            return 'black';
        }
        return 'red';
    }

    public function get_entity($suit = '')
    {
        if ($suit == '') {
            $suit = $this->get_suit();
        }
        switch($suit) {
            case "clubs":
                return '&clubs;';
            case "diamonds":
                return '&diams;';
            case "spades":
                return '&spades;';
            case "hearts":
                return '&hearts;';
            default:
                return '?';
        }
    }

    public function card_compare($a, $b) {
        $a = $a->get_numeric_rank();
        $b = $b->get_numeric_rank();

        if ($a == $b) {
            return 0;
        } elseif ($a < $b) {
            return -1;
        } else {
            return 1;
        }
    }
}