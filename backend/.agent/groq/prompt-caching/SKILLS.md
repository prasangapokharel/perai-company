---
description: Learn how to use prompt caching to reduce latency and costs.
title: Prompt Caching - GroqDocs
image: https://console.groq.com/og_cloudv5.jpg
---

# Prompt Caching

Model prompts often contain repetitive content, such as system prompts and tool definitions. Prompt caching automatically reuses computation from recent requests when they share a common prefix, delivering significant cost savings and improved response times while maintaining data privacy through volatile-only storage that expires automatically.

Prompt caching works automatically on all your API requests with no code changes required and no additional fees.

## [How It Works](#how-it-works)

1. **Prefix Matching**: When you send a request, the system examines and identifies matching prefixes from recently processed requests stored temporarily in volatile memory. Prefixes can include system prompts, tool definitions, few-shot examples, and more.
2. **Cache Hit**: If a matching prefix is found, cached computation is reused, dramatically reducing latency and token costs by 50% for cached portions.
3. **Cache Miss**: If no match exists, your prompt is processed normally, with the prefix temporarily cached for potential future matches.
4. **Automatic Expiration**: All cached data automatically expires after 2 hours without use.

Prompt caching works automatically on all your API requests to supported models with no code changes required and no additional fees. Groq tries to maximize cache hits, but this is not guaranteed. Pricing discount will only apply on successful cache hits.

Cached tokens do not count towards your rate limits. However, cached tokens are subtracted from your limits after processing, so it's still possible to hit your limits if you are sending a large number of input tokens in parallel requests.

## [Supported Models](#supported-models)

Prompt caching is currently only supported for the following models:

| Model ID                     | Model                                                             |
| ---------------------------- | ----------------------------------------------------------------- |
| openai/gpt-oss-20b           | [GPT-OSS 20B](https://console.groq.com/docs/model/openai/gpt-oss-20b)                     |
| openai/gpt-oss-120b          | [GPT-OSS 120B](https://console.groq.com/docs/model/openai/gpt-oss-120b)                   |
| openai/gpt-oss-safeguard-20b | [GPT-OSS-Safeguard 20B](https://console.groq.com/docs/model/openai/gpt-oss-safeguard-20b) |

We're starting with a limited selection of models and will roll out prompt caching to more models soon.

## [Pricing](#pricing)

Prompt caching is provided at no additional cost. There is a 50% discount for cached input tokens.

## [Structuring Prompts for Optimal Caching](#structuring-prompts-for-optimal-caching)

Cache hits are only possible for exact prefix matches within a prompt. To realize caching benefits, you need to think strategically about prompt organization:

### [Optimal Prompt Structure](#optimal-prompt-structure)

Place static content like instructions and examples at the beginning of your prompt, and put variable content, such as user-specific information, at the end. This maximizes the length of the reusable prefix across different requests.

If you put variable information (like timestamps or user IDs) at the beginning, even identical system instructions later in the prompt won't benefit from caching because the prefixes won't match.


**Place static content first:**

* System prompts and instructions
* Few-shot examples
* Tool definitions
* Schema definitions
* Common context or background information

**Place dynamic content last:**

* User-specific queries
* Variable data
* Timestamps
* Session-specific information
* Unique identifiers

### [Example Structure](#example-structure)

curl

```
[SYSTEM PROMPT - Static]
[TOOL DEFINITIONS - Static]
[FEW-SHOT EXAMPLES - Static]
[COMMON INSTRUCTIONS - Static]
[USER QUERY - Dynamic]
[SESSION DATA - Dynamic]
```

This structure maximizes the likelihood that the static prefix portion will match across different requests, enabling cache hits while keeping user-specific content at the end.

## [Prompt Caching Examples](#prompt-caching-examples)

Multi turn conversationsLarge prompts and contextTool definitions and use

Python

```
import Groq from "groq-sdk";

const groq = new Groq();

async function multiTurnConversation() {
  // Initial conversation with system message and first user input
  const initialMessages = [
    {
      role: "system",
      content: "You are a helpful AI assistant that provides detailed explanations about complex topics. Always provide comprehensive answers with examples and context."
    },
    {
      role: "user",
      content: "What is quantum computing?"
    }
  ];

  // First request - creates cache for system message
  const firstResponse = await groq.chat.completions.create({
    messages: initialMessages,
    model: "openai/gpt-oss-120b"
  });

  console.log("First response:", firstResponse.choices[0].message.content);
  console.log("Usage:", firstResponse.usage);

  // Continue conversation - system message and previous context will be cached
  const conversationMessages = [
    ...initialMessages,
    firstResponse.choices[0].message,
    {
      role: "user",
      content: "Can you give me a simple example of how quantum superposition works?"
    }
  ];

  const secondResponse = await groq.chat.completions.create({
    messages: conversationMessages,
    model: "openai/gpt-oss-120b"
  });

  console.log("Second response:", secondResponse.choices[0].message.content);
  console.log("Usage:", secondResponse.usage);

  // Continue with third turn
  const thirdTurnMessages = [
    ...conversationMessages,
    secondResponse.choices[0].message,
    {
      role: "user",
      content: "How does this relate to quantum entanglement?"
    }
  ];

  const thirdResponse = await groq.chat.completions.create({
    messages: thirdTurnMessages,
    model: "openai/gpt-oss-120b"
  });

  console.log("Third response:", thirdResponse.choices[0].message.content);
  console.log("Usage:", thirdResponse.usage);
}

multiTurnConversation().catch(console.error);
```

```
import os
from groq import Groq

client = Groq()

def multi_turn_conversation():
    # Initial conversation with system message and first user input
    initial_messages = [
        {
            "role": "system",
            "content": "You are a helpful AI assistant that provides detailed explanations about complex topics. Always provide comprehensive answers with examples and context."
        },
        {
            "role": "user",
            "content": "What is quantum computing?"
        }
    ]

    # First request - creates cache for system message
    first_response = client.chat.completions.create(
        messages=initial_messages,
        model="openai/gpt-oss-120b"
    )

    print("First response:", first_response.choices[0].message.content)
    print("Usage:", first_response.usage)

    # Continue conversation - system message and previous context will be cached
    conversation_messages = [
        *initial_messages,
        first_response.choices[0].message,
        {
            "role": "user",
            "content": "Can you give me a simple example of how quantum superposition works?"
        }
    ]

    second_response = client.chat.completions.create(
        messages=conversation_messages,
        model="openai/gpt-oss-120b"
    )

    print("Second response:", second_response.choices[0].message.content)
    print("Usage:", second_response.usage)

    # Continue with third turn
    third_turn_messages = [
        *conversation_messages,
        second_response.choices[0].message,
        {
            "role": "user",
            "content": "How does this relate to quantum entanglement?"
        }
    ]

    third_response = client.chat.completions.create(
        messages=third_turn_messages,
        model="openai/gpt-oss-120b"
    )

    print("Third response:", third_response.choices[0].message.content)
    print("Usage:", third_response.usage)

if __name__ == "__main__":
    multi_turn_conversation()
```

```
#!/bin/bash

# Multi-turn conversation example with prompt caching
# Set your GROQ_API_KEY environment variable before running

API_KEY="${GROQ_API_KEY}"
BASE_URL="https://api.groq.com/openai/v1"

if [ -z "$API_KEY" ]; then
    echo "Error: GROQ_API_KEY environment variable is not set"
    exit 1
fi

echo "=== First Request (Creates Cache) ==="

# First request - creates cache for system message
FIRST_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "messages": [
      {
        "role": "system",
        "content": "You are a helpful AI assistant that provides detailed explanations about complex topics. Always provide comprehensive answers with examples and context."
      },
      {
        "role": "user",
        "content": "What is quantum computing?"
      }
    ],
    "model": "openai/gpt-oss-120b"
  }')

echo "First response:"
echo "$FIRST_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$FIRST_RESPONSE" | jq '.usage'

# Extract the assistant's response for next turn
ASSISTANT_RESPONSE=$(echo "$FIRST_RESPONSE" | jq -r '.choices[0].message.content')

echo -e "\n=== Second Request (Uses Cache) ==="

# Second request - system message and previous context will be cached
SECOND_PAYLOAD=$(jq -n \
  --arg system_content "You are a helpful AI assistant that provides detailed explanations about complex topics. Always provide comprehensive answers with examples and context." \
  --arg user1_content "What is quantum computing?" \
  --arg assistant1_content "$ASSISTANT_RESPONSE" \
  --arg user2_content "Can you give me a simple example of how quantum superposition works?" \
  --arg model "openai/gpt-oss-120b" \
  '{
    "messages": [
      {
        "role": "system",
        "content": $system_content
      },
      {
        "role": "user",
        "content": $user1_content
      },
      {
        "role": "assistant",
        "content": $assistant1_content
      },
      {
        "role": "user",
        "content": $user2_content
      }
    ],
    "model": $model
  }')

SECOND_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d "$SECOND_PAYLOAD")

echo "Second response:"
echo "$SECOND_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$SECOND_RESPONSE" | jq '.usage'

# Extract the second assistant response for third turn
SECOND_ASSISTANT_RESPONSE=$(echo "$SECOND_RESPONSE" | jq -r '.choices[0].message.content')

echo -e "\n=== Third Request (Uses Cache) ==="

# Third request - even more conversation history cached
THIRD_PAYLOAD=$(jq -n \
  --arg system_content "You are a helpful AI assistant that provides detailed explanations about complex topics. Always provide comprehensive answers with examples and context." \
  --arg user1_content "What is quantum computing?" \
  --arg assistant1_content "$ASSISTANT_RESPONSE" \
  --arg user2_content "Can you give me a simple example of how quantum superposition works?" \
  --arg assistant2_content "$SECOND_ASSISTANT_RESPONSE" \
  --arg user3_content "How does this relate to quantum entanglement?" \
  --arg model "openai/gpt-oss-120b" \
  '{
    "messages": [
      {
        "role": "system",
        "content": $system_content
      },
      {
        "role": "user",
        "content": $user1_content
      },
      {
        "role": "assistant",
        "content": $assistant1_content
      },
      {
        "role": "user",
        "content": $user2_content
      },
      {
        "role": "assistant",
        "content": $assistant2_content
      },
      {
        "role": "user",
        "content": $user3_content
      }
    ],
    "model": $model
  }')

THIRD_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
  -H "Authorization: Bearer $API_KEY" \
  -H "Content-Type: application/json" \
  -d "$THIRD_PAYLOAD")

echo "Third response:"
echo "$THIRD_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$THIRD_RESPONSE" | jq '.usage'
```

### [How Prompt Caching Works in Multi-Turn Conversations](#how-prompt-caching-works-in-multiturn-conversations)

In this example, we demonstrate how to use prompt caching in a multi-turn conversation.

During each turn, the system automatically caches the longest matching prefix from previous requests. The system message and conversation history that remain unchanged between requests will be cached, while only new user messages and assistant responses need fresh processing.

This approach is useful for maintaining context in ongoing conversations without repeatedly processing the same information.

**For the first request:**

* `prompt_tokens`: Number of tokens in the system message and first user message
* `cached_tokens`: 0 (no cache hit on first request)

**For subsequent requests within the cache lifetime:**

* `prompt_tokens`: Total number of tokens in the entire conversation (system message + conversation history + new user message)
* `cached_tokens`: Number of tokens in the system message and previous conversation history that were served from cache

When set up properly, you should see increasing cache efficiency as the conversation grows, with the system message and earlier conversation turns being served from cache while only new content requires processing.

Python

```
import Groq from "groq-sdk";

const groq = new Groq();

async function analyzeLegalDocument() {
  // First request - creates cache for the large legal document
  const systemPrompt = `You are a legal expert AI assistant. Analyze the following legal document and provide detailed insights.

LEGAL DOCUMENT: <entire contents of large legal document>`;

  const firstAnalysis = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "What are the key provisions regarding user account termination in this agreement?"
      }
    ],
    model: "openai/gpt-oss-120b"
  });

  console.log("First analysis:", firstAnalysis.choices[0].message.content);
  console.log("Usage:", firstAnalysis.usage);

  // Second request - legal document will be cached, only new question processed
  const secondAnalysis = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "What are the intellectual property rights implications for users who submit content?"
      }
    ],
    model: "openai/gpt-oss-120b"
  });

  console.log("Second analysis:", secondAnalysis.choices[0].message.content);
  console.log("Usage:", secondAnalysis.usage);

  // Third request - same large context, different question
  const thirdAnalysis = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "Are there any concerning limitations of liability clauses that users should be aware of?"
      }
    ],
    model: "openai/gpt-oss-120b"
  });

  console.log("Third analysis:", thirdAnalysis.choices[0].message.content);
  console.log("Usage:", thirdAnalysis.usage);
}

analyzeLegalDocument().catch(console.error);
```

```
from groq import Groq

client = Groq()

def analyze_legal_document():
    # First request - creates cache for the large legal document
    system_prompt = """
    You are a legal expert AI assistant. Analyze the following legal document and provide detailed insights.\\n\\nLEGAL DOCUMENT:\\n<entire contents of large legal document>
    """

    first_analysis = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": system_prompt
            },
            {
                "role": "user",
                "content": "What are the key provisions regarding user account termination in this agreement?"
            }
        ],
        model="openai/gpt-oss-120b"
    )

    print("First analysis:", first_analysis.choices[0].message.content)
    print("Usage:", first_analysis.usage)

    # Second request - legal document will be cached, only new question processed
    second_analysis = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": system_prompt
            },
            {
                "role": "user",
                "content": "What are the intellectual property rights implications for users who submit content?"
            }
        ],
        model="openai/gpt-oss-120b"
    )

    print("Second analysis:", second_analysis.choices[0].message.content)
    print("Usage:", second_analysis.usage)

    # Third request - same large context, different question
    third_analysis = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": system_prompt
            },
            {
                "role": "user",
                "content": "Are there any concerning limitations of liability clauses that users should be aware of?"
            }
        ],
        model="openai/gpt-oss-120b"
    )

    print("Third analysis:", third_analysis.choices[0].message.content)
    print("Usage:", third_analysis.usage)

if __name__ == "__main__":
    analyze_legal_document()
```

```
#!/bin/bash

# Large prompts and context example with prompt caching
# Set your GROQ_API_KEY environment variable before running

API_KEY="${GROQ_API_KEY}"
BASE_URL="https://api.groq.com/openai/v1"

if [ -z "$API_KEY" ]; then
    echo "Error: GROQ_API_KEY environment variable is not set"
    exit 1
fi

SYSTEM_MESSAGE="You are a legal expert AI assistant. Analyze the following legal document and provide detailed insights.

LEGAL DOCUMENT: <entire contents of large legal document>"

echo "=== First Request (Creates Cache) ==="

# First request - creates cache for the large legal document
FIRST_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "What are the key provisions regarding user account termination in this agreement?"
                }
            ],
            "model": "openai/gpt-oss-120b"
        }')")

echo "First analysis:"
echo "$FIRST_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$FIRST_RESPONSE" | jq '.usage'

echo -e "\\n=== Second Request (Uses Cache) ==="

# Second request - legal document will be cached, only new question processed
SECOND_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "What are the intellectual property rights implications for users who submit content?"
                }
            ],
            "model": "openai/gpt-oss-120b"
        }')")

echo "Second analysis:"
echo "$SECOND_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$SECOND_RESPONSE" | jq '.usage'

echo -e "\\n=== Third Request (Uses Cache) ==="

# Third request - same large context, different question
THIRD_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "Are there any concerning limitations of liability clauses that users should be aware of?"
                }
            ],
            "model": "openai/gpt-oss-120b"
        }')")

echo "Third analysis:"
echo "$THIRD_RESPONSE" | jq '.choices[0].message.content'
echo "Usage:"
echo "$THIRD_RESPONSE" | jq '.usage'
```

### [How Prompt Caching Works with Large Context](#how-prompt-caching-works-with-large-context)

In this example, we demonstrate caching large static content like legal documents, research papers, or extensive context that remains constant across multiple queries.

The large legal document in the system message represents static content that benefits significantly from caching. Once cached, subsequent requests with different questions about the same document will reuse the cached computation for the document analysis, processing only the new user questions.

This approach is particularly effective for document analysis, research assistance, or any scenario where you need to ask multiple questions about the same large piece of content.

**For the first request:**

* `prompt_tokens`: Total number of tokens in the system message (including the large legal document) and user message
* `cached_tokens`: 0 (no cache hit on first request)

**For subsequent requests within the cache lifetime:**

* `prompt_tokens`: Total number of tokens in the system message (including the large legal document) and user message
* `cached_tokens`: Number of tokens in the entire cached system message (including the large legal document)

The caching efficiency is particularly high in this scenario since the large document (which may be thousands of tokens) is reused across multiple requests, while only small user queries (typically dozens of tokens) need fresh processing.

Python

```
import Groq from "groq-sdk";

const groq = new Groq();

// Define comprehensive tool set
const tools = [
  {
    type: "function",
    function: {
      name: "get_weather",
      description: "Get the current weather in a given location",
      parameters: {
        type: "object",
        properties: {
          location: {
            type: "string",
            description: "The city and state, e.g. San Francisco, CA"
          },
          unit: {
            type: "string",
            enum: ["celsius", "fahrenheit"],
            description: "The unit of temperature"
          }
        },
        required: ["location"]
      }
    }
  },
  {
    type: "function",
    function: {
      name: "calculate_math",
      description: "Perform mathematical calculations",
      parameters: {
        type: "object",
        properties: {
          expression: {
            type: "string",
            description: "Mathematical expression to evaluate, e.g. '2 + 2' or 'sqrt(16)'"
          }
        },
        required: ["expression"]
      }
    }
  },
  {
    type: "function",
    function: {
      name: "search_web",
      description: "Search the web for current information",
      parameters: {
        type: "object",
        properties: {
          query: {
            type: "string",
            description: "Search query"
          },
          num_results: {
            type: "integer",
            description: "Number of results to return",
            minimum: 1,
            maximum: 10,
            default: 5
          }
        },
        required: ["query"]
      }
    }
  },
  {
    type: "function",
    function: {
      name: "get_time",
      description: "Get the current time in a specific timezone",
      parameters: {
        type: "object",
        properties: {
          timezone: {
            type: "string",
            description: "Timezone identifier, e.g. 'America/New_York' or 'UTC'"
          }
        },
        required: ["timezone"]
      }
    }
  }
];

async function useToolsWithCaching() {
  // First request - creates cache for all tool definitions
  const systemPrompt = "You are a helpful assistant with access to various tools. Use the appropriate tools to answer user questions accurately.";
  const firstRequest = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "What's the weather like in New York City?"
      }
    ],
    model: "openai/gpt-oss-120b",
    tools: tools
  });

  console.log("First request response:", firstRequest.choices[0].message);
  console.log("Usage:", firstRequest.usage);

  // Check if the model wants to use tools
  if (firstRequest.choices[0].message.tool_calls) {
    console.log("Tool calls requested:", firstRequest.choices[0].message.tool_calls);
  }

  // Second request - tool definitions will be cached
  const secondRequest = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "Can you calculate the square root of 144 and tell me what time it is in Tokyo?"
      }
    ],
    model: "openai/gpt-oss-120b",
    tools: tools
  });

  console.log("Second request response:", secondRequest.choices[0].message);
  console.log("Usage:", secondRequest.usage);

  if (secondRequest.choices[0].message.tool_calls) {
    console.log("Tool calls requested:", secondRequest.choices[0].message.tool_calls);
  }

  // Third request - same tool definitions cached
  const thirdRequest = await groq.chat.completions.create({
    messages: [
      {
        role: "system",
        content: systemPrompt
      },
      {
        role: "user",
        content: "Search for recent news about artificial intelligence developments."
      }
    ],
    model: "openai/gpt-oss-120b",
    tools: tools
  });

  console.log("Third request response:", thirdRequest.choices[0].message);
  console.log("Usage:", thirdRequest.usage);

  if (thirdRequest.choices[0].message.tool_calls) {
    console.log("Tool calls requested:", thirdRequest.choices[0].message.tool_calls);
  }
}

useToolsWithCaching().catch(console.error);
```

```
from groq import Groq

client = Groq()

# Define comprehensive tool set
tools = [
    {
        "type": "function",
        "function": {
            "name": "get_weather",
            "description": "Get the current weather in a given location",
            "parameters": {
                "type": "object",
                "properties": {
                    "location": {
                        "type": "string",
                        "description": "The city and state, e.g. San Francisco, CA"
                    },
                    "unit": {
                        "type": "string",
                        "enum": ["celsius", "fahrenheit"],
                        "description": "The unit of temperature"
                    }
                },
                "required": ["location"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "calculate_math",
            "description": "Perform mathematical calculations",
            "parameters": {
                "type": "object",
                "properties": {
                    "expression": {
                        "type": "string",
                        "description": "Mathematical expression to evaluate, e.g. '2 + 2' or 'sqrt(16)'"
                    }
                },
                "required": ["expression"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "search_web",
            "description": "Search the web for current information",
            "parameters": {
                "type": "object",
                "properties": {
                    "query": {
                        "type": "string",
                        "description": "Search query"
                    },
                    "num_results": {
                        "type": "integer",
                        "description": "Number of results to return",
                        "minimum": 1,
                        "maximum": 10,
                        "default": 5
                    }
                },
                "required": ["query"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "get_time",
            "description": "Get the current time in a specific timezone",
            "parameters": {
                "type": "object",
                "properties": {
                    "timezone": {
                        "type": "string",
                        "description": "Timezone identifier, e.g. 'America/New_York' or 'UTC'"
                    }
                },
                "required": ["timezone"]
            }
        }
    }
]

def use_tools_with_caching():
    # First request - creates cache for all tool definitions
    first_request = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": "You are a helpful assistant with access to various tools. Use the appropriate tools to answer user questions accurately."
            },
            {
                "role": "user",
                "content": "What's the weather like in New York City?"
            }
        ],
        model="openai/gpt-oss-120b",
        tools=tools
    )

    print("First request response:", first_request.choices[0].message)
    print("Usage:", first_request.usage)

    # Check if the model wants to use tools
    if first_request.choices[0].message.tool_calls:
        print("Tool calls requested:", first_request.choices[0].message.tool_calls)

    # Second request - tool definitions will be cached
    second_request = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": "You are a helpful assistant with access to various tools. Use the appropriate tools to answer user questions accurately."
            },
            {
                "role": "user",
                "content": "Can you calculate the square root of 144 and tell me what time it is in Tokyo?"
            }
        ],
        model="openai/gpt-oss-120b",
        tools=tools
    )

    print("Second request response:", second_request.choices[0].message)
    print("Usage:", second_request.usage)

    if second_request.choices[0].message.tool_calls:
        print("Tool calls requested:", second_request.choices[0].message.tool_calls)

    # Third request - same tool definitions cached
    third_request = client.chat.completions.create(
        messages=[
            {
                "role": "system",
                "content": "You are a helpful assistant with access to various tools. Use the appropriate tools to answer user questions accurately."
            },
            {
                "role": "user",
                "content": "Search for recent news about artificial intelligence developments."
            }
        ],
        model="openai/gpt-oss-120b",
        tools=tools
    )

    print("Third request response:", third_request.choices[0].message)
    print("Usage:", third_request.usage)

    if third_request.choices[0].message.tool_calls:
        print("Tool calls requested:", third_request.choices[0].message.tool_calls)

if __name__ == "__main__":
    use_tools_with_caching()
```

```
#!/bin/bash

# Tool definitions and use example with prompt caching
# Set your GROQ_API_KEY environment variable before running

API_KEY="${GROQ_API_KEY}"
BASE_URL="https://api.groq.com/openai/v1"

if [[ -z "$API_KEY" ]]; then
    echo "Error: GROQ_API_KEY environment variable is not set"
    exit 1
fi

# Define comprehensive tool set
TOOLS='[
    {
        "type": "function",
        "function": {
            "name": "get_weather",
            "description": "Get the current weather in a given location",
            "parameters": {
                "type": "object",
                "properties": {
                    "location": {
                        "type": "string",
                        "description": "The city and state, e.g. San Francisco, CA"
                    },
                    "unit": {
                        "type": "string",
                        "enum": ["celsius", "fahrenheit"],
                        "description": "The unit of temperature"
                    }
                },
                "required": ["location"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "calculate_math",
            "description": "Perform mathematical calculations",
            "parameters": {
                "type": "object",
                "properties": {
                    "expression": {
                        "type": "string",
                        "description": "Mathematical expression to evaluate, e.g. '\''2 + 2'\'' or '\''sqrt(16)'\''"
                    }
                },
                "required": ["expression"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "search_web",
            "description": "Search the web for current information",
            "parameters": {
                "type": "object",
                "properties": {
                    "query": {
                        "type": "string",
                        "description": "Search query"
                    },
                    "num_results": {
                        "type": "integer",
                        "description": "Number of results to return",
                        "minimum": 1,
                        "maximum": 10,
                        "default": 5
                    }
                },
                "required": ["query"]
            }
        }
    },
    {
        "type": "function",
        "function": {
            "name": "get_time",
            "description": "Get the current time in a specific timezone",
            "parameters": {
                "type": "object",
                "properties": {
                    "timezone": {
                        "type": "string",
                        "description": "Timezone identifier, e.g. '\''America/New_York'\'' or '\''UTC'\''"
                    }
                },
                "required": ["timezone"]
            }
        }
    }
]'

SYSTEM_MESSAGE="You are a helpful assistant with access to various tools. Use the appropriate tools to answer user questions accurately."

echo "=== First Request (Creates Cache) ==="

# First request - creates cache for all tool definitions
FIRST_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        --argjson tools "$TOOLS" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "What'\''s the weather like in New York City?"
                }
            ],
            "model": "openai/gpt-oss-120b",
            "tools": $tools
        }')")

echo "First request response:"
echo "$FIRST_RESPONSE" | jq '.choices[0].message'
echo "Usage:"
echo "$FIRST_RESPONSE" | jq '.usage'

# Check if tool calls were requested
TOOL_CALLS=$(echo "$FIRST_RESPONSE" | jq '.choices[0].message.tool_calls // empty')
if [[ -n "$TOOL_CALLS" && "$TOOL_CALLS" != "null" ]]; then
    echo "Tool calls requested:"
    echo "$TOOL_CALLS"
fi

echo -e "\n=== Second Request (Uses Cache) ==="

# Second request - tool definitions will be cached
SECOND_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        --argjson tools "$TOOLS" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "Can you calculate the square root of 144 and tell me what time it is in Tokyo?"
                }
            ],
            "model": "openai/gpt-oss-120b",
            "tools": $tools
        }')")

echo "Second request response:"
echo "$SECOND_RESPONSE" | jq '.choices[0].message'
echo "Usage:"
echo "$SECOND_RESPONSE" | jq '.usage'

# Check if tool calls were requested
TOOL_CALLS=$(echo "$SECOND_RESPONSE" | jq '.choices[0].message.tool_calls // empty')
if [[ -n "$TOOL_CALLS" && "$TOOL_CALLS" != "null" ]]; then
    echo "Tool calls requested:"
    echo "$TOOL_CALLS"
fi

echo -e "\n=== Third Request (Uses Cache) ==="

# Third request - same tool definitions cached
THIRD_RESPONSE=$(curl -s -X POST "$BASE_URL/chat/completions" \
    -H "Authorization: Bearer $API_KEY" \
    -H "Content-Type: application/json" \
    -d "$(jq -n \
        --arg system_msg "$SYSTEM_MESSAGE" \
        --argjson tools "$TOOLS" \
        '{
            "messages": [
                {
                    "role": "system",
                    "content": $system_msg
                },
                {
                    "role": "user",
                    "content": "Search for recent news about artificial intelligence developments."
                }
            ],
            "model": "openai/gpt-oss-120b",
            "tools": $tools
        }')")

echo "Third request response:"
echo "$THIRD_RESPONSE" | jq '.choices[0].message'
echo "Usage:"
echo "$THIRD_RESPONSE" | jq '.usage'

# Check if tool calls were requested
TOOL_CALLS=$(echo "$THIRD_RESPONSE" | jq '.choices[0].message.tool_calls // empty')
if [[ -n "$TOOL_CALLS" && "$TOOL_CALLS" != "null" ]]; then
    echo "Tool calls requested:"
    echo "$TOOL_CALLS"
fi
```

### [How Prompt Caching Works with Tool Definitions](#how-prompt-caching-works-with-tool-definitions)

In this example, we demonstrate caching tool definitions.

All tool definitions, including their schemas, descriptions, and parameters, are cached as a single prefix when they remain consistent across requests. This is particularly valuable when you have a comprehensive set of tools that you want to reuse across multiple requests without re-processing them each time.

The system message and all tool definitions form the static prefix that gets cached, while user queries remain dynamic and are processed fresh for each request.

This approach is useful when you have a consistent set of tools that you want to reuse across multiple requests without re-processing them each time.

**For the first request:**

* `prompt_tokens`: Total number of tokens in the system message, tool definitions, and user message
* `cached_tokens`: 0 (no cache hit on first request)

**For subsequent requests within the cache lifetime:**

* `prompt_tokens`: Total number of tokens in the system message, tool definitions, and user message
* `cached_tokens`: Number of tokens in all cached tool definitions and system prompt

Tool definitions can be quite lengthy due to detailed parameter schemas and descriptions, making caching particularly beneficial for reducing both latency and costs when the same tool set is used repeatedly.

## [Requirements and Limitations](#requirements-and-limitations)

### [Caching Requirements](#caching-requirements)

* **Exact Prefix Matching**: Cache hits require exact matches of the beginning of your prompt
* **Minimum Prompt Length**: The minimum cacheable prompt length varies by model, ranging from 128 to 1024 tokens depending on the specific model used

To check how much of your prompt was cached, see the response [usage fields](#response-usage-structure).

### [What Can Be Cached](#what-can-be-cached)

* **Complete message arrays** including system, user, and assistant messages
* **Tool definitions** and function schemas
* **System instructions** and prompt templates
* **One-shot** and **few-shot examples**
* **Structured output schemas**
* **Large static content** like legal documents, research papers, or extensive context that remains constant across multiple queries
* **Image inputs**, including image URLs and base64-encoded images

### [Limitations](#limitations)

* **Exact Matching**: Even minor changes in cached portions prevent cache hits and force a new cache to be created
* **No Manual Control**: Cache clearing and management is automatic only

## [Tracking Cache Usage](#tracking-cache-usage)

You can monitor how many tokens are being served from cache by examining the `usage` field in your API response. The response includes detailed token usage information, including how many tokens were cached.

### [Response Usage Structure](#response-usage-structure)

JSON

```
{
  "id": "chatcmpl-...",
  "model": "openai/gpt-oss-120b",
  "usage": {
      "queue_time": 0.026959759,
      "prompt_tokens": 4641,
      "prompt_time": 0.009995497,
      "completion_tokens": 1817,
      "completion_time": 5.57691751,
      "total_tokens": 6458,
      "total_time": 5.586913007,
      "prompt_tokens_details": {
          "cached_tokens": 4608
      }
  },
  ... other fields
}
```

### [Understanding the Fields](#understanding-the-fields)

* **`prompt_tokens`**: Total number of tokens in your input prompt
* **`cached_tokens`**: Number of input tokens that were served from cache (within `prompt_tokens_details`)
* **`completion_tokens`**: Number of tokens in the model's response
* **`total_tokens`**: Sum of prompt and completion tokens

In the example above, out of 4641 prompt tokens, 4608 tokens (99.3%) were served from cache, resulting in significant cost savings and improved response time.

### [Calculating Cache Hit Rate](#calculating-cache-hit-rate)

To calculate your cache hit rate:

`Cache Hit Rate = cached_tokens / prompt_tokens × 100%`

For the example above: `4608 / 4641 × 100% = 99.3%`

A higher cache hit rate indicates better prompt structure optimization leading to lower latency and more cost savings.

## [Troubleshooting](#troubleshooting)

* Verify that sections that you want to cache are identical between requests
* Check that calls are made within the cache lifetime (a few hours). Calls that are too far apart will not benefit from caching.
* Ensure that `tool_choice`, tool usage, and image usage remain consistent between calls
* Validate that you are caching at least the [minimum number of tokens](#caching-requirements) through the [usage fields](#response-usage-structure).

Changes to cached sections, including `tool_choice` and image usage, will invalidate the cache and require a new cache to be created. Subsequent calls will use the new cache.

## [Frequently Asked Questions](#frequently-asked-questions)

### [How is data privacy maintained with caching?](#how-is-data-privacy-maintained-with-caching)

All cached data exists only in volatile memory and automatically expires within a few hours. No prompt or response content is ever stored in persistent storage or shared between organizations.

### [Does caching affect the quality or consistency of responses?](#does-caching-affect-the-quality-or-consistency-of-responses)

No. Prompt caching only affects the processing of the input prompt, not the generation of responses. The actual model inference and response generation occur normally, maintaining identical output quality whether caching is used or not.

### [Can I disable prompt caching?](#can-i-disable-prompt-caching)

Prompt caching is automatically enabled and cannot be manually disabled. This helps customers benefit from reduced costs and latency. Prompts are not stored in persistent storage.

### [How do I know if my requests are benefiting from caching?](#how-do-i-know-if-my-requests-are-benefiting-from-caching)

You can track cache usage by examining the `usage` field in your API responses. Cache hits are not guaranteed, but Groq tries to maximize them. See the [Tracking Cache Usage](#tracking-cache-usage) section above for detailed information on how to monitor cached tokens and calculate your cache hit rate.

### [Are there any additional costs for using prompt caching?](#are-there-any-additional-costs-for-using-prompt-caching)

No. Prompt caching is provided at no additional cost and can help to reduce your costs by 50% for cached tokens while improving response times.

### [Does caching affect rate limits?](#does-caching-affect-rate-limits)

Cached tokens do not count toward your rate limits.

### [Can I manually clear or refresh caches?](#can-i-manually-clear-or-refresh-caches)

No manual cache management is available. All cache expiration and cleanup happens automatically.

### [Does the prompt caching discount work with batch requests?](#does-the-prompt-caching-discount-work-with-batch-requests)

Batch requests can still benefit from prompt caching, but the prompt caching discount does not stack with the batch discount. [Batch requests](https://console.groq.com/docs/batch) already receive a 50% discount on all tokens, and while caching functionality remains active, no additional discount is applied to cached tokens in batch requests.
