#!/usr/bin/env bash
set -e
if git diff --cached --name-only --diff-filter=AM | grep -E '^public/(build|js/dist|.*\.map|mix-manifest.json)' >/dev/null; then
  echo "Error: compiled assets detected in commit."
  exit 1
fi
