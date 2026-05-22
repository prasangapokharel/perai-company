# Server Workers Standard

## Overview

Running multiple worker processes allows your FastAPI application to handle more requests by leveraging multiple CPU cores. This standard covers how to deploy FastAPI with multiple workers using Uvicorn or Gunicorn.

**Key Benefits**:
- Better CPU utilization (multiple cores)
- Higher request throughput
- Graceful handling of concurrent requests
- Improved fault tolerance

## Single vs Multiple Workers

### Single Worker (Development)

```bash
# Single process (suitable for development)
uvicorn main:app --host 0.0.0.0 --port 8000
```

**When to use**:
- Development environment
- Testing
- Debugging

### Multiple Workers (Production)

```bash
# Multiple processes (production-ready)
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

**When to use**:
- Production environments
- High-traffic scenarios
- Multi-core systems

## Calculating Number of Workers

### Formula

```
Number of Workers = (2 × CPU_cores) + 1
```

### Examples

```bash
# Single core machine
# (2 × 1) + 1 = 3 workers
uvicorn main:app --workers 3

# Dual core machine  
# (2 × 2) + 1 = 5 workers
uvicorn main:app --workers 5

# Quad core machine
# (2 × 4) + 1 = 9 workers
uvicorn main:app --workers 9

# 8-core machine
# (2 × 8) + 1 = 17 workers
uvicorn main:app --workers 17
```

### Auto-Detection

```bash
# Let Uvicorn auto-detect based on CPU cores
uvicorn main:app --workers auto
```

## Using FastAPI CLI (Recommended)

### Basic Usage

```bash
# Start with 4 workers using FastAPI CLI
fastapi run --workers 4 main.py
```

### With Host and Port

```bash
# Specify host, port, and worker count
fastapi run --host 0.0.0.0 --port 8080 --workers 8 main.py
```

### With Reload (Development)

```bash
# Development: auto-reload and 1 worker
fastapi dev main.py
```

## Using Uvicorn Directly

### Basic Multi-Worker Setup

```bash
uvicorn main:app --host 0.0.0.0 --port 8000 --workers 4
```

### With Custom Settings

```bash
uvicorn main:app \
  --host 0.0.0.0 \
  --port 8000 \
  --workers 4 \
  --loop uvloop \
  --http httptools \
  --log-level info
```

### With Environment Variables

```bash
export UVICORN_WORKERS=4
export UVICORN_HOST=0.0.0.0
export UVICORN_PORT=8000

uvicorn main:app
```

## Using Gunicorn with Uvicorn Workers

### Basic Gunicorn Setup

```bash
# Install gunicorn and uvicorn worker
pip install gunicorn uvicorn

# Run with gunicorn
gunicorn main:app \
  --workers 4 \
  --worker-class uvicorn.workers.UvicornWorker \
  --bind 0.0.0.0:8000
```

### Gunicorn Configuration File

Create `gunicorn_config.py`:

```python
# gunicorn_config.py
import multiprocessing

bind = "0.0.0.0:8000"
workers = (multiprocessing.cpu_count() * 2) + 1
worker_class = "uvicorn.workers.UvicornWorker"
worker_connections = 1000
max_requests = 1000
max_requests_jitter = 50
timeout = 30
keepalive = 5
log_level = "info"
```

Run with config:

```bash
gunicorn -c gunicorn_config.py main:app
```

## Production Deployment Patterns

### Pattern 1: Behind Nginx with Multiple Workers

```nginx
# nginx.conf
upstream fastapi {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}

server {
    listen 80;
    server_name example.com;

    location / {
        proxy_pass http://fastapi;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

```bash
# Run 4 separate worker processes
uvicorn main:app --port 8000 --host 127.0.0.1
uvicorn main:app --port 8001 --host 127.0.0.1
uvicorn main:app --port 8002 --host 127.0.0.1
uvicorn main:app --port 8003 --host 127.0.0.1
```

### Pattern 2: Docker with Multiple Workers

```dockerfile
# Dockerfile
FROM python:3.11

WORKDIR /app

COPY requirements.txt .
RUN pip install -r requirements.txt

COPY . .

# Run with 4 workers
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000", "--workers", "4"]
```

### Pattern 3: Systemd Service with Workers

```ini
# /etc/systemd/system/fastapi.service
[Unit]
Description=FastAPI Application
After=network.target

[Service]
Type=notify
User=fastapi
WorkingDirectory=/app
ExecStart=/app/venv/bin/uvicorn main:app \
    --host 0.0.0.0 \
    --port 8000 \
    --workers 4 \
    --log-config logging.yaml

Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable fastapi
sudo systemctl start fastapi
```

### Pattern 4: Supervisor Management

```ini
# /etc/supervisor/conf.d/fastapi.conf
[program:fastapi]
command=/app/venv/bin/uvicorn main:app --host 127.0.0.1 --port 8000 --workers 4
directory=/app
user=fastapi
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/fastapi.log
```

## Worker Lifecycle Management

### Graceful Shutdown

```python
# main.py
from contextlib import asynccontextmanager
from fastapi import FastAPI
import asyncio

@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Worker starting...")
    yield
    print("Worker shutting down...")
    # Cleanup code here
    await asyncio.sleep(1)  # Allow time for open connections to close

app = FastAPI(lifespan=lifespan)
```

### Handle Worker Signals

```python
import signal

def handle_sigterm(signum, frame):
    """Handle SIGTERM for graceful shutdown."""
    print("Received SIGTERM, initiating shutdown...")
    # Cleanup code

signal.signal(signal.SIGTERM, handle_sigterm)
```

## Monitoring and Logging

### Worker-Aware Logging

```python
import logging
import os

logger = logging.getLogger(__name__)

# Log which worker is handling requests
worker_id = os.environ.get("WORKER_ID", "unknown")

@app.get("/")
async def root():
    logger.info(f"Request handled by worker {worker_id}")
    return {"worker": worker_id}
```

### Health Check Endpoint

```python
@app.get("/health")
async def healthCheck():
    """Health check endpoint for load balancers."""
    return {
        "status": "healthy",
        "worker": os.getpid()
    }
```

### Metrics Collection

```python
from prometheus_client import Counter, generate_latest

requests_total = Counter("fastapi_requests_total", "Total requests")

@app.middleware("http")
async def addMetrics(request, call_next):
    requests_total.inc()
    return await call_next(request)

@app.get("/metrics")
async def metrics():
    return generate_latest()
```

## Performance Tuning

### Backlog Configuration

```bash
uvicorn main:app \
  --workers 4 \
  --backlog 2048  # Increase backlog for high concurrency
```

### Worker Connection Limits

```bash
# Gunicorn
gunicorn main:app \
  --workers 4 \
  --worker-connections 1000 \
  --worker-class uvicorn.workers.UvicornWorker
```

### Request Timeout

```bash
uvicorn main:app \
  --workers 4 \
  --timeout 30  # 30 second timeout per worker
```

## Session and State Management

### Shared State Pattern

```python
# Use Redis or database for shared state
import redis
from fastapi import FastAPI

redis_client = redis.Redis(host='localhost', port=6379)

app = FastAPI()

@app.get("/counter")
async def getCounter():
    """Counter shared across all workers."""
    value = redis_client.incr("counter")
    return {"count": value}
```

### Session Storage

```python
from fastapi_sessions.backends.session_backend import SessionBackend
from fastapi_sessions.frontends.implementations import SessionCookie

# Session data stored centrally (Redis or database)
# Accessible by all workers
```

## Common Issues and Solutions

### Issue: Worker Crashes

**Solution**: Monitor logs and set appropriate timeouts.

```bash
# Increase timeout for slow operations
uvicorn main:app --workers 4 --timeout 120

# Enable logging for debugging
uvicorn main:app --workers 4 --log-level debug
```

### Issue: High Memory Usage

**Solution**: Configure max requests per worker.

```bash
# Gunicorn: restart worker after N requests
gunicorn main:app \
  --workers 4 \
  --worker-class uvicorn.workers.UvicornWorker \
  --max-requests 1000 \
  --max-requests-jitter 50
```

### Issue: Uneven Load Distribution

**Solution**: Use proper load balancer (Nginx, HAProxy).

```nginx
# Use least connections load balancing
upstream fastapi {
    least_conn;
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
    server 127.0.0.1:8003;
}
```

## Deployment Checklist

- [ ] Determine correct number of workers for your hardware
- [ ] Configure proper logging across all workers
- [ ] Setup health check endpoints
- [ ] Implement graceful shutdown
- [ ] Configure load balancer for worker distribution
- [ ] Monitor worker memory and CPU usage
- [ ] Test failure scenarios (worker crash, restart)
- [ ] Setup alerts for worker failures
- [ ] Document your worker configuration
- [ ] Plan for horizontal scaling (multiple machines)

## Best Practices

### 1. Use Configuration Management

```python
# config.py
from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    workers: int = 4
    timeout: int = 30
    backlog: int = 2048
    
    class Config:
        env_file = ".env"

settings = Settings()
```

### 2. Implement Proper Error Handling

```python
@app.exception_handler(Exception)
async def globalExceptionHandler(request, exc):
    """Handle exceptions across all workers."""
    logger.error(f"Unhandled exception: {exc}")
    return {"detail": "Internal server error"}
```

### 3. Version Your Deployment

```bash
# Tag your deployment
APP_VERSION=1.0.0
uvicorn main:app --workers 4 --title "FastAPI v$APP_VERSION"
```

## References

- [Uvicorn Documentation](https://www.uvicorn.org/)
- [Gunicorn Documentation](https://docs.gunicorn.org/)
- [FastAPI Deployment](https://fastapi.tiangolo.com/deployment/)
- [Nginx Load Balancing](https://nginx.org/en/docs/http/load_balancing.html)
- [Supervisor Documentation](http://supervisord.org/)
