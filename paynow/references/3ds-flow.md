# 3D Secure / SecureCode Challenge Flow

This applies to **Visa/Mastercard Express Checkout** transactions only.

## When It Triggers

After `POST /interface/remotetransaction`, if the response contains:

```
Status=Pending3ds
```

The card issuer requires the cardholder to verify their identity before payment proceeds.

## Response Fields When Status=Pending3ds

| Field | Description |
|-------|-------------|
| `AcsUrl` | URL to send the challenge form POST to |
| `PaReq` | Challenge payload (pass through as-is) |
| `MD` | Identifier linking challenge to the card |
| `CallbackUrl` | PayNow URL to send the challenge result back to (format: `https://www.paynow.co.zw/interface/remote3ds?guid=...`) |

## Step-by-Step Flow

### Step 1: Render the Challenge

Create an HTML form in your app/webview and POST to `AcsUrl`:

```html
<form method="POST" action="{AcsUrl}">
  <input type="hidden" name="PaReq" value="{PaReq}" />
  <input type="hidden" name="MD" value="{MD}" />
  <input type="hidden" name="TermUrl" value="https://yoursite.com/3ds-callback" />
</form>
<script>document.forms[0].submit();</script>
```

> `TermUrl` is YOUR server endpoint where the challenge result will be posted back.

The form renders the bank's 3DS challenge UI (password prompt, OTP, etc.).
In some cases the challenge auto-completes silently — handle this gracefully.

### Step 2: Receive Challenge Result at TermUrl

After the cardholder completes the challenge, the bank POSTs to your `TermUrl`:

| Field | Description |
|-------|-------------|
| `PaRes` | Challenge response payload |
| `MD` | Same identifier from Step 1 |

### Step 3: Forward to PayNow CallbackUrl

`POST https://www.paynow.co.zw/interface/remote3ds?guid=...` (the `CallbackUrl` from Step 1)

| Field | Type | Description |
|-------|------|-------------|
| `id` | Integer | Your Integration ID |
| `status` | String | Always `"Message"` |
| `pares` | String | `PaRes` value received at your TermUrl |
| `md` | String | `MD` value received at your TermUrl |
| `hash` | String | SHA512 hash of all fields + Integration Key |

### Successful Response

```
Status=Ok&PollUrl=https://...&PaynowReference=12345&Hash=...
```

### Failed Response

```
Status=Error&Error=ElectronicCommerceIndicator+ThreeDSecure+or+ThreeDSecureAttempted+required
```

## Implementation Notes

- After a successful 3DS callback, use the returned `PollUrl` to poll for final payment status
- The `CallbackUrl` from the initiate response must be stored server-side between Step 1 and Step 3
- For mobile apps, render the challenge in a WebView and intercept the POST to your `TermUrl`
- Validate hash on the successful remote3ds response before proceeding
