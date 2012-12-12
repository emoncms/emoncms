  <?php
  /*

  All Emoncms code is released under the GNU Affero General Public License.
  See COPYRIGHT.txt and LICENSE.txt.

  ---------------------------------------------------------------------
  Emoncms - open source energy visualisation
  Part of the OpenEnergyMonitor project:
  http://openenergymonitor.org

  */

  $widgets = array();
  $dir = scandir("Modules/dashboard/Views/js/widgets");
  for ($i=2; $i<count($dir); $i++)
  {
    if (filetype("Modules/dashboard/Views/js/widgets/".$dir[$i])=='dir') 
    {
      if (is_file("Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_widget.php"))
      {
        require_once "Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_widget.php";
        $widgets[] = $dir[$i];
      }
      else if (is_file("Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_render.js"))
      {
        echo "<script type='text/javascript' src='".$path."Modules/dashboard/Views/js/widgets/".$dir[$i]."/".$dir[$i]."_render.js'></script>";
        $widgets[] = $dir[$i];
      }
    }
  }

  // Load module specific widgets

  $dir = scandir("Modules");
  for ($i=2; $i<count($dir); $i++)
  {
    if (filetype("Modules/".$dir[$i])=='dir') 
    {
      if (is_file("Modules/".$dir[$i]."/widget/".$dir[$i]."_widget.php"))
      {
        require_once "Modules/".$dir[$i]."/widget/".$dir[$i]."_widget.php";
        $widgets[] = $dir[$i];
      }
      else if (is_file("Modules/".$dir[$i]."/widget/".$dir[$i]."_render.js"))
      {
        echo "<script type='text/javascript' src='".$path."Modules/".$dir[$i]."/widget/".$dir[$i]."_render.js'></script>";
        $widgets[] = $dir[$i];
      }
    }
  }

?>
