# FastAPI Command Standards

## Overview

This document defines standard commands for development, testing, and deployment workflows. All commands use `uv` as the package manager for consistency and speed.

## CRITICAL: Package Manager Requirements

**NEVER** use `pip` after initial setup. Always use `uv pip` commands instead:

```bash
# ONE TIME ONLY - Bootstrap uv
pip install uv

# After that, ALWAYS use uv
uv pip install package-name      # Install packages
uv pip list                       # List packages
uv pip freeze > requirements.txt  # Generate requirements

# NEVER use bare pip in this project
pip install package-name          # ❌ WRONG
uv pip install package-name       # ✅ CORRECT
```

## Project Setup

### Initial Setup (WITH uv)

```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
# On Linux/macOS
source venv/bin/activate

# On Windows
venv\Scripts\activate

# VERIFY activation (terminal should show (venv) prefix)

# Install uv (only use pip once for this)
pip install uv

# Install all dependencies with uv
uv pip install -r requirements.txt
```

### Environment Configuration

```bash
# Copy example environment file
cp .env.example .env

# Edit .env with your configuration
nano .env  # or use your preferred editor
```

## Development Commands

### Start Development Server

```bash
# Using FastAPI CLI (recommended)
fastapi dev main.py

# Using Uvicorn directly
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

### Code Formatting

```bash
# Format code with Black (install via uv)
uv pip install black
black app/ testing/

# Format imports with isort (install via uv)
uv pip install isort
isort app/ testing/

# Combined formatting
uv pip install black isort
black app/ testing/ && isort app/ testing/
```

### Linting

```bash
# Check code with Flake8 (install via uv)
uv pip install flake8
flake8 app/ testing/

# Type checking with Mypy (install via uv)
uv pip install mypy
mypy app/

# Comprehensive linting
uv pip install flake8 mypy
flake8 app/ testing/ && mypy app/
```

## Testing Commands

### Run All Tests

```bash
# Install pytest via uv
uv pip install pytest pytest-cov

# Run entire test suite
pytest testing/

# Run with verbose output
pytest testing/ -v

# Run with coverage report
pytest testing/ --cov=app --cov-report=html
```

### Run Specific Tests

```bash
# Run tests for a module
pytest testing/api/v1/users/ -v

# Run a specific test file
pytest testing/unit/services/testUserService.py -v

# Run a specific test function
pytest testing/unit/services/testUserService.py::test_getUserById -v
```

### Test Coverage

```bash
# Generate coverage report
uv pip install pytest-cov
pytest testing/ --cov=app --cov-report=html --cov-report=term

# View coverage in terminal
coverage report

# Generate HTML coverage report
coverage html
```

### API Testing

```bash
# Test with fixtures and conftest
pytest testing/api/v1/ -v

# Test with specific markers
pytest -m "unit" testing/

# Test with parallel execution (install pytest-xdist first)
uv pip install pytest-xdist
pytest testing/ -n auto
```

## Database Commands

### Migrations (using Alembic)

```bash
# Install alembic (if not in requirements.txt)
uv pip install alembic

# Initialize Alembic (one-time)
alembic init migrations

# Create migration script
alembic revision --autogenerate -m "description of changes"

# Apply migrations
alembic upgrade head

# Revert migrations
alembic downgrade -1

# View migration history
alembic current
alembic history
```

### Database Reset

```bash
# Drop all tables (development only)
python -m scripts.resetDatabase

# Seed initial data
python -m scripts.seedDatabase
```

## Documentation Commands

### Generate API Documentation

```bash
# View automatic Swagger UI
# Visit: http://localhost:8000/docs

# View ReDoc documentation
# Visit: http://localhost:8000/redoc

# Download OpenAPI JSON
curl http://localhost:8000/openapi.json > openapi.json
```

### Generate Code Documentation

```bash
# Build documentation with Sphinx (if configured)
uv pip install sphinx
sphinx-build -b html docs/ docs/_build/

# Generate module documentation
uv pip install pdoc
pdoc --html app/ --output-dir docs/api
```

## Deployment Commands

### Build & Package

```bash
# Build Docker image
docker build -t my-fastapi-app:latest .

# Run Docker container locally
docker run -p 8000:8000 my-fastapi-app:latest

# Push to registry
docker tag my-fastapi-app:latest myregistry/my-fastapi-app:latest
docker push myregistry/my-fastapi-app:latest
```

### Production Deployment with Multiple Workers

```bash
# Install gunicorn and uvicorn
uv pip install gunicorn uvicorn

# Start with Gunicorn + Uvicorn (4 workers)
gunicorn -w 4 -k uvicorn.workers.UvicornWorker app.main:app

# Start with specific configuration
gunicorn -w 4 -k uvicorn.workers.UvicornWorker \
  --bind 0.0.0.0:8000 \
  --access-logfile - \
  --error-logfile - \
  app.main:app

# Start with FastAPI CLI (single worker)
fastapi run --workers 4 main.py
```

## Useful Development Commands

### Database Shell

```bash
# Connect to PostgreSQL
psql -U user -d database_name -h localhost

# Within psql
\dt                    # List tables
\d table_name          # Describe table
\q                     # Quit
```

### Python Interactive Shell

```bash
# Start Python REPL
python

# Or with IPython (install via uv)
uv pip install ipython
ipython

# Or with FastAPI REPL
fastapi run app/main.py --reload
```

### Dependency Management with uv

```bash
# List installed packages
uv pip list

# Show package details
uv pip show package-name

# Generate requirements file
uv pip freeze > requirements.txt

# Install specific version
uv pip install "package-name>=1.0,<2.0"

# Update all packages (generate new requirements)
uv pip install --upgrade -r requirements.txt

# Sync requirements exactly (remove extra packages)
uv pip sync requirements.txt
```

## Pre-commit Hooks

### Setup Pre-commit

```bash
# Install pre-commit framework via uv
uv pip install pre-commit

# Install git hooks
pre-commit install

# Run hooks on all files (manual)
pre-commit run --all-files

# Update pre-commit hooks
pre-commit autoupdate
```

### Configure .pre-commit-config.yaml

```yaml
repos:
  - repo: https://github.com/psf/black
    rev: 23.0.0
    hooks:
      - id: black
        language_version: python3

  - repo: https://github.com/PyCQA/isort
    rev: 5.12.0
    hooks:
      - id: isort

  - repo: https://github.com/PyCQA/flake8
    rev: 6.0.0
    hooks:
      - id: flake8

  - repo: https://github.com/pre-commit/mirrors-mypy
    rev: v1.0.0
    hooks:
      - id: mypy
```

## Git Workflow Commands

```bash
# Create feature branch
git checkout -b feature/feature-name

# Check status
git status

# Stage changes
git add app/

# Commit changes
git commit -m "feat: add new feature"

# Push changes
git push origin feature/feature-name

# Create pull request
gh pr create --title "Feature: Description" --body "Details"
```

## Performance & Monitoring Commands

### Load Testing

```bash
# Install Apache Bench (system package, not via pip)
# Ubuntu: sudo apt-get install apache2-utils
# macOS: brew install httpd

# Run load test
ab -n 1000 -c 10 http://localhost:8000/health

# Using Locust (install via uv)
uv pip install locust
locust -f testing/load/locustfile.py --host=http://localhost:8000
```

### Profiling

```bash
# Run with Python profiler
python -m cProfile -s cumulative main.py

# Generate flame graph (install via uv)
uv pip install py-spy
py-spy record -o profile.svg -- python main.py
```

## Quick Reference

| Command | Purpose |
|---------|---------|
| `uv pip install -r requirements.txt` | Install dependencies |
| `fastapi dev main.py` | Start dev server |
| `pytest testing/` | Run all tests |
| `uv pip install black && black app/` | Format code |
| `uv pip install flake8 && flake8 app/` | Lint code |
| `uv pip install mypy && mypy app/` | Type check |
| `alembic upgrade head` | Apply migrations |
| `docker build -t app:latest .` | Build image |
| `pre-commit run --all-files` | Run pre-commit checks |
| `uv pip freeze > requirements.txt` | Generate requirements |

## Environment Variables

### Development (.env)

```env
# Application
ENV=development
DEBUG=True

# Database
DATABASE_URL=postgresql://user:password@localhost:5432/app_db

# Security
SECRET_KEY=your-secret-key-here
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30

# Email
SMTP_SERVER=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password

# External APIs
OPENAI_API_KEY=your-api-key
REDIS_URL=redis://localhost:6379/0
```

## Troubleshooting Commands

```bash
# Clear Python cache
find . -type d -name __pycache__ -exec rm -r {} +
find . -type f -name "*.pyc" -delete

# Reinstall packages with uv
uv pip install --force-reinstall -r requirements.txt

# Check port usage
lsof -i :8000

# Kill process on port
kill -9 $(lsof -ti:8000)

# View recent logs
tail -f logs/app.log

# Verify uv is installed
uv --version

# Check which Python is active
which python
```

## Important Notes

- Always ensure virtual environment is activated (`(venv)` prefix visible)
- Always use `uv pip` instead of bare `pip` after initial setup
- All testing requires pytest to be installed: `uv pip install pytest pytest-cov`
- Code quality tools must be explicitly installed via uv
- Pre-commit hooks must be installed after cloning: `pre-commit install`
