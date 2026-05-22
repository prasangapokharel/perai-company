# Lifespan Events Standard

## Overview

Lifespan events allow you to execute code at specific points in your application's lifecycle:
- **Startup**: Code runs once before the application starts receiving requests
- **Shutdown**: Code runs once after the application finishes handling requests

**Common Use Cases**:
- Loading ML models
- Initializing database connections
- Setting up caches
- Starting background jobs
- Releasing resources
- Cleaning up connections

## Two Implementation Approaches

### Approach 1: Lifespan Context Manager (Recommended)

This is the modern, recommended way using a single context manager that handles both startup and shutdown.

### Approach 2: Deprecated Event Handlers

Using `@app.on_event("startup")` and `@app.on_event("shutdown")` is deprecated but still functional. Use Lifespan context manager instead.

## Lifespan Context Manager Pattern

### Basic Setup

```python
from contextlib import asynccontextmanager
from fastapi import FastAPI

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup code here
    print("Application starting up...")
    yield  # Application runs here
    # Shutdown code here
    print("Application shutting down...")

app = FastAPI(lifespan=lifespan)

@app.get("/")
async def root():
    return {"message": "Hello World"}
```

### With Resource Management

```python
from contextlib import asynccontextmanager
from fastapi import FastAPI

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Initialize resources
    db = await initializeDatabase()
    cache = await initializeCache()
    ml_model = loadMLModel()
    
    # Make resources available to app
    app.state.db = db
    app.state.cache = cache
    app.state.ml_model = ml_model
    
    print("Resources initialized")
    yield  # Application runs here
    
    # Shutdown: Clean up resources
    print("Cleaning up resources...")
    await db.close()
    await cache.close()
    ml_model.cleanup()

app = FastAPI(lifespan=lifespan)

@app.get("/predict")
async def predict(x: float):
    result = app.state.ml_model.predict(x)
    return {"result": result}
```

## Common Patterns

### Pattern 1: Database Connection Pool

```python
from contextlib import asynccontextmanager
from sqlalchemy.ext.asyncio import create_async_engine, AsyncSession
from sqlalchemy.orm import sessionmaker

DATABASE_URL = "postgresql+asyncpg://user:password@localhost/dbname"

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Create database engine
    engine = create_async_engine(DATABASE_URL)
    async_session = sessionmaker(engine, class_=AsyncSession, expire_on_commit=False)
    
    app.state.db_engine = engine
    app.state.SessionLocal = async_session
    
    print("Database connection pool created")
    yield
    
    # Shutdown: Close database connections
    await engine.dispose()
    print("Database connections closed")

app = FastAPI(lifespan=lifespan)

async def getDatabase():
    async with app.state.SessionLocal() as session:
        yield session
```

### Pattern 2: Loading ML Models

```python
from contextlib import asynccontextmanager
import asyncio

ml_models = {}

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Load models in background
    def loadMLModels():
        ml_models["sentiment"] = loadSentimentModel()
        ml_models["classification"] = loadClassificationModel()
        ml_models["summarization"] = loadSummarizationModel()
    
    # Load in thread pool to avoid blocking startup
    loop = asyncio.get_event_loop()
    await loop.run_in_executor(None, loadMLModels)
    
    app.state.ml_models = ml_models
    print(f"Loaded {len(ml_models)} ML models")
    
    yield
    
    # Shutdown: Unload models and free GPU memory
    for model_name, model in ml_models.items():
        model.unload()
    ml_models.clear()
    print("ML models unloaded")

app = FastAPI(lifespan=lifespan)
```

### Pattern 3: Background Tasks

```python
from contextlib import asynccontextmanager
import asyncio

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Start background task
    async def backgroundWorker():
        while True:
            try:
                await performBackgroundWork()
                await asyncio.sleep(60)
            except Exception as e:
                logger.error(f"Background task error: {e}")
                await asyncio.sleep(60)
    
    task = asyncio.create_task(backgroundWorker())
    app.state.background_task = task
    
    print("Background worker started")
    yield
    
    # Shutdown: Cancel background task
    task.cancel()
    try:
        await task
    except asyncio.CancelledError:
        pass
    print("Background worker stopped")

app = FastAPI(lifespan=lifespan)
```

### Pattern 4: Cache Initialization

```python
from contextlib import asynccontextmanager
import aioredis

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Startup: Initialize Redis connection
    redis = await aioredis.create_redis_pool("redis://localhost")
    app.state.redis = redis
    
    # Warm up cache with common data
    await warmupCache(redis)
    
    print("Cache initialized and warmed up")
    yield
    
    # Shutdown: Close Redis connection
    app.state.redis.close()
    await app.state.redis.wait_closed()
    print("Cache connection closed")

app = FastAPI(lifespan=lifespan)
```

### Pattern 5: Multiple Resources with Error Handling

```python
from contextlib import asynccontextmanager
from typing import Dict, Any

@asynccontextmanager
async def lifespan(app: FastAPI):
    resources: Dict[str, Any] = {}
    
    try:
        # Startup: Initialize all resources
        print("Initializing resources...")
        
        resources["db"] = await initializeDatabase()
        resources["cache"] = await initializeCache()
        resources["logger"] = initializeLogger()
        resources["config"] = loadConfig()
        
        app.state.resources = resources
        print("All resources initialized successfully")
        
    except Exception as e:
        print(f"Error during startup: {e}")
        # Cleanup any partially initialized resources
        for key, resource in resources.items():
            try:
                if hasattr(resource, "cleanup"):
                    await resource.cleanup()
                elif hasattr(resource, "close"):
                    await resource.close()
            except Exception as cleanup_error:
                print(f"Error cleaning up {key}: {cleanup_error}")
        raise
    
    yield
    
    # Shutdown: Clean up all resources
    print("Cleaning up resources...")
    for key, resource in resources.items():
        try:
            if hasattr(resource, "cleanup"):
                await resource.cleanup()
            elif hasattr(resource, "close"):
                await resource.close()
        except Exception as e:
            print(f"Error cleaning up {key}: {e}")
    
    resources.clear()
    print("Cleanup completed")

app = FastAPI(lifespan=lifespan)
```

## Deprecated: Event Handlers

### Startup Event (Deprecated)

```python
@app.on_event("startup")
async def startup_event():
    """Deprecated: Use lifespan context manager instead."""
    print("Application starting up")
    # Setup code here

@app.on_event("shutdown")
async def shutdown_event():
    """Deprecated: Use lifespan context manager instead."""
    print("Application shutting down")
    # Cleanup code here
```

**Why it's deprecated**: Lifespan context manager is more explicit and groups related startup/shutdown logic together.

## Best Practices

### 1. Use Async Functions

```python
# GOOD: Async allows non-blocking operations
@asynccontextmanager
async def lifespan(app: FastAPI):
    db = await asyncInitializeDatabase()
    yield
    await db.closeAsync()

# NOT RECOMMENDED: Blocking operations
@asynccontextmanager
async def lifespanBlocking(app: FastAPI):
    db = initializeDatabaseSync()  # Blocking!
    yield
    db.closeSync()
```

### 2. Store Resources in app.state

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    # Make resources accessible throughout app
    app.state.db = await initializeDatabase()
    app.state.cache = await initializeCache()
    yield
    await app.state.db.close()

# Access in endpoints
@app.get("/data")
async def getData():
    return await app.state.db.query(...)
```

### 3. Proper Error Handling

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        print("Starting up...")
        app.state.resource = await initializeResource()
        yield
    except Exception as e:
        logger.error(f"Startup error: {e}")
        raise
    finally:
        print("Cleaning up...")
        if hasattr(app.state, "resource"):
            await app.state.resource.close()
```

### 4. Use Type Hints

```python
# GOOD: Clear types
@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator[None, None]:
    yield
    print("Done")

# NOT RECOMMENDED: No types
@asynccontextmanager
async def lifespanNoTypes(app):
    yield
```

### 5. Graceful Degradation

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    resources_initialized = False
    
    try:
        app.state.resource = await initializeResource()
        resources_initialized = True
    except Exception as e:
        logger.warning(f"Failed to initialize resource: {e}")
        # App continues without this resource
    
    yield
    
    if resources_initialized:
        await app.state.resource.close()
```

## Testing with Lifespan Events

### Testing Startup Only

```python
from fastapi.testclient import TestClient

def test_startup():
    """Test startup code."""
    with TestClient(app) as client:
        # app.state should be initialized here
        assert hasattr(app.state, "resource")
        assert app.state.resource is not None
```

### Testing with Context

```python
async def test_lifespan_async():
    """Test lifespan context manager."""
    app = FastAPI(lifespan=lifespan)
    
    async with lifespan(app):
        assert hasattr(app.state, "resource")
        # Test code here
```

## Advanced Patterns

### Pattern: Conditional Resource Initialization

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    import os
    
    env = os.getenv("ENVIRONMENT", "development")
    
    if env == "production":
        app.state.db = await initializeProductionDatabase()
    else:
        app.state.db = await initializeTestDatabase()
    
    yield
    
    await app.state.db.close()
```

### Pattern: Dynamic Configuration Loading

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    config = await loadConfigFromEnvironment()
    app.state.config = config
    
    # Initialize based on config
    if config.enable_cache:
        app.state.cache = await initializeCache(config.cache_settings)
    
    if config.enable_monitoring:
        startMonitoring(config.monitoring_settings)
    
    yield
    
    # Cleanup
    if hasattr(app.state, "cache"):
        await app.state.cache.close()
```

## Troubleshooting

### Issue: Startup code doesn't run

**Solution**: Ensure lifespan is passed to FastAPI constructor.

```python
# CORRECT
app = FastAPI(lifespan=lifespan)

# WRONG
app = FastAPI()
# lifespan not used!
```

### Issue: Resources not available in endpoints

**Solution**: Store resources in `app.state`.

```python
# CORRECT
@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.db = await initializeDatabase()
    yield

@app.get("/data")
async def getData():
    return await app.state.db.query()

# WRONG
@asynccontextmanager
async def lifespanWrong(app: FastAPI):
    db = await initializeDatabase()  # db not stored
    yield
```

### Issue: Shutdown code doesn't run

**Solution**: Ensure proper exception handling and that yield is present.

```python
# CORRECT
@asynccontextmanager
async def lifespan(app: FastAPI):
    try:
        print("Starting")
        yield
    finally:
        print("Shutting down")  # Always runs

# WRONG
@asynccontextmanager
async def lifespanWrong(app: FastAPI):
    print("Starting")
    yield
    print("Shutting down")  # May not run on error
```

## Migration: Event Handlers to Lifespan

### Before (Deprecated)

```python
@app.on_event("startup")
async def startup():
    app.state.db = await initializeDatabase()

@app.on_event("shutdown")
async def shutdown():
    await app.state.db.close()
```

### After (Recommended)

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.db = await initializeDatabase()
    yield
    await app.state.db.close()

app = FastAPI(lifespan=lifespan)
```

## References

- [FastAPI Lifespan Events](https://fastapi.tiangolo.com/advanced/events/)
- [Starlette Lifespan](https://www.starlette.dev/lifespan/)
- [ASGI Lifespan Protocol](https://asgi.readthedocs.io/en/latest/specs/lifespan.html)
- [Context Managers in Python](https://docs.python.org/3/library/contextlib.html)
