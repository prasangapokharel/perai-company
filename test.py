import base64
import requests

API_BASE = "http://localhost:8000/api/v1"
COMPANY_ID = 20
API_KEY = "sk_TnaS2BLk6KOvL3iryKD1a06FpMW-kivtai-oWuEeHA4"

response = requests.post(
    f"{API_BASE}/company/{COMPANY_ID}/chat/query",
    headers={
        "Content-Type": "application/json",
        "X-API-Key": API_KEY,
    },
    json={
        "prompt": "What are your pricing plans?",
        "audio": True,
    },
    timeout=120,
)
response.raise_for_status()
data = response.json()
print(data["response"])

if data.get("audio_base64"):
    wav_bytes = base64.b64decode(data["audio_base64"])
    with open("reply.wav", "wb") as f:
        f.write(wav_bytes)
    print("Saved reply.wav")