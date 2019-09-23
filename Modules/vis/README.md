# Visualisation tools

The visualisation toolbox deals with two type of objects : feeds and/or multigraphs

## vis controller routes

The route /vis/list gives access to the visualisation control panel in order for users to create new visualisations
It uses the [/Modules/vis/Views/vis_main_view.php](/Modules/vis/Views/vis_main_view.php) to render the toolbox....

When vis objects are integrated in dashboards or external website or viewed through standalone visualisations, 
the followed route looks like <b>/vis/visualisation_key</b>, where visualisation_key can be multigraph, bargraph 
(cf [/Modules/vis/vis_object.php](/Modules/vis/vis_object.php) to find all existing vis objects)
the route must include either : 
- the mid parameter when dealing with a multigraph
- the feedid parameter when dealing with a feed

Minimal vis routes can be : /vis/multigraph?mid=1 or /vis/graph?feedid=1

An embed parameter can be used : 
- full screen view / integration into dashboard : /vis/multigraph?mid=1&embed=1
- within the 'main' viewport of emoncms : /vis/multigraph?mid=1&embed=0

For visitors or usage outside of emoncms, the read_only apikey can also be given as a parameter : 
/vis/multigraph?mid=1&embed=0&apikey=32_chars_apikey_read

## vis objects

The different visualisations source codes are stored in  [/Modules/vis/visualisations](/Modules/vis/visualisations)

Each php file includes specific instructions to catch the url parameters in order to cope with the various usages previously described.
That's why the script section of each individual vis object generally begins by extracting 
from the url the value of feedid/mid, embed, apikey  .....

## edition facilities

The EditDaily and EditRealtime vis objects permits to edit respectively PhpTimeSeries and PhpFina feeds 

To achieve the edition function, please note they must be run in full screen mode
