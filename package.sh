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

for i in \
	$(find . -name \.DS_Store) \
	composer.json info.json \
	node_modules package-lock.json package.json webpack.mix.js .editorconfig \
	./.git ./.gitignore \
	package.sh SUBMISSION_NOTES.txt \
	assets/wordpress_org \
	bitbucket-pipelines.yml; do
	echo "Removing $i" ; rm -rf $i
done

mv tools/generate_dkim.sh{,-example.txt}

cd "$(dirname "$0")"/..

mv /tmp/email-essentials "$TARGETDIR"
