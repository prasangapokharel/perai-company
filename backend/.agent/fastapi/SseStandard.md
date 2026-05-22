# Server-Sent Events (SSE) Standard

## Overview

Server-Sent Events (SSE) is a standard for streaming data from server to client over HTTP using the `text/event-stream` content type. SSE provides native browser support via the `EventSource` API and includes built-in reconnection logic, event IDs, and event types.

**Best For**:
- Real-time notifications
- Live data feeds
- Chat messages
- Progress updates
- AI/LLM response streaming
- Anything requiring one-way server-to-client streaming

## Key Advantages

1. **Native Browser Support**: Works with `EventSource` API (no polyfills needed)
2. **Automatic Reconnection**: Built-in handling for dropped connections
3. **Event Types**: Different event types for different purposes
4. **Event IDs**: Resume streams from last received event
5. **Retry Logic**: Client-side retry configuration
6. **Simple Protocol**: Human-readable text format

## Basic Implementation

### Simple SSE Endpoint

```python
from collections.abc import AsyncIterable
from fastapi import FastAPI
from fastapi.sse import EventSourceResponse
from pydantic import BaseModel

app = FastAPI()

class Item(BaseModel):
    name: str
    description: str | None = None

items = [
    Item(name="Item1", description="First item"),
    Item(name="Item2", description="Second item"),
    Item(name="Item3", description="Third item"),
]

@app.get("/items/stream", response_class=EventSourceResponse)
async def streamItems() -> AsyncIterable[Item]:
    """Stream items as Server-Sent Events."""
    for item in items:
        yield item
```

### With Type Annotations

```python
@app.get("/items/stream", response_class=EventSourceResponse)
async def streamItems() -> AsyncIterable[Item]:
    """Return type hints enable validation and OpenAPI documentation."""
    for item in items:
        yield item
```

### Non-Async Variant

```python
from collections.abc import Iterable

@app.get("/items/stream", response_class=EventSourceResponse)
def streamItemsSync() -> Iterable[Item]:
    """Synchronous streaming (use for CPU-bound work only)."""
    for item in items:
        yield item
```

## Advanced SSE with ServerSentEvent

### Using ServerSentEvent for Control

```python
from fastapi.sse import ServerSentEvent, EventSourceResponse

@app.get("/items/stream", response_class=EventSourceResponse)
async def streamItemsAdvanced() -> AsyncIterable[ServerSentEvent]:
    """Stream with full control over SSE fields."""
    # Optional: Send a comment
    yield ServerSentEvent(comment="Starting item stream")
    
    for i, item in enumerate(items):
        # Send item data with event type and ID
        yield ServerSentEvent(
            data=item,
            event="item_update",
            id=str(i + 1),
            retry=5000  # 5 second retry interval
        )
```

### ServerSentEvent Fields

```python
# Complete ServerSentEvent example
yield ServerSentEvent(
    data={"message": "Hello"},        # JSON-encoded data
    event="custom_event",             # Event type identifier
    id="42",                           # Event ID for resuming
    retry=5000,                        # Milliseconds before retry
    comment="Processing item"         # Comment (not sent to client)
)
```

## Raw Data (Non-JSON) Streaming

### Streaming Plain Text

```python
@app.get("/logs/stream", response_class=EventSourceResponse)
async def streamLogs() -> AsyncIterable[ServerSentEvent]:
    """Stream raw text data (not JSON encoded)."""
    logs = [
        "2025-01-01 INFO  Application started",
        "2025-01-01 DEBUG Connected to database",
        "2025-01-01 WARN  High memory usage detected",
    ]
    
    for log_line in logs:
        yield ServerSentEvent(
            raw_data=log_line,  # Sent as plain text
            event="log"
        )
```

### Streaming with Sentinel Values

```python
@app.get("/process/stream", response_class=EventSourceResponse)
async def streamWithDone() -> AsyncIterable[ServerSentEvent]:
    """Stream data and signal completion."""
    for i in range(5):
        yield ServerSentEvent(
            data={"progress": i * 20},
            event="progress"
        )
    
    # Signal completion to client
    yield ServerSentEvent(raw_data="[DONE]", event="done")
```

## Event Resumption with Last-Event-ID

### Resumable Streams

```python
from typing import Annotated
from fastapi import Header

@app.get("/items/stream", response_class=EventSourceResponse)
async def streamItemsResumable(
    last_event_id: Annotated[int | None, Header()] = None
) -> AsyncIterable[ServerSentEvent]:
    """Stream that can resume from last received event."""
    start_index = (last_event_id + 1) if last_event_id is not None else 0
    
    for i, item in enumerate(items):
        if i < start_index:
            continue  # Skip already-received items
        
        yield ServerSentEvent(
            data=item,
            id=str(i),
            event="item"
        )
```

## SSE with POST Requests

### POST Endpoint Streaming

```python
@app.post("/chat/stream", response_class=EventSourceResponse)
async def streamChat(prompt: str) -> AsyncIterable[ServerSentEvent]:
    """Stream response to a chat prompt (e.g., from LLM)."""
    words = prompt.split()
    
    for word in words:
        yield ServerSentEvent(
            data=word,
            event="token"
        )
    
    # Signal done
    yield ServerSentEvent(raw_data="[DONE]", event="done")
```

### With Request Body

```python
from pydantic import BaseModel

class ChatRequest(BaseModel):
    message: str
    temperature: float = 0.7

@app.post("/chat/stream", response_class=EventSourceResponse)
async def streamChatWithBody(
    request: ChatRequest
) -> AsyncIterable[ServerSentEvent]:
    """Stream chat response with request validation."""
    # Process request
    response = await generateChatResponse(
        request.message,
        temperature=request.temperature
    )
    
    # Stream tokens
    for token in response.tokens:
        yield ServerSentEvent(data=token, event="token")
```

## Best Practices

### 1. Proper Error Handling

```python
@app.get("/stream", response_class=EventSourceResponse)
async def streamWithErrors() -> AsyncIterable[ServerSentEvent]:
    """Stream with error handling."""
    try:
        for i, item in enumerate(items):
            if item.problematic:
                # Log and skip
                logger.warning(f"Skipping problematic item: {item.id}")
                continue
            
            yield ServerSentEvent(data=item, id=str(i))
    except Exception as e:
        logger.error(f"Stream error: {e}")
        # Error is sent to client (browser closes connection)
        raise
```

### 2. Type Annotations for OpenAPI

```python
# GOOD: Type hints enable validation and documentation
@app.get("/stream", response_class=EventSourceResponse)
async def streamTyped() -> AsyncIterable[DataModel]:
    """Validated and documented streaming."""
    for item in getItems():
        yield item

# NOT RECOMMENDED: No type hints
@app.get("/stream", response_class=EventSourceResponse)
async def streamUntyped():
    """No validation or OpenAPI documentation."""
    for item in getItems():
        yield item
```

### 3. Client-Side Connection Handling

```javascript
// Client-side example (JavaScript)
const eventSource = new EventSource('/items/stream');

eventSource.addEventListener('item_update', (event) => {
    const data = JSON.parse(event.data);
    console.log('Item:', data);
});

eventSource.addEventListener('error', () => {
    console.error('Connection lost');
    eventSource.close();
});

// Auto-reconnects with Last-Event-ID
```

### 4. Graceful Shutdown

```python
@app.get("/stream", response_class=EventSourceResponse)
async def streamWithShutdown(background_tasks) -> AsyncIterable[ServerSentEvent]:
    """Handle graceful shutdown."""
    try:
        for item in generateItems():
            yield ServerSentEvent(data=item, event="data")
    except GeneratorExit:
        # Client disconnected or server shutting down
        logger.info("Stream closed")
        raise
```

### 5. Rate Limiting

```python
import asyncio

@app.get("/stream", response_class=EventSourceResponse)
async def streamThrottled() -> AsyncIterable[ServerSentEvent]:
    """Rate-limited streaming."""
    for i, item in enumerate(items):
        yield ServerSentEvent(data=item, id=str(i))
        # Add delay to prevent overwhelming clients
        await asyncio.sleep(0.1)
```

## Common Patterns

### Pattern 1: Progress Updates

```python
@app.get("/process/progress", response_class=EventSourceResponse)
async def streamProgress(task_id: str) -> AsyncIterable[ServerSentEvent]:
    """Stream processing progress."""
    task = getTask(task_id)
    
    for update in task.getProgressUpdates():
        yield ServerSentEvent(
            data={
                "progress": update.percentage,
                "message": update.message
            },
            event="progress",
            id=str(update.step)
        )
```

### Pattern 2: Live Notifications

```python
@app.get("/notifications", response_class=EventSourceResponse)
async def streamNotifications(user_id: int) -> AsyncIterable[ServerSentEvent]:
    """Stream notifications for a user."""
    notification_queue = await getUserNotificationQueue(user_id)
    
    async for notification in notification_queue:
        yield ServerSentEvent(
            data=notification.dict(),
            event="notification",
            id=str(notification.id)
        )
```

### Pattern 3: LLM Response Streaming

```python
@app.post("/llm/stream", response_class=EventSourceResponse)
async def streamLLMResponse(prompt: str) -> AsyncIterable[ServerSentEvent]:
    """Stream token-by-token LLM response."""
    async for token in getLLMTokenStream(prompt):
        yield ServerSentEvent(
            data=token,
            event="token"
        )
    
    yield ServerSentEvent(raw_data="[DONE]", event="done")
```

### Pattern 4: Database Changes

```python
@app.get("/db/changes", response_class=EventSourceResponse)
async def streamDatabaseChanges() -> AsyncIterable[ServerSentEvent]:
    """Stream real-time database changes."""
    async with startChangeStream() as stream:
        async for change in stream:
            yield ServerSentEvent(
                data={
                    "operation": change.operation,
                    "collection": change.collection,
                    "document": change.document
                },
                event="db_change"
            )
```

## Testing SSE Endpoints

```python
from fastapi.testclient import TestClient
import json

client = TestClient(app)

def test_sse_endpoint():
    """Test an SSE endpoint."""
    response = client.get("/items/stream")
    assert response.status_code == 200
    assert "text/event-stream" in response.headers["content-type"]
    
    # Parse SSE format
    lines = response.text.strip().split("\n")
    for line in lines:
        if line.startswith("data:"):
            data = json.loads(line[5:].strip())
            assert "name" in data
```

## Built-in SSE Features

FastAPI automatically handles:

1. **Keep-Alive Pings**: Sends a comment ping every 15 seconds to keep connection alive
2. **Cache Control**: Sets `Cache-Control: no-cache` to prevent caching
3. **Proxy Buffering Prevention**: Sets `X-Accel-Buffering: no` for Nginx compatibility

These are transparent and automatic.

## Troubleshooting

### Issue: Connection closes immediately

**Solution**: Ensure you're yielding data and handling errors properly.

```python
@app.get("/stream", response_class=EventSourceResponse)
async def streamFixed() -> AsyncIterable[ServerSentEvent]:
    """Ensure items are being yielded."""
    for item in items:
        yield ServerSentEvent(data=item)
        # Don't break out of loop early
```

### Issue: Browser shows "Connection lost"

**Solution**: Check server logs and ensure proper error handling.

```python
@app.get("/stream", response_class=EventSourceResponse)
async def streamReliable() -> AsyncIterable[ServerSentEvent]:
    """Reliable streaming with proper error handling."""
    try:
        for item in items:
            yield ServerSentEvent(data=item)
    except Exception as e:
        logger.error(f"Streaming failed: {e}")
        # Connection will close, browser will retry
        raise
```

## Differences: SSE vs WebSockets

| Feature | SSE | WebSocket |
|---------|-----|-----------|
| Direction | Server→Client only | Bidirectional |
| Browser Support | Native (EventSource) | Native (WebSocket) |
| Reconnection | Automatic | Manual |
| Events | Supported (`event:` field) | Manual routing |
| Use Case | One-way streaming | Two-way communication |
| Complexity | Simple | More complex |

**Use SSE** for: Notifications, data feeds, progress updates, one-way streaming.

**Use WebSockets** for: Chat, real-time collaboration, bidirectional communication.

## References

- [FastAPI SSE Documentation](https://fastapi.tiangolo.com/tutorial/server-sent-events/)
- [HTML Spec: Server-Sent Events](https://html.spec.whatwg.org/multipage/server-sent-events.html)
- [MDN: EventSource API](https://developer.mozilla.org/en-US/docs/Web/API/EventSource)
- [Starlette EventSourceResponse](https://www.starlette.dev/responses/#event-source-response)
