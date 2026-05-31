"""
Prompt Template Generator
Dynamically generates system prompts from templates with variable substitution.
Supports tone, language, and company customization.
"""

from pathlib import Path
from typing import Dict, Any, Optional
from enum import Enum


class LanguageEnum(str, Enum):
    """Supported languages for prompts."""
    ENGLISH = "english"
    NEPALI = "nepali"


class ToneEnum(str, Enum):
    """Supported tones for prompts."""
    FORMAL = "formal"
    CASUAL = "casual"
    FRIENDLY = "friendly"
    PROFESSIONAL = "professional"


class PromptTemplateGenerator:
    """
    Generates dynamic system prompts from templates.
    Handles tone, language, and company-specific customization.
    """
    
    TONE_INSTRUCTIONS = {
        ToneEnum.FORMAL: {
            "tone_description": "Professional, structured, and formal",
            "tone_description_for_context": "formal and structured",
            "instructions": (
                "Use formal language and professional terminology. "
                "Structure responses with clear sections and bullet points. "
                "Maintain a professional distance while being helpful. "
                "Use complete sentences and formal grammar."
            )
        },
        ToneEnum.CASUAL: {
            "tone_description": "Conversational, friendly, and everyday",
            "tone_description_for_context": "conversational and approachable",
            "instructions": (
                "Use everyday language and conversational tone. "
                "Be friendly and approachable. "
                "Feel free to use contractions and informal expressions. "
                "Make the response feel like talking to a knowledgeable colleague."
            )
        },
        ToneEnum.FRIENDLY: {
            "tone_description": "Warm, welcoming, and empathetic",
            "tone_description_for_context": "warm and empathetic",
            "instructions": (
                "Be warm and welcoming in your responses. "
                "Show empathy and understanding for the user's needs. "
                "Use positive language and encouraging tone. "
                "Make the user feel valued and supported."
            )
        },
        ToneEnum.PROFESSIONAL: {
            "tone_description": "Expert, authoritative, and knowledgeable",
            "tone_description_for_context": "expert and authoritative",
            "instructions": (
                "Position yourself as an expert in the field. "
                "Use authoritative but accessible language. "
                "Provide detailed, well-researched responses. "
                "Cite company-specific knowledge and expertise."
            )
        }
    }
    
    LANGUAGE_INSTRUCTIONS = {
        LanguageEnum.ENGLISH: {
            "language_description": "English",
            "instructions": (
                "Respond in English with clear, grammatically correct sentences. "
                "Use English-appropriate idioms and expressions. "
                "Ensure all technical terms are in English."
            )
        },
        LanguageEnum.NEPALI: {
            "language_description": "नेपाली (Nepali)",
            "instructions": (
                "नेपालीमा स्पष्ट र सही व्याकरणसहित जवाफ दिनुहोस्। "
                "(Respond in Nepali with clear, grammatically correct sentences.) "
                "नेपाली-उपयुक्त मुहावरा र अभिव्यक्ति प्रयोग गर्नुहोस्। "
                "(Use Nepali-appropriate idioms and expressions.) "
                "सबै प्राविधिक शब्दहरू नेपालीमा अनुवाद गर्नुहोस् वा व्याख्या गर्नुहोस्। "
                "(Translate or explain all technical terms in Nepali.)"
            )
        }
    }
    
    def __init__(self):
        """Initialize the prompt template generator."""
        self.template_path = Path(__file__).parent / "prompts" / "SystemPrompt.md"
        self.template_content = self._load_template()
    
    def _load_template(self) -> str:
        """Load the system prompt template from file."""
        if self.template_path.exists():
            return self.template_path.read_text(encoding="utf-8")
        else:
            raise FileNotFoundError(f"Template file not found: {self.template_path}")
    
    def generate_system_prompt(
        self,
        company_name: str,
        category: str,
        website: str,
        tone: ToneEnum = ToneEnum.FORMAL,
        language: LanguageEnum = LanguageEnum.ENGLISH,
        max_tokens: int = 1000,
        knowledge_block: str = "",
        fallback_contact: str = "Contact support"
    ) -> str:
        """
        Generate a complete system prompt with all variables substituted.
        
        Args:
            company_name: Name of the company
            category: Company category/industry
            website: Company website URL
            tone: Tone of the response (formal, casual, friendly, professional)
            language: Language for the response (english, nepali)
            max_tokens: Maximum tokens for response
            knowledge_block: Company-specific knowledge base text
            fallback_contact: Fallback contact message
        
        Returns:
            Complete system prompt with all variables substituted
        """
        tone_obj = ToneEnum(tone) if isinstance(tone, str) else tone
        language_obj = LanguageEnum(language) if isinstance(language, str) else language
        
        tone_data = self.TONE_INSTRUCTIONS[tone_obj]
        language_data = self.LANGUAGE_INSTRUCTIONS[language_obj]
        
        variables: Dict[str, str] = {
            "company_name": company_name,
            "category": category,
            "website": website,
            "tone": tone_obj.value,
            "language": language_obj.value,
            "max_tokens": str(max_tokens),
            "tone_description": tone_data["tone_description"],
            "tone_description_for_context": tone_data["tone_description_for_context"],
            "tone_instructions": tone_data["instructions"],
            "language_description": language_data["language_description"],
            "language_instructions": language_data["instructions"],
            "knowledge_block": knowledge_block if knowledge_block else "No specific knowledge base provided yet.",
            "fallback_contact": fallback_contact
        }
        
        prompt = self.template_content
        for key, value in variables.items():
            prompt = prompt.replace(f"{{{{{key}}}}}", value)
        
        return prompt
    
    def get_tone_instructions(self, tone: ToneEnum) -> str:
        """Get instructions for a specific tone."""
        tone_obj = ToneEnum(tone) if isinstance(tone, str) else tone
        return self.TONE_INSTRUCTIONS[tone_obj]["instructions"]
    
    def get_language_instructions(self, language: LanguageEnum) -> str:
        """Get instructions for a specific language."""
        language_obj = LanguageEnum(language) if isinstance(language, str) else language
        return self.LANGUAGE_INSTRUCTIONS[language_obj]["instructions"]
    
    def get_available_tones(self) -> Dict[str, str]:
        """Get all available tones with descriptions."""
        return {
            tone.value: self.TONE_INSTRUCTIONS[tone]["tone_description"]
            for tone in ToneEnum
        }
    
    def get_available_languages(self) -> Dict[str, str]:
        """Get all available languages with descriptions."""
        return {
            language.value: self.LANGUAGE_INSTRUCTIONS[language]["language_description"]
            for language in LanguageEnum
        }
    
    def preview_prompt(
        self,
        company_name: str,
        category: str,
        website: str,
        tone: ToneEnum = ToneEnum.FORMAL,
        language: LanguageEnum = LanguageEnum.ENGLISH,
        max_tokens: int = 1000,
        knowledge_block: str = "",
        fallback_contact: str = "Contact support"
    ) -> Dict[str, Any]:
        """
        Generate a preview of the system prompt with metadata.
        
        Returns a dictionary with the prompt and related information.
        """
        prompt = self.generate_system_prompt(
            company_name=company_name,
            category=category,
            website=website,
            tone=tone,
            language=language,
            max_tokens=max_tokens,
            knowledge_block=knowledge_block,
            fallback_contact=fallback_contact
        )
        
        return {
            "prompt": prompt,
            "metadata": {
                "company_name": company_name,
                "category": category,
                "website": website,
                "tone": tone if isinstance(tone, str) else tone.value,
                "language": language if isinstance(language, str) else language.value,
                "max_tokens": max_tokens,
                "has_knowledge_block": bool(knowledge_block),
                "prompt_length": len(prompt.split()),
                "prompt_char_count": len(prompt)
            }
        }


# Initialize a global instance
prompt_template_generator = PromptTemplateGenerator()
