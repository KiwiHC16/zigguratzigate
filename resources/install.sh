#!/bin/bash
# Installe ziggurat-zigate s'il manque.
# 1. Binaire précompilé de la dernière release GitHub du plugin (aarch64), vérifié par SHA-256.
# 2. Sinon : compilation à faire à la main depuis https://github.com/KiwiHC16/ziggurat (branche ziggurat) :
#      cargo build --release -p ziggurat-zigate && install -m 755 target/release/ziggurat-zigate /usr/local/bin/

PROGRESS_FILE=${1:-/tmp/dependancy_zigguratzigate_in_progress}
BIN=${2:-/usr/local/bin/ziggurat-zigate}
REPO=KiwiHC16/zigguratzigate
touch "${PROGRESS_FILE}"
echo 0 > "${PROGRESS_FILE}"

finish() {
	rm -f "${PROGRESS_FILE}"
	exit "$1"
}

if [ -x "${BIN}" ]; then
	echo "ziggurat-zigate présent : ${BIN} ($("${BIN}" --version))"
	echo 100 > "${PROGRESS_FILE}"
	finish 0
fi

case "$(uname -m)" in
	aarch64|arm64) ARCH=aarch64 ;;
	*) ARCH="" ;;
esac

if [ -n "${ARCH}" ]; then
	ASSET="ziggurat-zigate-linux-${ARCH}"
	URL="https://github.com/${REPO}/releases/latest/download/${ASSET}"
	TMP=$(mktemp -d)
	echo "Téléchargement de ${URL}"
	echo 20 > "${PROGRESS_FILE}"
	if curl -fsSL -o "${TMP}/${ASSET}" "${URL}" && curl -fsSL -o "${TMP}/${ASSET}.sha256" "${URL}.sha256"; then
		echo 60 > "${PROGRESS_FILE}"
		if (cd "${TMP}" && sha256sum -c "${ASSET}.sha256"); then
			sudo install -m 755 "${TMP}/${ASSET}" "${BIN}"
			rm -rf "${TMP}"
			echo "ziggurat-zigate installé : ${BIN} ($("${BIN}" --version))"
			echo 100 > "${PROGRESS_FILE}"
			finish 0
		fi
		echo "Somme SHA-256 incorrecte : binaire non installé"
	else
		echo "Téléchargement impossible"
	fi
	rm -rf "${TMP}"
else
	echo "Pas de binaire précompilé pour $(uname -m)"
fi

echo "ziggurat-zigate introuvable : ${BIN}"
echo "Compilez-le depuis https://github.com/KiwiHC16/ziggurat (branche ziggurat) :"
echo "  cargo build --release -p ziggurat-zigate"
echo "  install -m 755 target/release/ziggurat-zigate $(dirname "${BIN}")/"
finish 1
