# Crosswalks

This directory contains the crosswalks (control mappings) between the clauses of two referentials
stored in [`../repository`](../repository/README.md). See [docs/crosswalk.md](../../../docs/crosswalk.md).

Each file is an XLSX workbook named `<source>-<target>.mappings.xlsx`. The `mappings` suffix, the
column names and the import command keep the data-layer vocabulary (`control_mappings`).

| File                                    | Description                                   |
|-----------------------------------------|-----------------------------------------------|
| ReCyF-2.5-ISO27001-2022.mappings.xlsx   | ANSSI ReCyF v2.5 (NIS 2) to ISO/IEC 27001:2022 |

## Format

The first row contains the column headers. All columns except `confidence` must be present;
"Value required" tells whether the cell may be left empty.

| Column             | Value required | Description                                                                 |
|--------------------|----------------|-----------------------------------------------------------------------------|
| source_framework   | yes            | Code of the source referential (must already exist)                         |
| source_clause      | yes            | Clause identifier in the source referential                                 |
| target_framework   | yes            | Code of the target referential (must already exist)                         |
| target_clause      | yes            | Clause identifier in the target referential                                 |
| mapping_type       | yes            | `equivalent`, `covers`, `covered_by`, `partial`, `supports` or `related`    |
| coverage           | no             | `full`, `high`, `medium`, `low` or `none`                                   |
| rationale          | no             | Why the two clauses are mapped                                              |
| source_reference   | no             | Reference of the document the mapping comes from                            |
| source_url         | no             | URL of that document                                                        |
| confidence         | no             | Number between 0 and 100 (optional column)                                  |

## Import

```bash
php artisan deming:import-mappings storage/app/crosswalks/<file>.mappings.xlsx
```

Options:

- `--dry-run`: validate the file and report what would change, without writing anything.
- `--update`: update the mappings that already exist instead of leaving them unchanged.

The import is atomic: if any row is invalid, no mapping is changed.
