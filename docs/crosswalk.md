# Framework crosswalks

The `/crosswalk` module manages generic documentary relationships between two controls. It contains no rules specific to ISO, NIS2, or any other framework.

## Data model

The `frameworks` table adds metadata to the codes already present in `Domain.framework`. It does not replace that field and does not change its semantics. The initial migration seeds a metadata row for each existing code. Subsequently saving a domain also seeds the minimal metadata for its code.

A `control_mappings` row is stored once, from `source_control_id` to `target_control_id`. The application also reads this relationship in the reverse direction. In that case, `covers` becomes `covered_by`, and vice versa. The reverse of `supports` is displayed as `supported_by` ("benefits from the support of") without adding this value to the persisted types. No mirror row is created.

The allowed types are `equivalent`, `covers`, `covered_by`, `partial`, `supports`, and `related`. The allowed coverage values are `full`, `high`, `medium`, `low`, and `none`.

## Permissions and screens

All authenticated users can view the list, detail, matrix, and exports. Only administrators (`role=1`) can create, update, validate, or delete a relationship.

The matrix presents documentary metrics: number of source controls, number of controls with a relationship, number of controls without a relationship, and number of relationships. These values are never compliance scores.

## XLSX import

Command:

```console
php artisan deming:import-mappings file.xlsx
```

Options:

- `--dry-run` validates the entire file without writing;
- `--update` allows updating an existing source/target pair.

Required columns:

```text
source_framework, source_clause, target_framework, target_clause,
mapping_type, coverage, rationale, source_reference, source_url
```

The `confidence` column is optional (a number from 0 to 100). It is included in exports to allow lossless round trips. If the column is absent during `--update`, the existing value is preserved; if it is present with an empty cell, it is cleared. When a `supports` relationship is exported from a reversed view, its persisted source, target, and type are preserved: exporting a directional row must never turn "A supports B" into "B supports A".

All rows are validated before the first write. The writes are then performed in a single transaction. An error therefore prevents any partial import. A relationship modified by import must be validated again.

## Initial ReCyF 2.5 to ISO 2700X dataset

`storage/app/repository/ReCyF-2.5-ISO27001-2022.mappings.xlsx` contains 281 relations from the official ANSSI comparator. They cover 118 ReCyF controls and 66 ISO controls present in `ISO27001-2022.fr.xlsx`.

This workbook contains only the relationships: it does not create the controls. Before importing, the instance must therefore already contain the source controls under the exact code `Domain.framework = NIS2-ReCyF-2.5-FR` and the target controls under `Domain.framework = 27001:2022`. This is notably the case for the 152-control ReCyF instance described for this batch. Always run `--dry-run` on the target instance before the actual import; a missing or ambiguous clause blocks the entire file.

The dataset is deliberately conservative:

- only references published by ANSSI and found exactly in Deming's ISO workbook are included;
- each relationship is of type `related`, because a documentary correspondence cannot establish equivalence or imply compliance;
- ANSSI's `ÉLEVÉE`, `MOYENNE`, and `FAIBLE/NULLE` levels are converted respectively to `high`, `medium`, and `low`, while the original label remains in the rationale;
- the ANSSI observation, the original ISO reference, the normalized Deming clause, and the source URL are retained;
- all imported relationships remain unvalidated until a human review in Deming.

Sources: [ANSSI comparator](https://messervices.cyber.gouv.fr/nis2#exigences) and [presentation of ReCyF and the comparator](https://lab.cyber.gouv.fr/les-actualit%C3%A9s-du-lab-anssi/recyf--publication-du-r%C3%A9f%C3%A9rentiel-dexigences-et-du-comparateur/).
