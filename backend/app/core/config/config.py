"""Application configuration settings."""

from pathlib import Path
from os import getenv
from dotenv import load_dotenv

# Load .env file
env_file = Path(__file__).resolve().parents[3] / ".env"
load_dotenv(env_file)

BASE_DIR = Path(__file__).resolve().parents[3]
DATABASE_URL = getenv("DB_URL", f"sqlite:///{BASE_DIR / 'perai.db'}")
