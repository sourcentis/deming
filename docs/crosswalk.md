# Framework crosswalks

The `/crosswalk` module stores generic documentary relationships between controls. It contains no ISO-, NIS2-, or framework-specific application logic.

`frameworks` adds metadata to the codes already stored in `Domain.framework`; it does not replace that field or change its semantics. A `control_mappings` row is stored once from source to target and is read in either direction without creating a mirror row. When read in reverse, `covers` and `covered_by` are inverted. The reverse of `supports` is presented as the non-persisted label `supported_by`; reverse exports retain the stored source, target, and type so they cannot misstate the relation.

All authenticated users can read mappings, the matrix, and XLSX exports. Only administrators (`role=1`) can create, update, validate, or delete them. Matrix counts are documentary metrics, never compliance scores.

## XLSX import

```console
php artisan deming:import-mappings file.xlsx
php artisan deming:import-mappings file.xlsx --dry-run
php artisan deming:import-mappings file.xlsx --update
```

Required columns:

```text
source_framework, source_clause, target_framework, target_clause,
mapping_type, coverage, rationale, source_reference, source_url
```

The command validates every row before writing, then performs all writes in a single transaction. Updated mappings require human validation again.

`confidence` is an optional import column containing a number from 0 to 100. Exports include it for lossless round trips. On `--update`, an absent column preserves the existing value; a present but empty cell clears it.

## ReCyF 2.5 to ISO 2700X starter file

`storage/app/repository/ReCyF-2.5-ISO27001-2022.mappings.xlsx` contains 281 relations for 118 ReCyF controls and 66 ISO controls present in Deming's `ISO27001-2022.fr.xlsx` workbook.

The workbook contains relationships only; it does not create controls. Before importing it, the instance must contain source controls under the exact `Domain.framework` code `NIS2-ReCyF-2.5-FR` and target controls under `27001:2022`. Run `--dry-run` on the target instance first; any missing or ambiguous clause aborts the complete import.

Only references published in the official [ANSSI comparator](https://messervices.cyber.gouv.fr/nis2#exigences) and matched to an exact Deming ISO clause are included. Every row uses `related`; it does not assert equivalence or that ISO conformity implies NIS2 conformity. ANSSI's original level, observation, ISO reference, normalized Deming clause, and source URL are retained for review.
