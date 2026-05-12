# Slug Auto-Generation + `checkbox_list` & `radio` Field Types

Date: 2026-05-12
Target release: `v0.3.0`

## Summary

Two user-facing additions to the form builder, plus a cross-project rollout:

1. **Slug auto-generation** on the Filament resource — the `slug` field fills itself from `name` on the Create page only. On Edit, the slug stays manual to avoid breaking live public URLs and embed snippets.
2. **Two new field types**:
   - `checkbox_list` — multiple-choice via checkboxes; submission value is an array.
   - `radio` — single-choice via radio buttons; submission value is a string.
3. **Consumer rollout** — after the package tag is published and indexed on Packagist, all 10 local consumer projects get their `madbox-99/filament-form-builder` constraint bumped to `^0.3.0` and pushed.

## Architecture

### Slug auto-generation
`DetailsSection`:
- `TextInput::make('name')` gets `live(onBlur: true)` and `afterStateUpdated(...)`.
- The callback receives `$operation`; only when `$operation === 'create'` it sets `$set('slug', Str::slug($state ?? ''))`.
- The slug field itself stays editable in both Create and Edit (no `disabled()`).

### New field types

`FormFieldBlueprint` (`src/Support/FormFieldBlueprint.php`)
- New constants:
  - `TYPE_CHECKBOX_LIST = 'checkbox_list'`
  - `TYPE_RADIO = 'radio'`
- Added to `TYPES`.
- `WIDTH_ELIGIBLE_TYPES` adds `TYPE_RADIO` (full width for `checkbox_list` is fine — same reasoning as textarea).
- `fromBlock()`: `options` parsing already exists for select; extend the condition to also cover `radio` and `checkbox_list`.
- `validationRules()`:
  - `TYPE_RADIO` — same as select: `string`, `max:255`, `in:<values>` when options present.
  - `TYPE_CHECKBOX_LIST` — top rule is `array`; when required, add `min:1`; plus a sibling field-level rule for each item: `"<key>.*"` ⇒ `['string', 'in:<values>']`. The blueprint exposes a new `arrayItemValidationRules()` that the Livewire form aggregates into the full rules array.
- `defaultValue()`:
  - `TYPE_CHECKBOX_LIST` → `[]`
  - `TYPE_RADIO` → `''`

`FieldBlocks` (`src/Filament/Resources/RegistrationForms/Schemas/Sections/FieldBlocks.php`)
- Two new `Block` entries with the same `options` repeater as `select`.
- `radio` gets the width selector; `checkbox_list` doesn't.

### Livewire form aggregation
The Livewire form composes its `rules()` from each blueprint's `validationRules()`. We extend it to also pull `arrayItemValidationRules()` when present, keyed under `formData.<key>.*`.

### Blade rendering
`resources/views/livewire/public-registration-form.blade.php`
- New `@case(FormFieldBlueprint::TYPE_RADIO)` — `<input type="radio">` per option, all sharing the same `name`, `wire:model.blur="formData.<key>"`.
- New `@case(FormFieldBlueprint::TYPE_CHECKBOX_LIST)` — `<input type="checkbox" value="<option>">` per option, all `wire:model.blur="formData.<key>"` (Livewire binds to an array).
- The label is rendered outside the switch (it already is — both new types get the standard label, not the consent-style inline one used by single `checkbox`).

### Styles
`resources/views/partials/base-styles.blade.php`
- `.ffb-radio-group`, `.ffb-checkbox-group` — vertical stacks with consistent spacing.
- `.ffb-option` — individual row (input + label).
- Reuses existing color/spacing tokens; no new design tokens.

### Lang strings
`resources/lang/{en,hu}/form.php`
- `field_types.checkbox_list` — "Checkbox list" / "Több választós (checkbox)"
- `field_types.radio` — "Radio buttons" / "Rádió"

### Tests
Pest, in `tests/Unit/FormFieldBlueprintTest.php`:
- `radio` produces `in:<vals>` rule.
- `checkbox_list` required produces `array` + `min:1` and item-level `in:<vals>`.
- `checkbox_list` default value is `[]`; `radio` default is `''`.
- Width eligibility: `radio` yes, `checkbox_list` no.

## Rollout plan

1. Implement + tests green locally.
2. Commit on `main`, tag `v0.3.0`, push both.
3. Poll Packagist for v0.3.0 (autosync via webhook — confirmed working: latest indexed tag matches the latest GH tag).
4. For each consumer repo:
   - If working tree dirty: `git stash push -u -m "ffb-bump-stash"`.
   - Bump `madbox-99/filament-form-builder` constraint to `^0.3.0` in `composer.json`.
   - `composer update madbox-99/filament-form-builder --with-dependencies`.
   - Commit `composer.json` + `composer.lock` with a stock message.
   - Push to the current upstream branch.
   - If we stashed: `git stash pop`. If pop conflicts, report and skip the pop (leave stash in place for user).
5. Report at the end: per-repo status (✓ pushed / ⚠ skipped / ✗ failed).

### Special cases
- `controling` — on `feat/unified-google-oauth`, not `main`. Bump will land on that feature branch (per user instruction "do all of them"). Flagged.
- Repos with pre-existing dirty state (`stat-analitics`, `datamind`, `mes`, `marketinghub`, `controling`) — stashed before, popped after.

## Non-goals

- No new design tokens.
- No migration of existing `checkbox` rows to `checkbox_list`.
- No update of consumer apps' own code beyond `composer.json`/`composer.lock`. Forms in those projects already use the JSON shape stored by the package, so the new types are simply available once the version bump lands.
