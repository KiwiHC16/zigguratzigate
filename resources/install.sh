#!/bin/bash
# Vérifie la présence de ziggurat-zigate. Le programme vient du dépôt gitlab2 jeedom/ziggurat
# (branche ziggurat), compilé en Rust :
#   cargo build --release -p ziggurat-zigate && install -m 755 target/release/ziggurat-zigate /usr/local/bin/
# La compilation n'est pas faite ici : elle demande la chaîne Rust et plusieurs Go.

PROGRESS_FILE=${1:-/tmp/dependancy_zigguratzigate_in_progress}
BIN=${2:-/usr/local/bin/ziggurat-zigate}
touch "${PROGRESS_FILE}"
echo 0 > "${PROGRESS_FILE}"
if [ -x "${BIN}" ]; then
	echo "ziggurat-zigate présent : ${BIN} ($("${BIN}" --version))"
	echo 100 > "${PROGRESS_FILE}"
	rm -f "${PROGRESS_FILE}"
	exit 0
fi
echo "ziggurat-zigate introuvable : ${BIN}"
echo "Compilez-le depuis gitlab2 jeedom/ziggurat (branche ziggurat) :"
echo "  cargo build --release -p ziggurat-zigate"
echo "  install -m 755 target/release/ziggurat-zigate $(dirname ${BIN})/"
rm -f "${PROGRESS_FILE}"
exit 1
