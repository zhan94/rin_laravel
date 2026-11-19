# AI-assisted development

## Audit

The owner commissioned a read-only audit of the Laravel CRM against the existing OOP application. Codex traced HTTP entry points, authorization, credential handling, booking and payment writes, reporting, dependency lockfiles, and the legacy import. It used the OOP and procedural applications as references. The audit findings below are a summary of the engineering work. The [initial read-only audit](initial-audit.md) preserves the original findings and measured baseline, with workstation paths removed.

| Finding | Remediation and verification |
| --- | --- |
| Public registration allowed staff enrollment. Administrator routes accepted a higher role or a higher-privilege target. | Disabled public registration and implemented Laravel policy checks for actor, target, and role assignment. API and feature regressions exercise the role matrix and prohibited actions. |
| Account disabling and credential changes left inconsistent authentication paths. A stored password hash could be accepted as a submitted password. | Unified current-account checks, invalidated sessions and tokens, bounded token scopes and lifetime, and hardened recovery and passkey handling. Regressions cover the authentication boundaries. |
| Browsers could reach some API writes outside the expected CSRF boundary. | Used Sanctum's stateful CSRF middleware for first-party sessions. Tested legitimate browser requests and rejected untrusted or missing CSRF state. |
| Default seeding could modify a prepared database. The manual contained screenshots whose data provenance was unverified. | Guarded seeding, stopped the destructive setup path, removed the former screenshots, and generated replacements in an isolated demo seeded with synthetic records. |
| Report printing treated editable values as HTML. CSV cells could be interpreted as spreadsheet formulas. | Rendered text nodes and escaped CSV cells. Unit tests cover unsafe input shapes. Chrome verified a real CSV download and print window. |
| Booking capacity and payment writes had gaps under parallel requests or retries. | Used database transactions, row locks, idempotency receipts, and a stale-balance check. Independent MySQL processes raced for one remaining seat and one outstanding balance. |
| Dashboard statistics were placeholders. The dependency audits and static checks exposed outstanding issues. | Added database-backed aggregates and fixed the identified quality issues. The initial scans identified 50 Composer advisories and 32 vulnerable npm packages. Compatible updates brought both lockfile scans to zero reports at verification time. |

## Human decisions and review

The owner authorized the private legacy import, including the one season-default replacement and the records whose synthetic provenance could not be established. Those approvals applied to the import only. The owner later approved the phased remediation and accepted the local verification results before authorizing repository preparation. After confirming the supplied GitLab project had no refs, the owner explicitly authorized a new local Git history from the verified source. The approval covers local commits and checkout verification. The prepared database remained untouched during remediation and repository preparation.

Codex wrote and reviewed the remediation, regression tests, and CI configuration. Automated checks produced the test, scan, and timing results documented in the acceptance report. The owner's approvals constitute the human decisions recorded here. No independent human code review or hosted CI sign-off has been recorded. Publication and any historical cleanup require the owner's separate approval.

## Engineering decisions

The application kept its Vue interface and existing service and repository structure. Laravel policies, middleware, Eloquent relationships, and transaction APIs cover common behavior. The custom receipt and locking code addresses writes that need an atomic retry boundary. Older API callers can still omit a receipt key. Full report exports retain browser pagination, which was verified for correctness on the demo rather than benchmarked at the 100,000-customer size.

Tests used isolated in-memory SQLite, a disposable MySQL server, and Chrome with generated demo accounts. Docker verification used separate Compose networks and database volumes for Laravel and OOP. At repository preparation, GitLab and GitHub used the shared verification script. The repository starts with the verified source import. It records current development activity with actual commit times and explicit AI attribution. The older candidate retains its own history. The local source import and CI changes use the agent identity `Codex <codex@example.test>` with normal Git commit dates. The cold and warm Docker measurements came from fresh Git clones. [Final acceptance report](final-acceptance.md)

## Recruitment preparation

The owner asked, “Do not restart the modernization process.” The scope reused the existing Git baseline, security audit, import documentation, and verified application behavior. The owner also instructed, “Do not suppress errors, weaken tests or modify quality thresholds simply to obtain passing results.” The local acceptance pass updated the repository with the already verified profile and localhost authentication fixes, traced the Driver report fields from the Vue visibility rules through the API, and added a regression for fields and search behavior that the earlier report test did not cover.

The browser coverage was expanded to load every CRM module with the isolated Super Admin account and check the German profile label. The historical audit and the new check use different evidence. The audit inspected code and reported vulnerabilities despite a passing test suite. The new check exercises the patched endpoints, records outputs from the current lockfile audits, and uses clean Git clones for Docker installation. The local evaluation runs and act of committing are agent work. The owner defined the evaluation scope and approved the earlier remediation phases and new local Git history. Publication approval, review of the rights to distribute assets, and a human code review have separate recorded statuses in the [acceptance report](final-acceptance.md).


## Final product review

The owner requested a final release evaluation that reused prior acceptance evidence and kept verification isolated. Codex reviewed all staff modules, compared source and browser behavior, and made targeted corrections to report filters and exports, unpaid payments, period overlaps, role boundaries, mobile account controls and session-aware sign-out. It retained the existing schema and lockfiles. The English manual and synthetic screenshots were refreshed using the current Laravel application.

The owner approved the local product acceptance and set GitHub as the sole destination. Codex completed the final translation and accessibility corrections, checked the synthetic screenshots and bundled asset provenance, and validated the GitHub workflow locally. The owner approved local acceptance. Rights clearance, access and visibility of the GitHub destination, hosted CI, and permission to publish have separate statuses. [Publication readiness report](publication-readiness.md)

Agent-run evidence includes the five-role interactive walkthrough, an instrumented Chrome smoke run, the clean Docker quality and secret gates, the SQLite and MySQL 100,000-customer benchmark, and both MySQL concurrency checks. The report records the initial browser failures found during review and the final passing results. The commits record current work and carry the existing agent authorship. [Final product acceptance](product-quality-review.md)
