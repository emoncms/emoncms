<?php

  $domain = "messages";
  bindtextdomain($domain, "Modules/node/locale");
  bind_textdomain_codeset($domain, 'UTF-8');

  $menu_left[] = array('name'=> dgettext($domain, "Node"), 'path'=>"node/list" , 'session'=>"write", 'order' => 0 );