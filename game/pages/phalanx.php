<?php

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "fleetorder", $GlobalUser['lang'] );
loca_add ( "events", $GlobalUser['lang'] );
loca_add ( "phalanx", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

require_once "phalanx_events.php";

?>
<html>
 <head>
  <link rel='stylesheet' type='text/css' href='css/default.css' />
  <link rel='stylesheet' type='text/css' href='css/formate.css' />
  <link rel="stylesheet" type="text/css" href="<?=UserSkin();?>formate.css" />

  <meta http-equiv="content-type" content="text/html; charset=UTF-8" />

<link rel="stylesheet" type="text/css" href="css/combox.css">

<title><?=va(loca("PAGE_TITLE"), $GlobalUni['num']);?></title>

  <script src="js/utilities.js" type="text/javascript"></script>
  <script language="JavaScript">
    var session = "<?=$session;?>";
  </script>
<script type="text/javascript" src="js/overLib/overlib.js"></script>
<!-- HEADER -->

</head>
<body style="scrollbars: auto;" onunload="" >
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<!-- END HEADER -->

<!-- LEFTMENU -->


<!-- END LEFTMENU -->

<!-- CONTENT AREA -->

<br>
<table width="519">
 <tr>
  <td class="c" colspan="4">
<?=loca("PHALANX_REPORT");?> <a href="javascript:showGalaxy(<?=$aktplanet['g'];?>,<?=$aktplanet['s'];?>,<?=$aktplanet['p'];?>)" >[<?=$aktplanet['g'];?>:<?=$aktplanet['s'];?>:<?=$aktplanet['p'];?>]</a> (<?=$GlobalUser['oname'];?>)  </td>

 </tr>
 <tr>
  <td colspan="4" class="c"><?=loca("PHALANX_EVENTS");?></td>
 </tr>

<?php

    $target = GetPlanet ( intval($_GET['spid']) );

    $outofrange = false;                    // Проверить радиус фаланги
    if ( $aktplanet['g'] != $target['g'] || $aktplanet["b42"] <= 0 )  $outofrange = true;
    else {
        $range = $aktplanet["b42"] * $aktplanet["b42"] - 1;
        if ( abs($aktplanet['s'] - $target['s']) > $range) $outofrange = true;
    }

/*
    if ( $GlobalUser['vacation'] )            // Игрок в режиме отпуска.
    {
        echo "<font color=#FF0000><center>Режим отпуска минимум до  ".date ("Y-m-d H:i:s", $GlobalUser['vacation_until'])."</center></font>";
    }
    else
*/

    if ( $aktplanet["b42"] <= 0 )        // Попытка скана фаланги с планеты или с другой луны без фаланги
    {
        echo "<font color=#FF0000>".loca("PHALANX_ERR_MISSING")."</font>";
    }
    else if ( $aktplanet["d"] < 5000 )        // Недостаточно дейтерия
    {
        echo "<font color=#FF0000>".loca("PHALANX_ERR_DEUT")."</font>";
    }
    else if (                                        // Попытка читерства
        $target['owner_id'] == $GlobalUser['player_id']         ||          // скан своих планет
        $aktplanet['owner_id'] != $GlobalUser['player_id']      ||         // скан с чужой луны/планеты
        !( ( $target['type'] > PTYP_MOON && $target['type'] < PTYP_DF ) || $target['type'] == PTYP_DEST_PLANET )   ||           // скан НЕ (планеты или уничтоженной планеты)
        $outofrange                                                                        // скан за пределом радиуса фаланги.
    )        
    {
        // Выписать автоматический бан без РО на час.

        echo "<font color=#FF0000>".loca("PHALANX_ERR_CHEATER")."</font>";
    }
    else
    {
        PhalanxEventList ($target['planet_id']);

        // Списать 5000 дейтерия.
        $aktplanet['d'] -= 5000;
        $query = "UPDATE ".$db_prefix."planets SET d = '".$aktplanet['d']."', lastpeek = '".$now."' WHERE planet_id = " . $aktplanet['planet_id'];
        dbquery ($query);
    }

?></table>

<!-- END CONTENT AREA -->

 </body>
</html>
<?php
ob_end_flush ();
?>