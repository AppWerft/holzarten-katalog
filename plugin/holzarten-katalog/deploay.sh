cd ~/Projekte/holzarten-katalog

# Änderungen versionieren
git add plugin/
git commit -m "class-hk-plugin erstellt"
git push

# Plugin in LocalWP aktualisieren
rsync -av plugin/holzarten-katalog/ \
"$HOME/Local Sites/holzurne/app/public/wp-content/plugins/holzarten-katalog/"