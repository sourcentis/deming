# AI agent

Deming can call an artificial intelligence agent to analyse a [control](measures.md#show), a
[measurement](controls.md#show), an [action plan](actions.md#show) or a [risk](risks.md#show). The agent returns a
structured opinion: summary, assessed status, risk level, findings, recommendations, open questions and four points
of view (CEO, CISO, DPO and auditor).

The agent is a **decision aid**: it never changes Deming's data. Its results must be validated by a human.

AI is **disabled by default**. Until an administrator enables it, no data is sent and the analysis icon is not
displayed.

The agent is a service compatible with the OpenAI API (`/v1/chat/completions`). By default, Deming is configured for
[OVHcloud AI Endpoints](https://www.ovhcloud.com/en/public-cloud/ai-endpoints/) with the `gpt-oss-120b` model, but
any other compatible provider or a local model (Ollama, vLLM, LM Studio…) can be used.

## Enable and configure {#config}

The configuration screen is available from the "Configuration → AI" menu. It is restricted to administrators.

<!-- TODO capture : images/ai-config.png -->

The screen contains a "Data protection" notice that summarizes what is sent to the agent; read the
[Data protection](#privacy) section before enabling AI.

The fields are:

* **Enable AI**: enables or disables the agent. When disabled, no request is sent to the agent, but the analyses
  already saved are kept (see [Delete all analyses](#purge));

* **AI agent URL**: the base address of the OpenAI-compatible API, for example
  `https://oai.endpoints.kepler.ai.cloud.ovh.net/v1`. `https` is required, except for a local model on
  `http://localhost` or `http://127.0.0.1`;

* **Model**: the model name expected by the provider, for example `gpt-oss-120b`;

* **API key**: the provider's access key. It is never displayed again: the screen only shows "Key saved" or "No
  key". Leaving the field empty keeps the current key; the "Delete the key" box removes it. The key is encrypted in
  the database with the application key (`APP_KEY`): if the `APP_KEY` changes, the key must be entered again;

* **Prompts**: the instructions given to the agent, one per object type (control, measurement, action plan and
  risk). `{{langue}}` is replaced by the user's language. The "Restore the default prompt" button restores the prompt
  shipped with Deming;

* **Response format: fixed**: the JSON schema the agent must follow, common to the four types. It is appended
  automatically to every prompt and used to validate the answer. It cannot be changed.

The "Test the connection" button sends a minimal request to the agent, with the saved configuration, and displays
the HTTP status, the latency and the model. The key is never displayed.

### Create an OVHcloud AI Endpoints key

1. Have an OVHcloud Public Cloud project with a payment method. Projects in "Discovery" mode do not work.
2. In the control panel, open "AI & Machine Learning → AI Endpoints", then "Create a new API key".
3. Copy the key and paste it in Deming's "API key" field. The key is displayed only once.

For a quick trial, AI Endpoints also accepts anonymous access, without a key, limited to 2 requests per minute.

### Use a local model

With a local server such as Ollama, vLLM or LM Studio, enter a URL such as `http://localhost:11434/v1` and the name
of the installed model. The server must expose the OpenAI-compatible `/chat/completions` API, and the model must be
able to produce JSON. With a local model, no data leaves your infrastructure.

### Delete all analyses {#purge}

The "Delete all AI analyses" button, at the bottom of the screen, erases the last analysis saved on every control,
measurement, action plan and risk, after confirmation. The operation is recorded in the
[logs](config.md#logs) (user and date, without content).

## Use the analysis {#use}

When AI is enabled, an "AI analysis" ✦ icon appears at the top right of the display screens of a
[control](measures.md#show), a [measurement](controls.md#show), an [action plan](actions.md#show) and a
[risk](risks.md#show), below the help button. A green dot on the icon means that an analysis is already saved.

Clicking the icon opens a panel on the right of the screen. If the object and its related objects have not changed
since the last analysis, it is displayed immediately, without calling the agent, with the mention "Analysis of … —
unchanged since". Otherwise, a new analysis is run; it may take up to a minute.

<!-- TODO capture : images/ai-panel.png -->

The panel contains:

* the **assessed status**, the **risk level** and the agent's **confidence** in its own analysis;

* the **summary**;

* the **findings**: strengths, weaknesses, missing evidence and inconsistencies;

* the **recommendations**, with their priority, rationale, a suggested deadline and a standard reference;

* the **open questions**, if any;

* four deliberately contrasting **points of view**: the CEO (business stakes and costs), the CISO (risk reading and
  priorities), the DPO (personal data and GDPR) and the auditor (standard requirements and evidence).

The meaning of the status depends on the object type:

| Status | Control / Measurement | Action plan | Risk |
|---|---|---|---|
| `conforme` | Compliant | Relevant and on track | Adequate treatment |
| `partiellement_conforme` | Partially compliant | Needs strengthening | Partial treatment |
| `non_conforme` | Non-compliant | Inadequate or stalled | Inadequate treatment |
| `non_evaluable` | Not assessable | Not assessable | Not assessable |

The "Run the analysis again" button forces a new analysis, even if nothing has changed. The footer of the panel
reminds that the analysis is AI-generated and must be validated by a human, and shows the model and the agent host
the data was sent to. The "Data sent" link displays the [context sent](#preview).

Only the last analysis of each object is kept. A new analysis is run automatically when the object, its related
objects, the prompt, the model or the user's language change.

## Who can run an analysis {#access}

An object can be analysed by anyone who can display it, except for the auditor and API roles. Rights are checked by
the server on every request.

| Role | Control | Measurement | Action plan | Risk |
|---|---|---|---|---|
| Administrator | ✓ | ✓ | ✓ | ✓ |
| User | ✓ (depending on assignment and group view) | ✓ (same) | ✓ | ✓ |
| Auditee | ✓ if assigned | ✓ if assigned | — | ✓ (all risks) |
| Auditor | — | — | — | — |
| API | — | — | — | — |

Details:

* in group view, a control is only available if the user is directly assigned to one of its measurements; a
  measurement is available if the user is assigned to it directly or through one of their [groups](config.md#groups);

* the auditee has no access to action plans, nor to their analysis;

* the auditee can display, and therefore analyse, all risks;

* the auditor has access neither to the icon, nor to the panel, nor to the saved analyses, even for an object they
  can display.

## Data protection {#privacy}

This section describes precisely what leaves Deming during an analysis. It is intended in particular for the CISO
and the DPO.

### Data sent to the agent

Every analysis sends the agent:

* the **prompt** of the object type and the **response schema**;

* **today's date**;

* the **context** of the analysed object, described below, after [masking](#redaction).

Long text fields of the analysed object are limited to 4,000 characters; those of related objects (observations,
justifications…) to 300 characters.

**Control**: clause, name, domain (framework and title), objective, attributes, input data, model,
indicator, action plan, periodicity, the last 5 measurements made (date, score, summarized observations and action plan),
the next 5 planned measurements (planned date, status, late or not), related open action plans (reference, name, type,
criticality, status, progress, due date, late or not), related risks (name, level, treatment status), exceptions
(name, status, start and end dates, expired or not, summarized justification and compensating controls) and up to 10
mappings to other frameworks (framework, clause, name, type and coverage).

**Measurement**: name, scope, covered controls (clause and name), objective, attributes, input data, model,
indicator, periodicity, status, planned date, completion date, late or not, score, grade, observations, action plan,
documents (**number and file names only**), action plans raised by the measurement (reference, name, type, criticality,
status, progress, due date, late or not) and the last 5 measurements made on the same control (date, score,
summarized observations and action plan).

**Action plan**: reference, name, type, criticality, status, scope, cause, remediation, creation, due and closing
dates, progress, justification, late or not, **number** of owners, related controls (clause, name, date and
score of the last measurement), related risks (name, level, treatment status) and the last 5 progress steps (date and
percentage, without their author).

**Risk**: name, description, probability and impact (value, label and comment), exposure and vulnerability (value
and label), residual risk, active scoring method (name, formula and thresholds), computed score and level, treatment
status and its comment, review frequency, next review date, review overdue or not, **whether** there is an owner (yes
or no), need for measures or actions according to the status, related controls (clause, name, date and
score of the last measurement) and related action plans (reference, name, criticality, progress, due date, closed or not,
late or not).

### Data never sent

* the names, logins and e-mail addresses of Deming users;

* the identity of the owners of an action plan and of the owner of a risk;

* the content of documents (only their names are sent);

* audit logs;

* Deming's configuration and the API key;

* objects that are not related to the analysed object.

### Automatic masking {#redaction}

Before sending, every text of the context is filtered. The following are replaced:

* e-mail addresses by `[email]`;

* phone numbers (international, Belgian, Luxembourg, French and German formats) by `[téléphone]`;

* IBANs by `[iban]`;

* IP addresses (IPv4 and IPv6) by `[ip]`;

* the name, login and e-mail address of every Deming user, as whole words and case-insensitively, by
  `[utilisateur]`. Values shorter than three characters are ignored to avoid false positives.

!!! warning "Important limit — free-text fields"
    Masking is **not** anonymization. It does not detect everything: the name of a patient, a customer or an external
    collaborator typed in observations, a description or a justification **will be sent** to the agent. Do not type
    personal data in Deming's text fields, and remind users of this rule.

### Preview of the data sent {#preview}

The "Data sent" link, at the bottom of the analysis panel, displays the context exactly as it is sent to the agent,
after minimization and masking (without the prompt). This preview does not call the agent: it can be used before or
after an analysis to check what is actually sent. It is subject to the same rights as the analysis.

<!-- TODO capture : images/ai-preview.png -->

### Where the data goes

The data is sent only to the host configured in the agent URL, shown at the bottom of the panel. Deming does not
follow any redirection to another host, and `https` is required, with certificate verification, except for a local
model.

With OVHcloud AI Endpoints, the data is processed by OVHcloud in its European data centres. For the retention and
possible reuse of requests, refer to
[OVHcloud's contractual terms](https://www.ovhcloud.com/en/terms-and-conditions/contracts/), in particular the
specific conditions of AI Endpoints and the data processing agreement (DPA). For another provider, refer to its own
terms.

With a local model, **no data leaves your infrastructure**.

### What Deming keeps

* Only the **last analysis** of each object, saved in the object itself. It is deleted with the object or with the
  "[Delete all AI analyses](#purge)" button.

* Analyses appear neither in the [REST API](api.md), nor in exports, nor in reports, and are not recorded in the
  [logs](config.md#logs): an analysis is not a change of the object.

* Application logs only contain metadata: object type and identifier, model, duration, number of tokens and status.
  Neither the prompt, nor the context, nor the answer, nor the key are logged.

* The API key is encrypted with the application's `APP_KEY`; changing the `APP_KEY` requires entering the key again.

### Security of the feature

* The analysis is subject to the same rights as the display of the object; the auditor and API roles are excluded
  (see [Who can run an analysis](#access)).

* Requests are protected against CSRF and limited to 10 per minute.

* AI answers are displayed as text, without HTML interpretation.

* The object's data is presented to the agent as data, never as instructions: an injection attempt in a text field
  is reported as an inconsistency.

* The AI cannot change anything in Deming.

### Before enabling AI

Points to be checked by the data controller. These recommendations are not legal advice.

- [ ] Consult the DPO.
- [ ] Update the record of processing activities (GDPR, article 30) if personal data may be sent.
- [ ] Have a data processing agreement (GDPR, article 28) with the agent's provider, or use a local model.
- [ ] For a provider other than OVHcloud, check where the processing takes place and that there is no transfer
      outside the European Union.
- [ ] Assess the need for a data protection impact assessment (DPIA), in particular in sectors handling sensitive
      data such as healthcare.
- [ ] Inform Deming users that the feature exists and of the rule about free texts.
- [ ] Keep in mind that the results are a decision aid validated by a human: no decision is taken automatically, and
      the agent does not handle decisions about individuals.

## Limits {#limits}

* The AI can be wrong, misinterpret data or miss an important point.

* The standard references quoted must be checked.

* Results are not strictly reproducible: two analyses of the same data may differ.

* Cost and response time depend on the provider and the model.

* The quality of the analysis depends on the quality of the data entered in Deming.
