<?php

// Меню `Мой альянс`

$SearchResults = "";
$AllianzenError = "";

loca_add ( "menu", $GlobalUser['lang'] );
loca_add ( "ally", $GlobalUser['lang'] );

if ( key_exists ('cp', $_GET)) SelectPlanet ($GlobalUser['player_id'], intval($_GET['cp']));
$GlobalUser['aktplanet'] = GetSelectedPlanet ($GlobalUser['player_id']);
$now = time();
UpdateQueue ( $now );
$aktplanet = GetPlanet ( $GlobalUser['aktplanet'] );
$aktplanet = ProdResources ( $aktplanet, $aktplanet['lastpeek'], $now );
UpdatePlanetActivity ( $aktplanet['planet_id'] );
UpdateLastClick ( $GlobalUser['player_id'] );
$session = $_GET['session'];

// Пользователь не состоит ни в каком альнсе, вывести меню для создания/поиска альянсов.
function AllyPage_NoAlly ()
{
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>".loca("ALLY_ALLY")."</td></tr>\n";
    echo "<tr><th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=1\">".loca("ALLY_FOUND_OWN")."</a></th>\n";
    echo "<th><a href=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\">".loca("ALLY_FIND_OTHER")."</a></th></tr>\n";
    echo "</table><br><br><br><br><br>\n";
}

// Основать свой альянс.
function AllyPage_CreateAlly ($tag, $name)
{
    echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=1&weiter=1\" method=POST>\n";
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>".loca("ALLY_FOUND_ALLY")."</td></tr>\n";
    echo "<tr><th>".loca("ALLY_FOUND_TAG")."</th><th><input type=text name=\"tag\" size=8 maxlength=8 value=\"$tag\"></th></tr>\n";
    echo "<tr><th>".loca("ALLY_FOUND_NAME")."</th><th><input type=text name=\"name\" size=20 maxlength=30 value=\"$name\"></th></tr>\n";
    echo "<tr><th colspan=2><input type=submit value=\"".loca("ALLY_FOUND_SUBMIT")."\"></th></tr></table></form><br><br><br><br>\n";
}

// Искать альянсы.
function AllyPage_Search ($text, $results="")
{
    echo "<table width=519>\n";
    echo "<tr><td class=c colspan=2>".loca("ALLY_FIND_ALLY")."</td></tr>\n";
    echo "<tr><th>".loca("ALLY_FIND_HEAD")."</th><th>\n";
    echo "<form action=\"index.php?page=allianzen&session=".$_GET['session']."&a=2\" method=POST>\n";
    echo "<input type=text name=suchtext value=\"$text\"><input type=submit value=\"".loca("ALLY_FIND_SUBMIT")."\">\n";
    echo "</th></tr></form></table><br>\n";
    echo "$results\n";
    echo "<br><br><br>\n";
}

// Вывести таблицу результатов.
function AllyPage_SearchResult ($result)
{
    global $SearchResults;
    $SearchResults = "";
    $rows = dbrows ($result);
    if ($rows == 0) return;
    $SearchResults .= "<table width=519>\n";
    $SearchResults .= "<tr><td class=c colspan=3>".loca("ALLY_FIND_RESULT")."</th></tr>\n";
    $SearchResults .= "<tr><th><center>".loca("ALLY_FIND_TAG")."</center></th><th><center>".loca("ALLY_FIND_NAME")."</center></th><th><center>".loca("ALLY_FIND_MEMBERS")."</center></th></tr>\n";
    if ($rows > 30) $rows = 30;
    for ($i=0; $i<$rows; $i++)
    {
        $ally = dbarray ($result);
        $enum = EnumerateAlly ($ally['ally_id']);
        $players = dbrows ($enum);
        $SearchResults .= "<tr><th><center>[<a href=\"index.php?page=bewerben&session=".$_GET['session']."&allyid=".$ally['ally_id']."\">".$ally['tag']."</a>]</center></th>\n";
        $SearchResults .= "<th><center>".$ally['name']."</center></th>\n";
        $SearchResults .= "<th><center>".$players."</center></th></tr>\n";
    }
    $SearchResults .= "</table><br>\n";
}

// Пользователь уже подал заявку в альянс.
function AllyPage_Already ($app_id)
{
    global $session;

    $app = LoadApplication ($app_id);
    $ally = LoadAlly ( $app['ally_id'] );

    if ( method () === "POST" )    // Отозвать заявление.
    {
        if ( key_exists ( 'bcancel', $_POST ) ) RemoveApplication ( $app['app_id'] );
    }

?>
<table width=519>
<form action="index.php?page=allianzen&session=<?=$session;?>" method=POST>
<tr><td class=c colspan=2><?=loca("ALLY_APPLY");?></td></tr>
<tr><th colspan=2><?=va(loca("ALLY_APPLY_ALREADY"), $ally['tag']);?></th></tr>
<tr><th colspan=2><input type=submit name="bcancel" value="<?=loca("ALLY_APPLY_WITHDRAW");?>"></th></tr>
</table></form><br><br><br><br>
<?php
}

// ***********************************************************

// Обработать POST-запросы.
if ( $GlobalUser['ally_id'] == 0 && key_exists('a', $_GET) )
{
    // Назначение этого параметра неизвестно (weiter = more нем.)
    $weiter = 0;
    if (key_exists('weiter', $_GET) && $_GET['weiter'] == 1) $weiter = 1;

    if ( $_GET['a'] == 1 && $weiter == 1 )    // Основать альянс.
    {
        $_POST['tag'] = str_replace ( "\"", "", $_POST['tag']);
        $_POST['tag'] = str_replace ( "'", "", $_POST['tag']);

        $_POST['name'] = str_replace ( "\"", "", $_POST['name']);
        $_POST['name'] = str_replace ( "'", "", $_POST['name']);

        if (mb_strlen ($_POST['tag'], "UTF-8")  < 3) $AllianzenError = loca("ALLY_FOUND_ERROR_TAG");
        else if (mb_strlen ($_POST['name'], "UTF-8")  < 3) $AllianzenError = loca("ALLY_FOUND_ERROR_NAME");
        else if (IsAllyTagExist ($_POST['tag'])) $AllianzenError = va(loca("ALLY_FOUND_ERROR_EXISTS"), $_POST['tag']);
        else
        {
            CreateAlly ($GlobalUser['player_id'], $_POST['tag'], $_POST['name']);
            {
                PageHeader ("allianzen");
                BeginContent ();
                echo "<br/><p>".va(loca("ALLY_FOUND_SUCCESS"), $_POST['name'], $_POST['tag'])."</p>\n";
                echo "<form method=\"post\" action=\"index.php?page=allianzen&session=".$_GET['session']."\">\n";
                echo "<input type=\"submit\" value=\"".loca("ALLY_FOUND_CONFIRM")."\"/></form><br/><br/><br/><br/>\n";
                EndContent ();
                PageFooter ();
                ob_end_flush ();
                exit ();
            }
        }
    }
    else if ( $_GET['a'] == 2 )        // Поиск альянса (не более 30 результатов)
    {
        if ( key_exists ('suchtext', $_POST) && $_POST['suchtext'] !== "" )
        {
            $result = SearchAllyTag ($_POST['suchtext']);
            AllyPage_SearchResult ($result);
        }
    }
}

// ***********************************************************

include "allianzen_main.php";
include "allianzen_members.php";
include "allianzen_ranks.php";
include "allianzen_settings.php";
include "allianzen_circular.php";
include "allianzen_misc.php";

PageHeader ("allianzen");

BeginContent ();
echo "<script src=\"js/cntchar.js\" type=\"text/javascript\"></script><script src=\"js/win.js\" type=\"text/javascript\"></script>\n";

if ( $GlobalUser['ally_id'] == 0 )
{
    $app_id = GetUserApplication ($GlobalUser['player_id']);
    if ( $app_id > 0 )
    {
        AllyPage_Already ($app_id);
    }
    else
    {
        if ( key_exists ('a', $_GET) && $_GET['a'] == 1 ) {
            $tag = "";
            $name = "";
            if (key_exists('tag', $_POST)) $tag = $_POST['tag'];
            if (key_exists('name', $_POST)) $name = $_POST['name'];
            AllyPage_CreateAlly ( $tag, $name );
        }
        else if ( key_exists ('a', $_GET) && $_GET['a'] == 2 ) {
            $search_text = "";
            if (key_exists('suchtext', $_POST)) $search_text = $_POST['suchtext'];
            AllyPage_Search ( $search_text, $SearchResults );
        }
        else AllyPage_NoAlly ();
    }
}
else
{
    $ally = LoadAlly ($GlobalUser['ally_id']);

    if ( key_exists ('a', $_GET) )
    {
        if ( $_GET['a'] == 3 ) PageAlly_Leave ();
        else if ( $_GET['a'] == 4 ) PageAlly_MemberList ();
        else if ( $_GET['a'] == 5 ) PageAlly_Settings ();
        else if ( $_GET['a'] == 6 ) PageAlly_Ranks ();
        else if ( $_GET['a'] == 7 ) PageAlly_MemberSettings ();
        else if ( $_GET['a'] == 9 ) PageAlly_ChangeTag ();
        else if ( $_GET['a'] == 10 ) PageAlly_ChangeName ();
        else if ( $_GET['a'] == 11 ) PageAlly_Settings ();
        else if ( $_GET['a'] == 12 ) PageAlly_Dismiss ();
        else if ( $_GET['a'] == 13 ) PageAlly_MemberSettings ();    // выгнать игрока
        else if ( $_GET['a'] == 15 ) PageAlly_Ranks ();
        else if ( $_GET['a'] == 16 ) PageAlly_MemberSettings ();    // назначить ранг игроку
        else if ( $_GET['a'] == 17 ) AllyPage_CircularMessage ();
        else if ( $_GET['a'] == 18 ) AllyPage_Takeover ();
        else AllyPage_Home ();
    }
    else AllyPage_Home ();
}

EndContent ();

PageFooter ("", $AllianzenError);
ob_end_flush ();
?>