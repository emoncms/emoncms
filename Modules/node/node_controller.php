<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function node_controller()
{
    global $mysqli, $redis, $session, $route, $feed_settings;
    $result = false;

    include "Modules/feed/feed_model.php";
    $feed = new Feed($mysqli,$redis,$feed_settings);

    require "Modules/input/input_model.php"; // 295
    $input = new Input($mysqli,$redis, $feed);

    require "Modules/input/process_model.php"; // 886
    $process = new Process($mysqli,$input,$feed);

    include "Modules/node/node_model.php";
    $node = new Node($mysqli,$redis,$process);

    if ($route->format == 'html') {
        if ($route->action == "list" && $session['write']) $result = view("Modules/node/node_view.php",array());
        if ($route->action == "config" && $session['write']) $result = view("Modules/node/process_list.php",array());
    }

    if ($route->format == 'json') {
        if ($route->action == 'set' && $session['write']) $result = $node->set($session['userid'],get('nodeid'),get('time'),get('data'));
        if ($route->action == 'setdecoder' && $session['write']) $result = $node->set_decoder($session['userid'],get('nodeid'),get('decoder'));
        if ($route->action == 'getall' && $session['write']) $result = $node->get_all($session['userid']);
    }

    return array('content'=>$result);
}
