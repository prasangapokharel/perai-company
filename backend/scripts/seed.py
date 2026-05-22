"""Database seed script for testing."""

import sys
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from app.core.database import SessionLocal, init_db
from app.models.company import Company, CompanyFinetune
from datetime import datetime


def seed_database():
    """Seed database with test data."""
    init_db()
    db = SessionLocal()

    try:
        # Check if data already exists
        existing = db.query(Company).first()
        if existing:
            print("✓ Database already seeded. Skipping.")
            return

        # Create test companies
        companies = [
            Company(
                company_name="Acme Corp",
                company_email="contact@acme.com",
                password_hash="hashed_password_1",
                logo="https://example.com/logo1.png",
                website="https://acme.com",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
            Company(
                company_name="TechStart Inc",
                company_email="hello@techstart.io",
                password_hash="hashed_password_2",
                logo="https://example.com/logo2.png",
                website="https://techstart.io",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
            Company(
                company_name="AI Innovations",
                company_email="info@aiinnovations.com",
                password_hash="hashed_password_3",
                logo="https://example.com/logo3.png",
                website="https://aiinnovations.com",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
        ]

        db.add_all(companies)
        db.commit()

        # Refresh to get IDs
        for company in companies:
            db.refresh(company)

        print(f"✓ Created {len(companies)} test companies")

        # Create test finetune records
        finetubes = [
            CompanyFinetune(
                company_id=companies[0].id,
                rag_company_path=f"backend/app/core/finetune/rag/companies/{companies[0].id}/company.md",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
            CompanyFinetune(
                company_id=companies[1].id,
                rag_company_path=f"backend/app/core/finetune/rag/companies/{companies[1].id}/company.md",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
            CompanyFinetune(
                company_id=companies[2].id,
                rag_company_path=f"backend/app/core/finetune/rag/companies/{companies[2].id}/company.md",
                created_at=datetime.utcnow(),
                updated_at=datetime.utcnow(),
            ),
        ]

        db.add_all(finetubes)
        db.commit()

        print(f"✓ Created {len(finetubes)} test finetune records")

        # Print seeded data
        print("\n✓ Seed Data Summary:")
        for i, company in enumerate(companies, 1):
            print(f"  {i}. ID: {company.id}, Name: {company.company_name}, Email: {company.company_email}")

        print("\n✓ Database seeding complete!")

    except Exception as e:
        print(f"✗ Error seeding database: {e}")
        db.rollback()
    finally:
        db.close()


if __name__ == "__main__":
    seed_database()
