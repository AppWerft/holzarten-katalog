#!/bin/bash

# Projektverzeichnis
PROJECT="$HOME/Projekte/holzarten-katalog"

# Quellverzeichnis
SOURCE="$PROJECT/plugin"

# ZIP-Datei
ZIP="$PROJECT/holzarten-katalog-plugin.zip"

# LocalWP-Zielverzeichnis
TARGET="$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog"

cd "$PROJECT" || exit 1

echo "1. Plugin-ZIP erstellen ..."

rm -f "$ZIP"

TEMP=$(mktemp -d) || exit 1
mkdir -p "$TEMP/holzarten-katalog"

# Plugin-Dateien in den korrekt benannten Ordner kopieren
rsync -a \
    --exclude=".DS_Store" \
    --exclude=".git" \
    "$SOURCE/" \
    "$TEMP/holzarten-katalog/"

# ZIP mit korrekter Verzeichnisstruktur erstellen
cd "$TEMP" || exit 1
zip -qr "$ZIP" holzarten-katalog

rm -rf "$TEMP"

cd "$PROJECT" || exit 1

echo "2. Git-Änderungen übernehmen ..."

git add .

echo "3. Commit erstellen ..."

git commit -m "Holzübersicht in MD, mit Vorschau über JS"

echo "4. Zu GitHub übertragen ..."

git push || exit 1

echo "5. Plugin in LocalWP aktualisieren ..."

mkdir -p "$TARGET"

rsync -av --delete \
    --exclude=".DS_Store" \
    "$SOURCE/" \
    "$TARGET/"

echo "Fertig."
echo "ZIP: $ZIP"
echo "Deploy-Ziel: $TARGET"