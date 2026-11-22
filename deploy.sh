#!/bin/bash

set -euo pipefail

PROJECT="$HOME/Projekte/holzarten-katalog"
SOURCE="$PROJECT/plugin"
ZIP="$PROJECT/holzarten-katalog-plugin.zip"
WP_ZIP="$PROJECT/holzarten-katalog-wordpress.zip"
TARGET="$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog"

VERSION="${1:-}"
RELEASE=false

# --------------------------------------------------
# Parameter prüfen
# --------------------------------------------------

if [[ $# -gt 1 ]]; then
    echo "Aufruf:"
    echo "  ./deploy.sh"
    echo "  ./deploy.sh 0.1.2"
    exit 1
fi

if [[ -n "$VERSION" ]]; then
    if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "Ungültige Versionsnummer: $VERSION"
        exit 1
    fi

    RELEASE=true
    TAG="v$VERSION"
fi

cd "$PROJECT" || exit 1

# Nur auf main arbeiten
if [[ "$(git branch --show-current)" != "main" ]]; then
    echo "FEHLER: Bitte zuerst auf den Branch main wechseln."
    exit 1
fi

# --------------------------------------------------
# Release-Prüfungen
# --------------------------------------------------

if [[ "$RELEASE" == true ]]; then

    if ! gh auth status >/dev/null 2>&1; then
        echo "FEHLER: GitHub CLI ist nicht angemeldet."
        exit 1
    fi

    if git rev-parse "$TAG" >/dev/null 2>&1; then
        echo "FEHLER: Git-Tag $TAG existiert bereits."
        exit 1
    fi

    if gh release view "$TAG" >/dev/null 2>&1; then
        echo "FEHLER: GitHub-Release $TAG existiert bereits."
        exit 1
    fi

    echo "1. Versionsnummer auf $VERSION setzen ..."

    VERSION="$VERSION" perl -i -pe '
        s/^ \* Version: .*/ * Version: $ENV{VERSION}/;
        s/define\('\''HOLZKATALOG_VERSION'\'', '\''[^'\'']+'\''\);/define('\''HOLZKATALOG_VERSION'\'', '\''$ENV{VERSION}'\'');/
    ' "$SOURCE/holzkatalog.php"

    grep -q "Version: $VERSION" "$SOURCE/holzkatalog.php"
    grep -q "HOLZKATALOG_VERSION', '$VERSION'" "$SOURCE/holzkatalog.php"

else
    echo "Entwicklungs-Deployment ohne Release."
fi

# --------------------------------------------------
# ZIP aus Git-Tracking entfernen
# --------------------------------------------------

if git ls-files --error-unmatch holzarten-katalog-plugin.zip >/dev/null 2>&1; then
    echo "ZIP aus Git-Tracking entfernen ..."
    git rm --cached -f holzarten-katalog-plugin.zip
fi

if ! grep -qxF "holzarten-katalog-plugin.zip" .gitignore 2>/dev/null; then
    echo "holzarten-katalog-plugin.zip" >> .gitignore
fi

# --------------------------------------------------
# Commit erstellen
# --------------------------------------------------

echo "2. Git-Änderungen übernehmen ..."

git add -A

if ! git diff --cached --quiet; then
    if [[ "$RELEASE" == true ]]; then
        COMMIT_MESSAGE="Release $TAG"
    else
        COMMIT_MESSAGE="Plugin aktualisiert, Ortssuche in Karte, noch kein Puls"
    fi

    git commit -m "$COMMIT_MESSAGE"
else
    echo "Keine Änderungen zum Committen."
fi

# --------------------------------------------------
# Zu GitHub übertragen
# --------------------------------------------------

echo "3. Zu GitHub übertragen ..."

git push origin main

# --------------------------------------------------
# ZIP-Dateien erstellen
# --------------------------------------------------

# --------------------------------------------------
# ZIP-Dateien erstellen
# --------------------------------------------------

echo "4. GitHub- und WordPress.org-Pakete erstellen ..."

TEMP=$(mktemp -d)
trap 'rm -rf "$TEMP"' EXIT

# GitHub-Paket: vollständiges Plugin
mkdir -p "$TEMP/github/holzarten-katalog"

rsync -a \
    --exclude=".DS_Store" \
    --exclude=".git" \
    "$SOURCE/" \
    "$TEMP/github/holzarten-katalog/"

rm -f "$ZIP"
(cd "$TEMP/github" && zip -qr "$ZIP" holzarten-katalog)

# WordPress.org-Paket: ohne GitHub-Updater
mkdir -p "$TEMP/wordpress/holzarten-katalog"

rsync -a \
    --exclude=".DS_Store" \
    --exclude=".git" \
    --exclude="plugin-update-checker/" \
    "$SOURCE/" \
    "$TEMP/wordpress/holzarten-katalog/"

# GitHub-Update-Code entfernen
awk '
    /BEGIN GITHUB UPDATE/ { skip=1; next }
    /END GITHUB UPDATE/   { skip=0; next }
    !skip { print }
' "$TEMP/wordpress/holzarten-katalog/holzkatalog.php" \
  > "$TEMP/wordpress/holzarten-katalog/holzkatalog.php.tmp"

mv \
  "$TEMP/wordpress/holzarten-katalog/holzkatalog.php.tmp" \
  "$TEMP/wordpress/holzarten-katalog/holzkatalog.php"

rm -f "$WP_ZIP"
(cd "$TEMP/wordpress" && zip -qr "$WP_ZIP" holzarten-katalog)

echo "GitHub-ZIP:     $ZIP"
echo "WordPress-ZIP:  $WP_ZIP"



# --------------------------------------------------
# GitHub Release
# --------------------------------------------------

if [[ "$RELEASE" == true ]]; then

    echo "5. Git-Tag $TAG erstellen ..."

    git tag -a "$TAG" -m "Release $TAG"
    git push origin "$TAG"

    echo "6. GitHub-Release veröffentlichen ..."

    gh release create "$TAG" "$ZIP" \
        --title "$TAG" \
        --generate-notes

fi

# --------------------------------------------------
# LocalWP aktualisieren
# --------------------------------------------------

echo "7. LocalWP aktualisieren ..."

mkdir -p "$TARGET"

rsync -av --delete --exclude=".DS_Store" --exclude="*.zip" "$SOURCE/" "$TARGET/"

echo
echo "----------------------------------------"

if [[ "$RELEASE" == true ]]; then
    echo "Release $TAG erfolgreich erstellt."
else
    echo "Entwicklungs-Deployment erfolgreich."
fi

echo "ZIP: $ZIP"
echo "LocalWP: $TARGET"
echo "----------------------------------------"