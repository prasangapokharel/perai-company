from pathlib import Path

from app.core.finetune.rag.main import _retrieve_from_text

COMPANY_20_KB = Path(__file__).resolve().parents[2] / "app/core/finetune/rag/companies/20/company.md"


def _kb() -> str:
    return COMPANY_20_KB.read_text(encoding="utf-8")


def test_literal_number_returns_only_matching_record():
    knowledge = (
        '{"title":"Roll 343562","content":"Student roll 343562 scored 92 percent in Class 12."}\n\n'
        '---\n\n'
        '{"title":"School history","content":"Founded in 1990 with 500 students and 40 teachers."}'
    )
    ctx, used = _retrieve_from_text(knowledge, "What is the result for 343562?")
    assert used
    assert "343562" in ctx
    assert "1990" not in ctx
    assert len(ctx) < 600


def test_no_match_returns_empty_context():
    knowledge = '{"title":"General","content":"School hours are 8am to 3pm."}'
    ctx, used = _retrieve_from_text(knowledge, "weather forecast")
    assert ctx == ""
    assert used is False


def test_empty_query_returns_empty_context():
    knowledge = '{"title":"General","content":"School hours are 8am to 3pm."}'
    ctx, used = _retrieve_from_text(knowledge, "")
    assert ctx == ""
    assert used is False


def test_andy_lambert_name_query():
    ctx, used = _retrieve_from_text(_kb(), "Who is Andy Lambert?")
    assert used
    assert "Andy Lambert" in ctx
    assert "Senior Engineer" in ctx
    assert "SPX-8305724" in ctx
    assert "Elon Musk" not in ctx
    assert len(ctx) < 500


def test_employee_id_query():
    ctx, used = _retrieve_from_text(_kb(), "SPX-8305724")
    assert used
    assert "Andy Lambert" in ctx
    assert "Avionics" in ctx


def test_spacex_general_query():
    ctx, used = _retrieve_from_text(_kb(), "What is SpaceX?")
    assert used
    assert "SpaceX" in ctx
    assert "Elon Musk" not in ctx or "Space Exploration" in ctx


def test_staff_list_query_includes_employees():
    ctx, used = _retrieve_from_text(_kb(), "Who are the key staff at SpaceX?")
    assert used
    assert "Employee ID" in ctx
    assert "SpaceX" in ctx
    assert len(ctx) < 1300
