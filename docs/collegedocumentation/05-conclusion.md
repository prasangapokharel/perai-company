# Chapter 5: Conclusion and Future Recommendations

## 5.1 Conclusion

This project presents the complete design, implementation, and testing of **Perai** — a multi-tenant B2B SaaS platform that enables any company to deploy an AI-powered customer support chat assistant trained on its own proprietary knowledge base, without requiring machine learning expertise or heavy infrastructure investment.

The following objectives were successfully achieved:

1. **Multi-tenant SaaS platform:** Twelve database tables with `company_id` row-level isolation ensure complete data separation between tenants. A FastAPI dependency (`require_company`) enforces cross-company access control at the API layer, verified in 8 dedicated cross-tenant test cases.

2. **Vectorless RAG system:** BM25 retrieval over per-company JSONL files delivers relevant knowledge context in 3–6 ms, eliminating the need for a vector database or embedding model. This reduces operational cost while maintaining retrieval quality adequate for the SMB knowledge base sizes (hundreds to a few thousand records) that are Perai's target market.

3. **Token-based metering and billing:** The reserve-then-finalize billing model accurately accounts for actual token usage with no over-charging or under-charging across all tested cases. Balance deduction, reservation, and refund all operate within single database transactions, ensuring consistency.

4. **Khalti ePayment integration:** The Khalti hosted checkout flow allows companies in Nepal to top up credits in Nepalese Rupees using mobile wallets and bank transfers — removing the international card requirement that makes global AI SaaS platforms inaccessible to Nepali businesses. Server-side idempotency guarantees exactly-once crediting, confirmed by 7 dedicated Khalti test cases.

5. **Embeddable widget and REST API:** Companies can integrate the AI assistant into their website by pasting a single HTML snippet. Direct API access with hashed API keys enables custom integrations in any programming language.

6. **Normalized database schema:** The 12-table schema is fully normalized to Third Normal Form (3NF), with Alembic-managed migrations enabling safe incremental schema evolution in production.

The project demonstrates that a modern LLM-powered customer support SaaS with local payment integration, localized language support, and complete billing infrastructure can be built and tested within a single academic semester using open-source technologies, without requiring specialized ML infrastructure.

All 34 planned test cases (26 unit tests, 8 system tests) passed, and the system operated correctly under multi-tenant, multi-concurrent-request, and payment-failure scenarios.

---

## 5.2 Future Recommendations

Based on the development experience and limitations identified in Section 1.4, the following enhancements are recommended for future work:

1. **Semantic retrieval (vector RAG):** Replace BM25 with sentence-transformer embeddings stored in PostgreSQL's `pgvector` extension. This would improve retrieval quality for paraphrased and semantically related queries that share no overlapping keywords with knowledge records. A hybrid BM25 + dense retrieval model (e.g., BM25 rank fusion) could be implemented to preserve keyword-exact matching benefits while adding semantic understanding.

2. **Multiple payment gateways:** Add eSewa, Fonepay, and credit/debit card (Stripe) support to expand the addressable customer base. Each gateway should implement the same `initiate/verify` interface used by Khalti, enabling easy addition without changes to the billing core.

3. **Automatic language detection:** Detect the end user's message language automatically (e.g., using `langdetect` library) and respond in the detected language, rather than requiring the company to configure a fixed language setting. This would support multilingual companies with mixed-language customer bases.

4. **Multi-document knowledge base:** Support multiple JSONL files per company, with named "topics" (e.g., "products", "support-policy", "pricing"). The RAG engine would search across all files and include the most relevant records from each topic, improving recall for diverse queries.

5. **Analytics dashboard:** Add a per-company analytics module showing chat volume per day, average response time, token cost per session, top queries, and knowledge coverage gaps (questions that returned zero context). This would help companies improve their knowledge base quality.

6. **Fine-tuning support:** Allow companies with sufficient usage history to submit their chat logs as fine-tuning data to a hosted LLM provider, creating a genuinely personalized model that responds more naturally in the company's voice.

7. **Role-based access control (RBAC):** Support multiple company users (admin, agent, viewer) with different permission levels. Currently only one company account exists per company.

8. **Webhook notifications:** Notify company systems via webhooks on payment success, balance low, or new ticket creation, enabling automated workflows without polling the API.

9. **Rate limiting per API key:** Allow companies to configure per-key rate limits (e.g., 10 requests/minute for a publicly embedded widget) to prevent abuse by end users.

10. **Deployment automation:** Add Docker Compose configuration and GitHub Actions CI/CD pipeline for automated testing on every push and one-command production deployment.
