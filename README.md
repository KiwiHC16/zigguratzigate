# Ziggurat ZiGate (plugin Jeedom)

Remplace la ZiGate par une **pile Zigbee ouverte** sans rien changer côté
[Abeille](https://github.com/KiwiHC16/Abeille).

Le plugin fait tourner des passerelles
[`ziggurat-zigate`](https://github.com/KiwiHC16/ziggurat/tree/ziggurat/crates/ziggurat-zigate) :
chacune se présente à Abeille comme une **ZiGate v1 WiFi**, et fait tourner derrière la pile
Zigbee [Ziggurat](https://github.com/zigpy/ziggurat) (Rust) sur une radio **OpenThread RCP**.

```
Abeille ──TCP──▶ ziggurat-zigate ──Spinel──▶ radio RCP (clé Sonoff MG24, ou ZiGate v1 avec zigate-rcp)
         (ZiGate v1)   └─ pile Zigbee Ziggurat, sur l'hôte
```

Pourquoi : la pile NXP fermée des ZiGate a des limites connues (14 NPDU, fuite de tampons,
redémarrages « Ndpu stuck »). Ici la radio ne fait qu'émettre, recevoir et acquitter les
trames 802.15.4 ; tout le reste (sécurité, routage, réessais) tourne sur l'hôte, sans limite
de tampons, dans un code qu'on peut lire et corriger.

## Matériel

| Radio | Firmware | Réglages |
|---|---|---|
| **Sonoff Dongle Plus MG24** (conseillée) | OpenThread RCP 2.7.2.0 (`sonoff_dongle-pmg24_openthread_rcp_2.7.2.0_..._460800_sw_flow.gbl`), flashé avec [`universal-silabs-flasher`](https://github.com/NabuCasa/universal-silabs-flasher) | 460800 bauds, contrôle de flux logiciel |
| **ZiGate v1** (JN5168) | [`zigate-rcp`](https://github.com/KiwiHC16/zigate-rcp) (expérimental) | 115200 bauds, sans contrôle de flux |

Retour arrière sur la clé Sonoff : reflasher le firmware Zigbee NCP d'origine.

## Installation

1. Installer le plugin, puis lancer l'installation des dépendances : sur un hôte ARM 64 bits
   (aarch64), le binaire `ziggurat-zigate` est téléchargé depuis la dernière
   [release](https://github.com/KiwiHC16/zigguratzigate/releases) et vérifié (SHA-256).
   Ailleurs, le compiler depuis [KiwiHC16/ziggurat](https://github.com/KiwiHC16/ziggurat),
   branche `ziggurat` :

       cargo build --release -p ziggurat-zigate
       install -m 755 target/release/ziggurat-zigate /usr/local/bin/

2. L'utilisateur `www-data` doit être dans le groupe `dialout` pour ouvrir la clé.
3. Créer un équipement par radio (voir ci-dessous), l'activer, démarrer le démon.
4. Dans Abeille, déclarer une ZiGate **WIFI** sur l'adresse d'écoute de la passerelle
   (par exemple `127.0.0.1:20108`).

## Équipement = une passerelle

| Champ | Option | Exemple |
|---|---|---|
| Clé | `--device` | `/dev/ttyUSB0`, ou `tcp://127.0.0.1:9000` pour un simulateur |
| Vitesse, contrôle de flux | `--baudrate`, `--flow-control` | 460800, logiciel (Sonoff MG24) |
| Canal, puissance | `--channel`, `--tx-power` | 25, 8 dBm |
| Écoute | `--listen` | `127.0.0.1:20108` ; dans Abeille : ZiGate en WIFI sur cette adresse |
| Attente au démarrage | `--warm-up` | 35 s |
| Fichier d'état | `--state` | vide : `data/bridge<id>.json` du plugin |
| Capture Wireshark | `--capture` | `0.0.0.0:17761` |

Le démon du plugin lance une passerelle par équipement activé et les surveille ; le log de
chaque passerelle est `zigguratzigate_<id>`. La commande info « Démarrée » est mise à jour
toutes les 5 minutes. Le programme est cherché dans `/usr/local/bin` (dossier réglable dans
la configuration du plugin).

## Fichier d'état

L'équivalent du PDM de la ZiGate : clé réseau, PAN, canal, compteurs de trames, clés de lien,
appareils. **À sauvegarder** : le perdre oblige à réappairer tous les appareils. Il est en
mode 0600 et jamais dans git (`data/` est ignoré). Le dossier `data/` ne doit pas être servi
par Apache : `.htaccess` fourni pour une installation avec `AllowOverride All`, sinon une
règle `Require all denied` dans la configuration d'Apache.

Abeille ne peut pas effacer ce fichier (commande 0x0012 refusée par la passerelle).

## Licence

AGPL-3.0 (voir `LICENSE`). Ziggurat et `ziggurat-zigate` sont sous licence Apache-2.0.
