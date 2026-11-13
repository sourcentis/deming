{{-- Aide : balises utilisables dans le modèle du rapport de pilotage --}}
<div class="card report-help">
    <div class="card-header"><i class="bi bi-braces"></i> {{ trans('report_template.help_title') }}</div>
    <div class="card-body">

        <h6 class="fw-bold">{{ trans('report_template.variables_title') }}</h6>
        <table class="table table-sm mb-3">
            <tbody>
            @foreach (trans('report_template.variables') as $variable => $description)
                <tr>
                    <td class="text-nowrap"><code class="copy-tag" title="Copier">${{ '{' . $variable . '}' }}</code></td>
                    <td>{{ $description }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <h6 class="fw-bold">{{ trans('report_template.tables_title') }}</h6>
        <p class="mb-1">{{ trans('report_template.syntax') }} :</p>
        <pre class="report-syntax">${table:&lt;table&gt;:&lt;colonne&gt;[=Entête],...[|option]}</pre>
        <p class="help-block">{{ trans('report_template.syntax_helper') }}</p>

        <p class="mb-1">{{ trans('report_template.options_title') }} :</p>
        <table class="table table-sm mb-3">
            <tbody>
                <tr><td class="text-nowrap"><code>period:&lt;colonne&gt;</code></td><td>{{ trans('report_template.options.period') }}</td></tr>
                <tr><td class="text-nowrap"><code>where:&lt;colonne&gt;=&lt;valeur&gt;</code></td><td>{{ trans('report_template.options.where') }}</td></tr>
                <tr><td class="text-nowrap"><code>order:[-]&lt;colonne&gt;</code></td><td>{{ trans('report_template.options.order') }}</td></tr>
                <tr><td class="text-nowrap"><code>limit:&lt;n&gt;</code></td><td>{{ trans('report_template.options.limit') }}</td></tr>
            </tbody>
        </table>

        <p class="mb-1">{{ trans('report_template.example') }} :</p>
        <pre class="report-syntax copy-tag" title="Copier">${table:actions:reference,name,owners,due_date|where:status=0|order:due_date}</pre>
        <pre class="report-syntax copy-tag" title="Copier">${table:measures:clauses,name,realisation_date,score|period:realisation_date|where:status=2}</pre>

        <h6 class="fw-bold mt-3">{{ trans('report_template.columns_title') }}</h6>
        <div class="accordion accordion-flush" id="report-tables">
        @foreach ($reportTables as $table => $info)
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button"
                            data-bs-toggle="collapse" data-bs-target="#report-table-{{ $table }}" aria-expanded="false">
                        <span class="me-2">{{ $info['label'] }}</span>
                        <code>{{ $table }}</code>
                    </button>
                </h2>
                <div id="report-table-{{ $table }}" class="accordion-collapse collapse" data-bs-parent="#report-tables">
                    <div class="accordion-body p-2">
                        @if ($info['framework'])
                            <p class="help-block mb-1"><i class="bi bi-funnel"></i> {{ trans('report_template.framework_filtered') }}</p>
                        @endif
                        <table class="table table-sm mb-0">
                            <tbody>
                            @foreach ($info['columns'] as $column => $col)
                                <tr>
                                    <td class="text-nowrap"><code class="copy-tag" title="Copier">{{ $column }}</code></td>
                                    <td>
                                        {{ $col['label'] }}
                                        @if ($col['virtual'])
                                            <span class="badge text-bg-light border">{{ trans('report_template.computed') }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    </div>
</div>

<script @nonce>
// Clic sur une balise ou une colonne de l'aide : copie dans le presse-papiers
document.addEventListener('click', (e) => {
    const el = e.target.closest('.report-help .copy-tag');
    if (!el || !navigator.clipboard) {
        return;
    }
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        el.classList.add('copied');
        setTimeout(() => el.classList.remove('copied'), 800);
    });
});
</script>
