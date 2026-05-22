# FastAPI Model Standards

## Overview

This document defines the standards for defining database models in FastAPI applications using SQLAlchemy.

## File Structure

```
app/models/
├── __init__.py
├── user.py
├── chatMessage.py
├── session.py
└── ...
```

## File Naming

- **Format**: `camelCase.py`
- **Content**: Single model per file

**Examples:**
```
user.py         → class User
chatMessage.py  → class ChatMessage
session.py      → class Session
```

## Model Definition Standards

### Basic Model Structure

```python
# app/models/user.py
from datetime import datetime
from sqlalchemy import Column, Integer, String, Boolean, DateTime
from sqlalchemy.orm import relationship
from app.core.database import Base

# Constants for this model
MAX_NAME_LENGTH = 100
MAX_EMAIL_LENGTH = 255


class User(Base):
    """User database model
    
    Represents a user in the system with authentication
    and profile information.
    """
    __tablename__ = "user"

    # Primary Key
    id = Column(Integer, primary_key=True, index=True)

    # Basic Fields
    name = Column(String(MAX_NAME_LENGTH), nullable=False)
    email = Column(String(MAX_EMAIL_LENGTH), nullable=False, unique=True, index=True)
    
    # Security
    hashedPassword = Column(String(255), nullable=False)
    
    # Status
    isActive = Column(Boolean, default=True)
    isVerified = Column(Boolean, default=False)
    
    # Timestamps
    createdAt = Column(DateTime, default=datetime.utcnow, nullable=False)
    updatedAt = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)
    
    # Relationships
    chatMessages = relationship("ChatMessage", back_populates="user")
    sessions = relationship("Session", back_populates="user")

    def __repr__(self) -> str:
        return f"<User id={self.id} email={self.email}>"
```

## Column Naming Conventions

### Field Names

- **Format**: `camelCase`
- **Pattern**: `fieldName`

**Examples:**
```python
firstName = Column(String(50))
lastName = Column(String(50))
emailAddress = Column(String(255))
phoneNumber = Column(String(20))
createdAt = Column(DateTime)
updatedAt = Column(DateTime)
isActive = Column(Boolean)
```

### Boolean Fields

Use `is` prefix for boolean columns:

```python
class User(Base):
    __tablename__ = "user"
    
    isActive = Column(Boolean, default=True)
    isVerified = Column(Boolean, default=False)
    isAdmin = Column(Boolean, default=False)
    isDeleted = Column(Boolean, default=False)
```

### Timestamp Fields

Always include timestamps:

```python
class User(Base):
    __tablename__ = "user"
    
    createdAt = Column(
        DateTime,
        default=datetime.utcnow,
        nullable=False
    )
    updatedAt = Column(
        DateTime,
        default=datetime.utcnow,
        onupdate=datetime.utcnow
    )
    deletedAt = Column(DateTime, nullable=True)  # Soft delete
```

## Column Types & Constraints

### Basic Types

```python
from sqlalchemy import (
    Column, Integer, String, Text, Float,
    Boolean, DateTime, Date, Time, JSON,
    Enum, LargeBinary
)

class ExampleModel(Base):
    __tablename__ = "example"
    
    # Numeric
    count = Column(Integer, default=0)
    rating = Column(Float, default=0.0)
    amount = Column(Float, precision=10, scale=2)
    
    # String
    name = Column(String(100), nullable=False)
    description = Column(Text)
    
    # Boolean
    isActive = Column(Boolean, default=True)
    
    # Date/Time
    birthDate = Column(Date)
    registeredAt = Column(DateTime)
    
    # JSON
    metadata = Column(JSON, default={})
    
    # Binary
    profileImage = Column(LargeBinary)
```

### Constraints

```python
from sqlalchemy import UniqueConstraint, CheckConstraint, Index

class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    
    # Column constraints
    email = Column(String(255), nullable=False, unique=True, index=True)
    age = Column(Integer, nullable=False)
    
    # Table constraints
    __table_args__ = (
        UniqueConstraint('email', name='uq_user_email'),
        CheckConstraint('age >= 18', name='ck_user_age'),
        Index('ix_user_email_active', 'email', 'isActive'),
    )
```

## Relationships

### One-to-Many

```python
# Parent: User
class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    name = Column(String(100))
    
    # Relationship to multiple posts
    chatMessages = relationship(
        "ChatMessage",
        back_populates="user",
        cascade="all, delete-orphan"
    )


# Child: ChatMessage
class ChatMessage(Base):
    __tablename__ = "chat_message"
    id = Column(Integer, primary_key=True)
    content = Column(Text)
    
    # Foreign key
    userId = Column(Integer, ForeignKey("user.id"), nullable=False)
    
    # Relationship back to parent
    user = relationship("User", back_populates="chatMessages")
```

### One-to-One

```python
class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    
    # One-to-one relationship
    profile = relationship(
        "UserProfile",
        back_populates="user",
        uselist=False
    )


class UserProfile(Base):
    __tablename__ = "user_profile"
    id = Column(Integer, primary_key=True)
    
    userId = Column(Integer, ForeignKey("user.id"), nullable=False)
    user = relationship("User", back_populates="profile")
```

### Many-to-Many

```python
from sqlalchemy import Table

# Association table
userRole = Table(
    'user_role',
    Base.metadata,
    Column('userId', Integer, ForeignKey('user.id')),
    Column('roleId', Integer, ForeignKey('role.id'))
)


class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    
    roles = relationship(
        "Role",
        secondary=userRole,
        back_populates="users"
    )


class Role(Base):
    __tablename__ = "role"
    id = Column(Integer, primary_key=True)
    
    users = relationship(
        "User",
        secondary=userRole,
        back_populates="roles"
    )
```

## Model Examples

### Complete User Model

```python
# app/models/user.py
from datetime import datetime
from sqlalchemy import Column, Integer, String, Boolean, DateTime, CheckConstraint
from sqlalchemy.orm import relationship
from app.core.database import Base

MAX_NAME_LENGTH = 100
MAX_EMAIL_LENGTH = 255


class User(Base):
    """User model representing system users.
    
    Attributes:
        id: Primary key
        name: User's full name
        email: Unique email address
        hashedPassword: Hashed password for authentication
        isActive: Whether user account is active
        isVerified: Whether email is verified
        createdAt: Account creation timestamp
        updatedAt: Last update timestamp
    """
    __tablename__ = "user"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(MAX_NAME_LENGTH), nullable=False)
    email = Column(
        String(MAX_EMAIL_LENGTH),
        nullable=False,
        unique=True,
        index=True
    )
    hashedPassword = Column(String(255), nullable=False)
    isActive = Column(Boolean, default=True)
    isVerified = Column(Boolean, default=False)
    
    createdAt = Column(
        DateTime,
        default=datetime.utcnow,
        nullable=False
    )
    updatedAt = Column(
        DateTime,
        default=datetime.utcnow,
        onupdate=datetime.utcnow
    )

    # Relationships
    chatMessages = relationship(
        "ChatMessage",
        back_populates="user",
        cascade="all, delete-orphan"
    )

    def __repr__(self) -> str:
        return f"<User(id={self.id}, email={self.email})>"
```

### Chat Message Model

```python
# app/models/chatMessage.py
from datetime import datetime
from sqlalchemy import Column, Integer, String, Text, DateTime, ForeignKey
from sqlalchemy.orm import relationship
from app.core.database import Base


class ChatMessage(Base):
    """Chat message model for storing conversation messages.
    
    Attributes:
        id: Primary key
        userId: Foreign key to user
        content: Message content
        createdAt: Message creation timestamp
        updatedAt: Last update timestamp
    """
    __tablename__ = "chat_message"

    id = Column(Integer, primary_key=True, index=True)
    userId = Column(Integer, ForeignKey("user.id"), nullable=False)
    content = Column(Text, nullable=False)
    
    createdAt = Column(
        DateTime,
        default=datetime.utcnow,
        nullable=False
    )
    updatedAt = Column(
        DateTime,
        default=datetime.utcnow,
        onupdate=datetime.utcnow
    )

    # Relationships
    user = relationship("User", back_populates="chatMessages")

    def __repr__(self) -> str:
        return f"<ChatMessage(id={self.id}, userId={self.userId})>"
```

## Model Best Practices

### 1. Constants for String Lengths

```python
# At top of file
MAX_NAME_LENGTH = 100
MAX_EMAIL_LENGTH = 255
MAX_DESCRIPTION_LENGTH = 1000

class User(Base):
    __tablename__ = "user"
    name = Column(String(MAX_NAME_LENGTH))
    email = Column(String(MAX_EMAIL_LENGTH))
```

### 2. Proper Indexing

```python
class User(Base):
    __tablename__ = "user"
    
    # Index on primary key (automatic)
    id = Column(Integer, primary_key=True)
    
    # Index on frequently searched fields
    email = Column(String(255), unique=True, index=True)
    
    # Composite index for complex queries
    __table_args__ = (
        Index('ix_user_email_active', 'email', 'isActive'),
    )
```

### 3. Soft Deletes

```python
class User(Base):
    __tablename__ = "user"
    
    # Instead of deleting, mark as deleted
    deletedAt = Column(DateTime, nullable=True)
    
    @property
    def isDeleted(self) -> bool:
        return self.deletedAt is not None
```

### 4. Always Include Timestamps

```python
class AnyModel(Base):
    __tablename__ = "any_table"
    
    createdAt = Column(
        DateTime,
        default=datetime.utcnow,
        nullable=False
    )
    updatedAt = Column(
        DateTime,
        default=datetime.utcnow,
        onupdate=datetime.utcnow
    )
```

### 5. Cascade Delete for Relationships

```python
class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    
    # Cascade delete: delete messages when user is deleted
    chatMessages = relationship(
        "ChatMessage",
        back_populates="user",
        cascade="all, delete-orphan"
    )
```

### 6. Repr for Debugging

```python
def __repr__(self) -> str:
    return f"<User(id={self.id}, email={self.email}, isActive={self.isActive})>"
```

## Model Initialization

### app/models/__init__.py

```python
"""Database models package."""
from app.models.user import User
from app.models.chatMessage import ChatMessage
from app.models.session import Session

__all__ = [
    "User",
    "ChatMessage",
    "Session",
]
```

## Validation in Models

```python
from sqlalchemy import event
from sqlalchemy.orm import validates


class User(Base):
    __tablename__ = "user"
    
    email = Column(String(255), nullable=False)
    age = Column(Integer)
    
    @validates('email')
    def validateEmail(self, key, email):
        if '@' not in email:
            raise ValueError("Invalid email format")
        return email.lower()
    
    @validates('age')
    def validateAge(self, key, age):
        if age and age < 18:
            raise ValueError("Age must be 18 or older")
        return age
```

## Query Helpers

```python
class User(Base):
    __tablename__ = "user"
    id = Column(Integer, primary_key=True)
    email = Column(String(255), unique=True)
    isActive = Column(Boolean, default=True)
    
    @classmethod
    def getActiveUsers(cls, session):
        """Get all active users."""
        return session.query(cls).filter(cls.isActive == True).all()
    
    @classmethod
    def getByEmail(cls, session, email):
        """Get user by email."""
        return session.query(cls).filter(cls.email == email).first()
```

## Documentation & Comments

```python
class ChatMessage(Base):
    """Chat message model for storing conversation messages.
    
    This model stores messages sent between users in the chat
    system. Each message is associated with a user and a
    conversation session.
    
    Attributes:
        id: Unique message identifier
        userId: Foreign key referencing the user who sent the message
        content: The message content (up to 10000 characters)
        createdAt: UTC timestamp when message was created
        updatedAt: UTC timestamp of last modification
    """
    __tablename__ = "chat_message"
    
    id = Column(Integer, primary_key=True, index=True)
    userId = Column(Integer, ForeignKey("user.id"), nullable=False)
    content = Column(String(10000), nullable=False)
    
    createdAt = Column(DateTime, default=datetime.utcnow)
    updatedAt = Column(DateTime, onupdate=datetime.utcnow)
    
    user = relationship("User", back_populates="chatMessages")
```
