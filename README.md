# Perai

Multi-tenant B2B platform for company-branded AI chat. Upload a JSONL knowledge base, embed a widget or call the REST API, and bill by token usage.

## Features

- **Knowledge base (finetune)** — JSONL upload with append/replace; vectorless RAG (match-only retrieval, low token cost)
- **Chat API** — Groq-powered replies with company tone, language, and RAG context
- **API keys** — `X-API-Key` auth for chat, finetune, and integrations
- **Optional TTS** — `"audio": true` on chat query returns WAV (Supertonic)
- **Dashboard** — Balance, sessions, analytics, widget embed, developer snippets
- **Short answers by default** — System prompt tuned for direct 1–3 sentence replies

## Stack

| Layer | Tech |
|-------|------|
| Backend | FastAPI, SQLAlchemy, Alembic, Groq |
| Frontend | Next.js, shadcn/ui |
| Database | PostgreSQL (Supabase) |
| RAG | File-based BM25 + exact ID/name match |

## Quick start

### Backend

```bash
cd backend
python -m venv venv && source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env   # set DB_URL, GROQ_API_KEY, JWT_SECRET
alembic upgrade head
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

API docs: http://localhost:8000/docs

### Frontend

```bash
cd frontend
npm install
npm run dev
```

App: http://localhost:3000

### Example (API key + chat + audio)

```python
import base64
import requests

r = requests.post(
    "http://localhost:8000/api/v1/company/20/chat/query",
    headers={"Content-Type": "application/json", "X-API-Key": "YOUR_API_KEY"},
    json={"prompt": "What are your pricing plans?", "audio": True},
    timeout=120,
)
data = r.json()
print(data["response"])
if data.get("audio_base64"):
    open("reply.wav", "wb").write(base64.b64decode(data["audio_base64"]))
```

## Project layout

```
backend/          FastAPI app, RAG, migrations, tests
frontend/         Next.js company portal + widget
backend/py/       Local TTS experiments (Supertonic / ONNX)
```

## Environment (backend)

| Variable | Purpose |
|----------|---------|
| `DB_URL` | Primary Postgres URL (Supabase pooler) |
| `DB_URL_FALLBACK` | Direct Supabase host if pooler fails |
| `DB_HOST` / `DB_USER` | Pooler host and user (auto-builds URL if `DB_URL` omitted) |
| `GROQ_API_KEY` | Groq API key (supports `GROQ_API_KEY2`–`5` fallback) |
| `JWT_SECRET` | Dashboard JWT signing |
| `RAG_TOP_K` | Max RAG records per query (default `3`) |
| `CHAT_COMPLETION_CAP` | Max reply tokens (default `400`) |
| `TTS_VOICE` | Supertonic voice (default `M4`) |

Never commit `.env` or API keys to git.

## Author

**Prasanga Pokharel**

- Email: [prasangaramanpokharel@gmail.com](mailto:prasangaramanpokharel@gmail.com)
- GitHub: [prasangapokharel/perai-company](https://github.com/prasangapokharel/perai-company)

## License

Private / all rights reserved unless otherwise noted.
