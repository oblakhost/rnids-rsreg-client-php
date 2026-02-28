# RNIDS Extension Reference

Consolidated reference for RsReg2-specific extensions described in `EPP-commands.txt` appendix.

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
- These fields should be modeled as typed first-class DTO properties in this library API.

## 3) Finance Info Object Schema

### Namespace
`urn:ietf:params:xml:ns:finance-1.0`

### Elements
- request: `finance:info`
- response: `finance:infData`
- payload field: `finance:balance` (`decimal`)

This is functionally used by the `finance:info` command and documented separately in [Finance and Poll](./epp-finance-and-poll.md).
