cd ~/Projekte/holzarten-katalog

cd plugin

rm -f holzarten-katalog.zip

cp "$HOME/Downloads/holzarten-export.zip" .
zip -r holzarten-katalog-plugin.zip holzarten-katalog -x "*/.DS_Store" "*/.git/*"

cd ..

pwd

git add plugin/
echo "3. Commit erstellen ..."

git commit -m "Plugin aktualisiert"

echo "4. Zu GitHub übertragen ..."

git push

echo "5. Plugin in LocalWP aktualisieren ..."

rsync -av \
    "plugin/holzarten-katalog/" \
    "$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"
rsync -av \
    "plugin/holzarten-katalog/" \
    "$HOME/Local Sites/holzurne2/app/public/wp-content/plugins/holzarten-katalog/"

echo "Fertig."

