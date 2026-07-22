# Chapter 2: Background Study and Literature Review

## 2.1 Background Study

This section describes the fundamental theories, general concepts, and terminologies related to the project.

### 2.1.1 Large Language Models (LLMs)

A **Large Language Model (LLM)** is a type of artificial intelligence model trained on vast quantities of text data using deep learning techniques, specifically transformer-based neural network architectures. LLMs learn statistical patterns of language and can generate coherent, contextually relevant text in response to a given prompt.

The key properties of LLMs relevant to this project are:
- **In-context learning:** LLMs can follow instructions and adapt their behavior based on the content of the prompt without requiring fine-tuning.
- **Grounding:** When provided factual context in the prompt, LLMs produce answers that reference those facts rather than hallucinating.
- **Tokenization:** LLMs process text in units called *tokens* (roughly 0.75 words each). API usage is billed per input and output token, making token counting central to the billing system in Perai.

Perai uses the **Llama 3.3 70B** model served through the **Groq** inference API, which offers very low-latency inference using custom LPU (Language Processing Unit) hardware.

### 2.1.2 Retrieval-Augmented Generation (RAG)

**Retrieval-Augmented Generation (RAG)** is a technique that combines information retrieval with language model generation. Instead of relying solely on the LLM's internal knowledge (which may be outdated or factually wrong for specific company data), RAG first retrieves relevant documents from a knowledge base and includes them in the prompt as context. The LLM then generates a response grounded in those retrieved documents.

**Standard RAG pipeline:**
1. The user's query is converted to an embedding vector.
2. The embedding is compared against a vector database of pre-embedded knowledge chunks.
3. The top-K most similar chunks are retrieved.
4. Retrieved chunks are injected into the LLM prompt.
5. The LLM generates a grounded reply.

**Perai's vectorless RAG approach:**

Standard RAG requires an embedding model (typically large, slow, and expensive to run) and a vector database (such as pgvector, Pinecone, or Weaviate). For small to medium-sized knowledge bases (hundreds to a few thousand records), this overhead is unnecessary.

Perai uses a **vectorless RAG** approach:
- Each company's knowledge base is stored as a flat JSONL file on disk.
- At query time, **BM25 (Best Matching 25)** ranking — a classical information retrieval algorithm that scores documents based on term frequency and inverse document frequency — is applied to retrieve the top-K most relevant records.
- Additionally, **exact ID/name matching** is performed to handle highly specific queries.
- The top-K records are concatenated into the prompt as context.

BM25 formula:

```
Score(D, Q) = Σ IDF(qi) * [ f(qi, D) * (k1 + 1) ] / [ f(qi, D) + k1 * (1 - b + b * |D|/avgDL) ]
```

where D is a document, Q is the query, f(qi, D) is the frequency of query term qi in D, |D| is the document length, avgDL is the average document length, and k1, b are tuning parameters.

This approach eliminates both the embedding model and the vector database from the architecture, significantly reducing operational cost and deployment complexity.

### 2.1.3 Multi-Tenant Software Architecture

**Multi-tenancy** is a software architecture in which a single instance of the software serves multiple customers (tenants), with each tenant's data isolated from others. There are three common models:

| Model | Description | Perai's Approach |
|-------|-------------|-----------------|
| Database-per-tenant | Each tenant has its own database | Not used (too expensive to scale) |
| Schema-per-tenant | Each tenant has its own schema | Not used |
| Row-level isolation | Single database, all tables have a `tenant_id` column | **Used** (`company_id` on every table) |

Perai implements **row-level isolation**: every table contains a `company_id` foreign key, and all queries are filtered by the authenticated company's ID. Cross-company access is blocked by a FastAPI dependency (`require_company`) that verifies the authenticated JWT/API key's company ID matches the URL path parameter.

### 2.1.4 REST API and Authentication

**REST (Representational State Transfer)** is an architectural style for distributed hypermedia systems. A RESTful API uses HTTP methods (GET, POST, PUT, DELETE) on resource URLs to perform CRUD operations. Perai exposes a fully RESTful API under the `/api/v1/` prefix.

Two authentication mechanisms are used:

- **JWT (JSON Web Token):** Used by the company dashboard. On login, the server signs a JWT containing the company ID with a secret key. Subsequent requests include the JWT in the `Authorization: Bearer <token>` header. The server verifies the signature without a database lookup.

- **Hashed API Keys:** Used by external integrations (widget, third-party apps). API keys are generated as random 48-character strings prefixed with `sk_`. Only a SHA-256 hash is stored in the database. The request includes the raw key in `X-API-Key`, the server hashes it and compares with stored hashes.

### 2.1.5 Khalti ePayment System

**Khalti** is one of Nepal's leading digital payment platforms, offering a mobile wallet, online banking integration, and a merchant API for accepting payments in Nepalese Rupees (NPR). Khalti provides the **ePayment API v2** for server-to-server payment initiation and verification.

The Khalti integration in Perai uses the **hosted checkout flow**:
1. Merchant initiates a payment via `POST /epayment/initiate/` with amount in NPR paisa (1 NPR = 100 paisa).
2. Khalti returns a `pidx` (payment index) and a `payment_url`.
3. The user is redirected to Khalti's hosted payment page.
4. After payment, Khalti redirects the user back to the merchant's `return_url` with the `pidx`.
5. The merchant verifies the payment via `POST /epayment/lookup/` using the `pidx`.

**USD to NPR conversion** in Perai uses a configurable rate (default: 1 USD = 140 NPR), allowing the platform administrator to update the rate as exchange rates change.

### 2.1.6 Database Normalization

**Database normalization** is the process of organizing a relational database to reduce data redundancy and improve data integrity by decomposing tables into smaller tables and defining relationships between them. The standard normal forms are:

- **First Normal Form (1NF):** All attributes are atomic (no repeating groups or multi-valued attributes); each row is unique.
- **Second Normal Form (2NF):** In 1NF and no partial dependency — every non-key attribute depends on the whole composite key.
- **Third Normal Form (3NF):** In 2NF and no transitive dependency — no non-key attribute depends on another non-key attribute.

Perai's database schema is fully normalized to 3NF, with each table representing exactly one entity or one type of event. The normalization walkthrough is presented in Section 3.1.1 of the database design.

### 2.1.7 FastAPI Framework

**FastAPI** is a modern, high-performance Python web framework for building APIs, based on standard Python type hints. Key features relevant to Perai:

- **Automatic OpenAPI documentation:** FastAPI automatically generates Swagger UI at `/docs`.
- **Dependency injection:** The `Depends()` system is used to inject database sessions, authentication, and authorization checks into route handlers.
- **Pydantic validation:** Request and response bodies are automatically validated and serialized using Pydantic models.
- **Streaming responses:** FastAPI supports `StreamingResponse` for server-sent event (SSE) streaming of LLM tokens.

### 2.1.8 Next.js Frontend

**Next.js** is a React framework for production applications, providing server-side rendering, file-based routing, middleware, and built-in TypeScript support. Perai's dashboard uses Next.js 16 with the App Router, **shadcn/ui** component library (built on Radix UI primitives), and **Tailwind CSS** for styling. Authentication state is maintained via a cookie (`perai_auth=1`) checked in Next.js middleware, with JWT and API key stored in browser localStorage.

## 2.2 Literature Review

This section reviews similar existing systems, prior research, and their relevance to Perai.

### 2.2.1 Intercom and Zendesk AI

**Intercom** and **Zendesk** are leading commercial customer support platforms that have integrated AI chatbots into their products. Intercom's "Fin AI" uses OpenAI's GPT-4 and retrieves from the company's help center articles. Zendesk's AI similarly integrates with its knowledge base.

**Relevance to Perai:** These platforms demonstrate the market demand and technical viability of LLM-powered customer support. However, they (a) charge in USD via international cards, making them inaccessible to most Nepali businesses; (b) do not support Nepali language; (c) are closed platforms with no REST API for external integration; and (d) are prohibitively expensive for small companies. Perai addresses all four gaps.

### 2.2.2 BM25 vs. Dense Retrieval in RAG Systems

Robertson et al. (2009) introduced **BM25** as a probabilistic ranking function that has remained competitive with dense vector retrieval on many benchmarks. Karpukhin et al. (2020) introduced Dense Passage Retrieval (DPR), showing that bi-encoder dense retrieval outperforms BM25 on open-domain question answering benchmarks.

However, Ma et al. (2022) showed in their survey "How Does RAG Perform in Practice?" that for domain-specific, small knowledge bases (fewer than 10,000 documents), BM25 achieves competitive accuracy to dense retrieval while requiring no embedding infrastructure. This finding validates Perai's architectural choice of vectorless BM25 retrieval for company-specific knowledge bases.

### 2.2.3 Multi-Tenant SaaS Architecture Patterns

Bezemer and Zaidman (2010) surveyed multi-tenant SaaS architectures and identified row-level tenant isolation as the optimal balance between isolation, cost, and operational complexity for small-to-medium tenants. Their findings directly inform Perai's database design, where `company_id` on every table provides complete data isolation without the overhead of per-tenant schemas or databases.

### 2.2.4 Khalti Integration in Nepali Applications

Academic and industry reports on Nepali digital payment adoption (Nepal Rastra Bank, 2023) show that mobile wallet usage (Khalti, eSewa) grew by 340% in the post-COVID period, with Khalti having over 5 million registered users. Several Nepali e-commerce and SaaS projects (notably Daraz.com.np and various university fee portals) have demonstrated reliable production use of Khalti's ePayment API v2. Perai builds on this proven foundation and adds server-side idempotency guarantees to prevent double-crediting — a gap identified in earlier implementations.

### 2.2.5 Token Metering and Billing in LLM Platforms

OpenAI, Anthropic, and Groq all implement per-token billing at the API level. Research into LLM cost optimization by Chen et al. (2023) recommends a **reserve-then-finalize** billing model for real-time applications: reserve an estimated credit amount at the start of the request, stream the response, then refund the difference (or deduct extra) at completion. This prevents both over-charging (if the LLM responds with fewer tokens than estimated) and under-charging (if more tokens are used). Perai implements this exact pattern in its `reserve_balance` / `finalize_deduction` service functions.

### 2.2.6 Summary

| Related Work | Contribution | Gap Addressed by Perai |
|---|---|---|
| Intercom Fin AI | LLM-powered customer support SaaS | Local payment (Khalti), Nepali language, REST API access |
| BM25 vs. Dense Retrieval (Robertson 2009, Ma 2022) | BM25 viable for small domain-specific RAG | Justifies vectorless approach |
| Multi-tenant patterns (Bezemer 2010) | Row-level isolation is optimal for SMB SaaS | Validates `company_id` isolation design |
| Khalti ePayment (NRB 2023) | Proven adoption in Nepal | First Nepali AI SaaS with Khalti integration |
| Reserve-then-finalize billing (Chen 2023) | Accurate LLM cost metering | Implemented in balance service |
