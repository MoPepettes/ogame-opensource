<?php

// Строительство построек.

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "techshort", $GlobalUser['lang'] );
loca_add ( "build", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ( $GlobalUser['player_id'], intval ($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);

// Обработка параметров.
if ( key_exists ('modus', $_GET) && !$GlobalUser['vacation'] )
{
    if ( $_GET['modus'] === 'add' ) BuildEnque ( intval ($_GET['planet']), intval ($_GET['techid']), 0 );
    else if ( $_GET['modus'] === 'destroy' ) BuildEnque ( intval ($_GET['planet']), intval ($_GET['techid']), 1 );
    else if ( $_GET['modus'] === 'remove' ) BuildDeque ( intval ($_GET['planet']), intval ($_GET['listid']) );
}

$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];
$prem = PremiumStatus ($GlobalUser);

$unitab = LoadUniverse ( );
$speed = $unitab['speed'];

PageHeader ("b_building");

$buildmap = array ( 1, 2, 3, 4, 12, 14, 15, 21, 22, 23, 24, 31, 33, 34, 41, 42, 43, 44 );

BeginContent();

?>
<script type="text/javascript">
<!--
function t() {
	v = new Date();
	var bxx = document.getElementById('bxx');
	var timeout = 1;
	n=new Date();
	if (!('dpp' in bxx)) {
		bxx.dpp = n.getTime() + pp * 1000;
	}
	ss=Math.round((bxx.dpp-n.getTime())/1000.);
	aa=Math.round((n.getTime()-v.getTime())/1000.);
	s=ss-aa;
	m=0;
	h=0;
	
	if ((ss + 3) < aa) {
	  bxx.innerHTML="<?=loca("BUILD_COMPLETE");?><br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+"><?=loca("BUILD_NEXT");?></a>";
	  
	  if ((ss + 6) >= aa) {	    
	  	window.setTimeout('document.location.href="index.php?page=b_building&session='+ps+'&planet='+pl+'";', 3500);
  	  }
	} else {
	if(s < 0) {
	    if (1) {
			bxx.innerHTML="<?=loca("BUILD_COMPLETE");?><br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+"><?=loca("BUILD_NEXT");?></a>";
			window.setTimeout('document.location.href="index.php?page=b_building&session='+ps+'&planet='+pl+'";', 2000);
		} else {
			timeout = 0;
			bxx.innerHTML="<?=loca("BUILD_COMPLETE");?><br>"+"<a href=index.php?page=b_building&session="+ps+"&planet="+pl+"><?=loca("BUILD_NEXT");?></a>";
		}
	} else {
		if(s>59){
			m=Math.floor(s/60);
			s=s-m*60;
		}
        if(m>59){
        	h=Math.floor(m/60);
        	m=m-h*60;
        }
        if(s<10){
        	s="0"+s;
        }
        if(m<10){
        	m="0"+m;
        }
        
        if (1) {
        	bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=b_building&session="+ps+"&listid="+pk+"&modus="+pm+"&planet="+pl+"><?=loca("BUILD_CANCEL");?></a>";
    	} else {
    		bxx.innerHTML=h+":"+m+":"+s+"<br><a href=index.php?page=b_building&session="+ps+"&listid="+pk+"&modus="+pm+"&planet="+pl+"><?=loca("BUILD_CANCEL");?></a>";
    	}
	}    
	if (timeout == 1) {
    	window.setTimeout("t();", 999);
    }
    }
}
//-->
</script>

<?php

if ( $GlobalUser['vacation'] ) {
    echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>\n\n";
}

echo "<table align=top ><tr><td style='background-color:transparent;'>\n";
if ( $GlobalUser['useskin'] ) echo "<table width=\"530\">\n";
else echo "<table width=\"468\">\n";

// Проверить ведется ли исследование или нет.
$result = GetResearchQueue ( $GlobalUser['player_id'] );
$resqueue = dbarray ($result);
$reslab_operating = ($resqueue != null);

// Проверить ведется ли постройка на верфи.
$result = GetShipyardQueue ( $aktplanet['planet_id'] );
$shipqueue = dbarray ($result);
$shipyard_operating = ($shipqueue != null);

// Вывести очередь построек (если активен Командир)
$result = GetBuildQueue ( $aktplanet['planet_id'] );
$cnt = dbrows ( $result );
for ( $i=0; $i<$cnt; $i++ )
{
    $queue = dbarray ($result);
    if ($i == 0) $queue0 = $queue;
    if ( $prem['commander'] )
    {
        if ( $queue['destroy'] ) $queue['level']++;
        echo "<tr><td class=\"l\" colspan=\"2\">".($i+1).".: ".loca("NAME_".$queue['tech_id']);
        if ($queue['level'] > 0) echo " , " . va(loca("BUILD_LEVEL"), $queue['level']);
        if ( $queue['destroy'] ) echo "\n " . loca("BUILD_DEMOLISH");
        if ($i==0) {
            echo "<td class=\"k\"><div id=\"bxx\" class=\"z\"></div><SCRIPT language=JavaScript>\n";
            echo "                  pp=\"".($queue['end']-$now)."\"\n";
            echo "                  pk=\"".$queue['list_id']."\"\n";
            echo "                  pm=\"remove\"\n";
            echo "                  pl=\"".$aktplanet['planet_id']."\"\n";
            echo "                  ps=\"$session\"\n";
            echo "                  t();\n";
            echo "                  </script></tr>\n";
        }
        else {
            echo "<td class=\"k\"><font color=\"red\"><a href=\"index.php?page=b_building&session=$session&modus=remove&listid=".$queue['list_id']."&planet=".$aktplanet['planet_id']."\">".loca("BUILD_DEQUEUE")."</a></font></td></td></tr>\n";
        }
    }
}

foreach ( $buildmap as $i => $id )
{
    $lvl = $aktplanet['b'.$id];
    if ( ! BuildMeetRequirement ( $GlobalUser, $aktplanet, $id ) ) continue;

    echo "<tr>";

    if ( $GlobalUser['useskin'] ) {
        echo "<td class=l>";
        echo "<a href=index.php?page=infos&session=$session&gid=".$id.">";
        echo "<img border='0' src=\"".UserSkin()."gebaeude/".$id.".gif\" align='top' width='120' height='120'></a></td>";
    }

    echo "<td class=l>";
    echo "<a href=index.php?page=infos&session=$session&gid=".$id.">".loca("NAME_$id")."</a></a>";
    if ( $lvl ) echo " (".va(loca("BUILD_LEVEL"), $lvl).")";
    echo "<br>". loca("SHORT_$id");
    $res = BuildPrice ( $id, $lvl+1 );
    $m = $res['m']; $k = $res['k']; $d = $res['d']; $e = $res['e'];
    echo "<br>".loca("BUILD_PRICE").":";
    if ($m) echo " ".loca("METAL").": <b>".nicenum($m)."</b>";
    if ($k) echo " ".loca("CRYSTAL").": <b>".nicenum($k)."</b>";
    if ($d) echo " ".loca("DEUTERIUM").": <b>".nicenum($d)."</b>";
    if ($e) echo " ".loca("ENERGY").": <b>".nicenum($e)."</b>";
    $t = BuildDuration ( $id, $lvl+1, $aktplanet['b14'], $aktplanet['b15'], $speed );
    echo "<br>".loca("BUILD_DURATION").": ".BuildDurationFormat ( $t )."<br>";

    if ( $prem['commander'] ) {
        if ( $cnt ) {
            if ( $cnt < 5) echo "<td class=k><a href=\"index.php?page=b_building&session=$session&modus=add&techid=$id&planet=".$aktplanet['planet_id']."\">".loca("BUILD_ENQUEUE")."</a></td>";
            else echo "<td class=k>";
        }
        else
        {
                  if ( $aktplanet['fields'] >= $aktplanet['maxfields'] ) {
                        echo "<td class=l><font color=#FF0000>".loca("BUILD_QUEUE_FULL")."</font>";
                  }
                  else if ( $id == 31 && $reslab_operating ) {
				echo "<td class=l><font  color=#FF0000>".loca("BUILD_BUSY")."</font> <br>";
			}
			else if ( ($id == 15 || $id == 21 ) && $shipyard_operating ) {
				echo "<td class=l><font  color=#FF0000>".loca("BUILD_BUSY")."</font> <br>";
			}
   			else if ( $lvl == 0 )
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>".loca("BUILD_BUILD")."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>".loca("BUILD_BUILD")."</font>\n";
			}
   			else
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>".va(loca("BUILD_BUILD_LEVEL"),$lvl+1)."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>".va(loca("BUILD_BUILD_LEVEL"),$lvl+1)."</font>";
			}
        }
    }
    else {
        if ( $cnt ) {
            if ( $queue0['tech_id'] == $id )
            {
                $left = $queue0['end'] - time ();
                echo "<td class=k><div id=\"bxx\" class=\"z\"></div><SCRIPT language=JavaScript>pp='".$left."'; pk='1'; pm='remove'; pl='".$aktplanet['planet_id']."'; ps='".$_GET['session']."'; t();</script>\n";
            }
            else echo "<td class=k>";
        }
        else {
                  if ( $aktplanet['fields'] >= $aktplanet['maxfields'] ) {
                        echo "<td class=l><font color=#FF0000>".loca("BUILD_QUEUE_FULL")."</font>";
                  }
			else if ( $id == 31 && $reslab_operating ) {
				echo "<td class=l><font  color=#FF0000>".loca("BUILD_BUSY")."</font> <br>";
			}
			else if ( ($id == 15 || $id == 21 ) && $shipyard_operating ) {
				echo "<td class=l><font  color=#FF0000>".loca("BUILD_BUSY")."</font> <br>";
			}
   			else if ( $lvl == 0 )
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>".loca("BUILD_BUILD")."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>".loca("BUILD_BUILD")."</font>\n";
			}
   			else
      		{
        		if ( IsEnoughResources ( $aktplanet, $m, $k, $d, $e )) echo "<td class=l><a href='index.php?page=b_building&session=$session&modus=add&techid=".$id."&planet=".$aktplanet['planet_id']."'><font color=#00FF00>".va(loca("BUILD_BUILD_LEVEL"),$lvl+1)."</font></a>\n";
          		else echo "<td class=l><font color=#FF0000>".va(loca("BUILD_BUILD_LEVEL"),$lvl+1)."</font>";
			}
		}
    }
    echo "</td></tr>\n";
}

echo "  </table>\n</tr>\n</table>\n";

echo "<br><br><br><br>\n";
EndContent();

PageFooter ();
ob_end_flush ();
?>