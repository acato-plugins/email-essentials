#!/usr/bin/env bash
# Build script for Email Essentials
cd "$(dirname "$0")"

[ -f ~/.bashrc ] && source ~/.bashrc

[ -f .nvmrc ] && [ ! -z nvm ] && nvm install && nvm use
[ ! -f .nvmrc ] && [ ! -z nvm ] && nvm install 20 && nvm use 20
npm install
npm run build
