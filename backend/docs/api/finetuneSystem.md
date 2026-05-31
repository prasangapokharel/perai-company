# Finetune System - Complete Implementation Guide

## Overview

The Finetune System is a professional playground for customizing your AI assistant's behavior. It allows companies to:
- **Customize Tone** - Choose from 4 different communication styles
- **Select Language** - Respond in English or Nepali
- **Control Response Length** - Set token limits (100-4000)
- **Upload Knowledge Base** - Add company-specific documents
- **Preview Prompts** - See exactly how settings affect system prompts

## Architecture

### Backend Components

#### 1. PromptTemplateGenerator (`app/core/promptTemplateGenerator.py`)

The core service for dynamic prompt generation.

```python
from app.core.promptTemplateGenerator import (
    PromptTemplateGenerator,
    ToneEnum,
    LanguageEnum
)

# Initialize generator
generator = PromptTemplateGenerator()

# Generate complete prompt
prompt = generator.generate_system_prompt(
    company_name="Acme Corp",
    category="Tech Solutions",
    website="https://acme.com",
    tone=ToneEnum.PROFESSIONAL,
    language=LanguageEnum.ENGLISH,
    max_tokens=1500,
    knowledge_block="Our company specializes in...",
    fallback_contact="support@acme.com"
)

# Get preview with metadata
preview = generator.preview_prompt(...)
# Returns: {
#   "prompt": "...",
#   "metadata": {
#     "company_name": "...",
#     "tone": "formal",
#     "language": "english",
#     "max_tokens": 1000,
#     "prompt_length": 250,
#     "prompt_char_count": 2500
#   }
# }
```

**Key Features:**
- Template-based prompt generation with variable substitution
- Support for 2 languages: English, Nepali
- Support for 4 tones: Formal, Casual, Friendly, Professional
- Tone-specific instructions embedded in prompts
- Language-specific instructions (including Nepali native text)
- Auto-loading of system prompt template from `app/core/finetune/prompts/SystemPrompt.md`
- Metadata generation for frontend display

**Methods:**
- `generate_system_prompt()` - Generate complete prompt with all variables
- `preview_prompt()` - Generate prompt with metadata for UI preview
- `get_tone_instructions()` - Get instructions for specific tone
- `get_language_instructions()` - Get instructions for specific language
- `get_available_tones()` - List all available tones with descriptions
- `get_available_languages()` - List all available languages with descriptions

#### 2. System Prompt Template (`app/core/finetune/prompts/SystemPrompt.md`)

Dynamic template with variable placeholders (using `{{variable}}` format):

```markdown
# System Prompt Template

You are an AI assistant for {{company_name}}.

## Your Role
You represent {{company_name}}, a {{category}}...

## Language & Tone Settings
- Language: {{language}} ({{language_description}})
- Tone: {{tone}} ({{tone_description}})
- Max Tokens: {{max_tokens}}

## Tone-Specific Instructions
{{tone_instructions}}

## Language-Specific Instructions
{{language_instructions}}

## Company Knowledge Base
{{knowledge_block}}
```

**Variables:**
- `{{company_name}}` - Company name
- `{{category}}` - Company category/industry
- `{{website}}` - Company website
- `{{language}}` - Language (english, nepali)
- `{{language_description}}` - Language display name
- `{{tone}}` - Tone (formal, casual, friendly, professional)
- `{{tone_description}}` - Tone display name
- `{{tone_description_for_context}}` - Tone in sentence format
- `{{tone_instructions}}` - Full tone instructions
- `{{language_instructions}}` - Full language instructions
- `{{max_tokens}}` - Token limit
- `{{knowledge_block}}` - Company-specific knowledge
- `{{fallback_contact}}` - Fallback contact message

#### 3. Chat API Endpoint - Prompt Preview

**Endpoint:** `POST /api/v1/company/{company_id}/prompt/preview`

**Request:**
```json
{
  "tone": "professional",
  "language": "english",
  "max_tokens": 1500,
  "company_name": "Acme Corp",
  "category": "Tech Solutions",
  "website": "https://acme.com"
}
```

**Response:**
```json
{
  "prompt": "# System Prompt\n\nYou are an AI assistant for Acme Corp...",
  "metadata": {
    "company_name": "Acme Corp",
    "tone": "professional",
    "language": "english",
    "max_tokens": 1500,
    "prompt_length": 350,
    "prompt_char_count": 3500
  }
}
```

### Frontend Components

#### 1. DragAndDrop Component (`components/finetune/dragandDrop.tsx`)

File upload component with drag-and-drop support.

```tsx
import { DragAndDrop } from "@/components/finetune";

export function MyComponent() {
  const handleFilesSelected = (files: File[]) => {
    console.log("Files selected:", files);
  };

  return (
    <DragAndDrop
      onFilesSelected={handleFilesSelected}
      acceptedFileTypes={[".txt", ".md", ".pdf"]}
      maxFileSize={10 * 1024 * 1024}
      maxFiles={5}
      disabled={false}
      isLoading={false}
    />
  );
}
```

**Features:**
- Drag and drop file upload
- Click to browse files
- File validation (type, size, count)
- Error messages with details
- File list display
- Remove individual files
- Loading state support
- Dark mode support

#### 2. SettingsForm Component (`components/finetune/settingsForm.tsx`)

Settings form for tone, language, and token configuration.

```tsx
import { SettingsForm } from "@/components/finetune";

export function MySettings() {
  const handleSave = async (settings: FinetuneSettings) => {
    // Call API to save settings
    await api.saveSettings(settings);
  };

  return (
    <SettingsForm
      initialSettings={{
        language: "english",
        tone: "formal",
        max_tokens: 1000
      }}
      onSettingsSave={handleSave}
      disabled={false}
      isLoading={false}
    />
  );
}
```

**Features:**
- Language selection (English, Nepali)
- Tone selection (Formal, Casual, Friendly, Professional)
- Token limit slider (100-4000)
- Change detection
- Save/Reset buttons
- Success/Error messages
- Full validation
- Dark mode support

#### 3. PromptPreview Component (`components/finetune/promptPreview.tsx`)

Display system prompt with metadata and copy functionality.

```tsx
import { PromptPreview } from "@/components/finetune";

export function MyPreview() {
  return (
    <PromptPreview
      prompt="# System Prompt\n\nYou are an AI assistant..."
      metadata={{
        company_name: "Acme Corp",
        tone: "professional",
        language: "english",
        max_tokens: 1500,
        prompt_length: 350,
        prompt_char_count: 3500
      }}
      isLoading={false}
    />
  );
}
```

**Features:**
- Formatted prompt display
- Metadata cards (company, tone, language, tokens)
- Copy to clipboard button
- Word/character count
- Scrollable content
- Loading skeleton
- Dark mode support

#### 4. FinetunePanel Component (`components/finetune/finetunePanel.tsx`)

Complete finetune playground combining all components.

```tsx
import { FinetunePanel } from "@/components/finetune";

export function MyPlayground() {
  return (
    <FinetunePanel
      companyId="123"
      companyName="Acme Corp"
      companyCategory="Tech Solutions"
      companyWebsite="https://acme.com"
      apiKey="sk_xxxx..."
      onSuccess={(message) => console.log(message)}
      onError={(error) => console.error(error)}
    />
  );
}
```

**Features:**
- Three tabs: Settings, Upload, Preview
- Settings form integration
- File upload with progress
- Live prompt preview
- Sidebar preview (sticky)
- Error handling
- Success notifications
- Full dark mode support

### Frontend Pages

#### 1. Config Page (`app/(company)/config/page.tsx`)

Simple configuration page for managing settings.

**Location:** `/company/config`

**Features:**
- Left column: Settings form
- Right column: Sticky prompt preview
- Info section with usage guidelines
- Responsive design

#### 2. Finetune Playground Page (`app/(company)/finetune/page.tsx`)

Full-featured finetune playground with all tools.

**Location:** `/company/finetune`

**Features:**
- Settings customization
- File upload interface
- Live prompt preview
- Tabbed interface
- Progress tracking
- Error handling
- Responsive layout

## Tone Descriptions

### Formal
Professional, structured, and formal communication.
- Use formal language and professional terminology
- Structure responses with clear sections
- Maintain professional distance
- Use complete sentences and formal grammar

### Casual
Conversational, friendly, and everyday language.
- Use everyday language
- Be friendly and approachable
- Use contractions and informal expressions
- Feel like talking to a knowledgeable colleague

### Friendly
Warm, welcoming, and empathetic communication.
- Be warm and welcoming
- Show empathy and understanding
- Use positive language
- Make user feel valued

### Professional
Expert, authoritative, and knowledgeable communication.
- Position as an expert
- Use authoritative but accessible language
- Provide detailed, well-researched responses
- Cite company-specific expertise

## Language Support

### English
Standard English language responses.
- Clear, grammatically correct sentences
- English-appropriate idioms and expressions
- Technical terms in English

### Nepali (नेपाली)
Native Nepali language responses.
- नेपालीमा स्पष्ट र सही व्याकरणसहित जवाफ
- नेपाली-उपयुक्त मुहावरा र अभिव्यक्ति
- प्राविधिक शब्दहरू नेपालीमा अनुवाद गरिएको

## Integration Guide

### Backend Integration

#### 1. Using PromptTemplateGenerator

```python
from app.core.promptTemplateGenerator import prompt_template_generator

# Generate prompt for a company
prompt = prompt_template_generator.generate_system_prompt(
    company_name="Acme Corp",
    category="Tech",
    website="https://acme.com",
    tone="professional",
    language="english",
    max_tokens=1500
)

# Use in chat API
messages = [
    {"role": "system", "content": prompt},
    {"role": "user", "content": "Help me with..."}
]
```

#### 2. Chat with Settings

```python
from app.api.v1.chat.service import get_company_prompt_with_settings

# Get prompt with company settings
model_name, system_prompt, settings = get_company_prompt_with_settings(
    db, company_id
)

# Use settings.max_tokens when calling Groq
response = groq_client.chat.completions.create(
    model="mixtral-8x7b-32768",
    messages=messages,
    max_tokens=settings.max_tokens
)
```

### Frontend Integration

#### 1. In Pages

```tsx
import { FinetunePanel } from "@/components/finetune";

export default function FinetunePlaygroundPage() {
  return (
    <FinetunePanel
      companyId={companyId}
      companyName={companyName}
      companyCategory={category}
      companyWebsite={website}
      apiKey={apiKey}
      onSuccess={(msg) => showNotification(msg)}
      onError={(err) => showError(err)}
    />
  );
}
```

#### 2. In Custom Components

```tsx
import { SettingsForm, PromptPreview } from "@/components/finetune";

export function MyCustomUI() {
  const [settings, setSettings] = useState(...);
  const [preview, setPreview] = useState("");

  const handleSave = async (newSettings) => {
    const response = await fetch(`/api/v1/company/${id}/settings`, {
      method: "PUT",
      headers: { "X-API-Key": apiKey },
      body: JSON.stringify(newSettings)
    });
    // Handle response...
  };

  return (
    <div className="grid grid-cols-2 gap-6">
      <SettingsForm
        initialSettings={settings}
        onSettingsSave={handleSave}
      />
      <PromptPreview prompt={preview} />
    </div>
  );
}
```

## API Endpoints Summary

### Company Settings API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company/{id}/settings` | Create/update settings |
| GET | `/api/v1/company/{id}/settings` | Get settings (auto-create) |
| PUT | `/api/v1/company/{id}/settings` | Partial update settings |
| DELETE | `/api/v1/company/{id}/settings` | Delete settings |

### Chat API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company/{id}/chat/query` | Chat with settings |
| POST | `/api/v1/company/{id}/prompt/preview` | Preview prompt |
| POST | `/api/v1/company/{id}/chat/stream` | Stream chat response |
| GET | `/api/v1/company/{id}/chat/ping` | Health check |

### Finetune API

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/company/{id}/finetune/upload` | Upload knowledge base |
| GET | `/api/v1/company/{id}/finetune` | Get finetune status |

## Best Practices

### 1. Settings Configuration
- Set language to match your target audience
- Choose tone that reflects your brand voice
- Test different token limits to find optimal response length
- Review generated prompts before using in production

### 2. File Upload
- Start with 1-2 key documents
- Use clear, well-structured content
- Include company info, FAQs, policies
- Test with sample queries
- Gradually add more documents

### 3. Testing
- Use Config page for simple settings
- Use Finetune Playground for full features
- Test different combinations of tone + language
- Review prompt previews carefully
- Try actual chat queries before deploying

### 4. Maintenance
- Review settings monthly
- Update knowledge base quarterly
- Monitor user feedback
- Adjust tone/language based on feedback
- Keep token limits appropriate

## Examples

### Example 1: Professional Tech Company

**Settings:**
- Language: English
- Tone: Professional
- Max Tokens: 2000

**Use Case:** SaaS product support and documentation

### Example 2: Friendly E-commerce

**Settings:**
- Language: English
- Tone: Friendly
- Max Tokens: 1500

**Use Case:** Customer service and product recommendations

### Example 3: Casual Local Business

**Settings:**
- Language: Nepali
- Tone: Casual
- Max Tokens: 1000

**Use Case:** Local customer support in native language

## Troubleshooting

### Preview Not Updating
- Check company details are loaded
- Verify API key is valid
- Check browser console for errors
- Try refreshing the page

### Settings Not Saving
- Verify API key permissions
- Check network tab for 403/401 errors
- Ensure valid setting values (tone, language)
- Check token limit is 100-4000

### File Upload Failing
- Check file size (max 10MB)
- Verify file type (.txt, .md, .pdf)
- Ensure max 5 files
- Check network connection

### Prompt Too Short/Long
- Adjust max_tokens slider
- Longer tokens = longer responses
- Test with actual queries
- Review prompt preview

## File Structure

```
frontend/
├── app/(company)/
│   ├── finetune/
│   │   └── page.tsx              # Finetune playground page
│   └── config/
│       └── page.tsx              # Config page
└── components/finetune/
    ├── index.ts                  # Exports
    ├── dragandDrop.tsx          # File upload component
    ├── settingsForm.tsx         # Settings form component
    ├── promptPreview.tsx        # Prompt preview component
    └── finetunePanel.tsx        # Main finetune panel

backend/
├── app/core/
│   ├── promptTemplateGenerator.py    # Dynamic prompt generation
│   ├── finetune/
│   │   └── prompts/
│   │       └── SystemPrompt.md       # Prompt template
│   └── promptGenerator.py            # Legacy generator (still used)
└── app/api/v1/chat/
    └── route.py                      # Chat endpoints
```

## Version History

- **v1.0.0** - Initial release
  - Dynamic prompt templates
  - 4 tone options
  - 2 language options
  - Token limit control
  - Professional playground UI

## Support

For issues or questions about the finetune system:
1. Check this documentation
2. Review backend logs
3. Check browser console
4. Test API endpoints with Postman/Insomnia
5. Review component props and types

---

**Last Updated:** 2024
**Status:** Production Ready ✅
