cd ~/Projekte/holzarten-katalog/

zip -r holzarten-katalog-plugin.zip plugin/* -x "*/.DS_Store" "*/.git/*"

pwd

ls -la 


git add *

echo "3. Commit erstellen ..."

git commit -m "Bilder auf 512 skaliert"

echo "4. Zu GitHub übertragen ..."

git push

echo "5. Plugin in LocalWP aktualisieren ..."


rsync -av \
    "plugin/" \
    "$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"

echo "Fertig.\n"