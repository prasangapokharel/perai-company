# Static Files Standard

## Overview

Static files are files that don't change and are served as-is (CSS, JavaScript, images, fonts, etc.). FastAPI provides the `StaticFiles` middleware to serve these efficiently.

**Use Cases**:
- CSS and JavaScript files
- Images (PNG, JPG, SVG, etc.)
- Fonts
- HTML assets
- PDFs and documents
- Downloadable files

## Basic Setup

### Mount Static Files Directory

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

app = FastAPI()

# Mount static files directory
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.get("/")
async def root():
    return {"message": "Hello World"}
```

**Directory Structure**:
```
project/
├── main.py
└── static/
    ├── css/
    │   └── style.css
    ├── js/
    │   └── script.js
    └── images/
        └── logo.png
```

**Access URLs**:
- `/static/css/style.css`
- `/static/js/script.js`
- `/static/images/logo.png`

## Multiple Static Directories

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

app = FastAPI()

# Mount multiple directories
app.mount("/static", StaticFiles(directory="static"), name="static")
app.mount("/public", StaticFiles(directory="public"), name="public")
app.mount("/media", StaticFiles(directory="media"), name="media")
```

**Directory Structure**:
```
project/
├── main.py
├── static/          # /static/* routes
├── public/          # /public/* routes
└── media/           # /media/* routes
```

## Advanced Configuration

### 1. Custom Directory Path

```python
import os
from pathlib import Path

# Absolute path
static_dir = Path(__file__).parent / "static"
app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")

# Environment-based path
static_dir = os.getenv("STATIC_DIR", "static")
app.mount("/static", StaticFiles(directory=static_dir), name="static")
```

### 2. With HTML Index File

```python
# Serve index.html for directory access
app.mount(
    "/static",
    StaticFiles(directory="static", html=True),
    name="static"
)

# Now /static/ serves static/index.html
```

### 3. Custom Error Handling

```python
from starlette.staticfiles import StaticFiles
from starlette.responses import FileResponse
from starlette.middleware.base import BaseHTTPMiddleware

class CustomStaticFiles(StaticFiles):
    async def __call__(self, scope, receive, send):
        try:
            return await super().__call__(scope, receive, send)
        except FileNotFoundError:
            # Custom 404 handling
            response = FileResponse("static/404.html", status_code=404)
            return response(scope, receive, send)

app.mount("/static", CustomStaticFiles(directory="static"), name="static")
```

## Common Patterns

### Pattern 1: Web Assets Organization

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

app = FastAPI()

# CSS
app.mount("/css", StaticFiles(directory="static/css"), name="css")

# JavaScript
app.mount("/js", StaticFiles(directory="static/js"), name="js")

# Images
app.mount("/images", StaticFiles(directory="static/images"), name="images")

# Fonts
app.mount("/fonts", StaticFiles(directory="static/fonts"), name="fonts")
```

### Pattern 2: Environment-Specific Static Files

```python
import os
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

app = FastAPI()

env = os.getenv("ENVIRONMENT", "development")

if env == "production":
    # Minified files in production
    app.mount("/static", StaticFiles(directory="static/dist"), name="static")
else:
    # Raw files in development
    app.mount("/static", StaticFiles(directory="static/src"), name="static")
```

### Pattern 3: CDN Integration

```python
from fastapi import FastAPI
from fastapi.responses import RedirectResponse

app = FastAPI()

# In production, redirect to CDN
@app.get("/static/{file_path:path}")
async def serveStatic(file_path: str):
    cdn_url = "https://cdn.example.com"
    return RedirectResponse(url=f"{cdn_url}/{file_path}")
```

### Pattern 4: Versioned Static Files

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
import os

app = FastAPI()

# Serve versioned static files
# URLs like: /v1/static/css/style.css
version = os.getenv("APP_VERSION", "v1")
mount_path = f"/{version}/static"

app.mount(mount_path, StaticFiles(directory="static"), name="static")
```

### Pattern 5: Gzip Compression

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.gzip import GZIPMiddleware

app = FastAPI()

# Enable gzip compression for static files
app.add_middleware(GZIPMiddleware, minimum_size=1000)

app.mount("/static", StaticFiles(directory="static"), name="static")
```

## Serving HTML Single Page Apps (SPA)

### SPA with FastAPI Backend

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse

app = FastAPI()

# API routes
@app.get("/api/data")
async def getData():
    return {"data": "value"}

# Serve React/Vue/Angular SPA
app.mount("/", StaticFiles(directory="frontend/build", html=True), name="spa")
```

**Directory Structure**:
```
project/
├── main.py
├── frontend/
│   └── build/
│       ├── index.html
│       ├── css/
│       └── js/
```

## Performance Optimization

### 1. Caching Headers

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
from fastapi.middleware.base import BaseHTTPMiddleware

class CacheControlMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request, call_next):
        if request.url.path.startswith("/static/"):
            response = await call_next(request)
            response.headers["Cache-Control"] = "public, max-age=31536000"  # 1 year
            return response
        return await call_next(request)

app = FastAPI()
app.add_middleware(CacheControlMiddleware)
app.mount("/static", StaticFiles(directory="static"), name="static")
```

### 2. ETag Support

```python
from starlette.middleware.base import BaseHTTPMiddleware
import hashlib
import os

class ETagMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request, call_next):
        if request.url.path.startswith("/static/"):
            response = await call_next(request)
            # Add ETag for cache validation
            if "etag" not in response.headers:
                file_path = request.url.path.replace("/static/", "static/")
                if os.path.exists(file_path):
                    with open(file_path, "rb") as f:
                        etag = hashlib.md5(f.read()).hexdigest()
                        response.headers["ETag"] = f'"{etag}"'
            return response
        return await call_next(request)

app = FastAPI()
app.add_middleware(ETagMiddleware)
app.mount("/static", StaticFiles(directory="static"), name="static")
```

### 3. Content-Type Handling

```python
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles

# StaticFiles automatically detects MIME types
app.mount("/static", StaticFiles(directory="static"), name="static")

# Common MIME types handled:
# .js -> application/javascript
# .css -> text/css
# .html -> text/html
# .json -> application/json
# .png -> image/png
# .jpg -> image/jpeg
# .svg -> image/svg+xml
```

## Security Best Practices

### 1. Path Traversal Prevention

```python
# StaticFiles prevents path traversal attacks
# These would be blocked:
# /static/../../../etc/passwd
# /static/./././secret.txt

# StaticFiles automatically handles this
app.mount("/static", StaticFiles(directory="static"), name="static")
```

### 2. Hide Sensitive Files

```python
# Use .gitignore patterns to exclude files
# .env files, secrets, config files should not be in static/

# Correct structure:
# project/
# ├── main.py
# ├── .env               (NOT in static/)
# ├── secrets.json       (NOT in static/)
# └── static/
#     ├── css/
#     └── js/
```

### 3. Serve Only Public Files

```python
# Create separate public directory
# project/
# ├── main.py
# ├── private/           (NOT served)
# │   ├── .env
# │   └── config.yaml
# └── public/            (served via /static)
#     ├── css/
#     └── js/

app.mount("/static", StaticFiles(directory="public"), name="static")
```

## Testing Static Files

```python
from fastapi.testclient import TestClient

client = TestClient(app)

def test_static_files():
    """Test that static files are served."""
    response = client.get("/static/css/style.css")
    assert response.status_code == 200
    assert "text/css" in response.headers["content-type"]

def test_missing_static_file():
    """Test 404 for missing static file."""
    response = client.get("/static/nonexistent.css")
    assert response.status_code == 404
```

## Troubleshooting

### Issue: Static files return 404

**Solution**: Verify directory path and mount configuration.

```python
# CORRECT
import os
from pathlib import Path

static_dir = Path(__file__).parent / "static"
app.mount("/static", StaticFiles(directory=str(static_dir)), name="static")

# VERIFY directory exists
print(f"Static dir: {static_dir}")
print(f"Exists: {static_dir.exists()}")
print(f"Files: {list(static_dir.glob('*'))}")
```

### Issue: MIME type incorrect

**Solution**: StaticFiles auto-detects, but you can add custom types in Nginx/server.

```nginx
# Add to nginx.conf
types {
    application/wasm wasm;
    text/plain log;
}
```

### Issue: Large files slow to serve

**Solution**: Use CDN or reverse proxy (Nginx) to serve static files.

```nginx
# Let Nginx serve static files (better performance)
location /static/ {
    alias /app/static/;
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# FastAPI handles only API requests
location /api/ {
    proxy_pass http://fastapi:8000;
}
```

## Migration: From Flask/Django

### Flask

```python
# Flask
from flask import Flask
app = Flask(__name__, static_folder="static")

# FastAPI
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
app = FastAPI()
app.mount("/static", StaticFiles(directory="static"), name="static")
```

### Django

```python
# Django settings.py
STATIC_URL = "/static/"
STATIC_ROOT = "staticfiles/"

# FastAPI
from fastapi import FastAPI
from fastapi.staticfiles import StaticFiles
app = FastAPI()
app.mount("/static", StaticFiles(directory="staticfiles"), name="static")
```

## Performance Comparison

| Method | Speed | Scalability | Best For |
|--------|-------|-------------|----------|
| FastAPI StaticFiles | Medium | Single server | Development, small apps |
| Nginx reverse proxy | Fast | Multi-server | Production |
| CDN | Fastest | Global | Large deployments |
| AWS S3 + CloudFront | Fast | Global | Cloud-native |

## References

- [FastAPI Static Files](https://fastapi.tiangolo.com/tutorial/static-files/)
- [Starlette Static Files](https://www.starlette.dev/staticfiles/)
- [HTTP Caching Headers](https://developer.mozilla.org/en-US/docs/Web/HTTP/Caching)
- [MIME Types](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types)
- [Nginx Static Content](https://nginx.org/en/docs/beginners_guide/static.html)
