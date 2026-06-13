from decimal import Decimal

from sqlalchemy.orm import Session

from app.core.config.config import DEFAULT_COMPANY_BALANCE
from app.models.balance import CompanyBalance
from app.models.balance_deduct import BalanceDeduct
from app.models.balance_topup import BalanceTopup

_STARTING_BALANCE = Decimal(DEFAULT_COMPANY_BALANCE)


class InsufficientBalanceError(ValueError):
    pass


def get_or_create_balance(db: Session, company_id: int) -> CompanyBalance:
    row = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .one_or_none()
    )
    if row:
        return row

    row = CompanyBalance(company_id=company_id, balance=_STARTING_BALANCE)
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


def create_initial_balance(db: Session, company_id: int) -> CompanyBalance:
    existing = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .one_or_none()
    )
    if existing:
        return existing

    row = CompanyBalance(company_id=company_id, balance=_STARTING_BALANCE)
    db.add(row)
    db.flush()
    return row


def get_balance(db: Session, company_id: int) -> Decimal:
    row = get_or_create_balance(db, company_id)
    return Decimal(str(row.balance))


def reserve_balance(db: Session, company_id: int, amount: Decimal) -> Decimal:
    if amount <= 0:
        return Decimal("0")

    row = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .with_for_update()
        .one_or_none()
    )
    if row is None:
        row = CompanyBalance(company_id=company_id, balance=_STARTING_BALANCE)
        db.add(row)
        db.flush()

    current = Decimal(str(row.balance))
    if current < amount:
        raise InsufficientBalanceError(
            f"Insufficient balance. Required {amount}, available {current}."
        )

    row.balance = current - amount
    db.commit()
    return amount


def release_reserve(db: Session, company_id: int, amount: Decimal) -> None:
    if amount <= 0:
        return

    row = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .with_for_update()
        .one_or_none()
    )
    if row is None:
        return

    row.balance = Decimal(str(row.balance)) + amount
    db.commit()


def finalize_deduction(
    db: Session,
    company_id: int,
    reserved: Decimal,
    actual: Decimal,
    session_id: str | None = None,
    chat_message_id: int | None = None,
    token_consume: int = 0,
    model_name: str | None = None,
) -> BalanceDeduct:
    actual = max(actual, Decimal("0"))
    reserved = max(reserved, Decimal("0"))

    row = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .with_for_update()
        .one_or_none()
    )
    if row is None:
        raise ValueError("Company balance account not found")

    refund = reserved - actual
    if refund > 0:
        row.balance = Decimal(str(row.balance)) + refund
    elif refund < 0:
        extra = abs(refund)
        current = Decimal(str(row.balance))
        if current < extra:
            raise InsufficientBalanceError("Insufficient balance after usage.")
        row.balance = current - extra

    deduct = BalanceDeduct(
        company_id=company_id,
        chat_message_id=chat_message_id,
        session_id=session_id,
        amount=actual,
        token_consume=token_consume,
        model_name=model_name,
    )
    db.add(deduct)
    db.commit()
    db.refresh(deduct)
    return deduct


def list_deductions(
    db: Session,
    company_id: int,
    limit: int = 50,
    offset: int = 0,
) -> list[BalanceDeduct]:
    return (
        db.query(BalanceDeduct)
        .filter(BalanceDeduct.company_id == company_id)
        .order_by(BalanceDeduct.created_at.desc())
        .offset(offset)
        .limit(min(limit, 100))
        .all()
    )


def list_topups(
    db: Session,
    company_id: int,
    limit: int = 50,
    offset: int = 0,
) -> list[BalanceTopup]:
    return (
        db.query(BalanceTopup)
        .filter(BalanceTopup.company_id == company_id)
        .order_by(BalanceTopup.created_at.desc())
        .offset(offset)
        .limit(min(limit, 100))
        .all()
    )


def topup_balance(
    db: Session,
    company_id: int,
    amount: Decimal,
    reference: str | None = None,
) -> tuple[Decimal, BalanceTopup]:
    if amount <= 0:
        raise ValueError("Top-up amount must be greater than zero")

    row = (
        db.query(CompanyBalance)
        .filter(CompanyBalance.company_id == company_id)
        .with_for_update()
        .one_or_none()
    )
    if row is None:
        row = CompanyBalance(company_id=company_id, balance=_STARTING_BALANCE)
        db.add(row)
        db.flush()

    row.balance = Decimal(str(row.balance)) + amount
    topup = BalanceTopup(company_id=company_id, amount=amount, reference=reference)
    db.add(topup)
    db.commit()
    db.refresh(topup)
    db.refresh(row)
    return Decimal(str(row.balance)), topup
