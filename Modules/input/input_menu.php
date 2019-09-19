<?php

    $menu['sidebar']['emoncms'][] = array(
        'text' => _("Inputs"),
        'path' => 'input/view',
        'icon' => 'input',
        'order' => 10
    );

// these <symbols> will be included into the theme.php file within the <svg><defs> 
$menu['includes']['icons'][] = <<<ICONS
<symbol id="icon-checkbox-checked" viewBox="0 0 32 32">
<!--<title>checkbox-checked</title>-->
<path d="M28 0h-24c-2.2 0-4 1.8-4 4v24c0 2.2 1.8 4 4 4h24c2.2 0 4-1.8 4-4v-24c0-2.2-1.8-4-4-4zM14 24.828l-7.414-7.414 2.828-2.828 4.586 4.586 9.586-9.586 2.828 2.828-12.414 12.414z"></path>
</symbol>

<symbol id="icon-checkbox-unchecked" viewBox="0 0 32 32">
<!--<title>checkbox-unchecked</title>-->
<path d="M28 0h-24c-2.2 0-4 1.8-4 4v24c0 2.2 1.8 4 4 4h24c2.2 0 4-1.8 4-4v-24c0-2.2-1.8-4-4-4zM28 28h-24v-24h24v24z"></path>
</symbol>
ICONS;

// use them like this:
// <svg class="icon"><use xlink:href="#icon-checkbox-unchecked"></use></svg>
