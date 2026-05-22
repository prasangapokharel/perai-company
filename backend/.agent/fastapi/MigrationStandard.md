# FastAPI Migration Standards

## Overview

This document defines the database migration standards using Alembic for FastAPI projects.

## Installation & Setup

### Install Alembic

```bash
pip install alembic sqlalchemy
```

### Initialize Alembic

```bash
alembic init migrations
```

This creates:
```
migrations/
├── alembic.ini
├── env.py
├── script.py.mako
└── versions/
```

## File Structure

```
project-root/
├── app/
│   ├── core/
│   │   └── database.py
│   └── models/
│       ├── user.py
│       ├── chatMessage.py
│       └── ...
├── migrations/
│   ├── alembic.ini
│   ├── env.py
│   ├── script.py.mako
│   └── versions/
│       ├── 001_initial_schema.py
│       ├── 002_add_user_email_unique.py
│       └── 003_create_chat_table.py
└── main.py
```

## Configuration

### alembic.ini

```ini
[alembic]
sqlalchemy.url = driver://user:password@localhost/dbname

# Logging
[loggers]
keys = root,sqlalchemy,alembic

[handlers]
keys = console

[formatters]
keys = generic

[logger_root]
level = WARN
handlers = console
qualname =

[logger_sqlalchemy]
level = WARN
handlers =
qualname = sqlalchemy.engine

[logger_alembic]
level = INFO
handlers =
qualname = alembic

[handler_console]
class = StreamHandler
args = (sys.stderr,)
level = NOTSET
formatter = generic

[formatter_generic]
format = %(levelname)-5.5s [%(name)s] %(message)s
datefmt = %H:%M:%S
```

### env.py Configuration

```python
"""Alembic environment configuration"""
from logging.config import fileConfig
from sqlalchemy import engine_from_config
from sqlalchemy import pool
from alembic import context
from app.core.database import Base
from app.models import user, chatMessage  # Import all models

config = context.config

# Database URL from environment or config
if config.config_file_name is not None:
    fileConfig(config.config_file_name)

target_metadata = Base.metadata


def run_migrations_offline() -> None:
    """Run migrations in 'offline' mode."""
    configuration = config.get_section(config.config_ini_section)
    configuration["sqlalchemy.url"] = "postgresql://user:password@localhost/db"

    context.configure(
        url=configuration["sqlalchemy.url"],
        target_metadata=target_metadata,
        literal_binds=True,
        dialect_opts={"paramstyle": "named"},
    )

    with context.begin_transaction():
        context.run_migrations()


def run_migrations_online() -> None:
    """Run migrations in 'online' mode."""
    configuration = config.get_section(config.config_ini_section)

    configuration["sqlalchemy.url"] = "postgresql://user:password@localhost/db"

    connectable = engine_from_config(
        configuration,
        prefix="sqlalchemy.",
        poolclass=pool.NullPool,
    )

    with connectable.connect() as connection:
        context.configure(
            connection=connection,
            target_metadata=target_metadata
        )

        with context.begin_transaction():
            context.run_migrations()


if context.is_offline_mode():
    run_migrations_offline()
else:
    run_migrations_online()
```

## Migration File Naming

### Format

```
<revision_number>_<description>.py
```

### Examples

```
001_initial_schema.py
002_add_user_email_unique.py
003_create_chat_messages_table.py
004_add_user_profile_fields.py
005_create_sessions_table.py
```

## Creating Migrations

### Auto-generate Migration (Recommended)

```bash
# FastAPI will compare models with database
alembic revision --autogenerate -m "add user email unique constraint"
```

### Manual Migration

```bash
# Create empty migration file
alembic revision -m "add user email unique constraint"
```

## Migration File Structure

### Auto-generated Migration Example

```python
"""add_user_email_unique_constraint

Revision ID: 002
Revises: 001
Create Date: 2024-01-15 10:30:00.000000
"""
from alembic import op
import sqlalchemy as sa


# Revision identifiers used by Alembic
revision = '002'
down_revision = '001'
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Apply upgrade migration"""
    # Add unique constraint to user email
    op.create_unique_constraint(
        'uq_user_email',
        'user',
        ['email']
    )


def downgrade() -> None:
    """Revert migration"""
    op.drop_constraint('uq_user_email', 'user')
```

### Manual Migration Example - Add Column

```python
"""add_phone_number_to_user

Revision ID: 003
Revises: 002
Create Date: 2024-01-16 10:30:00.000000
"""
from alembic import op
import sqlalchemy as sa


revision = '003'
down_revision = '002'
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Add phone_number column to user table"""
    op.add_column(
        'user',
        sa.Column(
            'phoneNumber',
            sa.String(20),
            nullable=True
        )
    )


def downgrade() -> None:
    """Remove phone_number column"""
    op.drop_column('user', 'phoneNumber')
```

### Manual Migration Example - Create Table

```python
"""create_chat_messages_table

Revision ID: 004
Revises: 003
Create Date: 2024-01-17 10:30:00.000000
"""
from alembic import op
import sqlalchemy as sa


revision = '004'
down_revision = '003'
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Create chat_messages table"""
    op.create_table(
        'chat_messages',
        sa.Column('id', sa.Integer, primary_key=True),
        sa.Column('userId', sa.Integer, nullable=False),
        sa.Column('content', sa.Text, nullable=False),
        sa.Column('createdAt', sa.DateTime, nullable=False),
        sa.Column('updatedAt', sa.DateTime, nullable=True),
        sa.ForeignKeyConstraint(['userId'], ['user.id']),
        sa.UniqueConstraint('id')
    )


def downgrade() -> None:
    """Drop chat_messages table"""
    op.drop_table('chat_messages')
```

### Manual Migration Example - Add Index

```python
"""add_user_email_index

Revision ID: 005
Revises: 004
Create Date: 2024-01-18 10:30:00.000000
"""
from alembic import op
import sqlalchemy as sa


revision = '005'
down_revision = '004'
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Add index on user.email for faster queries"""
    op.create_index(
        'ix_user_email',
        'user',
        ['email'],
        unique=False
    )


def downgrade() -> None:
    """Drop index"""
    op.drop_index('ix_user_email', table_name='user')
```

## Migration Commands

### Apply Migrations

```bash
# Apply all pending migrations
alembic upgrade head

# Apply specific number of migrations
alembic upgrade +2

# Apply to specific revision
alembic upgrade 002
```

### Revert Migrations

```bash
# Revert last migration
alembic downgrade -1

# Revert multiple migrations
alembic downgrade -3

# Revert to specific revision
alembic downgrade 001

# Revert all migrations
alembic downgrade base
```

### View Migration Status

```bash
# Show current revision
alembic current

# Show migration history
alembic history

# Show detailed history
alembic history --verbose
```

### Generate Migration SQL

```bash
# Show SQL for upgrade
alembic upgrade head --sql

# Show SQL for downgrade
alembic downgrade -1 --sql
```

## Best Practices

### 1. Always Create Reversible Migrations

```python
def upgrade() -> None:
    op.add_column('user', sa.Column('age', sa.Integer))

def downgrade() -> None:
    op.drop_column('user', 'age')
```

### 2. One Change Per Migration

```bash
# Good - separate migration per change
001_add_user_table.py
002_add_user_email_unique.py
003_add_user_phone.py

# Avoid - multiple unrelated changes in one migration
001_add_user_and_other_changes.py
```

### 3. Descriptive Migration Names

```bash
# Good
002_add_user_email_unique_constraint.py
003_create_chat_messages_table_with_indexes.py

# Avoid
002_changes.py
003_update.py
```

### 4. Data Migrations

```python
"""populate_user_default_role

Revision ID: 010
Revises: 009
Create Date: 2024-01-20 10:30:00.000000
"""
from alembic import op
import sqlalchemy as sa


revision = '010'
down_revision = '009'
branch_labels = None
depends_on = None


def upgrade() -> None:
    """Add default role to existing users"""
    connection = op.get_bind()
    
    # Add column first if needed
    op.add_column(
        'user',
        sa.Column('role', sa.String(50), default='user')
    )
    
    # Update existing rows
    connection.execute(
        "UPDATE user SET role = 'user' WHERE role IS NULL"
    )


def downgrade() -> None:
    op.drop_column('user', 'role')
```

### 5. Pre-production Testing

```bash
# Always test migrations locally first
alembic upgrade head

# Verify schema
psql -d database_name -c "\dt"

# Test downgrade
alembic downgrade -1

# Test upgrade again
alembic upgrade head
```

### 6. Naming Consistency

- Use `camelCase` for column names matching Python models
- Use `UPPER_SNAKE_CASE` for constraint names

```python
def upgrade() -> None:
    op.create_unique_constraint(
        'uq_user_email',  # UPPER_SNAKE_CASE
        'user',
        ['email']         # camelCase
    )
```

## Troubleshooting

### Reset Alembic (Development Only)

```bash
# Remove all migrations and start fresh
rm migrations/versions/*.py

# Recreate initial migration
alembic revision --autogenerate -m "initial schema"
```

### Merge Migrations

```bash
# If multiple migrations target same parent (merge conflicts)
alembic merge -m "merge conflicts" --revisions <rev1>:<rev2>
```

### Check Migration Status

```bash
# Show pending migrations
alembic current
alembic heads

# Verify all migrations applied
alembic upgrade head
alembic current
```

## Integration with FastAPI

### Application Startup

```python
# app/main.py
from alembic.config import Config
from alembic import command

def runMigrations():
    """Run pending migrations at startup"""
    alembicConfig = Config("alembic.ini")
    command.upgrade(alembicConfig, "head")

if __name__ == "__main__":
    runMigrations()
    uvicorn.run(app, host="0.0.0.0", port=8000)
```

### Docker Integration

```dockerfile
FROM python:3.11

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

# Run migrations before starting app
CMD ["sh", "-c", "alembic upgrade head && uvicorn app.main:app --host 0.0.0.0"]
```

## Version Control

Always commit migrations:

```bash
# Migration files should be in version control
git add migrations/versions/*.py
git commit -m "chore: add migration for user phone field"
```

## Production Checklist

- [ ] Migrations tested locally
- [ ] Downgrade tested and works
- [ ] Backward compatible changes
- [ ] No data loss in downgrade
- [ ] Meaningful commit message
- [ ] Migration names are descriptive
- [ ] All team members notified
- [ ] Backup created before deployment
