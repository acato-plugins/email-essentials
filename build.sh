#!/usr/bin/env bash
# Build script for Email Essentials
cd "$(dirname "$0")"

[ -f ~/.bashrc ] && source ~/.bashrc
[ -f ~/.nvm/nvm.sh ] && source ~/.nvm/nvm.sh

[ -f .nvmrc ] && [ ! -z nvm ] && nvm install && nvm use
[ ! -f .nvmrc ] && [ ! -z nvm ] && nvm install 24 && nvm use 24
npm install
npm run build
