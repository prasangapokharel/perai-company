# FastAPI Coding Standards

## Overview

This document defines the coding conventions for FastAPI projects. All code must follow these standards to maintain consistency, readability, and maintainability across the codebase.

## Naming Conventions

### File Naming

- **Format**: `camelCase`
- **Pattern**: `fileName.py`

**Examples:**
```
userService.py
chatMessage.py
authSchema.py
emailValidator.py
dateUtils.py
```

### Function and Variable Naming

- **Format**: `camelCase`
- **Pattern**: `functionName()` and `variableName`

**Examples:**
```python
def getUserById(userId: int):
    pass

def createNewUser(userData: dict):
    pass

currentUserSession = None
isAuthenticated = False
```

### Class Naming

- **Format**: `PascalCase`
- **Pattern**: `ClassName`

**Examples:**
```python
class UserService:
    pass

class ChatMessage:
    pass

class AuthenticationError:
    pass
```

### Constants

- **Format**: `UPPER_SNAKE_CASE`
- **Pattern**: `CONSTANT_NAME`

**Examples:**
```python
MAX_RETRY_ATTEMPTS = 3
DEFAULT_PAGE_SIZE = 20
DATABASE_URL = "postgresql://localhost/myapp"
API_TIMEOUT_SECONDS = 30
ALLOWED_FILE_EXTENSIONS = ['.jpg', '.png', '.pdf']
```

### Module Constants

Place constants at the top of the file after imports:

```python
# File: app/core/config/config.py
from typing import Optional

# Constants
DATABASE_HOST = "localhost"
DATABASE_PORT = 5432
DEFAULT_ENCODING = "utf-8"

# Configuration
class Settings:
    pass
```

## Code Organization

### Import Order

1. Standard library imports
2. Third-party imports
3. Local application imports

```python
# Standard library
import os
from datetime import datetime
from typing import Optional, List

# Third-party
from fastapi import FastAPI, HTTPException
from sqlalchemy import Column, String, Integer
import jwt

# Local
from app.core.config import settings
from app.services.userService import getUserById
from app.models.user import User
```

### Function Organization

```python
# Public functions first
def publicFunction():
    pass

# Private functions (prefix with _)
def _privateHelper():
    pass

# Internal utilities (prefix with __)
def __internalUtility():
    pass
```

## Type Hints

Always use type hints for function parameters and return types:

```python
from typing import Optional, List, Dict

def getUserData(userId: int) -> Optional[Dict]:
    """Retrieve user data by ID."""
    pass

def createBatch(items: List[str]) -> Dict[str, int]:
    """Create a batch of items."""
    pass

async def processRequest(data: str, timeout: int = 30) -> bool:
    """Process request asynchronously."""
    pass
```

## Docstrings

Use triple-quoted docstrings following Google style:

```python
def calculateTotal(items: List[float], taxRate: float) -> float:
    """Calculate total with tax.
    
    Args:
        items: List of item prices
        taxRate: Tax rate as decimal (0.1 = 10%)
    
    Returns:
        Total amount including tax
    
    Raises:
        ValueError: If taxRate is negative
    """
    if taxRate < 0:
        raise ValueError("Tax rate cannot be negative")
    
    subtotal = sum(items)
    return subtotal * (1 + taxRate)
```

## Code Style

### Line Length

- **Maximum**: 100 characters
- Use line breaks for long expressions

```python
# Good
result = getUserData(userId) \
    .filter(isActive=True) \
    .order_by('createdAt')

# Good
user = await db.query(User) \
    .filter(User.email == email) \
    .first()
```

### Indentation

- Use 4 spaces (not tabs)
- Align continuation lines properly

```python
# Good
def function(param1: str, param2: int,
             param3: str) -> bool:
    pass

# Good
data = {
    'name': 'John',
    'email': 'john@example.com',
    'active': True
}
```

### Blank Lines

- 2 blank lines between top-level definitions
- 1 blank line between method definitions
- 1 blank line between logical sections within functions

```python
class UserService:

    def __init__(self):
        self.cache = {}

    def getUser(self, userId: int):
        # Retrieve user
        user = self._fetchUser(userId)

        # Process user data
        processedUser = self._processData(user)
        return processedUser

    def _fetchUser(self, userId: int):
        pass
```

## Error Handling

### Custom Exceptions

Use descriptive exception names in `PascalCase`:

```python
class UserNotFoundError(Exception):
    """Raised when user is not found"""
    pass

class InvalidTokenError(Exception):
    """Raised when JWT token is invalid"""
    pass

class DatabaseConnectionError(Exception):
    """Raised when database connection fails"""
    pass
```

### Exception Handling

```python
try:
    user = getUserById(userId)
except UserNotFoundError:
    logger.warning(f"User not found: {userId}")
    raise HTTPException(status_code=404, detail="User not found")
except Exception as e:
    logger.error(f"Unexpected error: {str(e)}")
    raise HTTPException(status_code=500, detail="Internal server error")
```

## Async/Await

Use async patterns for I/O operations:

```python
async def fetchUserData(userId: int) -> Dict:
    """Fetch user data asynchronously."""
    try:
        user = await db.query(User).filter(User.id == userId).first()
        return user
    except Exception as e:
        logger.error(f"Error fetching user: {e}")
        raise

# In endpoint
@app.get("/users/{userId}")
async def getUserEndpoint(userId: int):
    userData = await fetchUserData(userId)
    return userData
```

## Comments

Use comments sparingly - code should be self-documenting:

```python
# Good - clear function name and types
def isEmailValid(email: str) -> bool:
    return "@" in email and "." in email

# Avoid - unnecessary comment
def isEmailValid(email: str) -> bool:
    # Check if email contains @ and .
    return "@" in email and "." in email
```

Use comments for complex logic:

```python
# Complex algorithm needs explanation
def calculateOptimalBatch(items: List[Dict]) -> List[List[Dict]]:
    """Group items using first-fit decreasing algorithm."""
    # Sort items by size descending for better packing
    sorted_items = sorted(items, key=lambda x: x['size'], reverse=True)
    
    # Initialize bins
    bins = []
    return bins
```

## Logging

Use consistent logging with module names:

```python
import logging

logger = logging.getLogger(__name__)

def processUser(userId: int):
    logger.info(f"Processing user: {userId}")
    
    try:
        user = getUserData(userId)
        logger.debug(f"User data retrieved: {user}")
    except UserNotFoundError:
        logger.warning(f"User not found: {userId}")
    except Exception as e:
        logger.error(f"Error processing user: {e}", exc_info=True)
```

## Code Quality Checklist

- [ ] All functions have type hints
- [ ] All functions have docstrings
- [ ] Constants are in UPPER_SNAKE_CASE
- [ ] Functions and variables are camelCase
- [ ] Classes are PascalCase
- [ ] Filenames are camelCase
- [ ] No lines exceed 100 characters
- [ ] Imports are organized
- [ ] Error handling is present
- [ ] Logging is used appropriately
- [ ] Tests exist for all public functions
