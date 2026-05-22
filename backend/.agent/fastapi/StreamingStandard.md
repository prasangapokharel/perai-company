# Streaming Standard

## Overview

Streaming in FastAPI allows you to send data progressively to clients without waiting for the entire response to be ready. This is useful for:
- Large file transfers
- Real-time data feeds
- AI/LLM response streaming
- JSON Lines format data
- Progressive data processing

## Two Main Streaming Approaches

### 1. JSON Lines Streaming (Recommended for Structured Data)

**JSON Lines** sends one JSON object per line, separated by newline characters (`\n`). This is ideal when you have a sequence of structured data items.

**Content-Type**: `application/jsonl`

**Format**:
```json
{"name": "Item1", "description": "First item"}
{"name": "Item2", "description": "Second item"}
{"name": "Item3", "description": "Third item"}
```

### 2. Server-Sent Events (SSE) (Recommended for Real-Time Updates)

**SSE** uses the `text/event-stream` format with native browser support via `EventSource` API. Better for real-time streaming with retry logic and connection management.

**Content-Type**: `text/event-stream`

See SseStandard.md for detailed SSE implementation.

## JSON Lines Streaming Implementation

### Basic Pattern with Async

```python
from collections.abc import AsyncIterable
from fastapi import FastAPI
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

@app.get("/items/stream", response_class="application/jsonl")
async def streamItems() -> AsyncIterable[Item]:
    """Stream items as JSON Lines."""
    for item in items:
        yield item
```

### With Non-Async Function

```python
from collections.abc import Iterable

@app.get("/items/stream")
def streamItems() -> Iterable[Item]:
    """Stream items synchronously."""
    for item in items:
        yield item
```

### Without Type Annotation

```python
@app.get("/items/stream")
async def streamItems():
    """Stream items without explicit type annotation.
    
    FastAPI will use jsonable_encoder for serialization.
    """
    for item in items:
        yield item
```

## Best Practices

### 1. Always Declare Return Types

```python
# GOOD: Type hints enable validation and documentation
@app.get("/data/stream")
async def streamData() -> AsyncIterable[DataModel]:
    """Streams data with validation and OpenAPI documentation."""
    for item in getData():
        yield item

# NOT RECOMMENDED: No type hints means no validation
@app.get("/data/stream")
async def streamData():
    """No type hints = no validation."""
    for item in getData():
        yield item
```

### 2. Use Async When Possible

```python
# GOOD: Async function for non-blocking I/O
@app.get("/stream")
async def streamAsync() -> AsyncIterable[Item]:
    """Non-blocking, efficient streaming."""
    async for item in getItemsAsync():
        yield item

# Use sync only for CPU-intensive work
@app.get("/stream")
def streamSync() -> Iterable[Item]:
    """Use only when function is CPU-bound."""
    for item in getItems():
        yield item
```

### 3. Proper Error Handling

```python
@app.get("/items/stream")
async def streamItemsWithErrorHandling() -> AsyncIterable[Item]:
    """Stream with proper error handling."""
    try:
        for item in await getItems():
            yield item
    except ItemNotFoundError as e:
        # Log error appropriately
        logger.error(f"Streaming failed: {e}")
        raise HTTPException(status_code=500, detail="Stream processing failed")
```

### 4. Resource Management

```python
@app.get("/items/stream")
async def streamWithResources() -> AsyncIterable[Item]:
    """Properly manage external resources."""
    db = await getDatabase()
    try:
        async for item in db.streamItems():
            yield item
    finally:
        await db.close()
```

### 5. Pagination and Limiting

```python
@app.get("/items/stream")
async def streamItemsLimited(
    skip: int = 0,
    limit: int = 100
) -> AsyncIterable[Item]:
    """Stream items with pagination support."""
    count = 0
    for item in items[skip:]:
        if count >= limit:
            break
        yield item
        count += 1
```

## Common Patterns

### Pattern 1: Stream from Database

```python
@app.get("/users/stream")
async def streamUsers() -> AsyncIterable[UserModel]:
    """Stream all users from database."""
    async with session.begin():
        async for user in session.stream(select(UserModel)):
            yield user
```

### Pattern 2: Stream from File

```python
import asyncio

@app.get("/logs/stream")
async def streamLogFile(filename: str) -> AsyncIterable[str]:
    """Stream log file line by line."""
    async with asyncio.open(filename) as f:
        async for line in f:
            yield line.strip()
```

### Pattern 3: Stream with Transformation

```python
@app.get("/data/transform/stream")
async def streamTransformed() -> AsyncIterable[TransformedData]:
    """Stream raw data with transformations applied."""
    async for raw_item in getRawDataStream():
        transformed = await transformData(raw_item)
        yield transformed
```

### Pattern 4: Stream with Rate Limiting

```python
import asyncio

@app.get("/stream/throttled")
async def streamThrottled() -> AsyncIterable[Item]:
    """Stream with rate limiting to prevent overwhelming clients."""
    for item in items:
        yield item
        await asyncio.sleep(0.1)  # 100ms delay between items
```

## Performance Considerations

### 1. Use Pydantic for Validation

```python
# FastAPI validates and serializes using Rust side (faster)
@app.get("/stream")
async def streamWithValidation() -> AsyncIterable[ValidatedModel]:
    for item in items:
        yield item  # Validated by Pydantic in Rust
```

### 2. Memory Efficiency

```python
# GOOD: Generates items on-demand
@app.get("/large/stream")
async def streamLargeDataset() -> AsyncIterable[Item]:
    for item in generateItems():  # Generator doesn't load all in memory
        yield item

# NOT RECOMMENDED: Loads everything first
@app.get("/large/stream")
async def streamLargeDatasetBad() -> AsyncIterable[Item]:
    all_items = await fetchAllItems()  # Loads entire dataset in memory
    for item in all_items:
        yield item
```

## Testing Streaming Endpoints

```python
from fastapi.testclient import TestClient

client = TestClient(app)

def test_streaming_endpoint():
    """Test a streaming endpoint."""
    response = client.get("/items/stream")
    assert response.status_code == 200
    
    lines = response.text.strip().split("\n")
    assert len(lines) == 3
    
    for line in lines:
        data = json.loads(line)
        assert "name" in data
```

## Troubleshooting

### Issue: Streaming stops prematurely

**Solution**: Ensure generators are properly yielding all items and errors are handled.

```python
@app.get("/stream")
async def streamFixed() -> AsyncIterable[Item]:
    """Ensures all items are yielded."""
    try:
        for item in items:
            yield item
    except Exception as e:
        logger.error(f"Stream error: {e}")
        # Continue or re-raise based on requirements
```

### Issue: Clients receive partial data

**Solution**: Ensure proper error handling and resource cleanup.

```python
@app.get("/stream")
async def streamReliable() -> AsyncIterable[Item]:
    """Reliable streaming with cleanup."""
    resource = None
    try:
        resource = await acquireResource()
        for item in resource.getItems():
            yield item
    finally:
        if resource:
            await resource.cleanup()
```

## Differences: JSON Lines vs SSE

| Feature | JSON Lines | SSE |
|---------|-----------|-----|
| Format | One JSON per line | `data:` field format |
| Content-Type | `application/jsonl` | `text/event-stream` |
| Browser Support | Manual parsing needed | Native `EventSource` API |
| Retry Logic | Manual | Built-in `retry:` field |
| Event Types | Not supported | Supported via `event:` field |
| IDs for Resume | No built-in support | Built-in `id:` field |
| Use Case | Data streaming | Real-time updates |

**Choose JSON Lines when**: Streaming structured data items that clients parse manually or with libraries.

**Choose SSE when**: Streaming real-time updates with native browser support and automatic reconnection.

## References

- [FastAPI Streaming Documentation](https://fastapi.tiangolo.com/tutorial/stream-json-lines/)
- [Streaming Response in Starlette](https://www.starlette.dev/responses/#streamingresponse)
- [JSON Lines Format](https://jsonlines.org/)
