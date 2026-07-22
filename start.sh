#!/usr/bin/env bash
set -euo pipefail

# Clean start for the backend inside its virtualenv.
# Usage: ./start.sh [host] [port]

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$ROOT_DIR/backend"
HOST="${1:-0.0.0.0}"
PORT="${2:-8000}"

cd "$BACKEND_DIR"

# Locate the virtualenv.
if [ -d "venv" ]; then
  VENV_DIR="venv"
elif [ -d ".venv" ]; then
  VENV_DIR=".venv"
else
  echo "Virtual environment not found in $BACKEND_DIR (expected venv/ or .venv/)." >&2
  echo "Run backend/setup.sh first." >&2
  exit 1
fi

# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"

# Clean start: free the port if something is already listening on it.
if command -v fuser >/dev/null 2>&1; then
  fuser -k "${PORT}/tcp" 2>/dev/null || true
fi

# Clear stale bytecode caches for a clean start.
find . -type d -name '__pycache__' -prune -exec rm -rf {} + 2>/dev/null || true

echo "Starting backend on http://${HOST}:${PORT} (venv: $VENV_DIR)"
exec uvicorn app.main:app --host "$HOST" --port "$PORT" --reload
