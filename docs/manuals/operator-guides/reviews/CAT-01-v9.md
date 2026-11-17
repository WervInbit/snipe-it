# CAT-01 v9 - Exact Version Review

Status: **Working draft; not accepted**. Date: 2026-10-20.
Baseline: v7, Gereed voor gebruikersreview; its bytes and decision remain unchanged.
New output: 5 A4 page(s). [New PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/CAT-01-model-en-modelnummer-aanmaken-v9-draft.pdf) | [Exact baseline v7](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/baselines/CAT-01-model-en-modelnummer-aanmaken-v7-draft.pdf) | [Visual comparison](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/review.html#CAT-01).

PDF SHA-256: `24399e278e84e0b85750f4ed1a1ed59d1bd80903821e4e92dd675a64172c6403`.

## Reader And Reasoning

Intended role: Supervisor. Starting context: Physical product label and confirmed manufacturer identity.
Outcome: A reusable product family contains the verified exact manufacturer model code without a duplicate.

Required alignment: Current actual Model Numbers route, canonical code and owning model/specification handoffs.

Chosen reader improvement: Apply search before capture, show the complete matching row and use page width for readable searches and Create Model Number.

Preserved support: Five-page A/B/C branching and the distinction between reusable product, exact code and one physical asset.

Placement/preservation tradeoff: Reuse useful later chapters; enlarge recognition at the search and create entry rather than shrinking screenshots to their predecessor's fitted content box.

## Actions And Choices

- 1-2: search product/generation in Asset modellen and complete printed code in Model Numbers.
- 3: A exact code exists -> page 5; B family exists/code missing -> page 4; C family missing -> page 3.
- 4: create only the missing reusable Basismodel with name/category/manufacturer.
- 5-6: open Create Model Number and save the full code, intentional Aa and default configuration label.
- 7-8: verify saved identity/category/manufacturer and hand off to CAT-02 or AST-03.

## Concrete Changes

- Werkelijk toegepaste zoekactie met de complete gevonden rij; aparte zoekactie op de volledige modelcode.
- A/B/C-routes blijven behouden; alleen ontbrekende objecten worden aangemaakt.
- Zoekbeelden en Create Model Number gebruiken meer nuttige breedte; exacte code, Aa en standaardconfiguratielabel blijven uitgelegd.

## Rendered Recognition Review

Every page was read as an instruction/reference at its intended page size and
compared with the retained baseline. Useful target text, complete control groups,
actual alternative/continuation meaning, conditions and handoffs were reviewed.
Screenshot labels were moved where they obscured native content; fresh crops
use their intended frame, not an older image's fitted content rectangle.
The following are actual displayed content sizes, not usability pass thresholds.

| Page | Evidence / recognition job key | Displayed content |
| --- | --- | --- |
| 1 | `cat-nav` | 31.9 x 34.0 mm |
| 1 | `models` | 174.0 x 50.1 mm |
| 1 | `numbers` | 174.0 x 26.4 mm |
| 2 | `model` | 79.0 x 18.4 mm |
| 2 | `models-create-action` | 69.4 x 20.0 mm |
| 3 | `model-create` | 160.7 x 56.0 mm |
| 4 | `model-entry` | 164.7 x 18.3 mm |
| 4 | `number-create` | 96.0 x 45.9 mm |
| 5 | `model` | 112.0 x 26.1 mm |
| 5 | `model-info` | 49.0 x 18.1 mm |

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
The [previous specification](../../../../resources/manuals/operator-guides/review-rounds/2026-10-20-remaining-guides/inputs/CAT-01-previous-specification.md) retains
the earlier reasoning and version history; its superseded procedure does not
override the current [specification](../guides/CAT-01.md).
