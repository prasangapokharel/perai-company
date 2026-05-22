# FastAPI Agent Setup Guide

## CRITICAL: Read This First! 🚨

**BEFORE YOU START CODING**:

1. **MANDATORY**: Read `.agent/fastapi/` standards BEFORE writing any code
   - Read `CodingStandard.md` before writing functions/variables
   - Read `ModelStandard.md` before creating models
   - Read `ApiStandard.md` before creating routes
   - Read `TestingStandard.md` before writing tests
   - Each standard has specific patterns you MUST follow

2. **MANDATORY**: Always use `uv` package manager, NOT `pip`
   - `uv` is faster and more reliable for this project
   - Install: `pip install uv` (only use pip once for this)
   - After that: use ONLY `uv pip install`, never bare `pip install`
   - Always run commands inside virtual environment

3. **MANDATORY**: Activate virtual environment BEFORE any work
   - `source venv/bin/activate` (Linux/macOS)
   - `venv\Scripts\activate` (Windows)
   - Verify: Your terminal should show `(venv)` prefix
   - Never run commands outside activated venv

## Overview

This is a comprehensive FastAPI project template with standardized coding conventions, folder structure, and best practices. Clone and follow this guide to start building scalable FastAPI applications immediately.

## Quick Start

### 1. Clone Repository

```bash
git clone <repository-url>
cd fastapi-project
```

### 2. Setup Environment

```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
source venv/bin/activate  # Linux/macOS
# or
venv\Scripts\activate  # Windows

# Verify activation (should show (venv) prefix in terminal)

# Install uv (package manager)
pip install uv

# Install dependencies with uv
uv pip install -r requirements.txt
```

### 3. Configure Environment

```bash
# Copy example env file
cp .env.example .env

# Edit with your configuration
nano .env
```

### 4. Database Setup

```bash
# Initialize database migrations
alembic upgrade head

# Or if starting fresh
alembic revision --autogenerate -m "initial schema"
alembic upgrade head
```

### 5. Start Development Server

```bash
# Using FastAPI CLI
fastapi run

# Or using Uvicorn
uvicorn app.main:app --reload
```

Visit: `http://localhost:8000/docs` for interactive API documentation

## Project Structure

```
fastapi-project/
├── app/
│   ├── __init__.py
│   ├── main.py
│   ├── constants.py
│   ├── core/
│   │   ├── __init__.py
│   │   ├── config/config.py
│   │   ├── security.py
│   │   ├── database.py
│   │   └── middleware.py
│   ├── models/
│   │   ├── __init__.py
│   │   ├── user.py
│   │   ├── chatMessage.py
│   │   └── ...
│   ├── schemas/
│   │   ├── __init__.py
│   │   ├── userSchema.py
│   │   ├── chatSchema.py
│   │   └── ...
│   ├── crud/
│   │   ├── __init__.py
│   │   ├── userCrud.py
│   │   ├── chatCrud.py
│   │   └── ...
│   ├── services/
│   │   ├── __init__.py
│   │   ├── userService.py
│   │   ├── chatService.py
│   │   ├── authService.py
│   │   └── ...
│   ├── utils/
│   │   ├── __init__.py
│   │   ├── dateUtils.py
│   │   ├── validationUtils.py
│   │   └── ...
│   └── api/
│       ├── __init__.py
│       └── v1/
│           ├── __init__.py
│           ├── users/
│           │   ├── __init__.py
│           │   ├── route.py
│           │   └── service.py
│           ├── chat/
│           │   ├── __init__.py
│           │   ├── route.py
│           │   └── service.py
│           └── ...
├── testing/
│   ├── __init__.py
│   ├── conftest.py
│   ├── fixtures.py
│   ├── api/
│   ├── unit/
│   └── integration/
├── docs/
│   ├── api/
│   ├── modules/
│   └── setup.md
├── migrations/
│   ├── env.py
│   ├── alembic.ini
│   └── versions/
├── .agent/
│   └── fastapi/
│       ├── CodingStandard.md
│       ├── FolderStructure.md
│       ├── CommandStandard.md
│       ├── TestingStandard.md
│       ├── MigrationStandard.md
│       ├── ModelStandard.md
│       ├── ApiStandard.md
│       └── FileNaming.md
├── main.py
├── requirements.txt
├── .env.example
├── .gitignore
├── README.md
└── AGENT.md (this file)
```

## Standards & Conventions

### File Naming

**Format**: `camelCase` for all files

```python
# Models
user.py
chatMessage.py

# Services  
userService.py
chatService.py

# Schemas
userSchema.py
chatSchema.py

# Utils
dateUtils.py
validationUtils.py

# Tests
testUserService.py
testUserRoute.py
```

See: `.agent/fastapi/FileNaming.md`

### Code Naming

**Functions & Variables**: `camelCase`
```python
def getUserById(userId: int):
    pass

currentUser = None
isAuthenticated = False
```

**Classes**: `PascalCase`
```python
class UserService:
    pass

class AuthenticationError(Exception):
    pass
```

**Constants**: `UPPER_SNAKE_CASE`
```python
MAX_RETRY_ATTEMPTS = 3
DEFAULT_PAGE_SIZE = 20
DATABASE_URL = "postgresql://localhost/db"
```

See: `.agent/fastapi/CodingStandard.md`

### Folder Structure

Follow the modular structure:
- `/app` - Main application code
- `/testing` - All tests (mirrors app structure)
- `/docs` - Documentation
- `/migrations` - Database migrations
- `/.agent/fastapi/` - Standards documentation

See: `.agent/fastapi/FolderStructure.md`

### API Standards

**Endpoints Pattern**: `/api/v1/<resource>`

```
GET    /api/v1/users          → List users
GET    /api/v1/users/{id}     → Get user
POST   /api/v1/users          → Create user
PATCH  /api/v1/users/{id}     → Update user
DELETE /api/v1/users/{id}     → Delete user
```

**Response Format**:
```python
# Success (200/201)
{
    "id": 1,
    "name": "John",
    "email": "john@example.com",
    "createdAt": "2024-01-15T10:30:00"
}

# Error (4xx/5xx)
{
    "detail": "User not found"
}
```

See: `.agent/fastapi/ApiStandard.md`

### Testing Standards

**Test Files**: `test<Module>.py`

```bash
pytest testing/                    # Run all tests
pytest testing/ -v                 # Verbose
pytest testing/ --cov=app          # With coverage
pytest testing/api/v1/users/      # Specific module
```

**Test Structure**: Arrange-Act-Assert (AAA)

```python
def test_getUserById_returnsUserWhenExists():
    # Arrange
    userId = 1
    
    # Act
    response = client.get(f"/api/v1/users/{userId}")
    
    # Assert
    assert response.status_code == 200
```

See: `.agent/fastapi/TestingStandard.md`

### Database Migrations

```bash
# Create migration
alembic revision --autogenerate -m "description"

# Apply migrations
alembic upgrade head

# Revert migration
alembic downgrade -1

# View history
alembic history --verbose
```

See: `.agent/fastapi/MigrationStandard.md`

### Model Standards

**Model Files**: `<modelName>.py`

```python
from sqlalchemy import Column, Integer, String, DateTime, Boolean
from datetime import datetime
from app.core.database import Base

class User(Base):
    """User model representing system users."""
    __tablename__ = "user"
    
    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False)
    email = Column(String(255), unique=True, index=True)
    
    createdAt = Column(DateTime, default=datetime.utcnow)
    updatedAt = Column(DateTime, onupdate=datetime.utcnow)
    
    isActive = Column(Boolean, default=True)
```

See: `.agent/fastapi/ModelStandard.md`

## Common Commands

### Development

```bash
# Start dev server
fastapi dev main.py

# Format code (with uv)
uv pip install black && black app/ testing/

# Lint code (with uv)
uv pip install flake8 && flake8 app/

# Type checking (with uv)
uv pip install mypy && mypy app/
```

### Testing

```bash
# Run tests (with uv)
uv pip install pytest && pytest testing/

# With coverage
uv pip install pytest-cov && pytest testing/ --cov=app --cov-report=html

# Specific test
pytest testing/api/v1/users/testUserRoute.py::test_getUser_returnsUser -v
```

### Database

```bash
# Create migration
alembic revision --autogenerate -m "add user fields"

# Apply migrations
alembic upgrade head

# Reset database (dev only)
alembic downgrade base
alembic upgrade head
```

### Deployment

```bash
# Build Docker image
docker build -t myapp:latest .

# Run with Gunicorn (install with: uv pip install gunicorn)
gunicorn -w 4 -k uvicorn.workers.UvicornWorker app.main:app
```

See: `.agent/fastapi/CommandStandard.md`

## Adding a New Feature

### 1. Create Models

```bash
# Create app/models/paymentTransaction.py
class PaymentTransaction(Base):
    __tablename__ = "payment_transaction"
    # ... define columns
```

### 2. Create Schema

```bash
# Create app/schemas/paymentSchema.py
class PaymentCreate(BaseModel):
    # ... define fields

class PaymentResponse(BaseModel):
    # ... define fields
```

### 3. Create CRUD

```bash
# Create app/crud/paymentCrud.py
async def createPayment(db: Session, data: dict):
    pass
```

### 4. Create Service

```bash
# Create app/api/v1/payments/service.py
class PaymentService:
    def createPayment(self, data: PaymentCreate):
        pass
```

### 5. Create Routes

```bash
# Create app/api/v1/payments/route.py
@router.post("/payments", response_model=PaymentResponse)
async def createPayment(data: PaymentCreate):
    pass
```

### 6. Create Tests

```bash
# Create testing/api/v1/payments/testPaymentRoute.py
def test_createPayment_successfullyCreates():
    pass
```

### 7. Register Router

```python
# In app/main.py
from app.api.v1.payments.route import router as paymentRouter
app.include_router(paymentRouter)
```

### 8. Create Migration

```bash
alembic revision --autogenerate -m "create payment transaction table"
alembic upgrade head
```

## Key Dependencies

### FastAPI Ecosystem
- `fastapi` - Web framework
- `uvicorn` - ASGI server
- `pydantic` - Data validation

### Database
- `sqlalchemy` - ORM
- `alembic` - Migrations
- `psycopg2` - PostgreSQL driver

### Authentication
- `python-jose` - JWT tokens
- `passlib` - Password hashing
- `python-multipart` - Form data

### Testing
- `pytest` - Test framework
- `pytest-cov` - Coverage reporting
- `httpx` - Async HTTP client

### Code Quality
- `black` - Code formatter
- `flake8` - Linter
- `mypy` - Type checker
- `isort` - Import sorter

See: `requirements.txt`

## Environment Configuration

### .env Example

```env
# Application
ENV=development
DEBUG=True

# Database
DATABASE_URL=postgresql://user:password@localhost:5432/fastapi_db

# Security
SECRET_KEY=your-secret-key-change-in-production
ALGORITHM=HS256
ACCESS_TOKEN_EXPIRE_MINUTES=30

# Email
SMTP_SERVER=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=app-password

# External APIs
OPENAI_API_KEY=your-api-key
REDIS_URL=redis://localhost:6379/0
```

## Pre-commit Setup

```bash
# Install pre-commit
pip install pre-commit

# Install hooks
pre-commit install

# Run hooks manually
pre-commit run --all-files
```

## Documentation Files

Located in `.agent/fastapi/`:

### Core Standards (Read These First!)
1. **CodingStandard.md** - Code naming and style conventions
2. **FolderStructure.md** - Directory layout and organization  
3. **FileNaming.md** - File naming rules for all file types
4. **CommandStandard.md** - Common CLI commands and workflows

### Feature Standards (Before Building Features)
5. **ModelStandard.md** - SQLAlchemy model definitions
6. **ApiStandard.md** - REST API endpoint conventions
7. **TestingStandard.md** - Testing guidelines and examples
8. **MigrationStandard.md** - Database migration patterns

### Advanced Standards (For Advanced Features)
9. **StreamingStandard.md** - JSON Lines streaming patterns
10. **SseStandard.md** - Server-Sent Events implementation
11. **EventStandard.md** - Lifespan events and startup/shutdown
12. **BackgroundTasksStandard.md** - Background task patterns
13. **ServerWorkersStandard.md** - Multi-worker deployment
14. **StaticFilesStandard.md** - Static file serving
15. **AdvancedPathOperationStandard.md** - Advanced routing configuration

## Best Practices

1. **Always use camelCase** for files, functions, and variables
2. **Always use type hints** for function parameters and returns
3. **Always add docstrings** to functions and classes
4. **Always write tests** before or alongside code
5. **Always validate input** with Pydantic schemas
6. **Always handle errors** gracefully with appropriate status codes
7. **Always use async/await** for I/O operations
8. **Always use dependency injection** via FastAPI's `Depends`
9. **Always organize code** by feature module
10. **Always version your API** with `/api/v1/`

## Troubleshooting

### Port Already in Use
```bash
# Find process on port 8000
lsof -i :8000

# Kill process
kill -9 <PID>
```

### Database Connection Issues
```bash
# Check PostgreSQL is running
psql -U user -d database_name

# Reset database
alembic downgrade base
alembic upgrade head
```

### Import Errors
```bash
# Verify virtual environment
which python

# Reinstall dependencies
pip install -r requirements.txt --force-reinstall
```

### Test Failures
```bash
# Run with verbose output
pytest testing/ -vv

# Run specific test with prints
pytest testing/unit/services/testUserService.py::test_func -s
```

## Getting Help

### Resources
- FastAPI Docs: https://fastapi.tiangolo.com
- SQLAlchemy Docs: https://docs.sqlalchemy.org
- Pydantic Docs: https://docs.pydantic.dev
- Pytest Docs: https://docs.pytest.org

### Standards Documentation
- See `.agent/fastapi/` for all standards
- Each document covers specific aspects with examples

## Contributing

1. Follow all standards in `.agent/fastapi/`
2. Write tests for all new features
3. Ensure code passes linting and type checking
4. Create descriptive commit messages
5. Submit pull request with clear description

## License

[Specify your license]

## Contact

[Specify contact information]

---

**Last Updated**: January 2024
**Version**: 1.0.0

For questions or improvements, refer to the standards documentation in `.agent/fastapi/`
