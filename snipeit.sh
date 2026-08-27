#!/bin/sh
set -eu

echo "Unsupported installer: this fork cannot be installed with the inherited Snipe-IT script." >&2
echo "Use README.md for local development or docs/production-deployment.md for the V1 production path." >&2
exit 1
