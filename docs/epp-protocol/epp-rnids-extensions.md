# RNIDS Extension Reference

Consolidated reference for RsReg2-specific extensions described in the historical
`EPP-commands.txt` appendix. See the [reference index](epp-reference-index.md) for
scope and the [registry compatibility record](../registry-compatibility.md) for
verified development-registry behavior.

## 1) Domain Extension

### Namespace
`http://www.rnids.rs/epp/xml/domain-rnids-ext-1.0`

### Root Element
`domain-ext`

### Fields
- `remark` (`xs:token`, optional)
- `isWhoisPrivacy` (`xs:boolean`, optional)
- `operationMode` (`normal|secure`, optional)
- `notifyAdmin` (`xs:boolean`, optional)
- `dnsSec` (`xs:boolean`, optional)
- `isDomainVerified` (`xs:boolean`, optional)
- `isWhoisPrivacyPaid` (`xs:boolean`, optional)
- `domainVerifiedOn` (`xs:dateTime`, optional)
- `domainVerificationRequestExpiresOn` (`xs:dateTime`, optional)
- `whoisPrivacyPaidUntil` (`xs:dateTime`, optional)

### Operation-Context Notes
- Used in domain create/update extension requests.
- Returned in domain info extension response.
- Create/update inputs are `remark`, `isWhoisPrivacy`, `operationMode`, `notifyAdmin`,
  and `dnsSec`. Verification and payment fields are info response data; see the
  [Domain API](../api-domain.md) for the array keys and returned date objects.
- `operationMode` has strong behavioral impact (`secure` can trigger deferred approval flows for some updates).
- In documented behavior, stored DNSSEC state is driven by secDNS details, not only `dnsSec` flag value.

## 2) Contact Extension

### Namespace
`http://www.rnids.rs/epp/xml/contact-rnids-ext-1.0`

### Root Element
`contact-ext`

### Fields
- `identKind` (`personal_ID|personal_IDDocument|passport|other`)
- `identDescription` (`xs:token`)
- `identExpiry` (`xs:dateTime`)
- `isLegalEntity` (`xs:boolean`)
- `vatNo` (`xs:token`)
- `ident` (`xs:token`)

### Operation-Context Notes
- Used in contact create/update extension requests.
- Returned in contact info extension response.
- Public services expose these fields through shaped arrays and use typed DTO
  properties internally. See the [Contact API](../api-contact.md) for value types
  and supported clearing behavior.

## 3) Finance Info Object Schema

### Namespace
`urn:ietf:params:xml:ns:finance-1.0`

### Elements
- request: `finance:info`
- response: `finance:infData`
- payload field: `finance:balance` (`decimal`)

This protocol object is documented in [Finance and Poll](./epp-finance-and-poll.md).
The current SDK and CLI do not implement `finance:info`.
