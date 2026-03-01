# Contact Domain Update and Live Lifecycle Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver domain update command support and live contact reassignment coverage, then finalize docs and full verification.

**Architecture:** Add a new domain update command path to the Domain service layer using typed DTOs plus XML builder/parser, following the existing Domain/Host command architecture. Then consume that API in a new dedicated live contact integration class split into two scenarios for better isolation. Finish by aligning docs and running full regression gates.

**Tech Stack:** PHP 8+, PHPUnit, PHPCS, PHPStan, RNIDS EPP XML command builders/parsers.

---

### Task 1: Split Task Tracking (27a/27b)

**Files:**
- Create: `tasks/27a-domain-update-command-support.md`
- Create: `tasks/27b-contact-live-domain-reassignment.md`

**Step 1: Verify new task files exist**

Run: `ls -la tasks/27a-domain-update-command-support.md tasks/27b-contact-live-domain-reassignment.md`
Expected: both files listed

**Step 2: Commit task split metadata**

```bash
git add tasks/27a-domain-update-command-support.md tasks/27b-contact-live-domain-reassignment.md
git commit -m "chore(tasks): split contact live flow into 27a and 27b"
```

### Task 2: Domain Update XML Builder and Parser (27a)

**Files:**
- Create: `src/Domain/Dto/DomainUpdateRequest.php`
- Create: `src/Domain/Dto/DomainUpdateSection.php`
- Create: `src/Domain/Dto/DomainUpdateResponse.php`
- Create: `src/Xml/Domain/DomainUpdateRequestBuilder.php`
- Create: `src/Xml/Domain/DomainUpdateResponseParser.php`
- Test: `tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php`
- Test: `tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`

**Step 1: Write failing XML builder/parser tests**

- Assert builder emits `<domain:update>` envelope.
- Assert add/rem contact fragments are serialized with escaped values.
- Assert parser returns typed response metadata DTO.

**Step 2: Run tests to verify failure**

Run: `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`
Expected: FAIL (missing classes)

**Step 3: Write minimal DTO + builder/parser implementation**

- Implement request section DTOs and response DTO.
- Implement XML generation for `name`, optional `add`, optional `rem`, optional `chg`.
- Reuse existing Domain contact/nameserver DTOs where practical.

**Step 4: Run tests to verify pass**

Run: `vendor/bin/phpunit tests/Unit/Xml/Domain/DomainUpdateRequestBuilderTest.php tests/Unit/Xml/Domain/DomainUpdateResponseParserTest.php`
Expected: PASS

**Step 5: Commit**

```bash
git add src/Domain/Dto src/Xml/Domain tests/Unit/Xml/Domain
git commit -m "feat(domain): add domain update xml builder and parser"
```

### Task 3: DomainService Update Wiring (27a)

**Files:**
- Modify: `src/Domain/DomainService.php`
- Modify: `src/Domain/DomainInputNormalizer.php`
- Modify: `src/Domain/DomainResponseMapper.php`
- Test: `tests/Unit/Domain/DomainServiceTest.php`
- Test: `tests/Unit/Domain/DomainInputNormalizerTest.php`

**Step 1: Write failing service/normalizer tests**

- Add service test that calls `update()` and asserts payload includes `domain:update` and mapped empty response.
- Add validation test for missing mutation sections.
- Add any normalizer tests for required update keys (`name`, section shape).

**Step 2: Run tests to verify failure**

Run: `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php tests/Unit/Domain/DomainInputNormalizerTest.php --filter update`
Expected: FAIL (missing method/validation)

**Step 3: Implement minimal service + normalizer changes**

- Add `DomainService::update(array $request): array`.
- Build `DomainUpdateRequest` from normalized input.
- Execute with update builder/parser, return empty mapped response.
- Enforce at least one mutation section in request.

**Step 4: Run targeted tests**

Run: `vendor/bin/phpunit tests/Unit/Domain/DomainServiceTest.php tests/Unit/Domain/DomainInputNormalizerTest.php --filter update`
Expected: PASS

**Step 5: Run broader domain unit checks**

Run: `vendor/bin/phpunit tests/Unit/Domain tests/Unit/Xml/Domain`
Expected: PASS

**Step 6: Commit**

```bash
git add src/Domain src/Xml/Domain tests/Unit/Domain tests/Unit/Xml/Domain
git commit -m "feat(domain): add update command support"
```

### Task 4: Live Contact Integration Split (27b)

**Files:**
- Create: `tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php`
- Modify: `tests/Integration/Support/IntegrationConfig.php`

**Step 1: Write failing integration tests (skip-friendly)**

- Scenario A grouped `contact-lifecycle`.
- Scenario B grouped `contact-domain-reassign`.
- Ensure skip guards are explicit for missing env/fixtures.

**Step 2: Run integration class and verify expected state**

Run: `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped`
Expected: SKIP in non-live env; otherwise execute and fail until implementation complete.

**Step 3: Implement integration flow with cleanup**

- Create unique contact via fixture helper.
- Perform update/info assertions.
- Capture admin/tech handles from stable domain info.
- Reassign using `domain()->update()` with add/rem contacts.
- Reset original contacts.
- Delete created contact in cleanup path.

**Step 4: Re-run integration verification**

Run: `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped`
Expected: PASS or explicit SKIP

**Step 5: Commit**

```bash
git add tests/Integration
git commit -m "test(integration): add live contact lifecycle and domain reassignment scenarios"
```

### Task 5: Documentation Alignment (Task 28)

**Files:**
- Modify: `docs/api-contact.md`
- Modify: `docs/api-reference.md` (if needed)
- Modify: `README.md` (if needed)

**Step 1: Write/update docs for enforced contact policy**

- ID optional create auto-generation.
- OBL- normalization behavior.
- Forced extension identDescription behavior.

**Step 2: Verify docs consistency checks**

Run: `vendor/bin/phpcs docs README.md`
Expected: PASS

Run: `rg -n "contact|OBL-|identDescription|Object Creation provided by Oblak Solutions" docs README.md`
Expected: policy references present and consistent

**Step 3: Commit**

```bash
git add docs README.md
git commit -m "docs(contact): align api contract with runtime policy"
```

### Task 6: Final Verification and Signoff (Task 29)

**Files:**
- Modify: `tasks/completed-27a.md` (create if not present)
- Modify: `tasks/completed-27b.md` (create if not present)
- Modify: `tasks/completed-28.md` (create if not present)
- Modify: `tasks/completed-29.md` (create if not present)

**Step 1: Execute required verification gates**

Run: `vendor/bin/phpunit tests/Unit/Contact tests/Unit/Xml/Contact`
Expected: PASS

Run: `vendor/bin/phpunit tests/Integration/RnidsLiveContactLifecycleIntegrationTest.php --display-skipped`
Expected: PASS or explicit SKIP reason

Run: `vendor/bin/phpunit`
Expected: PASS

Run: `vendor/bin/phpstan analyse --debug --no-progress --memory-limit=1G`
Expected: PASS

Run: `vendor/bin/phpcs`
Expected: PASS

**Step 2: Record outcomes and residual risks**

- Note exact command results.
- Capture any environment-dependent skips.
- Record non-blocking follow-up items.

**Step 3: Commit signoff notes**

```bash
git add tasks/completed-27a.md tasks/completed-27b.md tasks/completed-28.md tasks/completed-29.md
git commit -m "chore(tasks): record final verification and signoff evidence"
```
