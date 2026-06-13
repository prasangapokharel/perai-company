from decimal import Decimal, ROUND_HALF_UP

from app.core.config.config import GROQ_MODEL_INPUT_COST, GROQ_MODEL_OUTPUT_COST

INPUT_USD_PER_1K = Decimal(GROQ_MODEL_INPUT_COST)
OUTPUT_USD_PER_1K = Decimal(GROQ_MODEL_OUTPUT_COST)
USD_SCALE = Decimal("0.000001")


def estimate_tokens(text: str) -> int:
    if not text:
        return 0
    return max(1, len(text) // 4)


def calculate_usd_cost(input_tokens: int, output_tokens: int) -> Decimal:
    input_tokens = max(int(input_tokens), 0)
    output_tokens = max(int(output_tokens), 0)
    cost = (Decimal(input_tokens) / Decimal(1000)) * INPUT_USD_PER_1K
    cost += (Decimal(output_tokens) / Decimal(1000)) * OUTPUT_USD_PER_1K
    return cost.quantize(USD_SCALE, rounding=ROUND_HALF_UP)


def estimate_max_usd_cost(input_tokens: int, max_output_tokens: int) -> Decimal:
    return calculate_usd_cost(input_tokens, max_output_tokens)
