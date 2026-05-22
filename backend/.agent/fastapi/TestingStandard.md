# FastAPI Testing Standards

## Overview

This document defines the testing standards and best practices for FastAPI applications.

## Testing Framework

- **Framework**: Pytest
- **Coverage**: Aim for >80% code coverage
- **Types**: Unit, Integration, and API endpoint tests

## Project Structure

```
testing/
├── __init__.py
├── conftest.py              # Shared fixtures
├── fixtures.py              # Custom fixtures
├── api/
│   ├── __init__.py
│   └── v1/
│       ├── __init__.py
│       ├── users/
│       │   ├── __init__.py
│       │   └── testUserRoute.py
│       └── ...
├── unit/
│   ├── __init__.py
│   ├── services/
│   │   └── testUserService.py
│   └── utils/
│       └── testDateUtils.py
└── integration/
    ├── __init__.py
    └── testDatabaseIntegration.py
```

## Test File Naming

- **Format**: `test<ModuleName>.py`
- **Pattern**: `camelCase` prefix with `test`

**Examples:**
```
testUserRoute.py
testUserService.py
testAuthService.py
testDateUtils.py
```

## Test Function Naming

- **Format**: `test_<feature_description>`
- **Pattern**: lowercase with underscores

**Examples:**
```python
def test_getUserById_returnsUserWhenExists():
    pass

def test_getUserById_raises404WhenNotFound():
    pass

def test_createUser_successfullyCreatesNewUser():
    pass
```

## Pytest Configuration

### conftest.py

```python
import pytest
from fastapi.testclient import TestClient
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from app.main import app
from app.core.database import Base, getDb

# Test database
SQLALCHEMY_TEST_DATABASE_URL = "sqlite:///./test.db"

engine = create_engine(
    SQLALCHEMY_TEST_DATABASE_URL,
    connect_args={"check_same_thread": False}
)
TestingSessionLocal = sessionmaker(
    autocommit=False,
    autoflush=False,
    bind=engine
)

Base.metadata.create_all(bind=engine)


@pytest.fixture(scope="function")
def db():
    """Database fixture for each test."""
    Base.metadata.create_all(bind=engine)
    db = TestingSessionLocal()
    try:
        yield db
    finally:
        db.close()
        Base.metadata.drop_all(bind=engine)


@pytest.fixture(scope="function")
def client(db):
    """Test client fixture."""
    def overrideGetDb():
        try:
            yield db
        finally:
            db.close()

    app.dependency_overrides[getDb] = overrideGetDb
    yield TestClient(app)
    app.dependency_overrides.clear()


@pytest.fixture(scope="function")
def authenticatedClient(client, db):
    """Authenticated test client."""
    # Create test user and authenticate
    response = client.post(
        "/api/v1/auth/register",
        json={
            "email": "test@example.com",
            "password": "testpassword123"
        }
    )
    assert response.status_code == 201

    # Login to get token
    response = client.post(
        "/api/v1/auth/login",
        json={
            "email": "test@example.com",
            "password": "testpassword123"
        }
    )
    assert response.status_code == 200
    token = response.json()["access_token"]

    # Add token to headers
    client.headers = {"Authorization": f"Bearer {token}"}
    return client
```

## API Endpoint Testing

### testUserRoute.py

```python
import pytest
from fastapi import status


class TestGetUser:
    """Tests for GET /users/{userId}"""

    def test_getUser_returnsUserWhenExists(self, client, db):
        """Test successful user retrieval."""
        # Arrange
        userId = 1
        from app.models.user import User
        
        user = User(id=userId, name="John", email="john@example.com")
        db.add(user)
        db.commit()

        # Act
        response = client.get(f"/api/v1/users/{userId}")

        # Assert
        assert response.status_code == status.HTTP_200_OK
        data = response.json()
        assert data["id"] == userId
        assert data["name"] == "John"

    def test_getUser_returns404WhenNotFound(self, client):
        """Test 404 error for non-existent user."""
        # Act
        response = client.get("/api/v1/users/999")

        # Assert
        assert response.status_code == status.HTTP_404_NOT_FOUND
        assert response.json()["detail"] == "User not found"

    def test_getUser_requiresAuthentication(self, client):
        """Test authentication requirement."""
        # Act
        response = client.get("/api/v1/users/1")

        # Assert
        assert response.status_code == status.HTTP_401_UNAUTHORIZED


class TestCreateUser:
    """Tests for POST /users"""

    def test_createUser_successfullyCreatesNewUser(self, authenticatedClient):
        """Test successful user creation."""
        # Arrange
        userData = {
            "name": "Jane Doe",
            "email": "jane@example.com",
            "password": "securepass123"
        }

        # Act
        response = authenticatedClient.post(
            "/api/v1/users",
            json=userData
        )

        # Assert
        assert response.status_code == status.HTTP_201_CREATED
        data = response.json()
        assert data["name"] == userData["name"]
        assert data["email"] == userData["email"]

    def test_createUser_returnsErrorOnDuplicateEmail(
        self,
        authenticatedClient,
        db
    ):
        """Test duplicate email validation."""
        # Arrange
        from app.models.user import User
        
        user = User(name="John", email="john@example.com")
        db.add(user)
        db.commit()

        # Act
        response = authenticatedClient.post(
            "/api/v1/users",
            json={
                "name": "Duplicate",
                "email": "john@example.com",
                "password": "pass123"
            }
        )

        # Assert
        assert response.status_code == status.HTTP_400_BAD_REQUEST
        assert "email" in response.json()["detail"]

    def test_createUser_validatesRequiredFields(self, authenticatedClient):
        """Test required field validation."""
        # Act
        response = authenticatedClient.post(
            "/api/v1/users",
            json={"name": "John"}  # Missing email and password
        )

        # Assert
        assert response.status_code == status.HTTP_422_UNPROCESSABLE_ENTITY


class TestUpdateUser:
    """Tests for PUT/PATCH /users/{userId}"""

    def test_updateUser_successfullyUpdatesExistingUser(
        self,
        authenticatedClient,
        db
    ):
        """Test successful user update."""
        # Arrange
        from app.models.user import User
        
        user = User(name="John", email="john@example.com")
        db.add(user)
        db.commit()

        # Act
        response = authenticatedClient.patch(
            f"/api/v1/users/{user.id}",
            json={"name": "Jane"}
        )

        # Assert
        assert response.status_code == status.HTTP_200_OK
        data = response.json()
        assert data["name"] == "Jane"


class TestDeleteUser:
    """Tests for DELETE /users/{userId}"""

    def test_deleteUser_successfullyDeletesExistingUser(
        self,
        authenticatedClient,
        db
    ):
        """Test successful user deletion."""
        # Arrange
        from app.models.user import User
        
        user = User(name="John", email="john@example.com")
        db.add(user)
        db.commit()
        userId = user.id

        # Act
        response = authenticatedClient.delete(f"/api/v1/users/{userId}")

        # Assert
        assert response.status_code == status.HTTP_204_NO_CONTENT

        # Verify deletion
        response = client.get(f"/api/v1/users/{userId}")
        assert response.status_code == status.HTTP_404_NOT_FOUND
```

## Unit Testing

### testUserService.py

```python
import pytest
from unittest.mock import patch, MagicMock
from app.services.userService import UserService
from app.schemas.userSchema import UserCreate


class TestUserService:
    """Tests for UserService business logic"""

    @pytest.fixture
    def userService(self, db):
        """UserService fixture."""
        return UserService(db=db)

    def test_createUser_returnsCreatedUser(self, userService):
        """Test user creation service."""
        # Arrange
        userData = UserCreate(
            name="John Doe",
            email="john@example.com",
            password="securepass123"
        )

        # Act
        user = userService.createUser(userData)

        # Assert
        assert user.name == userData.name
        assert user.email == userData.email

    def test_getUserById_returnsUserWhenExists(self, userService):
        """Test retrieving existing user."""
        # Arrange
        user = userService.createUser(UserCreate(
            name="John",
            email="john@example.com",
            password="pass123"
        ))

        # Act
        retrievedUser = userService.getUserById(user.id)

        # Assert
        assert retrievedUser.id == user.id
        assert retrievedUser.name == "John"

    def test_getUserById_returnsNoneWhenNotFound(self, userService):
        """Test None returned for non-existent user."""
        # Act
        user = userService.getUserById(999)

        # Assert
        assert user is None

    @patch('app.services.emailService.sendEmail')
    def test_createUser_sendsWelcomeEmail(self, mockEmail, userService):
        """Test email sent on user creation."""
        # Arrange
        userData = UserCreate(
            name="John",
            email="john@example.com",
            password="pass123"
        )

        # Act
        user = userService.createUser(userData)

        # Assert
        mockEmail.assert_called_once()
        args, kwargs = mockEmail.call_args
        assert user.email in args
```

## Integration Testing

### testDatabaseIntegration.py

```python
import pytest
from app.models.user import User
from app.crud.userCrud import createUser, getUser


class TestDatabaseIntegration:
    """Integration tests with actual database"""

    def test_createAndRetrieveUser(self, db):
        """Test creating and retrieving user from database."""
        # Arrange
        userData = {
            "name": "John Doe",
            "email": "john@example.com",
            "hashedPassword": "hashed_pass_123"
        }

        # Act
        user = createUser(db, **userData)
        db.commit()

        retrievedUser = getUser(db, user.id)

        # Assert
        assert retrievedUser.name == userData["name"]
        assert retrievedUser.email == userData["email"]
```

## Fixtures

### fixtures.py

```python
import pytest
from app.models.user import User
from app.schemas.userSchema import UserCreate


@pytest.fixture
def sampleUser(db):
    """Sample user fixture."""
    user = User(
        name="Test User",
        email="test@example.com",
        hashedPassword="hashed_password"
    )
    db.add(user)
    db.commit()
    return user


@pytest.fixture
def sampleUsers(db):
    """Multiple sample users fixture."""
    users = [
        User(name="User 1", email="user1@example.com"),
        User(name="User 2", email="user2@example.com"),
        User(name="User 3", email="user3@example.com"),
    ]
    db.add_all(users)
    db.commit()
    return users


@pytest.fixture
def validUserData():
    """Valid user registration data."""
    return {
        "name": "John Doe",
        "email": "john@example.com",
        "password": "SecurePass123!"
    }


@pytest.fixture
def invalidUserData():
    """Invalid user data for testing validation."""
    return {
        "name": "",  # Invalid: empty name
        "email": "invalid-email",  # Invalid: bad email
        "password": "123"  # Invalid: weak password
    }
```

## Test Markers

### pytest.ini

```ini
[pytest]
addopts = -v --strict-markers --tb=short
testpaths = testing
python_files = test*.py
python_classes = Test*
python_functions = test_*

markers =
    unit: unit tests
    integration: integration tests
    api: API endpoint tests
    slow: slow running tests
    smoke: smoke tests
```

## Running Tests

```bash
# Run all tests
pytest testing/

# Run with coverage
pytest testing/ --cov=app --cov-report=html

# Run specific category
pytest testing/unit/
pytest testing/api/
pytest -m unit

# Run specific test
pytest testing/api/v1/users/testUserRoute.py::TestGetUser::test_getUser_returnsUserWhenExists

# Run with markers
pytest -m "not slow"

# Run in parallel
pytest testing/ -n auto
```

## Best Practices

1. **Arrange-Act-Assert**: Follow AAA pattern in every test
2. **One Assert Per Test**: Keep tests focused on single behavior
3. **Clear Names**: Test names should describe what's being tested
4. **DRY Principle**: Use fixtures to avoid code repetition
5. **Mock External Services**: Don't call real APIs or send emails in tests
6. **Test Error Cases**: Test both success and failure scenarios
7. **Isolation**: Each test should be independent
8. **Use Factories**: Use factories for creating complex test objects
9. **Document Edge Cases**: Comment on why certain edge cases are tested
10. **Keep Tests Fast**: Avoid slow operations; use in-memory database

## Coverage Goals

- **Overall**: >80%
- **Critical paths**: 100%
- **Utility functions**: >70%
- **Controllers/Handlers**: >90%

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        python-version: [3.9, 3.10, 3.11]

    steps:
    - uses: actions/checkout@v3
    - uses: actions/setup-python@v4
      with:
        python-version: ${{ matrix.python-version }}
    - run: pip install -r requirements.txt
    - run: pytest testing/ --cov=app --cov-report=xml
    - uses: codecov/codecov-action@v3
```
