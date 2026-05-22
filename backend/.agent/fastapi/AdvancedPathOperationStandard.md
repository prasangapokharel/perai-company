# Advanced Path Operation Configuration Standard

## Overview

This standard covers advanced configuration options for path operations (endpoints), including OpenAPI customization, operation IDs, response configurations, and OpenAPI extensions.

## Key Features

- **operationId**: Custom identifier for operations
- **include_in_schema**: Control OpenAPI documentation inclusion
- **openapi_extra**: Add custom OpenAPI extensions and modifications
- **summary/description**: Rich endpoint documentation
- **tags**: Group related endpoints in documentation
- **deprecated**: Mark endpoints as deprecated

## operationId Configuration

### Auto-Generated from Function Name

```python
from fastapi import FastAPI

app = FastAPI()

@app.get("/items/")
async def getItems():
    """Get all items."""
    return []

# operationId auto-generated: "getItems"
```

### Custom operationId

```python
@app.get("/items/", operation_id="list_all_items")
async def getItems():
    """Get all items."""
    return []

# operationId: "list_all_items"
```

### Override All operationIds

```python
from fastapi import FastAPI
from fastapi.routing import APIRoute

app = FastAPI()

@app.get("/items/")
async def getItems():
    return []

@app.post("/items/")
async def createItem():
    return {"id": 1}

def useRouteNamesAsOperationIds(app: FastAPI) -> None:
    """Use function names as operationIds."""
    for route in app.routes:
        if isinstance(route, APIRoute):
            route.operation_id = route.name

# Call after all routes are defined
useRouteNamesAsOperationIds(app)
```

## Excluding from OpenAPI Schema

### Hide Endpoint from Documentation

```python
@app.get("/internal-endpoint", include_in_schema=False)
async def internalEndpoint():
    """This endpoint won't appear in OpenAPI docs."""
    return {"internal": "data"}
```

### Use Case

```python
# Hide internal/debugging endpoints
@app.get("/debug", include_in_schema=False)
async def debugEndpoint():
    return app.openapi()

# Hide deprecated endpoints still in use
@app.get("/old-endpoint", include_in_schema=False)
async def oldEndpoint():
    return {"warning": "Use /new-endpoint instead"}
```

## Rich Endpoint Documentation

### Summary and Description

```python
@app.get(
    "/items/",
    summary="Get all items",
    description="""
    Retrieve all available items from the database.
    
    - Supports pagination with offset and limit
    - Results are sorted by creation date
    - Only shows items visible to current user
    """,
    response_description="List of items"
)
async def getItems(offset: int = 0, limit: int = 10):
    """Get all items with pagination."""
    return []
```

### Docstring Truncation

```python
@app.post("/items/", summary="Create new item")
async def createItem(item: Item) -> Item:
    """
    Create a new item in the database.
    
    ### Request
    - **name**: Item name (required)
    - **description**: Item description (optional)
    
    ### Response
    Returns the created item with generated ID
    
    ### Example
    ```json
    {
      "id": 123,
      "name": "Widget",
      "description": "A useful widget"
    }
    ```
    
    \\f
    :param item: Item data from request
    :return: Created item with ID
    """
    return item
```

The `\f` marker truncates the docstring for OpenAPI (everything after won't appear in docs).

## Tags for Grouping

### Basic Tagging

```python
@app.get("/users/", tags=["users"])
async def getUsers():
    return []

@app.post("/users/", tags=["users"])
async def createUser():
    return {"id": 1}

@app.get("/items/", tags=["items"])
async def getItems():
    return []
```

### Multiple Tags

```python
@app.get(
    "/premium-items/",
    tags=["items", "premium"]
)
async def getPremiumItems():
    return []
```

## Deprecated Endpoints

### Marking as Deprecated

```python
@app.get(
    "/old-items/",
    deprecated=True,
    summary="Deprecated: Use /items/ instead"
)
async def oldGetItems():
    """This endpoint is deprecated."""
    return []
```

### Gentle Deprecation

```python
@app.get(
    "/legacy-endpoint/",
    deprecated=True,
    tags=["deprecated"],
    description="This endpoint will be removed in v2.0. Use /new-endpoint/ instead."
)
async def legacyEndpoint():
    return {"warning": "This endpoint is deprecated"}
```

## OpenAPI Extra Configuration

### Adding Custom Fields

```python
@app.get(
    "/items/",
    openapi_extra={
        "x-custom-header": "value",
        "x-requires-auth": True
    }
)
async def getItems():
    return []
```

### OpenAPI Extensions

```python
@app.post(
    "/items/",
    openapi_extra={
        "x-code-samples": [
            {
                "lang": "python",
                "source": "client.items.create(name='widget')"
            },
            {
                "lang": "javascript",
                "source": "await client.items.create({name: 'widget'})"
            }
        ]
    }
)
async def createItem(item: Item):
    return item
```

### Custom Request Schema

```python
@app.post(
    "/items/",
    openapi_extra={
        "requestBody": {
            "content": {
                "application/json": {
                    "schema": {
                        "required": ["name", "price"],
                        "type": "object",
                        "properties": {
                            "name": {"type": "string"},
                            "price": {"type": "number"},
                            "tags": {
                                "type": "array",
                                "items": {"type": "string"}
                            }
                        },
                        "example": {
                            "name": "Widget",
                            "price": 9.99,
                            "tags": ["electronics", "useful"]
                        }
                    }
                }
            }
        }
    }
)
async def createItem(request: Request):
    data = await request.json()
    return {"created": True}
```

### Custom Content Type

```python
@app.post(
    "/upload-yaml/",
    openapi_extra={
        "requestBody": {
            "content": {
                "application/x-yaml": {
                    "schema": Item.model_json_schema()
                }
            }
        }
    }
)
async def uploadYaml(request: Request):
    import yaml
    body = await request.body()
    data = yaml.safe_load(body)
    return data
```

## Advanced Response Configuration

### Multiple Response Codes

```python
from fastapi import HTTPException

@app.post(
    "/items/",
    status_code=201,
    responses={
        200: {"description": "Item exists"},
        201: {"description": "Item created"},
        400: {"description": "Invalid input"},
        409: {"description": "Item already exists"}
    }
)
async def createItem(item: Item):
    existing = findItem(item.id)
    if existing:
        return {"message": "Item exists", "id": item.id}
    return item
```

### Response Headers

```python
@app.get(
    "/items/",
    responses={
        200: {
            "headers": {
                "X-Total-Count": {
                    "description": "Total number of items",
                    "schema": {"type": "integer"}
                },
                "X-Page": {
                    "description": "Current page number",
                    "schema": {"type": "integer"}
                }
            }
        }
    }
)
async def getItems():
    return []
```

## Common Patterns

### Pattern 1: API Versioning

```python
@app.get(
    "/v1/items/",
    summary="Get items (v1)",
    description="Legacy endpoint. Use /v2/items/ for new features.",
    deprecated=True,
    tags=["v1"]
)
async def getItemsV1():
    return []

@app.get(
    "/v2/items/",
    summary="Get items (v2)",
    description="Latest version with advanced filtering",
    tags=["v2"]
)
async def getItemsV2(filter: str = None):
    return []
```

### Pattern 2: Feature Flags

```python
import os

@app.get(
    "/experimental/items/",
    include_in_schema=os.getenv("ENABLE_EXPERIMENTAL") == "true",
    summary="Get items (experimental feature)"
)
async def getItemsExperimental():
    return []
```

### Pattern 3: Conditional Documentation

```python
def createItemEndpoint():
    extra = {}
    
    if os.getenv("ENVIRONMENT") == "development":
        extra["x-debug"] = True
        extra["x-internal-notes"] = "This is an internal endpoint"
    
    return extra

@app.post(
    "/items/",
    openapi_extra=createItemEndpoint()
)
async def createItem(item: Item):
    return item
```

### Pattern 4: Rate Limiting Documentation

```python
@app.get(
    "/premium/data/",
    openapi_extra={
        "x-rate-limit": {
            "requests": 1000,
            "per": "hour"
        }
    }
)
async def getPremiumData():
    return {"data": []}
```

### Pattern 5: Security Documentation

```python
from fastapi import Security, HTTPBearer

security = HTTPBearer()

@app.get(
    "/secure-data/",
    security_scopes=["admin"],
    responses={
        401: {"description": "Not authenticated"},
        403: {"description": "Not authorized"}
    }
)
async def getSecureData(token = Security(security)):
    return {"data": "secret"}
```

## Best Practices

### 1. Consistent Naming

```python
# GOOD: Clear, consistent naming
@app.get("/items/", operation_id="list_items")
@app.post("/items/", operation_id="create_item")
@app.get("/items/{item_id}", operation_id="get_item")
@app.put("/items/{item_id}", operation_id="update_item")
@app.delete("/items/{item_id}", operation_id="delete_item")

# NOT RECOMMENDED: Inconsistent
@app.get("/items/", operation_id="getAll")
@app.post("/items/", operation_id="add_new_item")
@app.get("/items/{item_id}", operation_id="fetch_by_id")
```

### 2. Clear Descriptions

```python
# GOOD: Detailed, helpful descriptions
@app.get(
    "/items/",
    summary="List all items",
    description="Returns paginated list of items. Supports filtering by category and price range."
)

# NOT RECOMMENDED: Vague descriptions
@app.get(
    "/items/",
    summary="Get items",
    description="Returns items"
)
```

### 3. Proper Tagging

```python
# GOOD: Logical grouping
@app.get("/users/", tags=["users"])
@app.get("/users/{user_id}", tags=["users"])
@app.post("/items/", tags=["items"])

# NOT RECOMMENDED: Too many tags
@app.get("/users/", tags=["users", "admin", "api", "v1"])
```

## Testing Advanced Configurations

```python
from fastapi.testclient import TestClient

client = TestClient(app)

def test_operation_id():
    """Test custom operationId."""
    response = client.get("/openapi.json")
    assert response.json()["paths"]["/items/"]["get"]["operationId"] == "getItems"

def test_deprecated_endpoint():
    """Test deprecated endpoint still works."""
    response = client.get("/old-items/")
    assert response.status_code == 200

def test_openapi_extra():
    """Test custom OpenAPI fields."""
    response = client.get("/openapi.json")
    path_spec = response.json()["paths"]["/items/"]["get"]
    assert "x-custom-header" in path_spec
```

## Migration Guide: Updating Endpoints

### Before

```python
@app.get("/items/")
async def getItems():
    return []
```

### After (with full configuration)

```python
@app.get(
    "/items/",
    operation_id="list_items",
    summary="List all items",
    description="Returns a paginated list of all items",
    tags=["items"],
    responses={
        200: {
            "description": "Successful retrieval",
            "content": {
                "application/json": {
                    "example": [{"id": 1, "name": "Widget"}]
                }
            }
        }
    }
)
async def listItems(offset: int = 0, limit: int = 10):
    """
    Retrieve all items with pagination support.
    
    **Query Parameters:**
    - offset: Number of items to skip
    - limit: Maximum items to return (max 100)
    """
    return []
```

## References

- [FastAPI Path Operation Configuration](https://fastapi.tiangolo.com/advanced/path-operation-advanced-configuration/)
- [OpenAPI Specification](https://github.com/OAI/OpenAPI-Specification)
- [FastAPI Tags](https://fastapi.tiangolo.com/tutorial/path-operation-configuration/#tags)
- [OpenAPI Extensions](https://spec.openapis.org/oas/v3.0.3#specification-extensions)
