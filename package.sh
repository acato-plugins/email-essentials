#!/usr/bin/env bash
# packaging script for Email Essentials

cd "$(dirname "$0")"

PLUGINDIR="$(pwd -P)"
echo "Source: $PLUGINDIR"
TARGETDIR="$PLUGINDIR/../email-essentials"
echo "Target: $TARGETDIR"

[ -d /tmp/email-essentials ] && rm -rf /tmp/email-essentials || true
[ -d "$TARGETDIR" ] && rm -rf "$TARGETDIR" || true
cp -a "$PLUGINDIR" /tmp/email-essentials
cd /tmp/email-essentials

[ -f .nvmrc ] && nvm use
[ ! -d node_modules ] && npm install --no-audit --no-fund
npm run build

for i in \
	$(find . -name \.DS_Store) \
	composer.json info.json \
	node_modules package-lock.json .editorconfig \
	./.git ./.gitignore \
	package.sh SUBMISSION_NOTES.txt \
	assets/wordpress_org \
	wp_mail_key.patch \
	lib/class-migrations.php \
	bitbucket-pipelines.yml; do
	echo "Removing $i" ; rm -rf $i
done

mv tools/generate_dkim.sh{,-example.txt}
mv tools/generate_dkim.php{,-example.txt}

cd "$(dirname "$0")"/..

mv /tmp/email-essentials "$TARGETDIR"

cd "$TARGETDIR"

cd ..

zip -r email-essentials{.zip,}
