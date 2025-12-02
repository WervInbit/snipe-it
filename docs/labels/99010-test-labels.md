# 99010 Test Labels (Manual)

Use this folder to store the two-size 99010 test label assets so it is easy to print without rebuilding QR templates.

- Existing asset: `docs/labels/Test label 28 en 25.label` (Dymo two-size 99010 label). Open/print via the Dymo tooling or send as raw to CUPS (`lp -d "$LABEL_PRINTER_QUEUE" -o raw "docs/labels/Test label 28 en 25.label"`), depending on driver support.
- Optional PDF: drop a copy at `docs/labels/99010-two-size-test.pdf` if you prefer PDF-based prints; use `lp -d "$LABEL_PRINTER_QUEUE" docs/labels/99010-two-size-test.pdf`.
- The app UI does not auto-serve these static files; open/print them directly when calibrating the LabelWriter or comparing layouts.
- If you prefer to keep test files untracked, add them to `.gitignore` after placing them locally.
