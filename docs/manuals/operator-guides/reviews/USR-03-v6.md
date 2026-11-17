# USR-03 v6 - Exact Version Review

Status: **Working draft; not accepted**. Date: 2026-10-20.
Baseline: v4, Te beoordelen; its bytes and decision remain unchanged.
New output: 1 A4 page(s). [New PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/usr-03-wachtwoord-resetten-v6-draft.pdf) | [Exact baseline v4](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/baselines/usr-03-wachtwoord-resetten-v4-draft.pdf) | [Visual comparison](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/review.html#USR-03).

PDF SHA-256: `32d140f25a7c7077e830d17a152b8f51b935204a7a2bbf75fd71d291b742c35c`.

## Reader And Reasoning

Intended role: Admin. Starting context: Correct person and private handoff opportunity.
Outcome: The correct user receives the saved temporary reset privately and proceeds to choose a private password.

Required alignment: Current Generate behavior, real save/login trial and the unchanged login-state boundary.

Chosen reader improvement: Give the whole reset control group a wider lower position so Generate remains legible.

Preserved support: Three actions, saved verification and the personal AC-02 handoff.

Placement/preservation tradeoff: No redundant screenshot of the user's later password task; that separate job remains in AC-02.

## Actions And Choices

- Identify the same user and open Gebruiker aanpassen.
- Genereer once, preserve login/rights, Save and read success.
- Hand over personally; the user follows AC-02 and never supplies the definitive password as proof.

## Concrete Changes

- Genereer vult beide velden; de bestaande loginstand en rechten blijven staan.
- Het resetformulier staat groter en buiten de instructietekst.
- Persoonlijke overdracht en AC-02, zonder verzoek om het uiteindelijke wachtwoord te tonen.

## Rendered Recognition Review

Every page was read as an instruction/reference at its intended page size and
compared with the retained baseline. Useful target text, complete control groups,
actual alternative/continuation meaning, conditions and handoffs were reviewed.
Screenshot labels were moved where they obscured native content; fresh crops
use their intended frame, not an older image's fitted content rectangle.
The following are actual displayed content sizes, not usability pass thresholds.

| Page | Evidence / recognition job key | Displayed content |
| --- | --- | --- |
| 1 | `usr-saved` | 69.0 x 16.6 mm |
| 1 | `usr-detail` | 69.0 x 10.8 mm |
| 1 | `usr-reset` | 97.0 x 23.9 mm |

## Evidence, Checks And Limits

The mounted application is the separate refurbisher-followups checkout,
not the root checkout. Source fingerprints, actual actors, Dutch locale,
owned synthetic fixtures and save/trial boundaries are recorded in the batch
provenance. Genuine English strings and narrow native status-choice text
remain product gaps. Header roles preserve the intended task allocation;
local seeded/custom grants can be wider than the operational-role document.
Physical printing/component work, destructive catalogue branches and an
unfamiliar-reader/A4 paper trial are unperformed. Artifact checks and editorial
read-back do not establish reader performance or exact acceptance.

The [batch review](remaining-guides-2026-10-20.md) records shared reconciliation,
actual UI trials, unperformed routes and final preservation/check results.
The [previous specification](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/inputs/USR-03-previous-specification.md) retains
the earlier reasoning and version history; its superseded procedure does not
override the current [specification](../guides/USR-03.md).
