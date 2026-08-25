# USR-01 v9 Review

| Field | Value |
| --- | --- |
| Previous version | v8, Internal review candidate for V1 |
| Feedback source | Floor/user feedback, 2026-08-18 |
| Impact | Step wording and navigation clarity |
| Status | Working draft; awaiting exact-version review |

## Correction

- Replace `Onderaan` in step 3 with the complete route: remain on
  `Informatie`, open the collapsed `Optionele informatie` bar, then select the
  standard group at `Groepen`.
- Keep `Machtigingen` as the separate top tab for direct rights.
- Reuse the existing 3A group-selector evidence and update its caption to
  state the required disclosure step.
- Use `Open Optionele informatie` as the compact step title so it remains clear
  of image badge 3A.

## QA

- Generated successfully as one A4 page; the `Optionele informatie` route and
  `Groepen` destination are present and the ambiguous `Onderaan` instruction
  is absent.
- Component geometry passed for 12 badges and five guide chips. Full-page
  rendered review passed after shortening the step title to clear image 3A.
- The default generator still renders pixel-identically to accepted v8; the
  accepted PDF and checksum remain unchanged.
