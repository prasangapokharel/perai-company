#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if ! command -v uv >/dev/null 2>&1; then
  echo "uv not found. Installing uv..."
  python3 -m pip install --user uv
  export PATH="$HOME/.local/bin:$PATH"
fi

if [ -d "venv" ]; then
  VENV_DIR="venv"
elif [ -d ".venv" ]; then
  VENV_DIR=".venv"
else
  echo "Virtual environment not found. Creating .venv..."
  uv venv
  VENV_DIR=".venv"
fi

# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"

uv pip install -r requirements.txt

echo "Backend environment is ready."
echo "Activated virtualenv: $VENV_DIR"
