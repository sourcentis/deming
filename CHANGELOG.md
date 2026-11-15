# Changelog

All notable changes to Deming are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Monitoring: in the contributor analysis, user and group names link to their
  page (administrators get every link, other users only their own page).
- AI settings: a notice explains how to get an access key or use your own AI
  engine.
- Security: a `Content-Security-Policy` header is sent on every page (inline
  scripts allowed by a per-request nonce, no inline event handler, no framing by
  another site). New installations enforce it (`CSP_REPORT_ONLY=false` in
  `.env.example`). When updating, the policy is only reported while
  `CSP_REPORT_ONLY` is missing from the `.env` file: check the browser console,
  then add `CSP_REPORT_ONLY=false` to enforce it.

### Changed

- The Markdown editor toolbar uses Bootstrap Icons instead of Font Awesome,
  which was downloaded from a CDN.
- The `AI_ACTIVATED` parameter of the `.env` file is removed: the AI tab of the
  settings is always shown. AI remains disabled until an administrator enables
  it in the settings.

### Fixed

- Security: the model and observations of a control, the description of a
  group and the user name in the audit log are escaped, and Markdown fields no
  longer render raw HTML nor `javascript:` links.
- Accepting a control no longer changes its realisation date: the date entered
  when the control was made is kept, and the field is read-only on the
  validation form.
