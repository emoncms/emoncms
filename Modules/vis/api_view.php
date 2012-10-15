<!--
All Emoncms code is released under the GNU Affero General Public License.
See COPYRIGHT.txt and LICENSE.txt.

---------------------------------------------------------------------
Emoncms - open source energy visualisation
Part of the OpenEnergyMonitor project:
http://openenergymonitor.org
-->

<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

global $path;
?>

<style>
  .apitext {
    border: 0;
    border-bottom: 1px solid #ccc;
    color: #F78623;
    font-weight: bold;
    width: 30px;
  }
</style>
  
  <h2><?php echo _('API Helper'); ?></h2>
  <table class="table table-striped ">
    <p><?php echo _('API keys are used to give authenticated access without login in via a normal session.'); ?></p>   
    <tr>
      <td>
        <b><?php echo _('Read only access: '); ?></b><?php echo $user['apikey_read']; ?>
        <br>
        <span class="label label-info"><?php echo _('Allows to access in read only mode'); ?></span>
      </td>
      <td>
        <form action="newapiread" method="post">
          <input type="submit" class="btn btn-warning" value="<?php echo _('new'); ?>">
        </form>
      </td>
    </tr>

    <tr>
      <td style="vertical-align:middle">
        <b><?php echo _('Write only access: '); ?></b><?php echo $user['apikey_write']; ?>
        <br>
        <span class="label label-warning"><?php echo _('Keep secret. Write mode access'); ?></span>
      </td>
      <td>
        <form action="newapiwrite" method="post">
          <input type="submit" class="btn btn-warning" value="<?php echo _('new'); ?>" >
        </form>
      </td>
    </tr>
  </table>
  
  <br><h2><?php echo _('Post API'); ?></h2>
  <table class="table table-striped ">
    <tr>
      <td>
        <p>
          <b>API url: </b><?php echo $GLOBALS['path']; ?>api/post
       </td>
       <td></td>
    </tr>
    <tr>
      <td>
        <p><b><?php echo _('Example: Click or copy this to your web browser or send from your monitoring hardware'); ?></b><br></p>
        <?php
          $testjson = $GLOBALS['path']."api/post?apikey=".$user['apikey_write']."&json={power:252.4,temperature:15.4}"
        ?>      
        <a href="<?php echo $testjson; ?>"><?php echo $testjson; ?></a>
      </td>
      <td>
        <a href="<?php echo $testjson; ?>" class="btn btn-info"><?php echo _('try me'); ?></a>
      </td>
    </tr>
    <tr>
      <td>
        <p><b><?php echo _('Using Node addressing'); ?></b><br></p>
        <?php
          $testjson = $GLOBALS['path']."api/post?apikey=".$user['apikey_write']."&node=1&json={power:252.4,temperature:15.4}"
        ?>      
        <?php echo $testjson; ?>
        <p><span class="label label-warning"><?php echo _('Change node_id from URL with the node identification'); ?></span></p>
      </td>
      <td>
        <a href="<?php echo $testjson; ?>" class="btn btn-info"><?php echo _('try me'); ?></a>
      </td>
    </tr>
  </table>

  <br><h2><?php echo _('Visualisation API'); ?></h2>
  <p><?php echo _('These are all the visualisations that are available in emoncms3. To view a visualisation enter in a relevant feedid in the underlined boxes below and then click on the > button.'); ?></p>  

  <table class='table table-striped '>
    <tr>
      <th>
        <?php echo _('Name'); ?>
      </th>
      <th style="text-align:right">
        <?php echo _('URL'); ?>
      </th>
      <th>
        <?php echo _('View'); ?>
      </th>
    </tr>
    <tr>
      <form action="realtime" method="get">
        <td>
          <?php echo _('Real-time graph'); ?>
        </td>
        <td style="text-align:right">
          vis/realtime?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="rawdata" method="get">
        <td>
          <?php echo _('Raw data graph'); ?>
        </td>
        <td style="text-align:right">
          vis/rawdata?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="bargraph" method="get">
        <td>
          <?php echo _('Bar graph'); ?>
        </td>
        <td style="text-align:right">
          vis/bargraph?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="smoothie" method="get">
        <td>
          <?php echo _('Smoothie'); ?>
        </td>
        <td style="text-align:right">
          vis/smoothie?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="histgraph" method="get">
        <td>
          <?php echo _('All time histogram'); ?>
        </td>
        <td style="text-align:right">
          vis/histgraph?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="dailyhistogram" method="get">
        <td>
          <?php echo _('Daily histogram'); ?>
         </td>
        <td style="text-align:right">
          vis/dailyhistogram?power=
          <input class="apitext" name="power" type='text'  />
          &kwhd=
          <input class="apitext" name="kwhd" type='text'  />
          &whw=
          <input class="apitext" name="whw" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="zoom" method="get">
        <td>
          <?php echo _('Zoom'); ?>
        </td>
        <td style="text-align:right">vis/zoom?power=
          <input class="apitext" name="power" type='text'  />
          &kwhd=
          <input class="apitext" name="kwhd" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>
    
    <tr>
      <form action="../vis/comparison" method="get">
        <td>
          <?php echo _('kWh/d Comparison'); ?>
        </td>
        <td style="text-align:right">
          vis/comparison?power=
          <input class="apitext" name="power" type='text' style='width:50px' />
          &amp;kwhd=
          <input class="apitext" name="kwhd" type='text' style='width:50px' />
          &amp;currency=
          <input class="apitext" name="currency" type='text' style='width:50px' />
          &amp;pricekwh=
          <input class="apitext" name="pricekwh" type='text' style='width:50px' />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="stacked" method="get">
        <td>
          <?php echo _('Stacked'); ?>
        </td>
        <td style="text-align:right">
          vis/stacked?kwhdA=
          <input class="apitext" name="kwhdA" type='text'  />
          &kwhdB=
          <input class="apitext" name="kwhdB" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr class="d1">
      <form action="threshold" method="get">
        <td>
          <?php echo _('Threshold'); ?>
        </td>
        <td style="text-align:right">
          vis/theshold/?feedid=
          <input class="apitext" name="feedid" type='text'  />
          &thresholdA=
          <input class="apitext" name="thresholdA" type='text'  />
          &thresholdB=
          <input class="apitext" name="thresholdB" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="simplezoom" method="get">
        <td>
          <?php echo _('Simple zoom'); ?>
        </td>
        <td style="text-align:right">
          vis/simplezoom?power=
          <input class="apitext" name="power" type='text'  />
          &kwhd=
          <input class="apitext" name="kwhd" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="orderbars" method="get">
        <td>
          <?php echo _('Bar graph (ordered by height)'); ?>
        </td>
        <td style="text-align:right">
          vis/orderbars?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="orderthreshold" method="get">
        <td>
          <?php echo _('Threshold ordered by height'); ?></td>
        <td style="text-align:right">
          vis/orderthreshold?feedid=
          <input class="apitext" name="feedid" type='text'  />
          &power=
          <input class="apitext" name="power" type='text'  />
          &thresholdA=
          <input class="apitext" name="thresholdA" type='text'  />
          &thresholdB=
          <input class="apitext" name="thresholdB" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="multigraph" method="get">
        <td>
          <?php echo _('Multigraph'); ?>
        </td>
        <td style="text-align:right">
          vis/multigraph
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

    <tr>
      <form action="edit" method="get">
        <td>
          <?php echo _('Datapoint Editor'); ?>
        </td>
        <td style="text-align:right">
          vis/edit?feedid=
          <input class="apitext" name="feedid" type='text'  />
        </td>
        <td>
          <input type="submit" value=">" class="btn btn-info"/>
        </td>
      </form>
    </tr>

  </table>
  
  <h3><?php echo _('Other options:'); ?></h3>

  <table class='table table-striped '>
    <tr>
      <td>
        <b><?php echo _('Hide menu') ?></b>   
        <br>
        <?php echo _('Hide the top menu and footer by adding the attribute &embed=1 to the URL.'); ?> 
      </td>
    </tr>
    <tr>
      <td>
        <b><?php echo _('Share'); ?></b>
        <br>
        <?php echo _('To share a visualisation use your read apikey. Add the attribute '); ?><i>&apikey=<?php echo $user['apikey_read']; ?></i><?php echo _(' to the URL'); ?>
      </td>
    </tr>   
    <tr>
      <td>
        <b><?php echo _('Embed'); ?></b>
        <br>
         <?php echo htmlspecialchars('<iframe style="width:650px; height:400px;" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="' . $path . 'vis/rawdata?feedid=1&apikey=' . $user['apikey_read'] . '&embed=1"></iframe>'); ?>
      </td>
    </tr>
    <tr>
      <td>
        <b><?php echo _('Reset Multigraph'); ?></b>
        <br>
        <?php echo _('The multigraph can be reset using the &clear=1 attribute.'); ?>
      </td>
    </tr>   
  </table>

