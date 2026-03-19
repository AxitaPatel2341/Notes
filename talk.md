# "Same Bug, New Clothes — Attacking Modern Architectures with Classic Primitives"
**Format:** Community Meetup | **Duration:** 35–40 mins + 5 min Q&A
**Style:** Code walkthrough + storytelling from a pentester's lens
**Year:** 2026

---

## INTRO — Why Are We Still Here? (4 mins)

**Hook (1 min)**
> *"It's 2026. We have AI-assisted code review, supply chain security mandates, DAST in every pipeline — and we're still finding SSRF, race conditions, and auth bypasses in production. These aren't new CVEs. These are primitives from 15 years ago. Why?"*

**Key points to land:**
- Awareness and patches fix *instances*, not *the class*
- Architectures evolve — cloud-native, serverless, agentic AI — but fundamental trust assumptions don't
- Devs move fast, security assumptions from the old architecture get copy-pasted into the new one
- Transition line: *"The bug didn't die. It just got a new deployment target."*

---

## SECTION 1 — SSRF: From Internal Port Scans to Cloud Credentials to LLM Backends (10 mins)

### The Classic (2 mins)
- What SSRF was in ~2010: hitting internal services, port scanning, reading local files
- Was often "medium severity" in on-prem — interesting but limited blast radius
- Quick code snippet: classic vulnerable endpoint
```python
# Classic SSRF — still written exactly like this in 2026
url = request.args.get('url')
response = requests.get(url)  # no validation, no allowlist
```

### The Cloud Twist — Still Happening (4 mins)
- In AWS/GCP/Azure: one SSRF = hitting **Instance Metadata Service (IMDS)**
- IMDSv1: no auth, just GET `http://169.254.169.254/latest/meta-data/iam/security-credentials/`
- Result: live IAM credentials — Access Key, Secret, Session Token — full cloud takeover
- IMDSv2 was the fix — but misconfigs persist, and SSRF filter bypasses are alive:
  - Redirects, DNS rebinding, IPv6, `http://0.0.0.0`, decimal IP encoding

### The 2026 Twist — SSRF in AI/LLM Backends (4 mins)
- Every major app now has an **LLM feature** — summarize, generate, fetch context
- These features make **server-side HTTP requests** based on user input — classic SSRF surface
- New attack path: user-supplied URL → LLM backend → internal service / cloud metadata
- Real pattern seen in 2025-2026: **Retrieval-Augmented Generation (RAG)** pipelines fetching attacker-controlled URLs
```python
# AI-powered "summarize any URL" feature — SSRF reborn
def summarize_url(url):
    content = requests.get(url).text   # attacker controls url
    return llm.summarize(content)
```
- **This isn't an AI bug. It's SSRF. The LLM is just the new vector.**
- Callout: MCP (Model Context Protocol) servers in agentic workflows are an emerging SSRF surface in 2026

> 💡 **Takeaway:** *SSRF went from "interesting finding" to "cloud takeover" to "AI pipeline pivot." The primitive is 15 years old. The blast radius keeps growing.*

---

## SECTION 2 — Race Conditions: From TOCTOU to Distributed Systems to Serverless (10 mins)

### The Classic (2 mins)
- TOCTOU — Time of Check, Time of Use — the OG race condition
- Classic example: coupon reuse, double-spend on a wallet
- On monoliths: hard to exploit reliably — timing window was microseconds

### The Microservices Twist (4 mins)
- Microservices introduced **distributed state** — multiple services each doing partial validation
- Network hops between services **widen the race window** from microseconds to 50-100ms
- Real-world pattern:
  - Service A checks balance ✅
  - Service B processes payment ✅
  - Both fire before either updates state ❌
```python
# Vulnerable: check-then-act is not atomic across services
def withdraw(user_id, amount):
    balance = get_balance(user_id)        # CHECK — crosses network
    if balance >= amount:
        deduct_balance(user_id, amount)   # ACT — race window is now 50ms+
        return "success"
```
- Turbo Intruder / parallel requests: send 20 simultaneous requests, one wins

### The 2026 Twist — Serverless & Event-Driven Architectures (4 mins)
- Serverless (Lambda, Cloud Functions, Azure Functions) is now the default for many orgs
- Race conditions in **event-driven pipelines**: same event processed by multiple concurrent function invocations
- Real pattern: file upload triggers Lambda → Lambda checks quota → Lambda stores file
  - Two simultaneous uploads, quota checked twice before either write completes
- Idempotency is hard — most devs don't think about it at design time
- **Bonus 2026 surface:** AI agent workflows triggering multiple tool calls in parallel — race conditions in agentic task execution are almost entirely unexplored

> 💡 **Takeaway:** *Monolith made races hard to hit. Microservices made them easy. Serverless made them architectural. The class of bug never changed.*

---

## SECTION 3 — Auth Logic Bugs: From Parameter Tampering to SSO to Agentic OAuth (10 mins)

### The Classic (2 mins)
- Auth bypass used to be simple: flip `isAdmin=true`, tamper a cookie, manipulate a role parameter
- Devs got better at this at the app layer... so the bug moved up the stack

### The SSO/Federated Identity Twist (5 mins)
- Modern apps delegate auth entirely to SSO — SAML, OIDC, OAuth 2.0
- The logic bug didn't disappear, it moved into the **integration and trust layer**

**Bug 1 — SAML Signature Wrapping (still found in 2026)**
- Server validates the signature but reads an *unsigned* copy of the assertion
- Attacker injects a second unsigned assertion — server reads the wrong one
- Show XML structure: signed node vs. attacker-inserted node side by side

**Bug 2 — JWT Algorithm Confusion**
- RS256 public key used as HMAC secret → attacker signs their own token
- `alg: none` still accepted by some libraries even in 2026
```python
# Vulnerable JWT validation
jwt.decode(token, options={"verify_signature": False})  # seen in real codebases
```

**Bug 3 — OAuth redirect_uri Bypass**
- Loose matching: `https://legit.com.evil.com` or `https://legit.com/callback/../evil`
- Auth code delivered to attacker → token theft without touching the user

### The 2026 Twist — Auth Logic Bugs in Agentic AI Workflows (3 mins)
- AI agents now act **on behalf of users** using OAuth tokens — booking meetings, sending emails, querying internal tools
- The agent is granted broad OAuth scopes at setup time — "just give it everything it needs"
- Auth logic bugs here mean: **one compromised agent = access to everything it was ever delegated**
- Emerging pattern: **confused deputy attacks** where an agent is manipulated via prompt injection to use its OAuth token against unintended targets
- This is `isAdmin=true` for the agentic era — same logic flaw, new clothing

> 💡 **Takeaway:** *Auth logic bugs followed the trust boundary. App layer → SSO layer → now the AI delegation layer. Each time we built something new, we forgot the old lesson.*

---

## OUTRO — The Pattern & The Fix Mindset (4 mins)

**The common thread across all three:**
- Every time architecture changes, **security assumptions from the old model get carried forward unchecked**
- Devs fix the *instance* (patch the CVE, update the library) but not the *class* (understand why the primitive is dangerous)
- Security tooling chases known patterns — novel surfaces (LLM backends, serverless, agentic workflows) start with zero coverage

**What actually helps:**
- Threat model at the **primitive level**, not just the feature level — ask "does this make an outbound HTTP call? Does this touch shared state? Does this involve delegated auth?"
- Learn attack primitives deeply, not just checklists
- When you adopt a new architecture pattern, ask: *"which old bugs just got a new home here?"*

**Closing line:**
> *"The bugs in your 2026 stack aren't new. They're the same ones from 2010, wearing a Kubernetes hoodie and asking for OAuth scopes."*

---

## Demo Plan (Code Walkthrough Style)

| Section | What to show |
|---|---|
| SSRF | Vulnerable RAG pipeline vs. allowlisted version side by side |
| Race Condition | Non-atomic withdraw vs. atomic DB transaction / idempotency key fix |
| Auth Logic | JWT `alg:none` decode snippet + SAML assertion XML structure |

**Tools to reference:** Burp Suite, Turbo Intruder, jwt.io, SAML Raider

---

## Suggested Talk Length Breakdown

| Segment | Time |
|---|---|
| Intro | 4 mins |
| SSRF | 10 mins |
| Race Conditions | 10 mins |
| Auth Logic Bugs | 10 mins |
| Outro + Fix Mindset | 4 mins |
| Q&A | 5 mins |
| **Total** | **~43 mins** |
