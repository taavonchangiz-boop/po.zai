#!/bin/sh
set -eu
cd "$(dirname "$0")"
if ! command -v node >/dev/null 2>&1; then
  echo "Node.js پیدا نشد. در Setup Node.js هاست، Node.js 22 را فعال کنید." >&2
  exit 1
fi
NODE_MAJOR=$(node -p "process.versions.node.split('.')[0]")
echo "[postyar] Node $(node -v)"
if [ "$NODE_MAJOR" -lt 22 ]; then
  echo "خطا: Node.js 22 یا بالاتر لازم است (الان $(node -v))" >&2
  exit 1
fi
exec node app.js
