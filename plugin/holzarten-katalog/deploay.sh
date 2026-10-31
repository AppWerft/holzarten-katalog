cd ~/Projekte/holzarten-katalog

# Änderungen versionieren
git add plugin/
git commit -m "Import addiert"
git push

# Plugin lokal deployen
rsync -av plugin/holzarten-katalog/ "$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"
