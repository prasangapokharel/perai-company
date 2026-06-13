import base64
import io
import threading

import soundfile as sf

from app.core.config.config import TTS_MAX_CHARS, TTS_VOICE

_lock = threading.Lock()
_tts = None


def _get_tts():
    global _tts
    if _tts is None:
        from supertonic import TTS

        _tts = TTS(auto_download=True)
    return _tts


def _trim_for_speech(text: str, max_chars: int) -> str:
    cleaned = " ".join(text.split()).strip()
    if not cleaned:
        raise ValueError("Empty text for speech synthesis")
    if len(cleaned) <= max_chars:
        return cleaned
    trimmed = cleaned[:max_chars]
    last_space = trimmed.rfind(" ")
    if last_space > 40:
        trimmed = trimmed[:last_space]
    return trimmed.rstrip(".,;:!? ") + "."


def synthesize_wav_base64(text: str, voice_name: str | None = None) -> str:
    cleaned = _trim_for_speech(text, TTS_MAX_CHARS)
    voice = voice_name or TTS_VOICE

    with _lock:
        tts = _get_tts()
        style = tts.get_voice_style(voice_name=voice)
        wav, duration = tts.synthesize(cleaned, voice_style=style)

    sample_rate = tts.sample_rate
    samples = max(1, int(sample_rate * float(duration[0])))
    audio = wav[0, :samples]

    buf = io.BytesIO()
    sf.write(buf, audio, sample_rate, format="WAV")
    return base64.b64encode(buf.getvalue()).decode("ascii")
