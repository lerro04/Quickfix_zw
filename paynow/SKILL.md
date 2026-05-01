---
name: paynow
description: >
  Use this skill whenever a developer is building, integrating, or debugging a PayNow (paynow.co.zw) payment integration. 
  Trigger this skill for ANY of the following: initiating PayNow transactions, handling PayNow webhooks/callbacks, 
  generating or validating PayNow hashes, implementing Express Checkout (EcoCash/Visa/Mastercard), handling 3D Secure 
  challenges, polling for transaction status, implementing recurring payments with tokens, setting up PayNow in 
  test/sandbox mode, or any question mentioning "paynow", "paynow.co.zw", "ecocash integration", or "Zimbabwe payment gateway".
  Always use this skill even if the question seems simple — PayNow has specific quirks (hash format, field ordering, 
  URL encoding rules) that require precise guidance.
---

# PayNow Integration Skill

PayNow (paynow.co.zw) is a Zimbabwean payment gateway supporting EcoCash, Visa, and Mastercard.
It communicates via HTTP POST with SHA512 hash-based authentication.

## Quick Reference

| Task | Method | Endpoint |
|------|--------|----------|
| Standard checkout | POST | `https://www.paynow.co.zw/interface/initiatetransaction` |
| Express checkout (mobile/card) | POST | `https://www.paynow.co.zw/interface/remotetransaction` |
| 3DS callback | POST | `https://www.paynow.co.zw/interface/remote3ds?guid=...` |
| Poll / check status | GET | `https://www.paynow.co.zw/interface/checkpayment/?guid={guid}` |
| Refund / cancel / confirm delivery | POST | `https://www.paynow.co.zw/interface/updatestatus` |

---

## 1. Setup & Keys

1. Register at https://www.paynow.co.zw/Customer/Register
2. Go to **Other Ways To Get Paid → Create/Manage Shopping Carts → Create Advanced Integration**
3. Note your **Integration ID** (shown on the page)
4. Click **Email Key To Company Address** to get your **Integration Key** — keep this secret, never expose in client-side code
5. Leave Notification URL blank — provide `resulturl` per transaction instead

> ⚠️ Generate a new Integration Key when moving from dev to production to invalidate old keys.

---

## 2. Hash Generation (CRITICAL)

Every message to/from PayNow must include a SHA512 hash. **Getting this wrong is the most common integration error.**

### Outbound hash (requests TO PayNow)
1. Concatenate ALL field values **in the exact order they appear in your POST body** — do **NOT** URL-encode values before joining, skip any disabled fields, skip the `hash` field itself
2. Append your Integration Key to the end
3. SHA512 hash the result → convert to **uppercase hex**

> ⚠️ **Field order matters.** The hash is order-sensitive. If your framework reorders form fields, your hash will be invalid. Build the hash from the same ordered array you use to construct the POST.

### Inbound hash (responses/callbacks FROM PayNow)

**Critical rules:**
- **Preserve field order** from the payload — do NOT reorder fields or use a hardcoded field list
- Include **ALL** fields except `hash` — do NOT use a hardcoded subset (PayNow may include `merchantfees`, `customerfees`, `paymentchannelreference`, etc.)
- PayNow internally concatenates values in Dictionary enumeration order (insertion order), trims values, and treats null as empty string
- Integration Key format is a GUID with hyphens (e.g., `3e9fed89-60e1-4ce5-ab6e-6b1eb2d4f977`)

**Steps:**
1. Split the response/callback body on `&`, then each pair on `=` — use a **LinkedHashMap** to preserve insertion order
2. URL-decode each key and value
3. Remove the `hash` entry
4. Concatenate all remaining values in their original order
5. Append Integration Key
6. SHA512 → uppercase hex → compare to received hash

**Callback field order** (from PayNow's `GenerateMessage()`):
- Base fields: `reference, paynowreference, amount, status, pollurl`
- Optional (on success with tokenize): `token, tokenexpiry`
- Optional (on success with payment instrument details): `paymentchannel, merchantfees, customerfees, paymentinstrument, paymentinstrumentname, paymentchannelreference, paymentinstrumentnationality, paymentinstrumentx, paymentfraudscore, paymentfrauddecision`
- Optional (EcoCash): `paymentchannelinitiated, paymentchannelcompleted`

### Hash Code Examples

**PHP:**
```php
function createHash(array $values, string $integrationKey): string {
    $string = "";
    foreach ($values as $key => $value) {
        if (strtoupper($key) !== "HASH") {
            $string .= $value;
        }
    }
    $string .= $integrationKey;
    return strtoupper(hash("sha512", $string));
}
```

**JavaScript/Node.js:**
```js
const crypto = require('crypto');

function createHash(values, integrationKey) {
    const str = Object.entries(values)
        .filter(([key]) => key.toLowerCase() !== 'hash')
        .map(([, val]) => val)
        .join('') + integrationKey;
    return crypto.createHash('sha512').update(str).digest('hex').toUpperCase();
}

function validateInboundHash(responseString, integrationKey) {
    const params = new URLSearchParams(responseString);
    const receivedHash = params.get('hash');
    const values = {};
    for (const [key, value] of params.entries()) {
        if (key.toLowerCase() !== 'hash') values[key] = value;
    }
    return createHash(values, integrationKey) === receivedHash;
}
```

**Test vector:** Integration Key `3e9fed89-60e1-4ce5-ab6e-6b1eb2d4f977`, values `1201 TEST REF 99.99 A test ticket transaction http://www.google.com/search?q=returnurl http://www.google.com/search?q=resulturl Message` → hash should be `2A033FC38798D913D42ECB786B9B19645ADEDBDE788862032F1BD82CF3B92DEF84F316385D5B40DBB35F1A4FD7D5BFE73835174136463CDD48C9366B0749C689`

---

## 3. Standard Checkout Flow

```
Merchant → POST /initiatetransaction → PayNow
PayNow  → returns browserurl + pollurl
Merchant → redirect customer to browserurl
Customer pays on PayNow
PayNow  → POST to resulturl (status update)
PayNow  → redirect customer to returnurl
```

### Initiate Transaction Request

`POST https://www.paynow.co.zw/interface/initiatetransaction`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | Integer | ✅ | Your Integration ID |
| `reference` | String | ✅ | Unique transaction reference on your side |
| `amount` | Decimal | ✅ | Amount in USD, 2 decimal places, no currency symbol |
| `returnurl` | String | ✅ | Customer redirect URL after payment |
| `resulturl` | String | ✅ | Your webhook URL for status updates |
| `status` | String | ✅ | Always `"Message"` |
| `hash` | String | ✅ | SHA512 hash (see section 2) |
| `additionalinfo` | String | ❌ | Info shown to customer on PayNow (no confidential data) |
| `authemail` | String | ❌ | Pre-fills customer email; if registered, prompts login |
| `tokenize` | Boolean | ❌ | Set `true` to receive a payment token for recurring payments |

### Successful Response

```
Status=Ok&BrowserUrl=https://...&PollUrl=https://...&Hash=...
```

> ⚠️ **Always validate the hash** before redirecting the customer to `BrowserUrl`.

### Error Response

```
Status=Error&Error=Invalid+amount+field
```

---

## 4. Express Checkout (No Redirect)

Captures payment details in your app — no browser redirect needed. Ideal for mobile apps.

> ⚠️ PCI DSS compliance required if capturing Visa/Mastercard/Zimswitch details directly.

**Supported `method` values:**

| Value | Payment Method |
|-------|---------------|
| `ecocash` | EcoCash mobile money |
| `onemoney` | OneMoney mobile money |
| `innbucks` | InnBucks |
| `paygo` | PayGo (e.g. Omari) |
| `vmc` | Visa / Mastercard |
| `zimswitch` | Zimswitch |

`POST https://www.paynow.co.zw/interface/remotetransaction`

Include all fields from section 3, plus:

| Field | Required For | Description |
|-------|-------------|-------------|
| `method` | All | `ecocash` or `vmc` (Visa/Mastercard) |
| `phone` | EcoCash | Mobile wallet number (e.g. `0771234567`) |
| `cardnumber` | Visa/MC | Card PAN |
| `cardname` | Visa/MC | Name on card |
| `cardcvv` | Visa/MC | 3-4 digit CVV |
| `cardexpiry` | Visa/MC | Format: `MMYYYY` (e.g. `052026`) |
| `billingline1` | Visa/MC | Billing address line 1 |
| `billingline2` | Visa/MC | Optional — helps fraud detection |
| `billingcity` | Visa/MC | City |
| `billingprovince` | Visa/MC | Optional — helps fraud detection |
| `billingcountry` | Visa/MC | Country |

If Visa/MC response is `Status=Pending3ds`, handle 3D Secure — see `references/3ds-flow.md`.

**EcoCash Express Checkout success response** includes an `instructions` field with the USSD dial string to show the customer, e.g.:
```
status=Ok&instructions=Dial+*151*2*4%23%0d%0aEnter+your+EcoCash+PIN...&paynowreference=...&pollurl=...&hash=...
```
Display these instructions to the customer in your app UI.

---

## 5. Status Updates (Webhooks)

PayNow POSTs to your `resulturl` whenever transaction status changes.

**Always validate the hash first.** If your server returns an HTTP error, PayNow retries up to 10 times.

Key status values:

| Status | Meaning |
|--------|---------|
| `Paid` | Payment successful — funds at next settlement |
| `Awaiting Delivery` | Paid but waiting for merchant delivery confirmation |
| `Delivered` | Delivery acknowledged, funds releasing after 24h window |
| `Created` | Transaction created, not yet paid |
| `Cancelled` | Cancelled — must recreate, cannot resume |
| `Disputed` | Customer disputed — funds held |
| `Refunded` | Refunded to customer |

**Optional fields returned on success:**
- `token` / `tokenexpiry` — for recurring payments (if `tokenize=true` was set)
- `paymentchannel` — e.g. `Visa`, `Mastercard`, `Ecocash`
- `paymentinstrument` — masked card/wallet number
- `paymentinstrumentname` — cardholder name
- `paymentinstrumentnationality` — `Domestic` or `Foreign`
- `paymentchannelreference` — approval code
- `paymentfraudscore` / `paymentfrauddecision` — fraud signals

---

## 6. Polling for Status

Poll only in these two cases:
1. You received a critical status update and want to verify it
2. Before deleting an old/unpaid transaction

```
GET https://www.paynow.co.zw/interface/checkpayment/?guid={guid}
```

Extract the `guid` from the `pollurl` returned by PayNow (e.g. `pollurl=https://...CheckPayment/?guid=dd564a16-...`). The response format is identical to the status update webhook fields.

### Polling for Stale/Pending Payments

PayNow callbacks may fail to arrive (network issues, server downtime). Implement a scheduled job to resolve stale PENDING payments:

1. Find all payments with `status = PENDING` older than a threshold (e.g., 30 minutes)
2. For each, `GET` the stored `pollUrl`
3. Parse the response preserving field order (same as callback format)
4. Validate the hash using the same inbound hash logic
5. Update payment status and trigger post-payment handlers (wallet credit, subscription, etc.)
6. If no `pollUrl` is stored, mark as FAILED

> The poll response uses the same format as the callback webhook — same hash validation applies.

---

## 7. Transaction Management (updatestatus)

`POST https://www.paynow.co.zw/interface/updatestatus`

Used to refund, cancel, or confirm delivery of a transaction. Requires **Advanced BuySafe** to be enabled on the integration for refund/delivery endpoints.

**Fields:**

| Field | Type | Description |
|-------|------|-------------|
| `transactionId` | String | PayNow reference number (the `paynowreference` from status updates) |
| `reference` | String | Your original merchant reference |
| `id` | Integer | Your Integration ID |
| `status` | String | Action to perform — see values below |

**`status` values:**

| Value | Action | Notes |
|-------|--------|-------|
| `refund` | Refund payment to customer | Requires Advanced BuySafe; idempotent |
| `confirm` | Mark transaction as Delivered | Requires Advanced BuySafe; idempotent |
| `cancel` | Cancel an unpaid transaction | ⚠️ Cannot cancel if payment already in-flight (mobile money USSD already sent) |

**Success response:** `Status=Ok`

**Error response example:** `Status=Error&Error=Transaction+is+not+in+an+unpaid+status+(Paid)`

> All three actions are **idempotent** — repeating the same request on an already-actioned transaction returns `Status=Ok`.

> ⚠️ Hash is **not** required for `updatestatus` requests based on the Postman collection.

---

## 8. Recurring Payments (Tokenization)

**Initial token acquisition:**
1. Use standard checkout with `tokenize=true` in the initiate transaction
2. Customer pays via Visa/Mastercard/Zimswitch on PayNow
3. Status update returns `token` + `tokenexpiry`

**Using a token for repeat payments:**
1. Use Express Checkout (`/interface/remotetransaction`) with `method=vmc` (or `zimswitch`)
2. Include `tokenize=true` and the **`merchanttrace`** field (a unique merchant-side reference for this recurring charge)
3. Pass the stored `token` in place of card details
4. The token is automatically re-tokenized on each successful payment — store the new token returned in the status update

> ⚠️ `merchanttrace` is **required** for Visa/Mastercard/Zimswitch token transactions.

**Token format:** Same length as original instrument, first 4 and last 4 digits preserved, middle tokenized.
- Card `1234-5678-9098-7654` → token `1234-4788-3349-7654` → display as `1234-****-****-7654`
- Wallet `263772123456` → token `263755833456` → display as `2637****3456`

---

## 9. Test Mode

New integrations start in test mode. Only the merchant account that created the integration can fake payments.

**Select "TESTING: Faked Success" on PayNow to simulate payment.**

**Express Checkout test numbers:**

| Number | Behaviour |
|--------|-----------|
| `0771111111` | Success after 5 seconds |
| `0772222222` | Success after 30 seconds (slow user) |
| `0773333333` | Failed/cancelled after 30 seconds |
| `0774444444` | Immediate failure — insufficient balance |

> ⚠️ `authemail` in test mode must match a login email for the merchant's test account.

To go live: **Integration Keys → Request to be Set Live** (requires at least one successful test transaction).

---

## 10. Security Checklist

- [ ] Integration Key stored server-side only — never in client-visible code or URLs
- [ ] Always validate inbound hash before processing any status update
- [ ] Always validate hash on initiate response before redirecting customer
- [ ] Use HTTPS for all `returnurl` and `resulturl` endpoints
- [ ] `resulturl` should be able to identify the transaction without extra state (embed reference in URL)
- [ ] Generate new Integration Key when deploying to production

---

## Reference Files

- `references/3ds-flow.md` — Detailed 3D Secure/SecureCode challenge flow for Visa/Mastercard Express Checkout
- `references/full-api-reference.md` — Complete field tables and response examples
