<?php

    /*
        All Emoncms code is released under the GNU Affero General Public License.
        See COPYRIGHT.txt and LICENSE.txt.

        ---------------------------------------------------------------------
        Emoncms - open source energy visualisation
        Part of the OpenEnergyMonitor project:
        http://openenergymonitor.org
    */

    global $path, $session, $menu, $menu, $mysqli;
    if (!isset($session['profile'])) $session['profile'] = 0;

    $menu_left = $menu['left'];
    $menu_right = $menu['right'];
    $menu_dropdown = $menu['dropdown'];

    require "Modules/dashboard/Views/build_menu.php";
    $dashboardmenu = new Dashboardmenu($mysqli);

    $dashmenu = $dashboardmenu->build_menu($session['userid'],"view");    

    if ($session['write']) $menu_right[] = array('name'=>"<b>Docs</b>", 'path'=>"site/docs", 'order' => 0 );
    if (!$session['write']) $menu_right[] = array('name'=>"Log In", 'path'=>"user/login", 'order' => -1 );
?>

<ul class="nav">
    <?php

    foreach ($menu_left as $item)
	{
	    if ($item['name'] == "Dashboard"  && $session['userid'] && !$dashmenu == "") 
        {
            echo "<li class=\"dropdown\">";
            echo "<a href=\"".$path.$item['path']."\" data-toggle=\"dropdown\" class=\"dropdown-toggle\">".$item['name']." <b class=\"caret\"></b></a>";
            echo "<ul class=\"dropdown-menu\">";			
            echo $dashmenu;
            echo "</ul>";
            echo "</li>";

        }
		else {
            if (isset($item['session'])) 
            {
                if (isset($session[$item['session']]) && $session[$item['session']]==1) 
                {
                    echo "<li><a href=\"".$path.$item['path']."\">".$item['name']."</a></li>";
                }
            } 
            else 
            {
                echo "<li><a href=\"".$path.$item['path']."\">".$item['name']."</a></li>";
            }

        }

    }		

    ?>

    <?php if (count($menu_dropdown) && $session['read']) { ?>
    <li class="dropdown">
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Extras <b class="caret"></b></a>
        <ul class="dropdown-menu">
            <?php foreach ($menu_dropdown as $item) { ?>
                <?php if (isset($session[$item['session']]) && $session[$item['session']]==1) { ?>
                    <li><a href="<?php echo $path.$item['path']; ?>"><?php echo $item['name']; ?></a></li>
                <?php } ?>
            <?php } ?>
        </ul>
    </li>
    <?php } ?>
</ul>

<ul class="nav pull-right">
    <?php

    foreach ($menu_right as $item)
    {
        if (isset($item['session'])) {
            if (isset($session[$item['session']]) && $session[$item['session']]==1) {
                echo "<li><a href=".$path.$item['path']." >".$item['name']."</a></li>";
            }
        } else {
            echo "<li><a href=".$path.$item['path']." >".$item['name']."</a></li>";
        }
    }

    ?>
</ul>

