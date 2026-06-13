import pytest

from app.services.supertonic.supertonic import _trim_for_speech


def test_trim_for_speech_short_text():
    assert _trim_for_speech("Hello world.", 100) == "Hello world."


def test_trim_for_speech_long_text():
    text = "word " * 400
    trimmed = _trim_for_speech(text, 120)
    assert len(trimmed) <= 121
    assert trimmed.endswith(".")


@pytest.mark.skipif(True, reason="Downloads TTS model on first run")
def test_synthesize_wav_base64_smoke():
    from app.services.supertonic.supertonic import synthesize_wav_base64

    encoded = synthesize_wav_base64("Hello from Perai.")
    assert encoded
    assert len(encoded) > 100
