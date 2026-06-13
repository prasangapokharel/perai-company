"""Company Settings API Service"""
from sqlalchemy.orm import Session
from app.models.companySettings import CompanySettings
from app.schemas.companyCustomSettings import (
    CompanyCustomSettingsCreate,
    CompanyCustomSettingsUpdate,
)


class CompanySettingsService:
    """Service for managing company settings"""

    @staticmethod
    def createOrUpdateSettings(
        db: Session, company_id: int, data: CompanyCustomSettingsCreate
    ) -> CompanySettings:
        """Create or update company settings"""
        settings = (
            db.query(CompanySettings).filter(CompanySettings.company_id == company_id).first()
        )

        if settings:
            # Update existing settings
            settings.language = data.language
            settings.tone = data.tone
            settings.max_tokens = data.max_tokens
        else:
            # Create new settings
            settings = CompanySettings(
                company_id=company_id,
                language=data.language,
                tone=data.tone,
                max_tokens=data.max_tokens,
            )
            db.add(settings)

        db.commit()
        db.refresh(settings)
        return settings

    @staticmethod
    def getSettingsByCompanyId(db: Session, company_id: int) -> CompanySettings:
        """Get settings by company ID"""
        return db.query(CompanySettings).filter(CompanySettings.company_id == company_id).first()

    @staticmethod
    def updateSettings(
        db: Session, company_id: int, data: CompanyCustomSettingsUpdate
    ) -> CompanySettings:
        """Update company settings"""
        settings = (
            db.query(CompanySettings).filter(CompanySettings.company_id == company_id).first()
        )

        if not settings:
            raise ValueError(f"Settings not found for company {company_id}")

        # Update only provided fields
        if data.language is not None:
            settings.language = data.language
        if data.tone is not None:
            settings.tone = data.tone
        if data.max_tokens is not None:
            settings.max_tokens = data.max_tokens

        db.commit()
        db.refresh(settings)
        return settings

    @staticmethod
    def deleteSettings(db: Session, company_id: int) -> bool:
        """Delete company settings"""
        settings = (
            db.query(CompanySettings).filter(CompanySettings.company_id == company_id).first()
        )

        if not settings:
            return False

        db.delete(settings)
        db.commit()
        return True

    @staticmethod
    def getOrCreateSettings(db: Session, company_id: int) -> CompanySettings:
        """Get existing settings or create default ones"""
        settings = (
            db.query(CompanySettings).filter(CompanySettings.company_id == company_id).first()
        )

        if not settings:
            # Create default settings
            settings = CompanySettings(
                company_id=company_id, language="english", tone="formal", max_tokens=1000
            )
            db.add(settings)
            db.commit()
            db.refresh(settings)

        return settings
