# FastAPI Folder Structure

## Project Layout

This document defines the standard directory structure for FastAPI applications.

```
project-root/
├── app/
│   ├── __init__.py
│   ├── main.py                    # Application entry point
│   ├── constants.py               # Global constants
│   │
│   ├── core/
│   │   ├── __init__.py
│   │   ├── config/
│   │   │   ├── __init__.py
│   │   │   └── config.py          # Configuration settings
│   │   ├── security.py            # Security utilities
│   │   ├── database.py            # Database setup
│   │   ├── middleware.py          # Custom middleware
│   │   └── exceptions.py          # Custom exceptions
│   │
│   ├── models/
│   │   ├── __init__.py
│   │   ├── user.py                # User model
│   │   ├── chatMessage.py         # Chat message model
│   │   ├── session.py             # Session model
│   │   └── ...
│   │
│   ├── schemas/
│   │   ├── __init__.py
│   │   ├── authSchema.py          # Auth schemas
│   │   ├── chatSchema.py          # Chat schemas
│   │   ├── userSchema.py          # User schemas
│   │   └── ...
│   │
│   ├── crud/
│   │   ├── __init__.py
│   │   ├── userCrud.py            # User CRUD operations
│   │   ├── chatCrud.py            # Chat CRUD operations
│   │   └── ...
│   │
│   ├── services/
│   │   ├── __init__.py
│   │   ├── authService.py         # Authentication service
│   │   ├── userService.py         # User business logic
│   │   ├── chatService.py         # Chat business logic
│   │   ├── emailService.py        # Email service
│   │   └── ...
│   │
│   ├── utils/
│   │   ├── __init__.py
│   │   ├── dateUtils.py           # Date utilities
│   │   ├── validationUtils.py     # Validation utilities
│   │   ├── hashUtils.py           # Hashing utilities
│   │   └── ...
│   │
│   └── api/
│       ├── __init__.py
│       └── v1/
│           ├── __init__.py
│           ├── auth/
│           │   ├── __init__.py
│           │   ├── route.py       # Auth endpoints
│           │   └── service.py     # Auth service
│           ├── users/
│           │   ├── __init__.py
│           │   ├── route.py       # User endpoints
│           │   └── service.py     # User service
│           ├── chat/
│           │   ├── __init__.py
│           │   ├── route.py       # Chat endpoints
│           │   └── service.py     # Chat service
│           └── ...
│
├── testing/
│   ├── __init__.py
│   ├── conftest.py                # Pytest configuration
│   ├── fixtures.py                # Test fixtures
│   │
│   ├── api/
│   │   ├── __init__.py
│   │   └── v1/
│   │       ├── __init__.py
│   │       ├── auth/
│   │       │   ├── __init__.py
│   │       │   └── testAuthRoute.py
│   │       ├── users/
│   │       │   ├── __init__.py
│   │       │   └── testUserRoute.py
│   │       ├── chat/
│   │       │   ├── __init__.py
│   │       │   └── testChatRoute.py
│   │       └── ...
│   │
│   ├── unit/
│   │   ├── __init__.py
│   │   ├── services/
│   │   │   ├── testAuthService.py
│   │   │   ├── testUserService.py
│   │   │   └── ...
│   │   └── utils/
│   │       ├── testDateUtils.py
│   │       ├── testValidationUtils.py
│   │       └── ...
│   │
│   └── integration/
│       ├── __init__.py
│       ├── testDatabaseIntegration.py
│       ├── testRedisIntegration.py
│       └── ...
│
├── docs/
│   ├── api/
│   │   ├── auth.md
│   │   ├── users.md
│   │   ├── chat.md
│   │   └── ...
│   ├── modules/
│   │   ├── userModule.md
│   │   ├── chatModule.md
│   │   └── ...
│   ├── setup.md                   # Setup instructions
│   └── architecture.md            # Architecture overview
│
├── .agent/
│   └── fastapi/
│       ├── CodingStandard.md       # Coding conventions
│       ├── FolderStructure.md      # This file
│       ├── CommandStandard.md      # CLI standards
│       ├── TestingStandard.md      # Testing guidelines
│       ├── MigrationStandard.md    # Database migrations
│       ├── ModelStandard.md        # Model definitions
│       ├── ApiStandard.md          # API conventions
│       └── FileNaming.md           # File naming rules
│
├── main.py                         # Entry point
├── requirements.txt                # Python dependencies
├── .env                            # Environment variables (local)
├── .env.example                    # Example environment variables
├── .gitignore                      # Git ignore rules
├── AGENT.md                        # Agent setup guide
└── README.md                       # Project readme
```

## Directory Descriptions

### `/app`
Main application package containing all business logic and API endpoints.

### `/app/core`
Core functionality including:
- Configuration management
- Security and authentication
- Database setup
- Middleware
- Exception definitions

### `/app/models`
SQLAlchemy/ORM models representing database tables in `PascalCase` filenames with `camelCase` class names:
```
user.py → class User
chatMessage.py → class ChatMessage
session.py → class Session
```

### `/app/schemas`
Pydantic request/response schemas for validation:
```
userSchema.py → class UserCreate, class UserResponse
chatSchema.py → class ChatMessageCreate, class ChatMessageResponse
authSchema.py → class TokenResponse, class LoginRequest
```

### `/app/crud`
Database operations (Create, Read, Update, Delete) using `camelCase` filenames:
```
userCrud.py → functions: createUser(), getUser(), updateUser(), deleteUser()
chatCrud.py → functions: createMessage(), getMessage(), updateMessage()
```

### `/app/services`
Business logic layer with `camelCase` filenames:
```
userService.py → class UserService with business methods
authService.py → class AuthService with authentication logic
emailService.py → class EmailService for email operations
```

### `/app/utils`
Utility functions for common operations:
```
dateUtils.py → date formatting, parsing
validationUtils.py → validation helpers
hashUtils.py → password hashing, verification
```

### `/app/api/v1`
API endpoint versioning structure:
- Each module (users, chat, auth) has its own directory
- Contains `route.py` for endpoint definitions
- Contains `service.py` for module-specific business logic

### `/testing`
Complete test suite mirroring the app structure:
- `/testing/api` → API endpoint tests
- `/testing/unit` → Unit tests for services, utils
- `/testing/integration` → Integration tests

### `/docs`
Documentation organized by module:
- `/docs/api` → API endpoint documentation
- `/docs/modules` → Module-specific documentation

### `/.agent/fastapi`
Agent configuration and standards documentation for setup and development.

## File Naming Examples

| Purpose | Filename | Class/Function |
|---------|----------|-----------------|
| Model | `user.py` | `class User` |
| Service | `userService.py` | `class UserService` |
| Schema | `userSchema.py` | `class UserCreate, UserResponse` |
| CRUD | `userCrud.py` | `async def getUser()` |
| Utilities | `dateUtils.py` | `def formatDate()` |
| Routes | `route.py` | `@router.get()` |
| Tests | `testUserService.py` | `def test_getUserById()` |

## Module Structure Example (users)

```
app/api/v1/users/
├── __init__.py
├── route.py              # Endpoints: GET /users, POST /users, etc.
└── service.py            # UserService class with business logic

testing/api/v1/users/
├── __init__.py
└── testUserRoute.py      # Tests for user endpoints
```

## Best Practices

1. **Separation of Concerns**: Keep models, schemas, routes, and business logic separate
2. **Module Organization**: Each API feature has its own directory under `/api/v1/`
3. **Test Coverage**: Mirror the app structure in the testing directory
4. **Constants**: Keep all constants in one place for easy modification
5. **Documentation**: Document complex modules and API endpoints
6. **Consistency**: Follow the naming conventions strictly across all files

## Adding New Features

When adding a new feature (e.g., `payments`):

1. Create module directory: `/app/api/v1/payments/`
2. Add endpoint file: `app/api/v1/payments/route.py`
3. Add service file: `app/api/v1/payments/service.py`
4. Create model: `app/models/payment.py`
5. Create schema: `app/schemas/paymentSchema.py`
6. Create CRUD operations: `app/crud/paymentCrud.py`
7. Create tests: `testing/api/v1/payments/testPaymentRoute.py`
8. Document: `docs/api/payments.md`
