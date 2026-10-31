cd ~/Projekte/holzarten-katalog

# Änderungen versionieren
git add plugin/
git commit -m "Holzarten-Admin erweitert"
git push

# Plugin lokal deployen
rsync -av \
plugin/holzarten-katalog/ \
"$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"
