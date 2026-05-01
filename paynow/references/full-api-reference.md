# PayNow Full API Reference

## Endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `https://www.paynow.co.zw/interface/initiatetransaction` | POST | Standard checkout |
| `https://www.paynow.co.zw/interface/remotetransaction` | POST | Express checkout |
| `https://www.paynow.co.zw/interface/remote3ds?guid=...` | POST | 3DS challenge callback |
| `https://www.paynow.co.zw/interface/checkpayment/?guid={guid}` | GET | Poll transaction status |
| `https://www.paynow.co.zw/interface/updatestatus` | POST | Refund / cancel / confirm delivery |

---

## Initiate Transaction — Full Field Reference

`POST https://www.paynow.co.zw/interface/initiatetransaction`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `id` | Integer | ✅ | Integration ID from PayNow dashboard |
| `reference` | String | ✅ | Your unique transaction ID |
| `amount` | Decimal | ✅ | USD, 2dp, no symbol e.g. `10.00` |
| `returnurl` | String | ✅ | Customer lands here after paying |
| `resulturl` | String | ✅ | PayNow posts status updates here |
| `status` | String | ✅ | Must be `"Message"` |
| `hash` | String | ✅ | SHA512 uppercase hex |
| `additionalinfo` | String | ❌ | Shown to customer; no sensitive data |
| `authemail` | String | ❌ | Pre-fills customer email |
| `tokenize` | Boolean | ❌ | `true` to enable recurring payment token |

### Successful Initiate Response

| Field | Type | Description |
|-------|------|-------------|
| `status` | String | `"Ok"` |
| `browserurl` | String | Redirect customer here |
| `pollurl` | String | Use to poll status |
| `hash` | String | Validate before redirecting |

Example:
```
Status=Ok&BrowserUrl=https%3a%2f%2fwww.paynow.co.zw%2fPayment%2fConfirmPayment%2f1169&PollUrl=https%3a%2f%2fwww.paynow.co.zw%2fInterface%2fCheckPayment%2f%3fguid%3d3cb27f4b-b3ef-4d1f-9178-5e5e62a43995&Hash=...
```

### Error Initiate Response

| Field | Type | Description |
|-------|------|-------------|
| `status` | String | `"Error"` |
| `error` | String | Human-readable error detail |

Example: `Status=Error&Error=Invalid+amount+field`

---

## Express Checkout — Additional Fields

`POST https://www.paynow.co.zw/interface/remotetransaction`  
(Includes all initiate transaction fields above, plus:)

| Field | Required For | Type | Description |
|-------|-------------|------|-------------|
| `method` | All | String | `ecocash` or `vmc` |
| `phone` | EcoCash | String | Mobile wallet number |
| `cardnumber` | Visa/MC | Numeric | Full PAN |
| `cardname` | Visa/MC | String | Name on card |
| `cardcvv` | Visa/MC | Numeric | 3-4 digit security code |
| `cardexpiry` | Visa/MC | Numeric | `MMYYYY` format e.g. `052026` |
| `billingline1` | Visa/MC | String | Address line 1 |
| `billingline2` | Visa/MC | String | Address line 2 (optional, aids fraud detection) |
| `billingcity` | Visa/MC | String | City |
| `billingprovince` | Visa/MC | String | Province (optional) |
| `billingcountry` | Visa/MC | String | Country |

---

## Status Update Webhook — Full Field Reference

PayNow POSTs this to your `resulturl`:

| Field | Type | Always Present | Description |
|-------|------|----------------|-------------|
| `reference` | String | ✅ | Your transaction reference |
| `amount` | Decimal | ✅ | Amount in USD |
| `paynowreference` | String | ✅ | PayNow's internal reference |
| `pollurl` | String | ✅ | URL to poll for current status |
| `status` | String | ✅ | See status table below |
| `hash` | String | ✅ | Validate before processing |
| `token` | String | ❌ | Recurring payment token (if tokenize=true) |
| `tokenexpiry` | String | ❌ | Token expiry `DDMMMYYYY` e.g. `01JAN2028` |
| `paymentchannel` | String | ❌ | `Visa`, `Mastercard`, `Ecocash` |
| `paymentinstrument` | String | ❌ | Masked card/wallet e.g. `4111****1111` |
| `paymentinstrumentname` | String | ❌ | Cardholder name |
| `paymentinstrumentnationality` | String | ❌ | `Domestic` or `Foreign` |
| `paymentchannelreference` | String | ❌ | Approval/transaction code |
| `paymentchanneleci` | String | ❌ | Electronic Commerce Indicator |
| `paymentfraudscore` | String | ❌ | Fraud score |
| `paymentfrauddecision` | String | ❌ | `Issue`, `Request Manual Review`, or `Reject` |

### All Transaction Statuses

| Status | Description |
|--------|-------------|
| `Created` | Transaction created, customer not yet paid |
| `Sent` | Customer referred to upstream payment system, not yet paid |
| `Paid` | **Payment complete** — funds at next settlement |
| `Awaiting Delivery` | Paid, waiting for merchant delivery confirmation |
| `Delivered` | Delivery acknowledged, funds releasing after 24h |
| `Cancelled` | Cancelled — must create new transaction |
| `Disputed` | Customer disputed — funds held in suspense |
| `Refunded` | Funds returned to customer |

---

## Token Format Reference

Tokens preserve first 4 and last 4 digits of the original instrument:

- Card `1234-5678-9098-7654` → token `1234-4788-3349-7654` → display as `1234-****-****-7654`
- Wallet `263772123456` → token `263755833456` → display as `2637****3456`

---

## Polling Response Example

```
reference=siteid123&paynowreference=1%2c082&amount=100.00&status=Created&pollurl=https%3a%2f%2f...&hash=...
```

## updatestatus — Refund / Cancel / Confirm Delivery

`POST https://www.paynow.co.zw/interface/updatestatus`

| Field | Type | Description |
|-------|------|-------------|
| `transactionId` | String | The `paynowreference` value from status updates |
| `reference` | String | Your original merchant reference |
| `id` | Integer | Integration ID |
| `status` | String | `refund` / `confirm` / `cancel` |

Response: `Status=Ok` or `Status=Error&Error=...`

---

## Real Response Examples (from staging)

### Initiate Transaction — Success
```
status=Ok&browserurl=https%3a%2f%2fstaging.paynow.co.zw%2fPayment%2fConfirmPayment%2f19310%2fcustomer%40domain.com%2f%2f&pollurl=https%3a%2f%2fstaging.paynow.co.zw%2fInterface%2fCheckPayment%2f%3fguid%3dfc7dafca-efde-40f5-8f2c-608d1b8ea0bd&paynowreference=19310&hash=54CDDD8F...
```

### Express Checkout (EcoCash) — Success
```
status=Ok&instructions=Dial+*151*2*4%23%0d%0aEnter+your+EcoCash+PIN%0d%0aOnce+you+have+authorised+the+payment+via+your+handset%2c+please+click+Check+For+Payment+below+to+conclude+this+transaction.&paynowreference=19309&pollurl=https%3a%2f%2fstaging.paynow.co.zw%2fInterface%2fCheckPayment%2f%3fguid%3ddd564a16-0e75-432e-acd0-4f0a7bd446b1&hash=45E576...
```

### Status Check (GET) — Awaiting Delivery
```
reference=TEST-1234&paynowreference=19309&amount=1.23&status=Awaiting+Delivery&pollurl=...&paymentchannel=EcoCash&merchantfees=0.0000&customerfees=0.5400&paymentinstrument=263772345678&paymentinstrumentname=263772345678&paymentchannelreference=&paymentfraudscore=&paymentfrauddecision=&paymentinstrumentnationality=domestic&paymentinstrumentx=102&paymentchannelinitiated=08-Nov-2022+15%3a19%3a03&paymentchannelcompleted=08-Nov-2022+15%3a19%3a41&hash=...
```

> Note: status check response also includes `merchantfees`, `customerfees`, `paymentinstrumentx`, `paymentchannelinitiated`, `paymentchannelcompleted` fields not documented in the Word doc.

---

| Error Message | Likely Cause |
|--------------|--------------|
| `Invalid amount field` | Amount has currency symbol, wrong decimal format, or missing |
| `Invalid hash` | Key/value order mismatch, URL-encoded values in hash, wrong Integration Key |
| `Insufficient balance` | EcoCash wallet has insufficient funds (test: use 0774444444) |
| `ElectronicCommerceIndicator ThreeDSecure or ThreeDSecureAttempted required` | 3DS challenge result not properly forwarded |
| `Integration not found` | Wrong Integration ID |
| Integration in test mode error shown to customer | Only merchant account can test — customer sees blocking message |
