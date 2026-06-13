import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent))

from app.core.database import SessionLocal, init_db
from app.models.company import Company, CompanyFinetune
from app.utils.password import hash_password

SEED_COMPANIES = [
    {
        "company_name": "Acme Corp",
        "company_email": "contact@acme.com",
        "password": "acme1234",
        "website": "https://acme.com",
    },
    {
        "company_name": "TechStart Inc",
        "company_email": "hello@techstart.io",
        "password": "techstart1234",
        "website": "https://techstart.io",
    },
    {
        "company_name": "AI Innovations",
        "company_email": "info@aiinnovations.com",
        "password": "aiinno1234",
        "website": "https://aiinnovations.com",
    },
]


def seed_database():
    init_db()
    db = SessionLocal()
    try:
        if db.query(Company).first():
            print("Database already seeded. Skipping.")
            return

        companies = [
            Company(
                company_name=c["company_name"],
                company_email=c["company_email"],
                password_hash=hash_password(c["password"]),
                website=c["website"],
            )
            for c in SEED_COMPANIES
        ]
        db.add_all(companies)
        db.commit()
        for co in companies:
            db.refresh(co)

        finetunes = [
            CompanyFinetune(
                company_id=co.id,
                company_model_name=f"perai-{co.company_name.lower().replace(' ', '-')}",
                rag_company_path=f"app/core/finetune/rag/companies/{co.id}/company.md",
            )
            for co in companies
        ]
        db.add_all(finetunes)
        db.commit()

        print(f"Seeded {len(companies)} companies.")
        for co in companies:
            print(f"  id={co.id}  name={co.company_name}  email={co.company_email}")
    except Exception as exc:
        print(f"Seed failed: {exc}")
        db.rollback()
    finally:
        db.close()


if __name__ == "__main__":
    seed_database()
