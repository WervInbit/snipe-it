# Refurbisher Workflow Testing

The refurbisher start page and QR scan flow have been implemented for mobile use. After this initial release, plan a round of user testing to gather feedback on button placement, wording, and the scan→test→done ordering. Test with real devices in typical indoor lighting.

- QR scanning performed well at 480x360 resolution with a 200ms sampling interval during indoor tests.
- Be prepared to adjust layout and copy based on user feedback.

To enable the pre-commit guard that blocks compiled assets, run:

```bash
git config core.hooksPath .githooks
```
