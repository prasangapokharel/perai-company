from groq import Groq
client = Groq()
completion = client.chat.completions.create(
    model="llama-3.3-70b-versatile",
    messages=[
        {
            "role": "user",
            "content": "Explain why fast inference is critical for reasoning models"
        }
    ]
)
print(completion.choices[0].message.content)

pip install groq


1. In backend/.env set your GROQ_API_KEY (get it from https://console.groq.com/keys)
