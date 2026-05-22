# Background Tasks Standard

## Overview

Background tasks allow you to execute code **after** returning a response to the client. This is ideal for operations that don't require the client to wait, such as:
- Sending email notifications
- Processing files
- Logging operations
- Writing to external systems
- Heavy computations
- Calling external APIs

**Key Benefit**: Client gets immediate response while work continues in the background.

## Two Approaches

### 1. FastAPI BackgroundTasks (Built-in, Recommended)

Use `BackgroundTasks` for simple, in-process background work. Perfect for most use cases.

### 2. Job Queue Systems (Advanced)

For complex scenarios, use Celery, RQ, or similar. See Troubleshooting section.

## BackgroundTasks Pattern

### Basic Usage

```python
from fastapi import BackgroundTasks, FastAPI

app = FastAPI()

def writeNotification(email: str, message: str = ""):
    """Send email notification."""
    with open("log.txt", mode="w") as email_file:
        content = f"notification for {email}: {message}"
        email_file.write(content)

@app.post("/send-notification/{email}")
async def sendNotification(email: str, background_tasks: BackgroundTasks):
    """Send notification and return immediately."""
    background_tasks.add_task(writeNotification, email, message="some notification")
    return {"message": "Notification sent in the background"}
```

### With Async Function

```python
async def sendEmailAsync(email: str, subject: str):
    """Send email asynchronously."""
    await emailService.send(email, subject)

@app.post("/send-email")
async def sendEmail(email: str, background_tasks: BackgroundTasks):
    """Queue async email sending."""
    background_tasks.add_task(sendEmailAsync, email, subject="Hello")
    return {"status": "Email queued"}
```

## BackgroundTasks.add_task() Syntax

```python
background_tasks.add_task(
    function_name,           # Function to call
    arg1, arg2, arg3,       # Positional arguments
    kwarg1=value1,          # Keyword arguments
    kwarg2=value2
)

# Example
background_tasks.add_task(
    processFile,
    filename="data.csv",
    format="csv",
    encoding="utf-8"
)
```

## Common Patterns

### Pattern 1: Email Notification

```python
from fastapi import BackgroundTasks, FastAPI
from pydantic import BaseModel
import smtplib

class EmailRequest(BaseModel):
    email: str
    subject: str
    body: str

async def sendEmailNotification(email: str, subject: str, body: str):
    """Send email in background."""
    try:
        # Email sending logic
        await emailService.send(email, subject, body)
        logger.info(f"Email sent to {email}")
    except Exception as e:
        logger.error(f"Failed to send email: {e}")

@app.post("/send-email")
async def sendEmailEndpoint(
    request: EmailRequest,
    background_tasks: BackgroundTasks
):
    """Send email (returns immediately)."""
    background_tasks.add_task(
        sendEmailNotification,
        request.email,
        request.subject,
        request.body
    )
    return {"message": "Email queued for sending"}
```

### Pattern 2: File Processing

```python
import asyncio
from fastapi import BackgroundTasks, FastAPI, UploadFile

async def processUploadedFile(filename: str, filepath: str):
    """Process file in background."""
    try:
        # Read and process file
        data = await readFileAsync(filepath)
        processed = await processData(data)
        
        # Save results
        await saveResults(filename, processed)
        logger.info(f"File {filename} processed successfully")
    except Exception as e:
        logger.error(f"File processing failed: {e}")
    finally:
        # Cleanup
        await deleteFile(filepath)

@app.post("/upload")
async def uploadFile(
    file: UploadFile,
    background_tasks: BackgroundTasks
):
    """Upload file and process in background."""
    filepath = f"/tmp/{file.filename}"
    
    # Save file
    with open(filepath, "wb") as f:
        content = await file.read()
        f.write(content)
    
    # Queue processing
    background_tasks.add_task(processUploadedFile, file.filename, filepath)
    
    return {"filename": file.filename, "status": "processing"}
```

### Pattern 3: Database Updates

```python
async def updateUserStats(user_id: int, action: str):
    """Update user statistics in background."""
    async with session() as db:
        user = await db.get(User, user_id)
        if user:
            user.stats[action] = user.stats.get(action, 0) + 1
            await db.commit()

@app.post("/action/{user_id}/{action}")
async def performAction(
    user_id: int,
    action: str,
    background_tasks: BackgroundTasks
):
    """Perform action and update stats."""
    # Do the action
    result = executeAction(action)
    
    # Update stats in background
    background_tasks.add_task(updateUserStats, user_id, action)
    
    return {"result": result}
```

### Pattern 4: External API Calls

```python
async def callExternalAPI(webhook_url: str, data: dict):
    """Call external API in background."""
    try:
        response = await httpClient.post(webhook_url, json=data)
        logger.info(f"Webhook call successful: {response.status_code}")
    except Exception as e:
        logger.error(f"Webhook call failed: {e}")

@app.post("/create-resource")
async def createResource(
    data: dict,
    background_tasks: BackgroundTasks
):
    """Create resource and notify external system."""
    resource = await saveResource(data)
    
    # Notify external system in background
    background_tasks.add_task(
        callExternalAPI,
        "https://external.com/webhook",
        resource.dict()
    )
    
    return {"id": resource.id}
```

### Pattern 5: Dependent Background Tasks

```python
from typing import Annotated
from fastapi import Depends

async def getBackgroundTasks() -> BackgroundTasks:
    """Dependency providing BackgroundTasks."""
    return BackgroundTasks()

async def stepOne(data: str):
    """First step."""
    logger.info(f"Step 1: {data}")
    await asyncio.sleep(1)

async def stepTwo(data: str):
    """Second step (depends on step 1)."""
    logger.info(f"Step 2: {data}")
    await asyncio.sleep(1)

async def stepThree(data: str):
    """Third step (depends on step 2)."""
    logger.info(f"Step 3: {data}")

@app.post("/process")
async def processSteps(
    background_tasks: BackgroundTasks
):
    """Queue dependent background tasks."""
    background_tasks.add_task(stepOne, "data")
    background_tasks.add_task(stepTwo, "data")
    background_tasks.add_task(stepThree, "data")
    
    return {"status": "processing"}
```

### Pattern 6: Multiple Background Tasks

```python
@app.post("/complex-operation")
async def complexOperation(background_tasks: BackgroundTasks):
    """Queue multiple background tasks."""
    # Queue task 1
    background_tasks.add_task(sendNotification, "user@example.com")
    
    # Queue task 2
    background_tasks.add_task(updateAnalytics, operation="create")
    
    # Queue task 3
    background_tasks.add_task(callWebhook, "https://webhook.com/endpoint")
    
    # All tasks will run after response is sent
    return {"status": "queued"}
```

## Using with Dependencies

```python
from typing import Annotated
from fastapi import Depends

def logBackgroundTask(task_name: str, background_tasks: BackgroundTasks):
    """Dependency that logs background tasks."""
    
    async def doLogging():
        logger.info(f"Background task '{task_name}' completed")
    
    background_tasks.add_task(doLogging)
    return background_tasks

@app.post("/endpoint")
async def endpoint(
    background_tasks: Annotated[BackgroundTasks, Depends(logBackgroundTask)]
):
    """Endpoint with logging dependency."""
    background_tasks.add_task(sendEmail, "user@example.com")
    return {"message": "Task queued"}
```

## Best Practices

### 1. Use Type Annotations

```python
# GOOD: Clear types
async def sendEmail(email: str, subject: str, body: str) -> None:
    """Send email."""
    await emailService.send(email, subject, body)

@app.post("/send")
async def endpoint(background_tasks: BackgroundTasks) -> dict:
    background_tasks.add_task(sendEmail, "user@example.com", "Hi", "Body")
    return {"status": "queued"}

# NOT RECOMMENDED: No types
async def sendEmailNoTypes(email, subject, body):
    await emailService.send(email, subject, body)
```

### 2. Error Handling in Background Tasks

```python
async def robustBackgroundTask(data: dict):
    """Background task with error handling."""
    try:
        result = await process(data)
        logger.info("Task completed successfully")
    except ValueError as e:
        logger.error(f"Validation error: {e}")
    except Exception as e:
        logger.error(f"Unexpected error: {e}")
        # Consider retrying or alerting

@app.post("/process")
async def processEndpoint(
    data: dict,
    background_tasks: BackgroundTasks
):
    background_tasks.add_task(robustBackgroundTask, data)
    return {"status": "queued"}
```

### 3. Timeout and Resource Limits

```python
import asyncio

async def backgroundTaskWithTimeout(data: dict):
    """Background task with timeout."""
    try:
        result = await asyncio.wait_for(
            processData(data),
            timeout=30.0  # 30 second timeout
        )
        logger.info("Task completed")
    except asyncio.TimeoutError:
        logger.error("Task timed out")
    except Exception as e:
        logger.error(f"Task failed: {e}")

@app.post("/process")
async def processEndpoint(
    background_tasks: BackgroundTasks
):
    background_tasks.add_task(backgroundTaskWithTimeout, {"data": "value"})
    return {"status": "queued"}
```

### 4. Status Tracking

```python
from typing import Optional

task_status: dict = {}

async def trackableTask(task_id: str, data: dict):
    """Background task with status tracking."""
    task_status[task_id] = "running"
    try:
        result = await process(data)
        task_status[task_id] = "completed"
    except Exception as e:
        task_status[task_id] = f"failed: {str(e)}"

@app.post("/process")
async def processEndpoint(background_tasks: BackgroundTasks):
    task_id = generateTaskId()
    task_status[task_id] = "queued"
    
    background_tasks.add_task(trackableTask, task_id, {"data": "value"})
    return {"task_id": task_id}

@app.get("/status/{task_id}")
async def getStatus(task_id: str):
    return {"status": task_status.get(task_id, "unknown")}
```

### 5. Resource Cleanup

```python
async def cleanupAfterTask(resource_path: str):
    """Background task with cleanup."""
    try:
        await processFile(resource_path)
    finally:
        # Always cleanup, even on error
        await deleteFile(resource_path)
        logger.info(f"Cleaned up {resource_path}")

@app.post("/process-file")
async def processFile(
    filepath: str,
    background_tasks: BackgroundTasks
):
    background_tasks.add_task(cleanupAfterTask, filepath)
    return {"status": "processing"}
```

## Testing Background Tasks

```python
from fastapi.testclient import TestClient

client = TestClient(app)

def test_background_task():
    """Test that background task is queued."""
    response = client.post("/send-notification/user@example.com")
    assert response.status_code == 200
    assert response.json()["message"] == "Notification sent in the background"
```

## When NOT to Use BackgroundTasks

Use a job queue system (Celery, RQ, etc.) when:

1. **Tasks are long-running** (>30 seconds)
2. **Tasks must survive server restarts**
3. **Need distributed processing** across multiple servers
4. **Need task retries** with exponential backoff
5. **Need task scheduling** (cron-like)
6. **Need complex task dependencies**

```python
# GOOD: Simple, fast task
background_tasks.add_task(sendSimpleEmail, email)

# CONSIDER CELERY: Complex, long-running task
@celery_app.task
def processHeavyData(data):
    """Better handled by Celery."""
    result = complexAnalysis(data)
    storeResult(result)
```

## Limitations

1. **No persistence**: Tasks are lost if server restarts
2. **No retries**: Failed tasks won't retry automatically
3. **Single server only**: Doesn't work across multiple processes/servers
4. **Limited monitoring**: No built-in task tracking UI
5. **Sequential by nature**: Tasks run after response, not parallel to it

## Troubleshooting

### Issue: Background task not running

**Solution**: Ensure task is properly added and server is running.

```python
# CORRECT
background_tasks.add_task(sendEmail, email)

# WRONG
# sendEmail(email)  # Runs immediately, not in background
```

### Issue: Server exits before task completes

**Solution**: Use graceful shutdown with task timeout.

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    yield
    # Allow time for background tasks to complete
    await asyncio.sleep(5)
```

## References

- [FastAPI Background Tasks](https://fastapi.tiangolo.com/tutorial/background-tasks/)
- [Starlette Background Tasks](https://www.starlette.dev/background/)
- [Celery (for advanced use cases)](https://docs.celeryq.dev/)
- [RQ (Simple job queue)](https://python-rq.org/)
