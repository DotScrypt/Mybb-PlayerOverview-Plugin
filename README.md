# Spieler-Übersicht
Dieses "Spieler-Übersicht" Plugin erstellt eine Wer ist Wer Liste - es ermöglicht somit Spieler/innen, eine Liste der gespielten Charakteren auf einer separaten Seite anzuzeigen. <b>Spieler/innen können einen Spieler-Namen, eine Spieler-Beschreibung, und einen Avatar für die Spieler-Übersicht angeben</b>. Das Plugin arbeitet nahtlos mit dem Accountswitcher Plugin von doylecc zusammen, was bedeutet, dass jede/r Spieler/in die Angaben (Name / Beschreibung / Avatar) nur einmal machen müssen, und alle angehängten Accounts erhalten automatisch dieselben Angaben.

### Hinweis
Wird ein Account in den Accountswitcher Einstellungen von einem anderen Account gelöst, wird er entsprechend in der Spieler-Übersicht unter einem neuen Spieler angezeigt. 

### Alternativen
- Wem die Funktion für zusätzliche Spieler-Felder in der Wer-ist-Wer Übersicht egal ist, kann sich das <a href="https://storming-gates.de/showthread.php?tid=19354" target="_blank">Wer-ist-Wer</a> Plugin von <a href="https://storming-gates.de/member.php?action=profile&uid=112" target="_blank">Chan / Melancholia</a> anschauen. Der Support für das Plugin wurde allerdings eingestellt.

- Wer gerne ein Wer-ist-Wer Plugin installieren möchte, welches andere Funktionen wie Spielerstatistiken etc. bietet, kann sich das <a href="https://github.com/little-evil-genius/Spielerverzeichnis" target="_blank">Spielerverzeichnis & Statistiken</a> Plugin von <a href="https://github.com/little-evil-genius" target="_blank">Lara / little.evil.genius</a> anschauen. 

## Änderungsprotokoll: 
- Version 1.0

### Geplante Erweiterungen: 
- Spielername / Beschreibung / Avatar in Posts

### Anmerkungen / Einschränkungen:
- Wenn ihr den Spielernamen bisher über Profilfelder geregelt habt, dann würde ich vorschlagen, das Profilfeld für die erste Zeit bestehen zu lassen, bis die Spieler ihre Einträge manuell übertragen haben. Derzeit gibt es noch keine automatische Übertragungen.
- Derzeit ist es noch nicht angesehen, den Spielernamen in den Beiträgen anzeigen zu können, das wird aber mit zukünftigen Versionen kommen. Sobald das kommt, ist das eigene Profilfeld mit dem Spielernamen obsolet. 

# Voraussetzungen
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein. 
- Das <a href="https://github.com/frostschutz/MyBB-Patches" target="_blank">Patches-Plugin</a> von frostschutz <b>muss</b> installiert sein. 
- Das Plugin wurde für PHP 8 programmiert. 

# Datenbank-Änderungen
### Hinzugefügte Tabelle: 
- PRÄFIX_players

### Hinzugefügte Spalten in der bestehenden Tabelle PRÄFIX_user:
- as_playerid

# Eingefügte Patches
### Für das File "inc/plugins/accountswitcher/as_usercp.php"
- Playeroverview edit for accountswitcher: Attach to another
- Playeroverview edit for accountswitcher: detach this user from master
- Playeroverview edit for accountswitcher: Attach to this
- Playeroverview edit for accountswitcher: detach another user from master

### Hinweis: 
Als Teil des Plugins werden automatisch neue Patches installiert. Alle Patches, die vorher bereits für das File "inc/plugins/accountswitcher/as_usercp.php" erstellt wurden, werden <b>AUTOMATISCH AKTIVIERT UND ENGEFÜGT</b>.

# Neue Sprachdateien
- deutsch_du/playeroverview.lang.php
- deutsch_du/admin/playeroverview.lang.php

# Einstellungen
- Spieler-Übersicht aktivieren / deaktivieren
- Spieler-Übersicht für Gäste aktivieren / deaktivieren
- Onlinestatus der Spieler in der Spieler-Übersicht anzeigen
- Abwesenheit der Spieler in der Spieler-Übersicht anzeigen
- Avatar der Spieler anzeigen
- Angabe des Default-Spieler-Avatars, falls ein Spieler das Feld leer lässt
- Spieler-Avatar Höhe
- Spieler-Avatar Breite
- Alle Charaktere des Spielers in der Übersicht anzeigen
- Avatar der angehängten Charaktere anzeigen
- Angabe des Default-Charakter-Avatars, falls das Avatar-Feld bei einem Charakter leer ist
- Charakter-Avatar Höhe
- Charakter-Avatar Breite

# Neue Templates
Alle neuen Templates sind in der Template-Gruppe "Player Overview Templates" zu finden. Die Aufteilung hier ist danach, in welchem Bereich sie dann aufgerufen werden.

### - playeroverview (misc) - 
- playeroverview									
- playeroverview_playerbit						
- playeroverview_playerbit_avatar
- playeroverview_playerbit_away					
- playeroverview_playerbit_characters				
- playeroverview_playerbit_characters_bit			
- playeroverview_playerbit_characters_bit_avatar
- playeroverview_playerbit_onlinestatus	
	
### - user_cp - 	
- playeroverview_ucp								
- playeroverview_ucp_avatar						

### - member_profile - 
- playeroverview_profile							
- playeroverview_profile_avatar					
- playeroverview_profile_characters				
- playeroverview_profile_characters_bit			
- playeroverview_profile_characters_bit_avatar	

### - header - 
- playeroverview_menu 

# Template Änderungen
### - member_profile - 
- {$playeroverview_profile} eingefügt
- {$profile_attached} entfernt (da die Charakter-Übersicht Teil des neuen Plugins ist)

### - user_cp - 
- {$playeroverview_ucp} eingefügt

### - header - 
- {$playeroverview_menu}

# Neue CSS
- playeroverview.css

# Neue Links
- deine-webseite.ch/misc.php?action=playeroverview

# Demo Screenshots
## Spieler-Übersicht Liste
### Deaktiviert
<img src="https://i.imgur.com/VrXQBN7.png" alt="Player-Overview-deactivated" border="0">

### Deaktiviert für Gäste
<img src="https://i.imgur.com/BZOaOTS.png" alt="Player-Overview-deactivated-guest" border="0"> 

### Aktiviert für Mitglieder
<img src="https://i.imgur.com/nnlsvmp.png" alt="Player-Overview-List" border="0">

### Aktiviert für Gäste
<img src="https://i.imgur.com/ZCiUaYt.png" alt="Player-Overview-List-guest" border="0">

### Avatare deaktiviert
<img src="https://i.imgur.com/eUnZpDI.png" alt="Player-Overview-noava" border="0">

### Charakter-Ansicht deaktiviert
<img src="https://i.imgur.com/skYbIQQ.png" alt="Player-Overview-nocharas" border="0">

### Online-Status und Abwesenheit in der Übersicht 
<img src="https://i.imgur.com/ZCiUaYt.png" alt="Player-Overview-nocharas" border="0">

## Profil-Ansicht
### Mitglieder Ansicht
<img src="https://i.imgur.com/FD1iVua.png" alt="Player-Overview-Profile" border="0">

## User-CP Ansicht
### User-CP: Spieler können ihre Informationen angeben
<img src="https://i.imgur.com/rfqu2W6.png" alt="Player-Overview-usercp" border="0">

## Einstellungen
### Einstellungen für das Plugin
<img src="https://i.imgur.com/GIMG7hn.png" alt="Player-Overview-Settings" border="0">
<img src="https://i.imgur.com/Bnzo99p.png" alt="Player-Overview-Settings2" border="0">

## Sonstiges
### Menu in der Linkleiste
<img src="https://i.imgur.com/610GUw1.png" alt="Player-Overview-menu" border="0">

### Wer ist online Ansicht
<img src="https://i.imgur.com/QrUvduQ.png" alt="Player-Overview-whosonline" border="0">
