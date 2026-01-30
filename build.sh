#!/usr/bin/env bash
# Build script for Email Essentials
cd "$(dirname "$0")"

[ -f .nvmrc ] && nvm use
[ ! -d node_modules ] && npm install --no-audit --no-fund
npm run build
