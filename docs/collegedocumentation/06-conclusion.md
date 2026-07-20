# Chapter 6 — Conclusion and Future Recommendations

## 6.1 Conclusion

The project successfully delivered **Perai**, a working multi-tenant platform through which any
company can deploy an AI customer-support assistant grounded in its own knowledge base, without
AI expertise or heavy infrastructure. All core objectives set in Chapter 1 were achieved:

- **Multi-tenancy** — every company's knowledge, settings, API keys, chats, and billing are
  isolated at row level, with cross-company access blocked (verified by tests).
- **Low-cost RAG** — the vectorless BM25/file approach removed the need for a vector
  database and embedding pipeline while still grounding LLM answers in company data.
- **Metered prepaid billing** — a reserve → finalize charging pipeline meters every request
  by tokens, with refunds for unused reservations, backed by a normalized (3NF) schema.
- **Local payment** — the **Khalti ePayment** integration lets Nepali companies top up USD
  credits in NPR, with server-side verification and exactly-once (idempotent) crediting.
- **Easy integration** — an embeddable widget and documented REST API with copy-paste
  snippets (TypeScript/Python/cURL) reduce integration to minutes.

Working through the full lifecycle — analysis, DFD/ER modelling, normalization,
implementation, and automated testing — consolidated the practical software-engineering
skills the BCA programme aims to develop.

## 6.2 Lessons Learned

- Designing payment flows demands **idempotency first**: redirects can be repeated, so
  crediting must be keyed to a unique gateway reference (`khalti:<pidx>`), not to the event.
- **Normalization pays off** operationally: separating balance state from top-up/deduct
  events made auditing and refund logic trivial.
- Mocking external gateways (Khalti, Groq) in tests keeps the suite fast and deterministic
  while still exercising the real application code paths.
- Handling the "empty state" (fresh account, no knowledge base) is as important to UX as the
  happy path.

## 6.3 Future Recommendations

1. **eSewa and card payments** alongside Khalti, with a common payment-provider interface.
2. **Semantic retrieval upgrade** — optional embedding-based retrieval (pgvector) for
   paraphrased queries, falling back to BM25 for cost-sensitive tenants.
3. **Server-side Khalti webhooks** in addition to redirect-based verification, so payments
   complete even if the user closes the browser before returning.
4. **Team accounts** — multiple users with roles (owner, developer, support) per company.
5. **Analytics dashboard** — per-question topic clustering, unanswered-question mining to
   suggest new knowledge-base entries.
6. **Multi-language replies** — automatic language detection per end-user message rather
   than a single per-company language setting.
7. **Live deployment hardening** — switch `KHALTI_BASE_URL` to the production gateway,
   rotate secrets, enable HTTPS-only cookies, and add structured audit logging.
