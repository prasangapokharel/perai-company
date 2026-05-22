# FastAPI File Naming Convention

## Overview

This document defines the file naming standards for FastAPI projects. All files must follow these conventions for consistency and clarity.

## Naming Format

- **Pattern**: `camelCase`
- **Separation**: Use lowercase first letter, capitalize subsequent words
- **Underscore**: Never use underscores in filenames (except in test files)
- **Extension**: Always `.py` for Python files

## File Types & Examples

### Model Files

**Format**: `<modelName>.py`

```
user.py
chatMessage.py
userProfile.py
paymentTransaction.py
sessionToken.py
```

**Content**: Single model class per file

```python
# user.py
class User(Base):
    __tablename__ = "user"
```

### Service Files

**Format**: `<moduleName>Service.py`

```
userService.py
chatService.py
authService.py
emailService.py
paymentService.py
```

**Content**: Service class with business logic

```python
# userService.py
class UserService:
    def createUser(self, userData: UserCreate):
        pass
```

### CRUD Files

**Format**: `<moduleName>Crud.py`

```
userCrud.py
chatCrud.py
sessionCrud.py
```

**Content**: Database operations

```python
# userCrud.py
async def createUser(db: Session, userData: dict):
    pass
```

### Schema Files

**Format**: `<moduleName>Schema.py`

```
userSchema.py
chatSchema.py
authSchema.py
```

**Content**: Pydantic models

```python
# userSchema.py
class UserCreate(BaseModel):
    pass

class UserResponse(BaseModel):
    pass
```

### Route Files

**Format**: `route.py`

```
app/api/v1/users/route.py
app/api/v1/chat/route.py
app/api/v1/auth/route.py
```

**Content**: API endpoint definitions

```python
# app/api/v1/users/route.py
@router.get("/users")
async def getUsers():
    pass
```

### Utility Files

**Format**: `<utilityName>Utils.py`

```
dateUtils.py
validationUtils.py
hashUtils.py
stringUtils.py
fileUtils.py
```

**Content**: Helper functions

```python
# dateUtils.py
def formatDate(date: datetime) -> str:
    pass

def parseDate(dateStr: str) -> datetime:
    pass
```

### Configuration Files

**Format**: `config.py`

```
app/core/config/config.py
```

**Content**: Application configuration

```python
# config.py
class Settings:
    DATABASE_URL = "..."
```

### Test Files

**Format**: `test<ClassName>.py`

```
testUserRoute.py
testUserService.py
testDateUtils.py
testAuthService.py
```

**Note**: Test files are the only exception to the camelCase rule. They use the pattern `test<Name>` with `test` prefix in lowercase.

**Content**: Test cases

```python
# testUserService.py
def test_getUserById_returnsUser():
    pass
```

## Directory Structure Example

```
app/
├── models/
│   ├── user.py
│   ├── chatMessage.py
│   ├── session.py
│   └── ...
├── schemas/
│   ├── userSchema.py
│   ├── chatSchema.py
│   ├── authSchema.py
│   └── ...
├── services/
│   ├── userService.py
│   ├── chatService.py
│   ├── authService.py
│   ├── emailService.py
│   └── ...
├── crud/
│   ├── userCrud.py
│   ├── chatCrud.py
│   └── ...
├── utils/
│   ├── dateUtils.py
│   ├── validationUtils.py
│   ├── hashUtils.py
│   └── ...
├── api/
│   └── v1/
│       ├── users/
│       │   ├── route.py
│       │   ├── service.py
│       │   └── __init__.py
│       ├── chat/
│       │   ├── route.py
│       │   ├── service.py
│       │   └── __init__.py
│       └── ...
├── core/
│   ├── config/
│   │   ├── config.py
│   │   └── __init__.py
│   ├── security.py
│   ├── database.py
│   └── ...
└── constants.py

testing/
├── api/
│   └── v1/
│       ├── users/
│       │   ├── testUserRoute.py
│       │   └── __init__.py
│       └── ...
├── unit/
│   ├── services/
│   │   ├── testUserService.py
│   │   └── ...
│   └── utils/
│       ├── testDateUtils.py
│       └── ...
└── integration/
    ├── testDatabaseIntegration.py
    └── ...
```

## Naming Conventions by Category

| Category | Format | Examples |
|----------|--------|----------|
| Models | `<name>.py` | `user.py`, `chatMessage.py` |
| Services | `<name>Service.py` | `userService.py`, `authService.py` |
| Schemas | `<name>Schema.py` | `userSchema.py`, `chatSchema.py` |
| CRUD | `<name>Crud.py` | `userCrud.py`, `sessionCrud.py` |
| Utils | `<name>Utils.py` | `dateUtils.py`, `hashUtils.py` |
| Routes | `route.py` | Fixed name for all route files |
| Tests | `test<Name>.py` | `testUserService.py`, `testAuthRoute.py` |
| Config | `config.py` | Fixed name for configuration |
| Constants | `constants.py` | Fixed name for constants |

## Rules Summary

### ✅ DO

- Use `camelCase` for filenames
- Use descriptive names
- Keep filenames short but meaningful
- Use consistent suffixes (Service, Crud, Schema, Utils)
- Use `test` prefix for test files

### ❌ DON'T

- Use underscores in filenames (except tests)
- Use hyphens in filenames
- Use multiple words without camelCase
- Use abbreviations
- Use UPPER_CASE for filenames
- Mix naming conventions in the same directory

## Examples of Good vs Bad

| Good | Bad |
|------|-----|
| `userService.py` | `user_service.py`, `User_Service.py`, `UserService.py` |
| `dateUtils.py` | `date-utils.py`, `date_utils.py`, `DateUtils.py` |
| `chatMessage.py` | `chat_message.py`, `ChatMessage.py`, `chat-message.py` |
| `testUserRoute.py` | `test_user_route.py`, `UserRouteTest.py`, `user_route_test.py` |
| `route.py` | `routes.py`, `routeHandler.py`, `apiRoute.py` |

## Special Cases

### __ init__.py Files

All package directories must have `__init__.py`:

```
app/
├── __init__.py
├── models/
│   └── __init__.py
├── services/
│   └── __init__.py
└── api/
    └── __init__.py
```

### Configuration Files

```
.env                 # Environment variables
.env.example         # Example env template
requirements.txt     # Python dependencies
.gitignore           # Git ignore rules
main.py             # Application entry point
```

### Documentation

```
README.md            # Project readme
AGENT.md             # Agent setup guide
docs/
├── api/             # API documentation
├── modules/         # Module documentation
└── setup.md         # Setup instructions
```

## Import Statements

Given the file naming convention, imports should follow this pattern:

```python
# ✅ Correct
from app.models.user import User
from app.services.userService import UserService
from app.schemas.userSchema import UserCreate
from app.utils.dateUtils import formatDate

# ❌ Incorrect
from app.models.User import User
from app.services.user_service import UserService
from app.schemas.user_schema import UserCreate
```

## Consistency Across Codebase

When adding new files, always:

1. Check existing files in the same directory
2. Follow the established naming pattern
3. Use consistent file naming with similar modules
4. Update this document if introducing new patterns
5. Use lowercase for test file prefix `test`, not `Test`

## Migration to Proper Naming

If inheriting code with improper naming:

1. Rename files to follow camelCase
2. Update all import statements
3. Update relative imports in `__init__.py` files
4. Run tests to ensure nothing breaks
5. Commit with clear message about refactoring

```bash
# Example rename
mv user_service.py userService.py

# Update imports in affected files
# Then test thoroughly
pytest testing/
```
