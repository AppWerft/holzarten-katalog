#!/bin/bash

PROJECT="$HOME/Projekte/holzarten-katalog"
SOURCE="$PROJECT/plugin"
ZIP="$PROJECT/holzarten-katalog-plugin.zip"
TARGET="$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog"

cd "$PROJECT" || exit 1

echo "1. Plugin-ZIP erstellen ..."

TEMP=$(mktemp -d) || exit 1
mkdir -p "$TEMP/holzarten-katalog"

rsync -a \
    --exclude=".DS_Store" \
    --exclude=".git" \
    "$SOURCE/" \
    "$TEMP/holzarten-katalog/"

rm -f "$ZIP"

(cd "$TEMP" && zip -qr "$ZIP" holzarten-katalog)

rm -rf "$TEMP"

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
    --exclude="*.zip" \
    "$SOURCE/" \
    "$TARGET/"

echo "Fertig."
echo "ZIP: $ZIP"
echo "Deploy-Ziel: $TARGET"
