<?php

if(is_numeric($vars['svc']))
{

$graph_types = array("bits"   => "Bits",
                     "pkts"   => "Packets",
                     "conns"  => "Connections",
                     "reqs"   => "Requests");

$i=0;

echo("<table class=\"table table-striped table-condensed\" style=\"margin-top: 10px;\">\n");
echo("  <thead>\n");
echo("  </thead>");

foreach (dbFetchRows("SELECT * FROM `netscaler_services` WHERE `device_id` = ? AND `svc_id` = ? ORDER BY `svc_name`", array($device['device_id'], $vars['svc'])) as $svc)
{

  if (is_integer($i/2)) { $bg_colour = $list_colour_a; } else { $bg_colour = $list_colour_b; }

  if ($svc['svc_state'] == "up") { $svc_class="green"; } else { $svc_class="red"; }

  echo("<tr bgcolor='$bg_colour'>");
  echo('<td width=320 class=object-name><a href="'.generate_url($vars, array('svc' => $svc['svc_id'], 'view' => NULL, 'graph' => NULL)).'">' . $svc['svc_name'] . '</strong></td>');
  echo("<td width=320>" . $svc['svc_ip'] . ":" . $svc['svc_port'] . "</a></td>");
  echo("<td width=100><span class='".$svc_class."'>" . $svc['svc_state'] . "</span></td>");
  echo("<td width=320>" . format_si($svc['svc_bps_in']*8) . "bps</a></td>");
  echo("<td width=320>" . format_si($svc['svc_bps_out']*8) . "bps</a></td>");
  echo("</tr>");

  $vsvrs = dbFetchRows("SELECT * FROM `netscaler_services_vservers` AS SV, `netscaler_vservers` AS v WHERE v.vsvr_name = SV.vsvr_name AND SV.svc_name = '".$svc['svc_name']."'");
  if(count($vsvrs))
  {
    echo('<tr><td colspan="5">');
    echo("<table class=\"table table-striped table-condensed\" style=\"margin-top: 10px;\">\n");
    echo("  <thead>\n");
    echo("    <th>Vserver</th>");
    echo("    <th>Address</th>");
    echo("    <th>Status</th>");
    echo("    <th>Input</th>");
    echo("    <th>Output</th>");
    echo("  </thead>");

    foreach ($vsvrs as $vsvr)
    {
      if ($vsvr['vsvr_state'] == "up") { $vsvr_class="green"; } else { $vsvr_class="red"; }
      echo("<tr>");
      echo('<td width=320 class="object-name"><a href="'.generate_url($vars, array('type' => 'netscaler_vsvr', 'vsvr' => $vsvr['vsvr_id'], 'svc' => NULL, 'view' => NULL, 'graph' => NULL)).'">' . $vsvr['vsvr_name'] . '</a></td>');
      echo("<td width=320>" . $vsvr['vsvr_ip'] . ":" . $vsvr['vsvr_port'] . "</a></td>");
      echo("<td width=100><span class='".$vsvr_class."'>" . $vsvr['vsvr_state'] . "</span></td>");
      echo("<td width=320>" . format_si($vsvr['vsvr_bps_in']*8) . "bps</a></td>");
      echo("<td width=320>" . format_si($vsvr['vsvr_bps_out']*8) . "bps</a></td>");
      echo("</tr>");
    }
  }

  foreach ($graph_types as $graph_type => $graph_text)
  {
    $i++;
    echo('<tr class="list-bold" bgcolor="'.$bg_colour.'">');
    echo('<td colspan="5">');
    $graph_type = "netscalersvc_" . $graph_type;
    $graph_array['height'] = "100";
    $graph_array['width']  = "213";
    $graph_array['to']     = $config['time']['now'];
    $graph_array['id']     = $svc['svc_id'];
    $graph_array['type']   = $graph_type;

    echo('<h3>'.$graph_text.'</h3>');

    include("includes/print-graphrow.inc.php");

    echo("
    </td>
    </tr>");
  }
}

echo("</table>");

} else {

// No service was specified so we show aggregate and a list of services in table

if(!$vars['graph'])
{ $graph_type = "device_netscalersvc_bits"; } else {
  $graph_type = "device_netscalersvc_".$vars['graph'];  }

$graph_array['height'] = "100";
$graph_array['width']  = "213";
$graph_array['to']     = $config['time']['now'];
$graph_array['device'] = $device['device_id'];
$graph_array['nototal'] = "yes";
$graph_array['legend'] = "no";
$graph_array['type']   = $graph_type;
echo('<h5>Aggregate</h5>');
include("includes/print-graphrow.inc.php");
unset($graph_array);




print_optionbar_start();

echo("<span style='font-weight: bold;'>Services</span> &#187; ");

$menu_options = array('basic' => 'Basic',
                     );

if (!$vars['view']) { $vars['view'] = "basic"; }

$sep = "";
foreach ($menu_options as $option => $text)
{
  if ($vars['view'] == $option) { echo("<span class='pagemenu-selected'>"); }
  echo('<a href="'.generate_url($vars, array('view' => 'basic', 'graph' => NULL)).'">'.$text.'</a>');
  if ($vars['view'] == $option) { echo("</span>"); }
  echo(" | ");
}

unset($sep);
echo(' Graphs: ');
$graph_types = array("bits"   => "Bits",
                     "pkts"   => "Packets",
                     "conns"  => "Connections",
                     "reqs"   => "Requests");

foreach ($graph_types as $type => $descr)
{
  echo("$type_sep");
  if ($vars['graph'] == $type) { echo("<span class='pagemenu-selected'>"); }
  echo('<a href="'.generate_url($vars, array('view' => 'graphs', 'graph' => $type)).'">'.$descr.'</a>');
  if ($vars['graph'] == $type) { echo("</span>"); }
  $type_sep = " | ";
}

print_optionbar_end();

echo("<table class=\"table table-striped table-condensed\" style=\"margin-top: 10px;\">\n");
echo("  <thead>\n");
echo("    <tr>\n");
echo("      <th>Service</th>\n");
echo("      <th>Address</th>\n");
echo("      <th>Status</th>\n");
echo("      <th>Input</th>\n");
echo("      <th>Output</th>\n");
echo("    </tr>");
echo("  </thead>");
$i = "0";
foreach (dbFetchRows("SELECT * FROM `netscaler_services` WHERE `device_id` = ? ORDER BY `svc_name`", array($device['device_id'])) as $svc)
{
  if (is_integer($i/2)) { $bg_colour = $list_colour_a; } else { $bg_colour = $list_colour_b; }

  if ($svc['svc_state'] == "up") { $svc_class="green"; } else { $svc_class="red"; }

  echo("<tr bgcolor='$bg_colour'>");
  echo('<td width=320 class=object-name><a href="'.generate_url($vars, array('svc' => $svc['svc_id'], 'view' => NULL, 'graph' => NULL)).'">' . $svc['svc_name'] . '</a></td>');
  echo("<td width=320>" . $svc['svc_ip'] . ":" . $svc['svc_port'] . "</a></td>");
  echo("<td width=100><span class='".$svc_class."'>" . $svc['svc_state'] . "</span></td>");
  echo("<td width=320>" . format_si($svc['svc_bps_in']*8) . "bps</a></td>");
  echo("<td width=320>" . format_si($svc['svc_bps_out']*8) . "bps</a></td>");
  echo("</tr>");
  if ($vars['view'] == "graphs")
  {
    echo('<tr class="list-bold" bgcolor="'.$bg_colour.'">');
    echo('<td colspan="5">');
    $graph_type = "netscalersvc_" . $vars['graph'];
    $graph_array['height'] = "100";
    $graph_array['width']  = "213";
    $graph_array['to']     = $config['time']['now'];
    $graph_array['id']     = $svc['svc_id'];
    $graph_array['type']   = $graph_type;

    include("includes/print-graphrow.inc.php");

    echo("
    </td>
    </tr>");
  }

echo("</td>");
echo("</tr>");

  $i++;
}

echo("</table>");

}

?>
