#!/usr/bin/env bash

cd "$(dirname "$0")"
rm BrokenData/*.bin.plist &>/dev/null || true

for f in BrokenData/*.xml.plist; do
  cp "$f" "${f/.xml./.bin.}"
   plutil -convert binary1 "${f/.xml./.bin.}"
done
