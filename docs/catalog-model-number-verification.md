# Catalog Model-Number Verification

The shared device catalog distinguishes identifiers verified for production
catalog use from synthesized demo placeholders. A descriptive combination such
as model, memory, storage, or color is not presented as a manufacturer part
number unless that exact manufacturer identifier has been sourced.

`verified_catalog_identifier` is a seed-eligibility status, not an assertion that
an internal variant code is an official manufacturer MPN.

## Production behavior

`DevicePresetSeeder`, as invoked by `ProductionFoundationSeeder`, seeds shared
blueprints only when their `model_number_verification` value is
`verified_catalog_identifier`. Missing or unknown verification metadata is
fail-closed and the blueprint is skipped.

`DeviceComponentCatalogSeeder` attaches expected-component templates only to
model numbers allowed by the same policy. When an older installation already
contains one of the demo placeholders, a production rerun removes only template
links owned by `DeviceComponentCatalogSeeder`. It does not delete or rewrite:

- the asset model or model-number row;
- assets that reference the row;
- model-number attributes;
- operator-created expected-component templates.

That preservation is intentional because prior seed output may already have
been reviewed, edited, or referenced by operational records. Operators upgrading
an existing database should inspect the five codes below and deprecate, merge,
or replace them only after identifying their actual hardware.

## Demo-only placeholders

These synthesized values are not manufacturer-confirmed part numbers:

| Device | Demo placeholder |
| --- | --- |
| HP ProBook 430 G3 | `HP-430G3-I3-4-128` |
| Microsoft Surface Pro 4 | `MS-SURFPRO4-I5-4-128` |
| Microsoft Surface Pro 5 | `MS-SURFPRO5-I5-4-128` |
| Apple iPhone 12 | `IP12-128-BLUE` |
| Google Pixel 8 Pro | `PIXEL8PRO-256-OBSIDIAN` |

They remain available only when both of the following are true:

- the application environment is `local` or `testing`;
- `SNIPEIT_ALLOW_DISPOSABLE_DATA_SEEDING=true` is explicitly set for the
  seeding process;

That opt-in is reserved for the guarded demo/development seeders.

Demo labels begin with `DEMO placeholder -` so screenshots and exported sample
data do not present these values as verified identifiers.

## Promoting a catalog entry

Do not replace a placeholder with a guessed stock-keeping unit. Before changing
its verification status:

1. Confirm the exact identifier from the physical device label or an
   authoritative manufacturer source.
2. Confirm that the seeded specifications and expected components apply to that
   exact variant.
3. Replace the placeholder code and label in the blueprint and component-template
   map.
4. Set `model_number_verification` to
   `verified_catalog_identifier`.
5. Add or update focused seeder tests for the verified code.

Existing rows using the old placeholder require an explicit operator migration;
the additive foundation seeder will not silently retarget them.
