# Changelog

All notable changes to Deming are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Monitoring: in the contributor analysis, user and group names link to their
  page (administrators get every link, other users only their own page).
- AI settings: a notice explains how to get an access key or use your own AI
  engine.

### Changed

- The `AI_ACTIVATED` parameter of the `.env` file is removed: the AI tab of the
  settings is always shown. AI remains disabled until an administrator enables
  it in the settings.

### Fixed

- Accepting a control no longer changes its realisation date: the date entered
  when the control was made is kept, and the field is read-only on the
  validation form.
