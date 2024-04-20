<?php
/**
 *          "Spielerübersicht" for MyBB 1.8, Version 1.0
 *          Copyright © DotScrypt 2024
 *          https://github.com/DotScrypt
 *
 *          based on the Plugin:
 *          "WER IST WER?" VON CHAN (MELANCHOLIA) © 2016
 *          https://storming-gates.de/showthread.php?tid=19354
 *
 *          This program is free software: you can redistribute it and/or modify
 *          it under the terms of the GNU General Public License as published by
 *          the Free Software Foundation, either version 3 of the License, or
 *          (at your option) any later version.
 *
 *          This program is distributed in the hope that it will be useful,
 *          but WITHOUT ANY WARRANTY; without even the implied warranty of
 *          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *          GNU General Public License for more details.
 *
 *          You should have received a copy of the GNU General Public License
 *          along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


/************************ GENERAL SETUP ************************/

if (!defined("IN_MYBB")) {
    die("You Cannot Access This File Directly. Please Make Sure IN_MYBB Is Defined.");
}

if (isset($templatelist)) {
    $templatelist .= ',';
} else {
    $templatelist = '';
}

//TEMPLATELIST
$templatelist .= 'playeroverview, playeroverview_playerbit, playeroverview_playerbit_away, playeroverview_playerbit_avatar, playeroverview_playerbit_onlinestatus, playeroverview_playerbit_characters, playeroverview_playerbit_characters_bit, playeroverview_playerbit_characters_bit_avatar';
$templatelist .= 'playeroverview_menu';

if (my_strpos($_SERVER['PHP_SELF'], 'member.php')) {
    if (isset($templatelist)) {
        $templatelist .= ',';
    }
    $templatelist .= 'playeroverview_profile, playeroverview_profile_avatar, playeroverview_profile_characters, playeroverview_profile_characters_bit, playeroverview_profile_characters_bit_avatar';
}

if (my_strpos($_SERVER['PHP_SELF'], 'usercp.php')) {
    if (isset($templatelist)) {
        $templatelist .= ',';
    }
    $templatelist .= 'playeroverview_ucp, playeroverview_ucp_avatar';
}

//HOOKS FOR USER CP, MISC, MEMBER PROFILE
$plugins->add_hook('misc_start', 'misc_playeroverview'); //we are using misc to show the playeroverview
$plugins->add_hook('member_profile_end', 'playeroverview_show_profile', 10); //show in profile
$plugins->add_hook('usercp_profile_start', 'playeroverview_show_usercp'); //show in usercp
$plugins->add_hook('usercp_do_profile_start', 'playeroverview_edit_usercp'); //edit in usercp

//REGISTER/UNREGISTER USER FUNCTIONS
$plugins->add_hook('member_do_register_end', 'playeroverview_user_created'); //create player in table when new user is created
$plugins->add_hook('datahandler_user_delete_start', 'playeroverview_user_deleted'); //delete player in table when user is deleted (and no other as_playerid);

//ATTACH / DETACH FUNCTIONS
$plugins->add_hook('as_usercp_attachuser', 'playeroverview_asusercp_attachuser'); //as_ucp action: Attach current user to another account
$plugins->add_hook('as_usercp_detachuser', 'playeroverview_asusercp_detachuser'); //as_ucp action: Detach current user from master
$plugins->add_hook('as_usercp_attachother', 'playeroverview_asusercp_attachother'); //as_ucp action: Attach an user to the current account
$plugins->add_hook('as_usercp_detachother', 'playeroverview_asusercp_detachother'); //as_ucp action: Detach current user from master

//SETUP LOCATION FOR WHO'S ONLINE LIST

$plugins->add_hook('global_intermediate', 'add_menu_playeroverview');
$plugins->add_hook("fetch_wol_activity_end", "playeroverview_online_activity");
$plugins->add_hook("build_friendly_wol_location_end", "playeroverview_online_location");

//INFO
function playeroverview_info()
{
    global $mybb, $lang, $db, $plugins_cache;
    $lang->load("playeroverview");

    $plugininfo = array(
        "name" => $db->escape_string($lang->playeroverview_titel),
        "description" => $db->escape_string($lang->playeroverview_desc),
        "website" => "",
        "author" => "DotScrypt",
        "authorsite" => "",
        "version" => "1.0",
        "codename" => "playeroverview",
        "compatibility" => "18*"
    );

    if (playeroverview_is_installed() && is_array($plugins_cache) && is_array($plugins_cache['active']) && isset($plugins_cache['active']['playeroverview'])) {
        $result = $db->simple_select('settinggroups', 'gid', "name = 'playeroverview'");
        $set = $db->fetch_array($result);
        if (!empty($set)) {
            $desc = $plugininfo['description'];
            $plugininfo['description'] = "" . $desc . "<div style=\"float:right;\"><img src=\"styles/default/images/icons/custom.png\" alt=\"\" style=\"margin-left: 10px;\" /><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=" . (int) $set['gid'] . "\" style=\"margin: 10px;\">" . $db->escape_string($lang->playeroverview_settingsgroup_name) . "</a><hr style=\"margin-bottom: 5px;\"></div>";
        }
    }

    return $plugininfo;
}


//INSTALL
function playeroverview_install()
{
    global $db, $mybb, $lang;
    $lang->load("playeroverview");

    //ADD SETTINGS
    playeroverview_settings_add();

    //CREATE DATABASE AND NECESSARY FIELDS
    playeroverview_db_create();

    //INSERT CSS
    playeroverview_css_add();

    //INSERT TEMPLATES - in all template sets! sid = -2 to install everywhere!
    playeroverview_templates_add();

    //INITIALIZE VALUES OF EXISTING USERS
    playeroverview_initialize();

    // Return true to indicate successful installation
    return true;
}

//IS INSTALLED
function playeroverview_is_installed()
{

    global $mybb;

    //one of the settings in the settingsgroups
    if (isset($mybb->settings['playeroverview_activate'])) {
        return true;
    }
    return false;
}

//UNINSTALL
function playeroverview_uninstall()
{
    global $db;

    //delete settings and settinggroups
    $db->delete_query('settings', "name LIKE '%playeroverview%'");
    $db->delete_query('settinggroups', "name LIKE '%playeroverview%'");

    //delete templates
    $db->delete_query("templates", "title LIKE '%playeroverview%'");

    //delete template group
    $db->delete_query("templategroups", "prefix LIKE '%playeroverview%'");

    //delete css
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'playeroverview.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    //delete field in user table (DB)
    if ($db->field_exists("as_playerid", "users")) {
        $db->drop_column("users", "as_playerid");
    }

    //delete DB
    $db->query("DROP TABLE " . TABLE_PREFIX . "players");

    rebuild_settings();
}

//ACTIVATE
function playeroverview_activate()
{
    global $db, $mybb;
    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

    //add templates that on activate
    //member_profile
    find_replace_templatesets('member_profile', '#{\$contact_details}#', "{\$contact_details}\n            {\$playeroverview_profile}");
    find_replace_templatesets('member_profile', '#(\n*)(\s*){\$profile_attached}(\n*)#', '', 0); //remove {$profile_attached} since charas are listed in playeroverview

    //usercp_profile
    find_replace_templatesets('usercp_profile', '#{\$awaysection}#', "{\$awaysection}\n{\$playeroverview_ucp}");

    //header
    find_replace_templatesets('header', '#{\$menu_memberlist}#', "{\$menu_memberlist}\n						{\$playeroverview_menu}");


    //apply patches
    playeroverview_as_patches();

}

//DEACTIVATE
function playeroverview_deactivate()
{
    global $db;
    require_once MYBB_ROOT . "/inc/adminfunctions_templates.php";

    //delete templates that are input when activated
    //member_profile
    find_replace_templatesets('member_profile', '#(\n*)(\s*){\$playeroverview_profile}(\n*)#', '', 0);
    find_replace_templatesets('member_profile', '#{\$profilefields}#', "{\$profilefields}\n            {\$profile_attached}");//add {$profile_attached} back

    //usercp_profile
    find_replace_templatesets('usercp_profile', '#(\n*)(\t*){\$playeroverview_ucp}(\t*)(\n?)#', '', 0);

    //header
    find_replace_templatesets('header', '#(\n?)(\t*){\$playeroverview_menu}(\t*)(\n?)#', '', 0);

    //delete patches
    playeroverview_delete_patches();
}

//ADD SETTINGS
function playeroverview_settings_add()
{
    global $db, $lang;
    $lang->load("playeroverview");

    // Avoid duplicated settings
    $query_setgr = $db->simple_select("settinggroups", "gid", "name='playeroverview_settings'");
    $ams = $db->fetch_array($query_setgr);

    if (isset($ams['gid'])) {
        $db->delete_query("settinggroups", "gid='" . (int) $ams['gid'] . "'");
        $db->delete_query("settings", "gid='" . (int) $ams['gid'] . "'");
    }

    // Add the settings
    $query = $db->simple_select("settinggroups", "COUNT(*) as po_rows");
    $rows = $db->fetch_field($query, "po_rows");

    //SETTINGS GROUP
    $playeroverview_setting_group = array(
        'gid' => 'NULL',
        'name' => 'playeroverview',
        'title' => $db->escape_string($lang->playeroverview_settingsgroup_name),
        'description' => $db->escape_string($lang->playeroverview_settingsgroup_desc),
        'disporder' => $rows + 1,
        'isdefault' => "0",
    );
    $db->insert_query('settinggroups', $playeroverview_setting_group);
    $gid = $db->insert_id();

    //SETTINGS
    $playeroverview_setting_array = array(
        'playeroverview_activate' => array( //activate player overview list
            'title' => $db->escape_string($lang->playeroverview_setting_activate),
            'description' => $db->escape_string($lang->playeroverview_setting_activate_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 1,
            'gid' => intval($gid),
        ),
        'playeroverview_activate_guest' => array( //viewable by guests?
            'title' => $db->escape_string($lang->playeroverview_setting_activate_guest),
            'description' => $db->escape_string($lang->playeroverview_setting_activate_desc_guest),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 2,
            'gid' => intval($gid),
        ),
        'playeroverview_onlinestatus' => array( //show online status in overview list?
            'title' => $db->escape_string($lang->playeroverview_setting_online),
            'description' => $db->escape_string($lang->playeroverview_setting_online_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 3,
            'gid' => intval($gid),
        ),
        'playeroverview_away' => array( //show away status in overview list?
            'title' => $db->escape_string($lang->playeroverview_setting_away),
            'description' => $db->escape_string($lang->playeroverview_setting_away_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 4,
            'gid' => intval($gid),
        ),
        'playeroverview_avatar' => array( //show avatar
            'title' => $db->escape_string($lang->playeroverview_setting_avatar),
            'description' => $db->escape_string($lang->playeroverview_setting_avatar_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 5,
            'gid' => intval($gid),
        ),
        'playeroverview_avatar_default' => array( //show avatar
            'title' => $db->escape_string($lang->playeroverview_setting_avatar_default),
            'description' => $db->escape_string($lang->playeroverview_setting_avatar_desc_default),
            'optionscode' => 'text',
            'value' => "", // Default
            'disporder' => 6,
            'gid' => intval($gid),
        ),
        'playeroverview_avatar_width' => array( // avatar width
            'title' => $db->escape_string($lang->playeroverview_setting_avatar_width),
            'description' => $db->escape_string($lang->playeroverview_setting_avatar_desc_width),
            'optionscode' => 'numeric',
            'value' => '250', // Default
            'disporder' => 7,
            'gid' => intval($gid),
        ),
        'playeroverview_avatar_height' => array( // avatar height
            'title' => $db->escape_string($lang->playeroverview_setting_avatar_height),
            'description' => $db->escape_string($lang->playeroverview_setting_avatar_desc_height),
            'optionscode' => 'numeric',
            'value' => '300', // Default
            'disporder' => 8,
            'gid' => intval($gid),
        ),
        'playeroverview_characters' => array( //show all characters
            'title' => $db->escape_string($lang->playeroverview_setting_character),
            'description' => $db->escape_string($lang->playeroverview_setting_character_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 9,
            'gid' => intval($gid),
        ),
        'playeroverview_characters_avatar' => array( //show avatar for characters
            'title' => $db->escape_string($lang->playeroverview_setting_character_avatar),
            'description' => $db->escape_string($lang->playeroverview_setting_character_avatar_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 10,
            'gid' => intval($gid),
        ),
        'playeroverview_characters_avatar_default' => array( //show avatar for characters
            'title' => $db->escape_string($lang->playeroverview_setting_character_avatar_default),
            'description' => $db->escape_string($lang->playeroverview_setting_character_avatar_desc_default),
            'optionscode' => 'text',
            'value' => "",
            'disporder' => 11,
            'gid' => intval($gid),
        ),
        'playeroverview_characters_avatar_width' => array( // character avatar width
            'title' => $db->escape_string($lang->playeroverview_setting_character_avatar_width),
            'description' => $db->escape_string($lang->playeroverview_setting_character_avatar_desc_width),
            'optionscode' => 'numeric',
            'value' => '50', // Default
            'disporder' => 12,
            'gid' => intval($gid),
        ),
        'playeroverview_characters_avatar_height' => array( // character avatar height
            'title' => $db->escape_string($lang->playeroverview_setting_character_avatar_height),
            'description' => $db->escape_string($lang->playeroverview_setting_character_avatar_desc_height),
            'optionscode' => 'numeric',
            'value' => '60', // Default
            'disporder' => 13,
            'gid' => intval($gid),
        )
    );

    // INSERT SETTINGS
    foreach ($playeroverview_setting_array as $name => $setting) {
        $setting['name'] = $db->escape_string($name);
        $setting['gid'] = $db->escape_string($gid);

        $db->insert_query('settings', $setting);
    }

    rebuild_settings();
}

//CREATE DATABASE
function playeroverview_db_create()
{
    global $db;

    //CREATE TABLE IN DATABASE
    //pid - player id - PRIMARY KEY
    //name - player name
    //desc - player description
    //avatar_link- player avatar

    if (!$db->table_exists('players') && ($db->engine == 'mysql' || $db->engine == 'mysqli')) {
        $db->query("CREATE TABLE `" . TABLE_PREFIX . "players` (
                `pid` int(10) UNSIGNED NOT NULL auto_increment,
                `name` varchar(255) NOT NULL,
                `desc` varchar(2500) NOT NULL,
                `avatar_link` varchar(255) NOT NULL,
                PRIMARY KEY(`pid`)
                ) ENGINE=MyISAM" . $db->build_create_table_collation());

    }

    //pid in user table - mark as as_playerid since Account Switcher is necessary for this
    if (!$db->field_exists('as_playerid', 'users')) {
        $db->add_column(
            'users',
            'as_playerid',
            'int(10)'
        );
    }

}

//ADD CSS
function playeroverview_css_add()
{
    global $db;

    // CSS
    $css = array(
        'name' => 'playeroverview.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '.character_box {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 5px;
        }
        
        .playeroverview img {
                object-fit: cover;
        }',
        'cachefile' => $db->escape_string(str_replace('/', '', 'playeroverview.css')),
        'lastmodified' => TIME_NOW
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query(
        "themestylesheets",
        array(
            "cachefile" => "playeroverview.css"
        ),
        "sid = '" . (int) $sid . "'",
        1
    );

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        cache_stylesheet($theme['tid'], "playeroverview.css", $css['stylesheet']);
        update_theme_stylesheet_list($theme['tid'], 0, 1);
    }

}

//ADD TEMPLATES
function playeroverview_templates_add()
{

    global $db, $lang;

    // Define the template group properties
    $template_group = array(
        'prefix' => 'playeroverview', // The prefix for your templates
        'title' => $db->escape_string($lang->templategroup_playeroverview_title), // The title of the template group
        'isdefault' => 1 // Set to 1 if this is the master template set, otherwise 0
    );

    // Insert the template group into the database
    $db->insert_query('templategroups', $template_group);

    /************************** TEMPLATES FOR PLAYER OVERVIEW LIST **************************/

    //playeroverview
    $template_playeroverview = '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->playeroverview_title}</title>
		{$headerinclude}
	</head>
	
	<body>
		{$header}
		
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder playeroverview"  width="100%">
			<tr>
				<td class="thead" colspan="{$colspan}"><strong>{$lang->playeroverview_title}</strong></td>
			</tr>
			<tr>
				{$avaheader}
				<td class="tcat"><span class="smalltext"><strong>{$lang->playeroverview_name}</strong></span></td>
				<td class="tcat"><span class="smalltext"><strong>{$lang->playeroverview_desc}</strong></span></td>
				<td class="tcat"><span class="smalltext"><strong>{$lang->playeroverview_lastactive}</strong></span></td>
				<td class="tcat"><span class="smalltext"><strong>{$lang->playeroverview_regdate}</strong></span></td>
				{$charaheader}
			</tr>
			
			{$playeroverview_playerbit}
			
		</table>

		{$footer}
	</body>
	
</html>';

    $insert_array = array(
        'title' => 'playeroverview',
        'template' => $db->escape_string($template_playeroverview),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit
    $template_playeroverview_playerbit = '<tr>
	{$playeroverview_playerbit_avatar}
	<td class="{$altbg}">
		{$playername}
		{$player_onlinestatus}
        {$player_away}
	</td>
	<td class="{$altbg}">{$playertext}</td>
	<td class="{$altbg}">{$lastactive_date}</td>
	<td class="{$altbg}">{$regdate_date}</td>
    {$playeroverview_playerbit_characters}
</tr>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit',
        'template' => $db->escape_string($template_playeroverview_playerbit),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit_avatar
    $template_playeroverview_playerbit_avatar = '<td class="{$altbg}">
	{$playeravatar_image_html}
</td>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_avatar',
        'template' => $db->escape_string($template_playeroverview_playerbit_avatar),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit_characters
    $template_playeroverview_playerbit_characters = '<td class="{$altbg}">
    {$playeroverview_playerbit_characters_bit}
</td>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_characters',
        'template' => $db->escape_string($template_playeroverview_playerbit_characters),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit_characters_bit
    $template_playeroverview_playerbit_characters_bit = '<div class="character_box">
	{$playeroverview_playerbit_characters_bit_avatar}
	<div>{$charalink}</div>
</div>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_characters_bit',
        'template' => $db->escape_string($template_playeroverview_playerbit_characters_bit),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit_characters_bit_avatar
    $template_playeroverview_playerbit_characters_bit_avatar = '<div><img src="{$charaavatar}" alt="Charakter-Avatar" width="{$playeroverview_characters_avatar_width}" height="{$playeroverview_characters_avatar_height}" /></div>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_characters_bit_avatar',
        'template' => $db->escape_string($template_playeroverview_playerbit_characters_bit_avatar),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);


    //playeroverview_playerbit_onlinestatus
    $template_playeroverview_playerbit_onlinestatus = '<div class="smalltext {$onlinestatus}"><strong>{$onlinestatus}</strong></div>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_onlinestatus',
        'template' => $db->escape_string($template_playeroverview_playerbit_onlinestatus),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_playerbit_away
    $template_playeroverview_playerbit_away = '<br />
      <div><strong>{$lang->playeroverview_away_note}</strong></div>
      <em>{$lang->playeroverview_away_reason} {$awayreason}</em>
      <div class="smalltext">
          {$lang->playeroverview_away_since} {$awaydate} <br />
          {$lang->playeroverview_away_returns} {$returndate}
      </div>';

    $insert_array = array(
        'title' => 'playeroverview_playerbit_away',
        'template' => $db->escape_string($template_playeroverview_playerbit_away),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);


    /************************** TEMPLATES FOR USER CP **************************/

    //playeroverview_ucp
    $template_playeroverview_ucp = '<fieldset class="trow2">
	<input type="hidden" name="player_id" value="{$user_player}" />

	<legend><strong>{$lang->playeroverview_profile_title}</strong></legend>
	<table cellspacing="0" cellpadding="5" width="100%" class="playeroverview">
		<tr>
			<td><span class="smalltext">{$lang->playeroverview_change_info}</span></td>
		</tr>
		<tr>
			<td>{$lang->playeroverview_name}:</td>
		</tr>
        <tr>
            <td><span class="smalltext">{$lang->playeroverview_name_info}</span></td>
        </tr>
		<tr>
			<td><input type="text" name="playeroverview_name" class="textbox" value="{$playername}" /></td>
		</tr>
		<tr>
			<td>{$lang->playeroverview_desc}:</td>
		</tr>
        <tr>
            <td><span class="smalltext">{$lang->playeroverview_desc_info}</span></td>
        </tr>
		<tr>
			<td><textarea name="playeroverview_desc" rows="6" cols="30" style="width: 95%">{$playertext}</textarea></td>
		</tr>
		
		{$playeroverview_ucp_avatar}
		
	</table>
</fieldset>
<br />';

    $insert_array = array(
        'title' => 'playeroverview_ucp',
        'template' => $db->escape_string($template_playeroverview_ucp),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_ucp_avatar
    $template_playeroverview_ucp_avatar = '<tr>
	<td>{$lang->playeroverview_avatar_title}:</td>
</tr>
<tr>
    <td><span class="smalltext">{$profile_avatar_text}</span></td>
</tr>
<tr>
	<td><input type="text" name="playeroverview_avatar" class="textbox" value="{$playeravalink}" /></td>
</tr>
<tr>
	<td>{$playeravatar_image_html}</td>
</tr>';

    $insert_array = array(
        'title' => 'playeroverview_ucp_avatar',
        'template' => $db->escape_string($template_playeroverview_ucp_avatar),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);



    /************************** TEMPLATES FOR PROFILE **************************/

    //playeroverview_profile
    $template_playeroverview_profile = '<br />
    <table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed playeroverview">
        <colgroup>
        <col style="width: 30%;" />
        </colgroup>
        <tr>
            <td colspan="2" class="thead"><strong>{$lang->playeroverview_profile_title}</strong></td>
        </tr>
        {$playeroverview_profile_avatar}
        <tr>
            <td class="trow2"><strong>{$lang->playeroverview_name}</strong></td>
            <td class="trow2">{$playername}</td>
        </tr>
        <tr>
            <td class="trow1"><strong>{$lang->playeroverview_desc}</strong></td>
            <td class="trow1">{$playertext}</td>
        </tr>
        <tr>
            <td class="trow2"><strong>{$lang->playeroverview_lastactive}</strong></td>
            <td class="trow2">{$lastactive_date}</td>
        </tr>
        <tr>
            <td class="trow1"><strong>{$lang->playeroverview_regdate}</strong></td>
            <td class="trow1">{$regdate_date}</td>
        </tr>
        
        {$playeroverview_profile_characters}
        
    </table>';

    $insert_array = array(
        'title' => 'playeroverview_profile',
        'template' => $db->escape_string($template_playeroverview_profile),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_profile_avatar
    $template_playeroverview_profile_avatar = '<tr>
	<td class="trow1"><strong>{$lang->playeroverview_avatar_title}</strong></td>
	<td class="trow1">
		{$playeravatar_image_html}
	</td>
</tr>';

    $insert_array = array(
        'title' => 'playeroverview_profile_avatar',
        'template' => $db->escape_string($template_playeroverview_profile_avatar),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_profile_characters
    $template_playeroverview_profile_characters = '<tr>
	<td class="trow2"><strong>{$lang->playeroverview_charas}</strong></td>
	<td class="trow2">{$playeroverview_profile_characters_bit}</td>
</tr>';

    $insert_array = array(
        'title' => 'playeroverview_profile_characters',
        'template' => $db->escape_string($template_playeroverview_profile_characters),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_profile_characters_bit
    $template_playeroverview_profile_characters_bit = '<div class="character_box">
	{$playeroverview_profile_characters_bit_avatar}
	<div>{$charalink}</div>
</div>';

    $insert_array = array(
        'title' => 'playeroverview_profile_characters_bit',
        'template' => $db->escape_string($template_playeroverview_profile_characters_bit),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

    //playeroverview_profile_characters_bit_avatar
    $template_playeroverview_profile_characters_bit_avatar = '<div><img src="{$charaavatar}" alt="Charakter-Avatar" width="{$playeroverview_characters_avatar_width}" height="{$playeroverview_characters_avatar_height}" /></div>';

    $insert_array = array(
        'title' => 'playeroverview_profile_characters_bit_avatar',
        'template' => $db->escape_string($template_playeroverview_profile_characters_bit_avatar),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);


    /************************** TEMPLATES FOR MENU LINK **************************/

    //playeroverview_menu
    $template_playeroverview_menu = '<li>
	<a href="{$mybb->settings[\'bburl\']}/misc.php?action=playeroverview" class="memberlist">{$lang->playeroverview_menu_nav}</a>
</li>';

    $insert_array = array(
        'title' => 'playeroverview_menu',
        'template' => $db->escape_string($template_playeroverview_menu),
        'sid' => '-2',
        'version' => '',
        'dateline' => time()
    );
    $db->insert_query('templates', $insert_array);

}

/************************ SHOW / EDIT CODE FOR USER CP, PROFILE, OVERVIEW LIST ************************/
//show in overview list
function misc_playeroverview()
{
    global $db, $mybb, $templates, $theme, $headerinclude, $header, $lang, $footer;
    global $playeroverview_characters_bit, $characters_bit, $avatar_bit, $charaheader;

    if ($mybb->input['action'] == "playeroverview") {

        $lang->load("playeroverview");
        $playeroverview_settings = get_playeroverview_settings();
        $colspan = $playeroverview_settings['avatar'] + $playeroverview_settings['characters'] + 4;

        // Check if player overview is activated
        if ($playeroverview_settings['activate'] != 1) {
            error($lang->playeroverview_inactive);
        }

        // Check permission for guests
        if ($playeroverview_settings['activate_guest'] != 1 && $mybb->user['uid'] == 0) {
            error_no_permission();
        }

        // NAVIGATION
        add_breadcrumb($lang->playeroverview_title, "misc.php?action=playeroverview");

        $playeroverview_playerbit = "";

         // Get players
         $players = $db->simple_select("players", "*", "", array("order_by" => "name ASC"));

         // Prepare variables
         $playeroverview_playerbit = "";

         // Process each player

        while ($player = $db->fetch_array($players)) {
            //variables
            $altbg = alt_trow();

            $player_away = $playeroverview_playerbit_avatar = $player_onlinestatus = "";

            // Sanitize player name and description
            $playername = htmlspecialchars_uni($player['name'] ?? $lang->playeroverview_noname);
            $playertext = htmlspecialchars_uni($player['desc'] ?? $lang->playeroverview_nodesc);

            //show if user is away
            if ($playeroverview_settings['show_away']) {
                $away = playeroverview_away($player);

                if ($away['is_away']) {
                    $lang->playeroverview_away_note = empty($player['name']) ? $lang->playeroverview_away_noname : $lang->sprintf($lang->playeroverview_away_note, $player['name']);
                    $awaydate = $away['awaydate'];
                    $awayreason = $away['awayreason'];
                    $returndate = $away['returndate'];

                    eval ("\$player_away = \"" . $templates->get("playeroverview_playerbit_away") . "\";");
                }
            }

            //SETTINGS: show avatar?
            if ($playeroverview_settings['avatar'] == 1) {
                //show avatar of player

                $playeravavalues = playeroverview_set_playeravatar($player);

                $avaheader = $playeravavalues['avaheader'];
                $playeravatar = $playeravavalues['playeravatar'];
                $playeravatar_image_html = $playeravavalues['playeravatar_image_html'];
                $playeravalink = $playeravavalues['playeravalink'];

                eval ("\$playeroverview_playerbit_avatar = \"" . $templates->get("playeroverview_playerbit_avatar") . "\";");
            }

            // Show characters if enabled
            $playeroverview_profile_characters = ($playeroverview_settings['characters'] == 1) ? playeroverview_show_characters("misc", (int) $player['pid'], $altbg) : "";

            //show online status
            if ($playeroverview_settings['show_onlinestatus']) {
                $onlinestatus = playeroverview_onlinestatus($player);
                eval ("\$player_onlinestatus = \"" . $templates->get("playeroverview_playerbit_onlinestatus") . "\";");
            }

            // Get additional player info based on characters
            $additional_info = get_additional_player_info((int) $player['pid']);

            $lastactive_date = $additional_info['lastactive_date'];
            $regdate_date = $additional_info['regdate_date'];

            eval ("\$playeroverview_playerbit .= \"" . $templates->get("playeroverview_playerbit") . "\";");
        }

        eval ("\$page  = \"" . $templates->get("playeroverview") . "\";");
        output_page($page);

    }
}

//show in profile
function playeroverview_show_profile()
{
    global $mybb, $templates, $lang, $theme;
    global $playeroverview_profile, $playeroverview_profile_avatar;
    global $playeroverview_characters_bit, $characters_bit, $avatar_bit;

    $lang->load("playeroverview");

    $playeroverview_settings = get_playeroverview_settings();

    // Variables initialization
    $playeroverview_profile_avatar = $playeroverview_profile = "";

    $user_uid = $mybb->get_input('uid', MyBB::INPUT_INT);

    if ($playeroverview_settings['activate'] == 1) {
        // Get player data
        $player = get_player_data($user_uid);
        $user_player = $player['pid'];

        // Sanitize player name and description
        $playername = htmlspecialchars_uni($player['name'] ?? $lang->playeroverview_noname);
        $playertext = htmlspecialchars_uni($player['desc'] ?? $lang->playeroverview_nodesc);

        //SETTINGS: show avatar?
        if ($playeroverview_settings['avatar'] == 1) {

            //show avatar of player
            $playeravavalues = playeroverview_set_playeravatar($player);
            $playeravatar_image_html = $playeravavalues['playeravatar_image_html'];
            eval ("\$playeroverview_profile_avatar = \"" . $templates->get("playeroverview_profile_avatar") . "\";");

        }

        // Show characters if enabled
        $playeroverview_profile_characters = ($playeroverview_settings['characters'] == 1) ? playeroverview_show_characters("profile", $user_player, 0) : "";

        // Get additional player info based on characters
        $additional_info = get_additional_player_info($user_player);
        $lastactive_date = $additional_info['lastactive_date'];
        $regdate_date = $additional_info['regdate_date'];

        // Check if user is guest
        $playeroverview_profile = ($mybb->user['uid'] == 0 && $playeroverview_settings['activate_guest'] != 1) ? "" : eval ($templates->render("playeroverview_profile"));

    }
}

//show in usercp
function playeroverview_show_usercp()
{
    global $playeroverview_ucp, $playeroverview_ucp_avatar;
    global $mybb, $templates, $lang;

    $lang->load("playeroverview");

    // Fetch player overview settings
    $playeroverview_settings = get_playeroverview_settings();

    //declare avatar field description text
    $profile_avatar_text = $lang->sprintf($lang->playeroverview_ava_info, $playeroverview_settings['avatar_width'], $playeroverview_settings['avatar_height']);

    //Initialize variables
    $playeroverview_ucp = $playeroverview_ucp_avatar = "";

    // If player overview is activated
    if ($playeroverview_settings['activate'] == 1) {
        // Get player data
        $player = get_player_data($mybb->user['uid']);
        $user_player = $player['pid'];

        // Initialize variables
        $playername = htmlspecialchars_uni($player['name']);
        $playertext = htmlspecialchars_uni($player['desc']);

        // If avatar is enabled, set the avatar HTML
        if ($playeroverview_settings['avatar'] == 1) {
            $playeravavalues = playeroverview_set_playeravatar($player);
            eval ("\$playeroverview_ucp_avatar = \"" . $templates->get("playeroverview_ucp_avatar") . "\";");
        }

        // Render the user control panel template
        $playeroverview_ucp = eval ($templates->render('playeroverview_ucp'));
    }
}


//edit in usercp
function playeroverview_edit_usercp()
{
    global $mybb, $db, $errors;

    $player_id = $mybb->get_input('player_id', MyBB::INPUT_INT);

    $name = $mybb->get_input('playeroverview_name');
    $desc = $mybb->get_input('playeroverview_desc');
    $avatar_link = $mybb->get_input('playeroverview_avatar');

    // Escape the values before inserting into the database query
    $name = $db->escape_string($name);
    $desc = $db->escape_string($desc);
    $avatar_link = $db->escape_string($avatar_link);


    $player = array(
        "name" => $name,
        "desc" => $desc,
        "avatar_link" => $avatar_link
    );

    if (playeroverview_validate($player)) {
        $db->update_query("players", $player, "pid='" . $player_id . "'");
    }

}


/************************ ADDITIONAL FUNCTIONS WHEN USER CREATED / DELETED ************************/

//create new player id when new user is created
function playeroverview_user_created()
{
    global $db, $user_info;

    //update user table with as_playerid of created PID
    $user_uid = $user_info['uid'];

    create_new_player($user_uid);

}

//delete player id when user is deleted, but only when no other users are assigned
function playeroverview_user_deleted($userhandler)
{
    global $db;

    // Get the UID of the deleted user
    $delete_uids = $userhandler->delete_uids;

    foreach ($delete_uids as $deleted_uid) {
        //get user info of uid

        //Escape the value to prevent SQL injection
        $user_uid = $db->escape_string($deleted_uid);

        //get as_playerid of deleted user
        $query = $db->simple_select('users', 'as_playerid', "uid= '$user_uid'");
        $user_previous_pid = $db->fetch_field($query, 'as_playerid');

        // flag that we don't change the as_playerid value to the one of the master, rather just delete the user completely
        // this is necessary since we use the delete_player function multiple times.
        $master_pid = -1;

        delete_player($user_uid, $master_pid, $user_previous_pid);
    }

}


/************************ ADDITIONAL FUNCTIONS WHEN USER ATTACHED OVER ACCOUNTSWITCHER / DETACHED OVER ACCOUNTSWITCHER ************************/

//as_ucp action: Attach current user to another account
function playeroverview_asusercp_attachuser($args)
{

    global $db, $mybb;

    //player id of master user
    $master_pid = $args['as_playerid'];

    //user id of current user
    $user_uid = $mybb->user['uid'];
    $user_previous_pid = $mybb->user['as_playerid'];

    delete_player($user_uid, $master_pid, $user_previous_pid);
}

//as_ucp action: Detach current user from master
function playeroverview_asusercp_detachuser()
{

    global $db, $mybb;

    //update user table with as_playerid of created PID
    $user_uid = $mybb->user['uid'];
    create_new_player($user_uid);

}


//as_ucp action:  Attach an user to the current account
function playeroverview_asusercp_attachother($args)
{

    global $db, $mybb;

    //uid of attached user
    $user_uid = $args['target_uid'];

    // as_playerid of master user
    $master_pid = $mybb->user['as_playerid'];

    //as_playerid of attached user
    $query = $db->simple_select('users', 'as_playerid', "uid = '$user_uid'");

    // Fetch the count from the result
    $user_previous_pid = $db->fetch_field($query, 'as_playerid');

    delete_player($user_uid, $master_pid, $user_previous_pid);

}

//as_ucp action: Detach user from current account
function playeroverview_asusercp_detachother($args)
{

    //user it from user that gets detached and needs to be updated
    $user_uid = $args['uid'];
    create_new_player($user_uid);
}

//create a new player in the player db and update the user with the new as_playerid of the created player
//also used when a new player is registering
function create_new_player($user_uid)
{

    global $db;

    $player = array(
        "name" => $db->escape_string(""),
        "desc" => $db->escape_string(""),
        "avatar_link" => $db->escape_string("")
    );

    $db->insert_query("players", $player);

    //last pid created
    $created_pid = $db->insert_id();

    $update_array = array(
        'as_playerid' => $db->escape_string($created_pid)
    );

    // Perform the database update
    $db->update_query('users', $update_array, "uid='$user_uid'");

}

//delete player when an account is attached to another account and update account with as_playerid of master account
//also used when user is deleted completely (over admin cp)
function delete_player($user_uid, $master_pid, $user_previous_pid)
{

    global $db;

    if ($master_pid == -1) { // no need to change as_playerid to a specific value because the user is just deleted

    } else {

        //assign as_playerid of master to current user
        //update user table with as_playerid of created PID
        $update_array = array(
            "as_playerid" => $db->escape_string($master_pid)
        );

        // Perform the database update
        $db->update_query('users', $update_array, "uid='$user_uid'");
    }

    //get count of users with previous as_playerid
    //if count > 0, then don't delete it - otherwise delete it!
    $query = $db->simple_select("users", "COUNT(*) as pid_count", "as_playerid = '$user_previous_pid'");
    $result = $db->fetch_field($query, "pid_count");

    $num_users = intval($result);

    if ($num_users > 0) {
        // there are other charas that are assigned to this player - don't delete this
    } else {
        //delete player with previous as_playerid of current user
        $condition = "pid = '$user_previous_pid'";

        // Run the query to delete the entry from the custom table
        $db->delete_query('players', $condition);
    }

}

/************************ HELPING FUNCTIONS ************************/

//when plugin is installed, go through existing users and initialize the as_playerid value
function playeroverview_initialize()
{

    global $db, $mybb;

    //get all users with as_uid = 0 since those are the master accounts, loop through them
    $master_users = $db->query("
    SELECT *
    FROM " . TABLE_PREFIX . "users u
    WHERE u.as_uid = '0'
    ");

    while ($master = $db->fetch_array($master_users)) {

        //create a player in the player table for each user
        $master_uid = $master['uid'];
        create_new_player($master_uid);

        //get all attached users -> as_uid same as the uid of the master user, loop through them
        $attached_users = $db->query("
        SELECT *
        FROM " . TABLE_PREFIX . "users u
        WHERE u.as_uid = '$master_uid'
        ");

        //assign previously created player to all those users
        while ($attached_user = $db->fetch_array($attached_users)) {

            //get as_playerid from master user
            $query = $db->simple_select('users', 'as_playerid', "uid = '$master_uid'");
            $master_pid = $db->fetch_field($query, "as_playerid");

            //assign master pid to as_playerid of attached accounts
            $update_array = array(
                'as_playerid' => $db->escape_string($master_pid)
            );

            // Perform the database update
            $attached_user_uid = $attached_user['uid'];
            $db->update_query('users', $update_array, "uid='$attached_user_uid'");

        }

    }

}

//validate userentries when db is updated
function playeroverview_validate($player)
{

    global $db, $mybb, $plugins, $lang, $errors;
    $lang->load("playeroverview");

    playeroverview_verify_username($player);
    playeroverview_verify_desc($player);
    $avatar_errors = playeroverview_verify_avatar($player);

    if (!empty($avatar_errors)) {
        $lang->redirect_profileupdated = "<div class=\"error\" style=\"text-align: left;\"><div style=\"font-weight: bold;\">Spielerinfo-Änderungen wurden nicht durchgeführt!</div><ul>";
        foreach ($avatar_errors as $avatar_error) {
            $lang->redirect_profileupdated .= "<li>" . $avatar_error . "</li>";
        }
        $lang->redirect_profileupdated .= "</ul></div>";
        return false;
    }

    return true;

}

//validate username
function playeroverview_verify_username($player)
{
    global $db, $mybb, $plugins;

    $playername = $player['name'];

    // nothing to validate - field can be empty and contain any character!
    return true;
}

//validate user description
function playeroverview_verify_desc($player)
{
    global $db, $mybb;

    $playerdesc = $player['desc'];

    // nothing to validate - field can be empty and contain any character!
    return true;
}

//validate avatar link
function playeroverview_verify_avatar($player)
{
    global $db, $mybb, $lang;
    $lang->load("playeroverview");

    $ava_url = $player['avatar_link'];

    $avalink_errors = array();

    //check if there is something written in the input field
    if (!empty($ava_url)) {
        $path = parse_url($ava_url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $ava_url);

        //check if link is a valid URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $avalink_errors[] = $lang->playeroverview_avalink_nonvalid_error;
        }

        //check that url is an image (valid extension)
        $ext = get_extension(my_strtolower($path));
        if (!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) {
            $avalink_errors[] = $lang->playeroverview_avalink_extension_error;
        }

        //validate avatar size
        //not possible at the moment!

    }

    return $avalink_errors;

}

//ensure standard avatars (for player and charas) are valid
function playeroverview_validate_standard_avatar($playeroverview_avatar_default)
{

    global $theme, $lang;

    $final_avatar_url = "{$theme['imgdir']}/" . $playeroverview_avatar_default;

    //check for double slash in the beginning - if it has one, remove first /
    $double_slash = substr($playeroverview_avatar_default, 0, 1) === "/";
    if ($double_slash) {
        $playeroverview_avatar_default = substr($playeroverview_avatar_default, 1);
        $final_avatar_url = "{$theme['imgdir']}/" . $playeroverview_avatar_default;
    }

    //check for http in the beginning - it's not a link to an image in the server
    $http_start = substr($playeroverview_avatar_default, 0, 4) === "http";
    if ($http_start) {
        $final_avatar_url = $playeroverview_avatar_default;
    }

    //check that it has a valid ending
    $path = parse_url($final_avatar_url, PHP_URL_PATH);
    $encoded_path = array_map('urlencode', explode('/', $path));
    $url = str_replace($path, implode('/', $encoded_path), $final_avatar_url);

    //check if link is a valid URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $final_avatar_url = "";
    }

    //check that url is an image (valid extension)
    $ext = get_extension(my_strtolower($path));
    if (!preg_match("#^(gif|jpg|jpeg|jpe|bmp|png)$#i", $ext)) {
        $final_avatar_url = "";
    }

    return $final_avatar_url;

}

//re-usable function to correctly set the player avatar
function playeroverview_set_playeravatar($player)
{

    global $lang, $mybb;

    $playeravalink = $playeravatar = $playeravatar_image_html = $avaheader = "";

    //SETTINGS
    $playeroverview_avatar_default = htmlspecialchars_uni($mybb->settings['playeroverview_avatar_default']);
    $playeroverview_avatar_width = intval($mybb->settings['playeroverview_avatar_width']);
    $playeroverview_avatar_height = intval($mybb->settings['playeroverview_avatar_height']);

    //show avatar of player
    $avaheader = '<td class="tcat"><span class="smalltext"><strong>' . $lang->playeroverview_avatar_title . '</strong></span></td>';
    $playeravalink = htmlspecialchars_uni($player['avatar_link']);
    $playeravatar = htmlspecialchars_uni($player['avatar_link']);
    $playeravatar_image_html = '<img src="' . $playeravatar . '" alt="Spieler-Avatar" width="' . $playeroverview_avatar_width . '" height="' . $playeroverview_avatar_height . '" />';

    if (empty($player['avatar_link']) || $mybb->user['uid'] == 0) {

        //if no avatar is given by the user, show the default avatar - but only if there is a default avatar given in the settings
        if (!empty($playeroverview_avatar_default)) {
            $playeravatar = playeroverview_validate_standard_avatar($playeroverview_avatar_default);
            $playeravatar_image_html = '<img src="' . $playeravatar . '" alt="Spieler-Avatar" width="' . $playeroverview_avatar_width . '" height="' . $playeroverview_avatar_height . '" />';

        } else {
            $playeravatar = "";
            $playeravatar_image_html = $lang->playeroverview_noava;
            if ($mybb->user['uid'] == 0 && !empty($player['avatar_link'])) {
                $playeravatar_image_html = $lang->playeroverview_noava_guest;
            }
        }
    }

    $avavalues = array(
        'avaheader' => $avaheader,
        'playeravatar' => $playeravatar,
        'playeravatar_image_html' => $playeravatar_image_html,
        'playeravalink' => $playeravalink
    );

    return $avavalues;
}

//re-usable function to correctly set the character avatar
function playeroverview_set_charaavatar($character)
{
    global $lang, $db, $mybb;

    $charaavatar = "";

    //SETTINGS
    $playeroverview_characters_avatar_default = htmlspecialchars_uni($mybb->settings['playeroverview_characters_avatar_default']);

    //show avatar of character
    $charaavatar = $character['avatar'];

    //if no avatar is given by the user, show the default avatar - but only if there is a default avatar given in the settings
    if (empty($character['avatar']) || $mybb->user['uid'] == 0) {

        if (!empty($playeroverview_characters_avatar_default)) {
            $charaavatar = playeroverview_validate_standard_avatar($playeroverview_characters_avatar_default);
        } else {
            $charaavatar = $character['avatar'];
            if ($mybb->user['uid'] == 0 && !empty($character['avatar'])) {
                $charaavatar = "";
            }
        }
    }

    return $charaavatar;

}

function playeroverview_onlinestatus($player)
{
    global $lang, $db, $mybb;

    $onlinestatus = "offline";
    $playerid = $player['pid'];

    $timesearch = TIME_NOW - (int) $mybb->settings['wolcutoff']; //same timesearch as in online part for footer

    //get info of admin uid1 (==NixAeterna Account)
    $query = $db->query("
        SELECT
                s.sid, s.ip, s.time, s.location, u.uid, u.username, u.invisible, u.usergroup, u.displaygroup, u.avatar, u.lastactive
        FROM
                " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "sessions s ON (u.uid=s.uid)
        WHERE u.as_playerid = '$playerid'
        ");

    while ($user = $db->fetch_array($query)) {

        //if user was online with any of the charas, show him as online
        if ($user['time'] > $timesearch) {
            $onlinestatus = "online";
        }

        //never set it to offline here, otherwise it might be set to offline with the last chara

    }

    return $onlinestatus;

}

//show away in overview
function playeroverview_away($player)
{
    global $db, $mybb, $lang;

    require_once MYBB_ROOT . "inc/class_parser.php";
    $parser = new postParser;

    $playerid = $player['pid'];

    $awaydate = $awayreason = $returndate = "";
    $is_away = false;

    $query = $db->simple_select("users", "*", "as_playerid='$playerid'");

    while ($user = $db->fetch_array($query)) {
        if ($user['away'] == 1 && $mybb->settings['allowaway'] != 0) {
            $is_away = true;
            $awaydate = my_date($mybb->settings['dateformat'], $user['awaydate']);
            if (!empty($user['awayreason'])) {
                $awayreason = htmlspecialchars_uni($parser->parse_badwords($user['awayreason']));
            } else {
                $awayreason = $lang->playeroverview_away_no_reason;
            }
            if (empty($user['returndate'])) {
                $returndate = $lang->playeroverview_away_unknown;
            } else {
                $returnmkdate = strtotime($user['returndate']);
                $returndate = my_date($mybb->settings['dateformat'], $returnmkdate);
            }
        }
    }

    $awayvalues = array(
        'is_away' => $is_away,
        'awaydate' => $awaydate,
        'awayreason' => $awayreason,
        'returndate' => $returndate
    );

    return $awayvalues;
}


//GET AND EVAL CHARACTERS THAT A USER PLAYS
function playeroverview_show_characters($template, $user_playerid, $altbg)
{
    global $db, $mybb, $templates, $lang;

    global $playeroverview_characters_avatar_width, $playeroverview_characters_avatar_height;
    global $playeroverview_characters_bit, $characters_bit, $avatar_bit;
    global $charaheader, $charaname, $charalink, $charaavatar;

    $lang->load("playeroverview");

    //SETTINGS
    $playeroverview_characters_avatar = intval($mybb->settings['playeroverview_characters_avatar']);
    $playeroverview_characters_avatar_width = intval($mybb->settings['playeroverview_characters_avatar_width']);
    $playeroverview_characters_avatar_height = intval($mybb->settings['playeroverview_characters_avatar_height']);

    $charaheader = '<td class="tcat"><span class="smalltext"><strong>' . $lang->playeroverview_charas . '</strong></span></td>';

    //CHARACTER INFO - name, username link, avatar
    //get all users with as_playerid = pid from players table
    $characters = $db->query("
                SELECT *
                FROM " . TABLE_PREFIX . "users u
                WHERE u.as_playerid = '$user_playerid'
                ORDER BY u.username ASC
            ");

    $playeroverview_characters_bit = $characters_bit = $avatar_bit = "";

    while ($character = $db->fetch_array($characters)) {
        //ensure correct username link
        $charaname = htmlspecialchars_uni($character['username']);
        $charalink = build_profile_link(format_name($charaname, $character['usergroup'], $character['displaygroup']), (int) $character['uid']);
        $charaavatar = "";

        //SETTINGS: character avatars only if setting allows
        if ($playeroverview_characters_avatar == 1) {
            $charaavatar = playeroverview_set_charaavatar($character);
            if (!empty($charaavatar)) {
                $avatar_template = ($template == "profile") ? "playeroverview_profile_characters_bit_avatar" : "playeroverview_playerbit_characters_bit_avatar";
                eval ("\$avatar_bit = \"" . $templates->get($avatar_template) . "\";");
            }
        }

        $bit_template = ($template == "profile") ? "playeroverview_profile_characters_bit" : "playeroverview_playerbit_characters_bit";
        eval ("\$characters_bit .= \"" . $templates->get($bit_template) . "\";");
    }

    $list_template = ($template == "profile") ? "playeroverview_profile_characters" : "playeroverview_playerbit_characters";
    eval ("\$playeroverview_characters_bit = \"" . $templates->get($list_template) . "\";");

    return true;
}


// Helper functions to refactor repetitive tasks
function get_player_data($user_uid)
{
    global $db;

    $user_player = $db->fetch_field($db->simple_select('users', 'as_playerid', "uid= '$user_uid'"), 'as_playerid');

    return $db->fetch_array($db->simple_select("players", "*", "pid='$user_player'"));
}

function get_additional_player_info($player_id)
{
    global $db;

    //MORE PLAYER INFO BASED ON CHARACTERS - latest visit, earliest reg date
    // Query to get both last active date and registration date
    $query = $db->simple_select('users', 'MAX(lastactive) AS max_lastactive, MIN(regdate) AS min_regdate', "as_playerid = '$player_id'");
    $result = $db->fetch_array($query);

    // Extract last active date and registration date from the result
    $lastactive_date = my_date('relative', (int) $result['max_lastactive']);
    $regdate_date = my_date('relative', (int) $result['min_regdate']);

    return array('lastactive_date' => $lastactive_date, 'regdate_date' => $regdate_date);
}


// Helper function to get player overview settings
function get_playeroverview_settings()
{
    global $mybb;

    // Get settings
    return array(
        'activate' => intval($mybb->settings['playeroverview_activate']),
        'activate_guest' => intval($mybb->settings['playeroverview_activate_guest']),
        'show_onlinestatus' => intval($mybb->settings['playeroverview_onlinestatus']),
        'show_away' => intval($mybb->settings['playeroverview_away']),
        'avatar' => intval($mybb->settings['playeroverview_avatar']),
        'avatar_default' => htmlspecialchars_uni($mybb->settings['playeroverview_avatar_default']),
        'avatar_width' => intval($mybb->settings['playeroverview_avatar_width']),
        'avatar_height' => intval($mybb->settings['playeroverview_avatar_height']),
        'characters' => intval($mybb->settings['playeroverview_characters']),
        'characters_avatar_default' => htmlspecialchars_uni($mybb->settings['playeroverview_characters_avatar_default']),
        'characters_avatar' => intval($mybb->settings['playeroverview_characters_avatar']),
        'characters_avatar_width' => intval($mybb->settings['playeroverview_characters_avatar_width']),
        'characters_avatar_height' => intval($mybb->settings['playeroverview_characters_avatar_height'])
    );

}


/************************ PATCHES SETUP ************************/

//set up the patches that are to be inserted
function playeroverview_as_patches()
{

    //patch 1
    $ptitle1 = "Playeroverview edit for accountswitcher: Attach to another";
    $pdescription1 = "Add hook for custom playeroverview plugin: Attach current user to another account";
    $psearch1 = "redirect(\"usercp.php?action=as_edit\", \$lang->aj_attach_success);";
    $pbefore1 = "\$arguments = array(
        'as_playerid' => (int) \$target['as_playerid'],
    );

    \$plugins->run_hooks('as_usercp_attachuser', \$arguments);";

    playeroverview_edit_patches($ptitle1, $pdescription1, $psearch1, $pbefore1);

    //patch 2
    $ptitle2 = "Playeroverview edit for accountswitcher: detach this user from master";
    $pdescription2 = "Add hook for custom playeroverview plugin: Detach current user from master";
    $psearch2 = "redirect(\"usercp.php\", \$lang->aj_detach_success);";
    $pbefore2 = "\$plugins->run_hooks('as_usercp_detachuser');";

    playeroverview_edit_patches($ptitle2, $pdescription2, $psearch2, $pbefore2);

    //patch 3
    $ptitle3 = "Playeroverview edit for accountswitcher: Attach to this";
    $pdescription3 = "Add hook for custom playeroverview plugin: Attach an user to the current account";
    $psearch3 = "redirect(\"usercp.php?action=as_edit\", \$lang->aj_user_attach_success);";
    $pbefore3 = "\$arguments = array(
        'target_uid' => (int) \$target['uid']
    );

    \$plugins->run_hooks('as_usercp_attachother', \$arguments);";

    playeroverview_edit_patches($ptitle3, $pdescription3, $psearch3, $pbefore3);

    //patch 4
    $ptitle4 = "Playeroverview edit for accountswitcher: detach another user from master";
    $pdescription4 = "Add hook for custom playeroverview plugin: Detach user from current account";
    $psearch4 = "redirect(\"usercp.php?action=as_edit\", \$lang->aj_user_detach_success);";
    $pbefore4 = "\$arguments = array(
        'uid' => \$detach_uid,
    );

    \$plugins->run_hooks('as_usercp_detachother', \$arguments);";

    playeroverview_edit_patches($ptitle4, $pdescription4, $psearch4, $pbefore4);

    //apply
    $revert = false;
    playeroverview_apply_patches($revert);
}

//insert patches into the patches overview list
function playeroverview_edit_patches($ptitle, $pdescription, $psearch, $pbefore)
{

    global $db, $PL, $lang;
    $PL || require_once PLUGINLIBRARY;

    $pfile = patches_normalize_file("inc/plugins/accountswitcher/as_usercp.php");

    $search = patches_normalize_search($psearch);

    if (!$search) {
        $lang->load('patches');
        $errors[] = $lang->patches_error_search;
    }

    $search = implode("\n", $search);

    $pafter = "";
    $preplace = $pmulti = $pnone = '0';

    //psize 1: active
    //pside 0: not active
    $data = array(
        'ptitle' => $db->escape_string($ptitle),
        'pdescription' => $db->escape_string($pdescription),
        'psearch' => $db->escape_string($search),
        'pafter' => $db->escape_string($pafter),
        'pbefore' => $db->escape_string($pbefore),
        'preplace' => $db->escape_string($preplace),
        'pmulti' => $db->escape_string($pmulti),
        'pnone' => $db->escape_string($pnone),
        'pfile' => $db->escape_string($pfile),
        'pdate' => 1, // Assuming these are constant values, no need to escape
        'psize' => 1  // Assuming these are constant values, no need to escape
    );

    //activate
    $db->insert_query('patches', $data);

}

//delete patches when plugin is uninstalled
function playeroverview_delete_patches()
{

    global $db;

    //revert patch
    //true -> revert
    $revert = true;
    playeroverview_apply_patches($revert);

    //delete patch from db
    $db->delete_query('patches', "ptitle LIKE '%Playeroverview%'");

    //reapply patches
    $revert = false;
    playeroverview_apply_patches($revert);

}

//apply patches (insert them into the correct .php file)
function playeroverview_apply_patches($revert)
{

    global $db, $PL, $lang;
    $PL || require_once PLUGINLIBRARY;

    $pfile = patches_normalize_file("inc/plugins/accountswitcher/as_usercp.php");
    $dbfile = $db->escape_string($pfile);

    if ($pfile) {
        $edits = array();

        if (!$revert) {
            $query = $db->simple_select(
                'patches',
                '*',
                "pfile='{$dbfile}' AND psize > 0"
            );

            while ($row = $db->fetch_array($query)) {
                $search = patches_normalize_search($row['psearch']);

                $edits[] = array(
                    'search' => $search,
                    'before' => $row['pbefore'],
                    'after' => $row['pafter'],
                    'replace' => (int) $row['preplace'],
                    'multi' => (int) $row['pmulti'],
                    'none' => (int) $row['pnone'],
                    'patchid' => (int) $row['pid'],
                    'patchtitle' => $row['ptitle'],
                );
            }
        }

        $result = $PL->edit_core('patches', $pfile, $edits, true, $debug);

        if ($result === true) {
            // Update deactivated patches:
            $db->update_query(
                'patches',
                array('pdate' => 0),
                "pfile='{$dbfile}' AND psize=0"
            );

            // Update activated patches:
            $update = array(
                'psize' => $revert ? 1 : max(@filesize(MYBB_ROOT . $pfile), 1),
                'pdate' => $revert ? 1 : max(@filemtime(MYBB_ROOT . $pfile), 1),
            );

            $db->update_query(
                'patches',
                $update,
                "pfile='{$dbfile}' AND psize!=0"
            );

        }
    }
}

/************************ WHO'S ONLINE LOCATION SETUP ************************/

function playeroverview_online_activity($user_activity)
{
    global $user;

    if (isset($user['location']) && (my_strpos($user['location'], "misc.php?action=playeroverview") !== false)) {
        $user_activity['activity'] = "playeroverview";
    }
    return $user_activity;
}

function playeroverview_online_location($plugin_array)
{
    global $mybb, $theme, $lang;
    $lang->load("playeroverview");

    if ($plugin_array['user_activity']['activity'] == "playeroverview") {
        $plugin_array['location_name'] = $lang->viewing_playeroverview;
    }

    return $plugin_array;
}

/************************ HEADER LINK ************************/

//add header link if list should be shown (to all or only to members)
function add_menu_playeroverview()
{
    global $db, $mybb, $lang, $templates, $playeroverview_menu;
    $lang->load("playeroverview");

    $playeroverview_menu = "";
    //SETTINGS
    $playeroverview_activate = intval($mybb->settings['playeroverview_activate']);
    $playeroverview_activate_guest = intval($mybb->settings['playeroverview_activate_guest']);

    if ($playeroverview_activate == 1 || ($playeroverview_activate_guest == 1 && $mybb->user['uid'] == 0)) {
        eval ("\$playeroverview_menu = \"" . $templates->get("playeroverview_menu") . "\";");
    }

}

/************************ DEBUG ************************/

//Function only used for error checking: can write to console
function debug_to_console($data)
{
    $output = $data;
    if (is_array($output)) {
        $output = implode(',', $output);
    }

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}
