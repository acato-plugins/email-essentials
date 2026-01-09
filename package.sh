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
	./.git ./.gitignore .claude \
	package.sh SUBMISSION_NOTES.txt \
	assets/wordpress_org \
	wp_mail_key.patch tools/generate_dkim.php \
	lib/class-migrations.php lib/class-deprecation.php lib/filter-deprecation.php lib/deprecation.php \
	bitbucket-pipelines.yml; do
	echo "Removing $i" ; rm -rf $i
done

echo "#!/bin/bash" > tools/generate_dkim.sh.txt
echo "echo 'Before you can use this tool, please read it and remove this line and the one above. Doing so is accepting responsibility.'; exit 1;" >> tools/generate_dkim.sh.txt
cat tools/generate_dkim.sh >> tools/generate_dkim.sh.txt
rm tools/generate_dkim.sh

cd "$(dirname "$0")"/..

mv /tmp/email-essentials "$TARGETDIR"

cd "$TARGETDIR"

cd ..

[ -f email-essentials.zip ] && rm email-essentials.zip
zip -r email-essentials{.zip,}
