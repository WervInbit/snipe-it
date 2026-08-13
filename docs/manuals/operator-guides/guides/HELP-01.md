# HELP-01 Problemen En Hulp

| Field | Current value |
| --- | --- |
| Status | Working draft; awaiting review |
| Family | HELP |
| Type | Troubleshooting |
| Current version | `HELP-01-problems-v6-draft` |
| Page model | One page |
| Layout recipe | `troubleshooting-grid` |
| Generator | `scripts/manuals/generate-component-followup-guides.mjs` |
| Role | Iedereen |

## Purpose

Provide short recovery and escalation choices without overloading every task guide.

## Current Topics

- Geen account.
- Wachtwoord kwijt.
- Geen telefoon.
- Camera opent niet.
- QR beschadigd.
- QR opent verkeerd asset of de fysieke identiteit komt niet overeen.
- Geen workflow.
- Geen rechten.
- Printer/label faalt.
- Algemene stopregel when digital and physical identity do not match.
- Dubbele asset tag of serienummer bij registratie.
- Component niet in tray of opslag.
- Component of fysieke identiteit komt niet overeen.

## Structure

- Use problem tiles or short rows rather than sequential step numbers.
- Each topic gives one immediate safe action and, when useful, one related guide.
- A central general stop rule is allowed because troubleshooting is the purpose of this guide.

## Complete When

The operator knows which safe recovery action, guide, or person is needed before continuing.

## Work Remaining

- Review the twelve recovery tiles and guide references with the user.
- Confirm the printer/label escalation wording with local support practice.
- Confirm the exact local supervisor/support contact wording.
- Decide real QR destination or omit it before third-party approval.

## Technical Verification

- 2026-08-04: v6 keeps twelve compact non-sequential recovery tiles, adds component/tray failures, preserves supervisor-only password reset, and removes self-referencing help chips.
- 2026-08-04: exported PDF contains exactly one A4 page and matches the reviewed PNG proof.

## Current Output

```text
output/manuals/proofs/component-followup-v2
```
