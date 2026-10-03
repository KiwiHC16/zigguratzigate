# Ziggurat ZiGate (plugin Jeedom)

Fait tourner des passerelles [`ziggurat-zigate`](http://gitlab2.localdomain/jeedom/ziggurat/-/tree/ziggurat/crates/ziggurat-zigate) :
chacune se fait passer pour une ZiGate v1 WiFi auprès d'Abeille, et fait tourner la pile
Zigbee ouverte Ziggurat sur une clé OpenThread RCP (Sonoff Dongle Plus MG24) ou sur le
simulateur (plugin Ziggurat Simulateur).

## Équipement = une passerelle

| Champ | Option | Exemple |
|---|---|---|
| Clé | `--device` | `/dev/ttyUSB0`, ou `tcp://127.0.0.1:9000` pour le simulateur |
| Vitesse, contrôle de flux | `--baudrate`, `--flow-control` | 460800, logiciel (Sonoff MG24) |
| Canal, puissance | `--channel`, `--tx-power` | 25, 8 dBm |
| Écoute | `--listen` | `127.0.0.1:20109` ; dans Abeille : ZiGate en WIFI sur cette adresse |
| Attente au démarrage | `--warm-up` | 35 s |
| Fichier d'état | `--state` | vide : `data/bridge<id>.json` du plugin |
| Capture Wireshark | `--capture` | `0.0.0.0:17761` |

Le démon du plugin lance une passerelle par équipement activé (`nohup setsid`, pid dans le
dossier temporaire Jeedom) et les surveille ; le log de chaque passerelle est
`zigguratzigate_<id>`. Commande info « Démarrée » mise à jour toutes les 5 minutes.

## Fichier d'état

L'équivalent du PDM de la ZiGate : clé réseau, compteurs de trames, clés de lien, appareils.
**À sauvegarder** (le perdre oblige à réappairer), mode 0600, jamais dans git (`data/` est
ignoré). Le dossier `data/` ne doit pas être servi par Apache : `.htaccess` pour une
installation avec `AllowOverride All`, sinon une règle `Require all denied` dans la
configuration d'Apache.

## Programme

`/usr/local/bin/ziggurat-zigate` par défaut (dossier réglable dans la configuration du
plugin), compilé depuis gitlab2 `jeedom/ziggurat`, branche `ziggurat` :

    cargo build --release -p ziggurat-zigate
    install -m 755 target/release/ziggurat-zigate /usr/local/bin/

L'utilisateur `www-data` doit être dans le groupe `dialout` pour ouvrir la clé.
