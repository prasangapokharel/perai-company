"""Company chat service."""

from sqlalchemy.orm import Session

from app.core.finetune.prompts.builder import build_company_system_prompt
from app.core.finetune.rag.main import load_training_file_for_company
from app.models.company import Company, CompanyFinetune


def get_company_prompt(db: Session, company_id: int) -> tuple[Company, str]:
    company = db.query(Company).filter(Company.id == company_id).one_or_none()
    if not company:
        raise ValueError("Company not found")

    finetune = (
        db.query(CompanyFinetune)
        .filter(CompanyFinetune.company_id == company_id)
        .one_or_none()
    )
    rag_text = load_training_file_for_company(company_id) if finetune else None

    return company, build_company_system_prompt(company, rag_text)
