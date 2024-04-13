# Spieler-Übersicht
Dieses "Spieler-Übersicht" Plugin erstellt eine Wer ist Wer Liste - es ermöglicht somit Spieler/innen, eine Liste der gespielten Charakteren auf einer separaten Seite anzuzeigen. <b>Spieler/innen können einen Spieler-Namen, eine Spieler-Beschreibung, und einen Avatar für die Spieler-Übersicht angeben</b>. Das Plugin arbeitet nahtlos mit dem Accountswitcher Plugin von doylecc zusammen, was bedeutet, dass jede/r Spieler/in die Angaben (Name / Beschreibung / Avatar) nur einmal machen müssen, und alle angehängten Accounts erhalten automatisch dieselben Angaben.

### Hinweis
Wird ein Account in den Accountswitcher Einstellungen von einem anderen Account gelöst, wird er entsprechend in der Spieler-Übersicht unter einem neuen Spieler angezeigt. 

### Credits
Die Basis-Idee stammt von dem <a href="https://storming-gates.de/showthread.php?tid=19354" target="_blank">Wer-ist-Wer</a> Plugin von <a href="https://storming-gates.de/member.php?action=profile&uid=112" target="_blank">Chan / Melancholia, wurde aber um einige Funktionen erweitert. Vielen Dank aber für die Basis! 

## Änderungsprotokoll: 
- Version 1.0

# Voraussetzungen
- Der <a href="https://www.mybb.de/erweiterungen/18x/plugins-verschiedenes/enhanced-account-switcher/" target="_blank">Accountswitcher</a> von doylecc <b>muss</b> installiert sein. 
- Das <a href="https://github.com/frostschutz/MyBB-Patches" target="_blank">Patches-Plugin</a> von frostschutz <b>muss</b> installiert sein. 


# Datenbank-Änderungen

### Hinzugefügte Tabelle: 
- PRÄFIX_players

### Hinzugefügte Spalten in der bestehenden Tabelle PRÄFIX_user:
- as_pid

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

# Neue Templates
### - playeroverview - 
- playeroverview									
- playeroverview_playerbit						
- playeroverview_playerbit_avatar					
- playeroverview_playerbit_characters				
- playeroverview_playerbit_characters_bit			
- playeroverview_playerbit_characters_bit_avatar	
	
### - user_cp - 	
- playeroverview_ucp								
- playeroverview_ucp_avatar						

### - member_profile - 
- playeroverview_profile							
- playeroverview_profile_avatar					
- playeroverview_profile_characters				
- playeroverview_profile_characters_bit			
- playeroverview_profile_characters_bit_avatar	

Die Templates sind für jedes Theme unter der Templategruppe "Player Overview Templates" zu finden.

# Template Änderungen
### - member_profile - 
- {$playeroverview_profile} eingefügt
- {$profile_attached} entfernt (da die Charakter-Übersicht Teil des neuen Plugins ist)

### - user_cp - 
- {$playeroverview_ucp} eingefügt

# Neue CSS
- playeroverview_container

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
<img src="https://i.imgur.com/YdBrOdW.png" alt="Player-Overview-Settings1" border="0">
<img src="https://i.imgur.com/Bnzo99p.png" alt="Player-Overview-Settings2" border="0">

## Sonstiges
### Menu in der Linkleiste
<img src="https://i.imgur.com/610GUw1.png" alt="Player-Overview-menu" border="0">

### Wer ist online Ansicht
<img src="https://i.imgur.com/QrUvduQ.png" alt="Player-Overview-whosonline" border="0">
