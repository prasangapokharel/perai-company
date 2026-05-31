"""System Prompt Generator with Dynamic Tone and Language"""
from app.models.companySettings import CompanySettings


class PromptGenerator:
    """Generate dynamic system prompts based on company settings"""
    
    # Tone-specific instructions
    TONE_INSTRUCTIONS = {
        "formal": """You are a professional AI assistant. Respond in a formal, structured manner. 
Use proper grammar, professional vocabulary, and maintain a business-appropriate tone. 
Be concise and direct in your responses.""",
        
        "casual": """You are a friendly AI assistant. Respond in a casual, conversational manner.
Use everyday language and a relaxed tone. Be helpful and approachable while still being accurate.""",
        
        "friendly": """You are a warm and welcoming AI assistant. Respond with friendliness and empathy.
Use a personable tone while maintaining professionalism. Show genuine interest in helping.""",
        
        "professional": """You are a professional AI consultant. Respond with expertise and authority.
Use industry-appropriate terminology. Provide comprehensive, well-reasoned responses."""
    }
    
    # Language-specific prefixes
    LANGUAGE_INSTRUCTIONS = {
        "english": """You are responding in English. Provide clear, grammatically correct responses.""",
        
        "nepali": """आप नेपालीमा प्रतिक्रिया दिइरहेको हुनुहुन्छ। स्पष्ट र व्याकरणिक रूपमा सही प्रतिक्रियाहरू प्रदान गर्नुहोस्।
(You are responding in Nepali. Provide clear and grammatically correct responses.)"""
    }
    
    @staticmethod
    def generateSystemPrompt(
        company_settings: CompanySettings,
        base_context: str = ""
    ) -> str:
        """Generate a complete system prompt with settings
        
        Args:
            company_settings: CompanySettings object with language and tone
            base_context: Company's knowledge base content
        
        Returns:
            Complete system prompt string
        """
        
        # Get tone and language instructions
        tone = company_settings.tone.lower()
        language = company_settings.language.lower()
        
        tone_instruction = PromptGenerator.TONE_INSTRUCTIONS.get(
            tone,
            PromptGenerator.TONE_INSTRUCTIONS["formal"]
        )
        
        language_instruction = PromptGenerator.LANGUAGE_INSTRUCTIONS.get(
            language,
            PromptGenerator.LANGUAGE_INSTRUCTIONS["english"]
        )
        
        # Build complete prompt
        system_prompt = f"""SYSTEM PROMPT FOR AI ASSISTANT
{'='*50}

TONE & BEHAVIOR:
{tone_instruction}

LANGUAGE PREFERENCE:
{language_instruction}

TOKEN LIMIT: {company_settings.max_tokens} tokens

COMPANY KNOWLEDGE BASE:
{base_context if base_context else "No specific company knowledge provided yet."}

{'='*50}

Please respond according to the above instructions."""
        
        return system_prompt
    
    @staticmethod
    def formatResponseByLanguage(
        response: str,
        language: str
    ) -> dict:
        """Format response metadata based on language
        
        Args:
            response: AI response text
            language: Language preference
        
        Returns:
            Dictionary with formatted response
        """
        return {
            "response": response,
            "language": language,
            "language_name": "English" if language == "english" else "नेपाली (Nepali)"
        }
    
    @staticmethod
    def getSettingsSummary(company_settings: CompanySettings) -> dict:
        """Get human-readable settings summary
        
        Returns:
            Dictionary with settings information
        """
        return {
            "language": company_settings.language,
            "language_display": "English" if company_settings.language == "english" else "नेपाली (Nepali)",
            "tone": company_settings.tone,
            "tone_display": company_settings.tone.capitalize(),
            "max_tokens": company_settings.max_tokens,
            "summary": f"Company configured for {company_settings.language} language with {company_settings.tone} tone (max {company_settings.max_tokens} tokens)"
        }
