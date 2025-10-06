#!/usr/bin/env bash
# packaging script for Email Essentials

cd "$(dirname "$0")"

[ -d /tmp/email-essentials ] && rm -rf /tmp/email-essentials || true
cp -a . /tmp/email-essentials
cd /tmp/email-essentials

for i in \
	$(find . -name \.DS_Store) \
	composer.json info.json \
	node_modules package-lock.json package.json webpack.mix.js .editorconfig \
	./.git ./.gitignore \
	phpcs.xml \
	package.sh \
	assets/wordpress_org \
	bitbucket-pipelines.yml; do
	echo "Removing $i" ; rm -rf $i
done

mv tools/generate_dkim.sh{,-example.txt}

find .
