# FastAPI API Standards

## Overview

This document defines the standards for creating API endpoints in FastAPI applications.

## Endpoint Structure

```
app/api/v1/<module>/
├── __init__.py
├── route.py          # Endpoint definitions
└── service.py        # Business logic
```

## Naming Conventions

### File Naming

- **Format**: `camelCase.py`

**Examples:**
```
route.py             # Endpoint definitions
service.py           # Service business logic
```

### Function Naming

- **Format**: `camelCase`
- **Pattern**: `<action><resource>`

**Examples:**
```
getUser()
getUserList()
createUser()
updateUser()
deleteUser()
```

## Endpoint Patterns

### Base URL Structure

```
/api/v1/<resource>
/api/v1/<resource>/{id}
/api/v1/<resource>/{id}/<sub-resource>
```

### HTTP Methods

| Method | Purpose | Status Code |
|--------|---------|-------------|
| GET | Retrieve resource(s) | 200 |
| POST | Create resource | 201 |
| PUT | Replace entire resource | 200 |
| PATCH | Partial update | 200 |
| DELETE | Delete resource | 204 |

## Route Implementation

### Example: User Routes

```python
# app/api/v1/users/route.py
from fastapi import APIRouter, HTTPException, status, Query, Depends
from typing import List, Optional
from sqlalchemy.orm import Session
from app.core.database import getDb
from app.schemas.userSchema import (
    UserCreate,
    UserUpdate,
    UserResponse,
    UserList
)
from app.services.userService import UserService

router = APIRouter(
    prefix="/api/v1/users",
    tags=["users"],
    responses={404: {"description": "Not found"}}
)

# Dependency injection
def getUserService(db: Session = Depends(getDb)) -> UserService:
    """Get user service instance."""
    return UserService(db=db)


@router.get(
    "",
    response_model=UserList,
    status_code=status.HTTP_200_OK
)
async def getUserList(
    skip: int = Query(0, ge=0),
    limit: int = Query(10, ge=1, le=100),
    isActive: Optional[bool] = None,
    userService: UserService = Depends(getUserService)
) -> UserList:
    """Get list of users with pagination.
    
    Query Parameters:
        skip: Number of users to skip (default: 0)
        limit: Maximum users to return (default: 10, max: 100)
        isActive: Filter by active status (optional)
    
    Returns:
        List of users with pagination info
    
    Responses:
        200: Successful retrieval
        422: Invalid query parameters
    """
    users = userService.getUserList(
        skip=skip,
        limit=limit,
        isActive=isActive
    )
    total = userService.count(isActive=isActive)
    
    return UserList(
        items=users,
        total=total,
        skip=skip,
        limit=limit
    )


@router.get(
    "/{userId}",
    response_model=UserResponse,
    status_code=status.HTTP_200_OK
)
async def getUser(
    userId: int,
    userService: UserService = Depends(getUserService)
) -> UserResponse:
    """Get user by ID.
    
    Path Parameters:
        userId: User ID
    
    Returns:
        User details
    
    Responses:
        200: User found
        404: User not found
    """
    user = userService.getUserById(userId)
    
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    return user


@router.post(
    "",
    response_model=UserResponse,
    status_code=status.HTTP_201_CREATED
)
async def createUser(
    userData: UserCreate,
    userService: UserService = Depends(getUserService)
) -> UserResponse:
    """Create new user.
    
    Request Body:
        UserCreate schema with name, email, password
    
    Returns:
        Created user
    
    Responses:
        201: User created successfully
        400: Email already exists or invalid data
        422: Validation error
    """
    # Check if email exists
    existingUser = userService.getUserByEmail(userData.email)
    if existingUser:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Email already registered"
        )
    
    try:
        user = userService.createUser(userData)
        return user
    except ValueError as e:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=str(e)
        )


@router.patch(
    "/{userId}",
    response_model=UserResponse,
    status_code=status.HTTP_200_OK
)
async def updateUser(
    userId: int,
    userData: UserUpdate,
    userService: UserService = Depends(getUserService)
) -> UserResponse:
    """Update user.
    
    Path Parameters:
        userId: User ID
    
    Request Body:
        UserUpdate schema (partial update)
    
    Returns:
        Updated user
    
    Responses:
        200: User updated successfully
        404: User not found
        400: Invalid data
    """
    user = userService.getUserById(userId)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    updated_user = userService.updateUser(userId, userData)
    return updated_user


@router.delete(
    "/{userId}",
    status_code=status.HTTP_204_NO_CONTENT
)
async def deleteUser(
    userId: int,
    userService: UserService = Depends(getUserService)
) -> None:
    """Delete user.
    
    Path Parameters:
        userId: User ID
    
    Responses:
        204: User deleted successfully
        404: User not found
    """
    user = userService.getUserById(userId)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="User not found"
        )
    
    userService.deleteUser(userId)
```

## Service Implementation

### app/api/v1/users/service.py

```python
"""User service business logic."""
from typing import Optional, List
from sqlalchemy.orm import Session
from app.schemas.userSchema import UserCreate, UserUpdate, UserResponse
from app.models.user import User
from app.crud.userCrud import (
    createUserCrud,
    getUserById,
    getUserByEmail,
    updateUserCrud,
    deleteUserCrud,
    getUserList
)
from app.utils.hashUtils import hashPassword


class UserService:
    """Service for user-related business logic."""
    
    def __init__(self, db: Session):
        """Initialize service with database session.
        
        Args:
            db: SQLAlchemy database session
        """
        self.db = db
    
    def getUserById(self, userId: int) -> Optional[UserResponse]:
        """Get user by ID.
        
        Args:
            userId: User ID
        
        Returns:
            User if found, None otherwise
        """
        user = getUserById(self.db, userId)
        return UserResponse.fromORM(user) if user else None
    
    def getUserByEmail(self, email: str) -> Optional[User]:
        """Get user by email.
        
        Args:
            email: User email
        
        Returns:
            User if found, None otherwise
        """
        return getUserByEmail(self.db, email)
    
    def getUserList(
        self,
        skip: int = 0,
        limit: int = 10,
        isActive: Optional[bool] = None
    ) -> List[UserResponse]:
        """Get list of users.
        
        Args:
            skip: Number of records to skip
            limit: Maximum records to return
            isActive: Filter by active status
        
        Returns:
            List of users
        """
        users = getUserList(
            self.db,
            skip=skip,
            limit=limit,
            isActive=isActive
        )
        return [UserResponse.fromORM(user) for user in users]
    
    def count(self, isActive: Optional[bool] = None) -> int:
        """Count users.
        
        Args:
            isActive: Filter by active status
        
        Returns:
            Total count
        """
        query = self.db.query(User)
        if isActive is not None:
            query = query.filter(User.isActive == isActive)
        return query.count()
    
    def createUser(self, userData: UserCreate) -> UserResponse:
        """Create new user.
        
        Args:
            userData: User creation data
        
        Returns:
            Created user
        
        Raises:
            ValueError: If email already exists
        """
        # Check email existence
        existingUser = getUserByEmail(self.db, userData.email)
        if existingUser:
            raise ValueError("Email already registered")
        
        # Hash password
        hashedPassword = hashPassword(userData.password)
        
        # Create user
        user = createUserCrud(
            self.db,
            name=userData.name,
            email=userData.email,
            hashedPassword=hashedPassword
        )
        
        return UserResponse.fromORM(user)
    
    def updateUser(
        self,
        userId: int,
        userData: UserUpdate
    ) -> UserResponse:
        """Update user.
        
        Args:
            userId: User ID
            userData: Update data
        
        Returns:
            Updated user
        
        Raises:
            ValueError: If user not found
        """
        user = getUserById(self.db, userId)
        if not user:
            raise ValueError("User not found")
        
        updateData = userData.dict(exclude_unset=True)
        
        # Hash new password if provided
        if "password" in updateData:
            updateData["hashedPassword"] = hashPassword(updateData["password"])
            del updateData["password"]
        
        updated_user = updateUserCrud(self.db, userId, **updateData)
        return UserResponse.fromORM(updated_user)
    
    def deleteUser(self, userId: int) -> None:
        """Delete user.
        
        Args:
            userId: User ID
        
        Raises:
            ValueError: If user not found
        """
        user = getUserById(self.db, userId)
        if not user:
            raise ValueError("User not found")
        
        deleteUserCrud(self.db, userId)
```

## Response Models

### Pydantic Schemas

```python
# app/schemas/userSchema.py
from pydantic import BaseModel, EmailStr, Field
from typing import List, Optional
from datetime import datetime


class UserBase(BaseModel):
    """Base user schema."""
    name: str = Field(..., min_length=1, max_length=100)
    email: EmailStr


class UserCreate(UserBase):
    """Schema for user creation."""
    password: str = Field(..., min_length=8, max_length=100)


class UserUpdate(BaseModel):
    """Schema for user updates (all fields optional)."""
    name: Optional[str] = Field(None, min_length=1, max_length=100)
    email: Optional[EmailStr] = None
    password: Optional[str] = Field(None, min_length=8)


class UserResponse(UserBase):
    """Schema for user response."""
    id: int
    isActive: bool
    isVerified: bool
    createdAt: datetime
    updatedAt: datetime
    
    class Config:
        from_attributes = True  # For ORM model conversion


class UserList(BaseModel):
    """Schema for user list response."""
    items: List[UserResponse]
    total: int
    skip: int
    limit: int
```

## Error Handling

### Consistent Error Responses

```python
from fastapi import HTTPException, status


# 400 Bad Request
raise HTTPException(
    status_code=status.HTTP_400_BAD_REQUEST,
    detail="Invalid input data"
)

# 401 Unauthorized
raise HTTPException(
    status_code=status.HTTP_401_UNAUTHORIZED,
    detail="Not authenticated",
    headers={"WWW-Authenticate": "Bearer"}
)

# 403 Forbidden
raise HTTPException(
    status_code=status.HTTP_403_FORBIDDEN,
    detail="Not authorized to perform this action"
)

# 404 Not Found
raise HTTPException(
    status_code=status.HTTP_404_NOT_FOUND,
    detail="Resource not found"
)

# 409 Conflict
raise HTTPException(
    status_code=status.HTTP_409_CONFLICT,
    detail="Resource already exists"
)

# 422 Unprocessable Entity
raise HTTPException(
    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
    detail="Validation error"
)

# 500 Internal Server Error
raise HTTPException(
    status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
    detail="Internal server error"
)
```

## Pagination

### Standard Pagination Parameters

```python
@router.get("/items")
async def getItems(
    skip: int = Query(0, ge=0, description="Skip this many items"),
    limit: int = Query(10, ge=1, le=100, description="Max items to return"),
    serviceInstance = Depends(getService)
) -> ItemList:
    """Get paginated items."""
    items = serviceInstance.getItems(skip=skip, limit=limit)
    total = serviceInstance.count()
    
    return ItemList(
        items=items,
        total=total,
        skip=skip,
        limit=limit,
        pages=(total + limit - 1) // limit
    )
```

## Filtering & Search

### Query Parameter Filtering

```python
@router.get("/users")
async def searchUsers(
    search: Optional[str] = Query(None, description="Search by name or email"),
    isActive: Optional[bool] = Query(None, description="Filter by status"),
    role: Optional[str] = Query(None, description="Filter by role"),
    serviceInstance = Depends(getService)
) -> List[UserResponse]:
    """Search and filter users."""
    return serviceInstance.search(
        search=search,
        isActive=isActive,
        role=role
    )
```

## Documentation & Tags

### Endpoint Documentation

```python
router = APIRouter(
    prefix="/api/v1/users",
    tags=["users"],
    responses={
        404: {"description": "Item not found"},
        400: {"description": "Bad request"}
    }
)

@router.get(
    "/{userId}",
    summary="Get user by ID",
    description="Retrieve a specific user by their ID. Requires authentication."
)
async def getUser(userId: int) -> UserResponse:
    """Get specific user."""
    pass
```

## Router Registration

### app/main.py

```python
from fastapi import FastAPI
from app.api.v1.users.route import router as usersRouter
from app.api.v1.chat.route import router as chatRouter
from app.api.v1.auth.route import router as authRouter

app = FastAPI(
    title="FastAPI Application",
    description="Comprehensive FastAPI application",
    version="1.0.0"
)

# Register routers
app.include_router(authRouter)
app.include_router(usersRouter)
app.include_router(chatRouter)


@app.get("/health", tags=["health"])
async def healthCheck():
    """Health check endpoint."""
    return {"status": "healthy"}
```

## Status Codes

### Standard HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 200 | OK | GET, PUT, PATCH success |
| 201 | Created | POST success |
| 204 | No Content | DELETE success |
| 400 | Bad Request | Invalid input |
| 401 | Unauthorized | Not authenticated |
| 403 | Forbidden | No permission |
| 404 | Not Found | Resource not found |
| 409 | Conflict | Resource exists |
| 422 | Validation Error | Invalid data format |
| 500 | Server Error | Unexpected error |

## Best Practices

1. **Consistent Naming**: Use resource names as plural nouns
2. **Status Codes**: Use appropriate HTTP status codes
3. **Versioning**: Use `/api/v1/` for version control
4. **Documentation**: Include docstrings and descriptions
5. **Error Handling**: Return meaningful error messages
6. **Validation**: Use Pydantic for request validation
7. **Authentication**: Require auth for protected endpoints
8. **Pagination**: Implement for list endpoints
9. **Rate Limiting**: Add rate limiting for public APIs
10. **CORS**: Configure CORS appropriately
