from decimal import Decimal

from app.utils.token_cost import calculate_usd_cost, estimate_max_usd_cost


def test_calculate_usd_cost_per_1k_tokens():
    cost = calculate_usd_cost(1000, 500)
    assert cost == Decimal("0.000450")


def test_calculate_usd_cost_zero_tokens():
    assert calculate_usd_cost(0, 0) == Decimal("0.000000")


def test_estimate_max_usd_cost():
    cost = estimate_max_usd_cost(2000, 1000)
    assert cost == Decimal("0.000900")
