cd ~/Projekte/holzarten-katalog/
#cp "$HOME/Downloads/holzarten-export.zip" .
zip -r holzarten-katalog-plugin.zip plugin/* -x "*/.DS_Store" "*/.git/*"
pwd
ls -la 

git add *

echo "3. Commit erstellen ..."

git commit -m "neue Plugin struktur ohen ZIP"

echo "4. Zu GitHub übertragen ..."

git push

echo "5. Plugin in LocalWP aktualisieren ..."


rsync -av \
    "plugin/" \
    "$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"

echo "Fertig.\n"