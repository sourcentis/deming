<?php

use App\Models\Action;
use App\Models\Control;
use App\Models\Domain;
use App\Models\User;
use App\Services\Report\TableTag;
use App\Services\Report\TemplateTableRenderer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

/** Crée un modèle .docx contenant un paragraphe par ligne et retourne son chemin. */
function makeTemplate(array $lines): string
{
    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    foreach ($lines as $line) {
        $section->addText($line);
    }
    $path = tempnam(sys_get_temp_dir(), 'tpl') . '.docx';
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    return $path;
}

/** Rend le modèle et retourne le XML du document produit. */
function renderTemplate(string $template, array $context = []): string
{
    $processor = new TemplateProcessor($template);
    (new TemplateTableRenderer)->render($processor, $context);
    $output = tempnam(sys_get_temp_dir(), 'out') . '.docx';
    $processor->saveAs($output);

    $zip = new ZipArchive;
    $zip->open($output);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    return $xml;
}

// ---------------------------------------------------------------------------
// Analyse des balises
// ---------------------------------------------------------------------------

test('a table tag is parsed with columns, headers and options', function () {
    $tag = TableTag::parse('table:actions:reference=Réf,name,owners|where:status=0|order:-due_date|limit:5');

    expect($tag->isValid())->toBeTrue()
        ->and($tag->table)->toBe('actions')
        ->and($tag->columns)->toBe([
            ['column' => 'reference', 'header' => 'Réf'],
            ['column' => 'name', 'header' => null],
            ['column' => 'owners', 'header' => null],
        ])
        ->and($tag->where)->toBe([['column' => 'status', 'operator' => '=', 'value' => '0']])
        ->and($tag->order)->toBe([['column' => 'due_date', 'direction' => 'desc']])
        ->and($tag->limit)->toBe(5);
});

test('unknown tables, columns and options are reported', function () {
    expect(TableTag::parse('table:foo:bar')->errors)->toHaveCount(1)
        ->and(TableTag::parse('table:actions:name,nope')->errors)->toHaveCount(1)
        ->and(TableTag::parse('table:actions:name|sort:name')->errors)->toHaveCount(1)
        ->and(TableTag::parse('table:actions')->errors)->toHaveCount(1);
});

test('sensitive columns are not exposed', function () {
    expect(TableTag::parse('table:users:name,password')->isValid())->toBeFalse()
        ->and(TableTag::parse('table:users:name,remember_token')->isValid())->toBeFalse();
});

test('options only accept real columns', function () {
    // "owners" est une colonne calculée : affichable, mais pas filtrable
    expect(TableTag::parse('table:actions:name,owners')->isValid())->toBeTrue()
        ->and(TableTag::parse('table:actions:name|order:owners')->isValid())->toBeFalse();
});

// ---------------------------------------------------------------------------
// Rendu
// ---------------------------------------------------------------------------

test('a table tag is replaced by a table with the selected rows', function () {
    $owner = User::factory()->create(['name' => 'Alice Owner']);
    $open = Action::factory()->create(['reference' => 'ACT-1', 'name' => 'Sauvegardes & restauration', 'status' => 0]);
    $open->owners()->attach($owner->id);
    Action::factory()->create(['reference' => 'ACT-2', 'name' => 'Action fermée', 'status' => 1]);

    $xml = renderTemplate(makeTemplate(['${table:actions:reference=Réf,name,owners|where:status=0}']));

    expect((new DOMDocument)->loadXML($xml))->toBeTrue()
        ->and($xml)->toContain('Réf')
        ->and($xml)->toContain('ACT-1')
        ->and($xml)->toContain('Sauvegardes &amp; restauration')
        ->and($xml)->toContain('Alice Owner')
        ->and($xml)->not->toContain('ACT-2')
        ->and($xml)->not->toContain('${table');
});

test('the period option filters on the report dates', function () {
    Action::factory()->create(['reference' => 'IN', 'due_date' => '2026-03-15']);
    Action::factory()->create(['reference' => 'OUT', 'due_date' => '2025-01-15']);

    $xml = renderTemplate(makeTemplate(['${table:actions:reference|period:due_date}']), [
        'start' => \Carbon\Carbon::parse('2026-01-01'),
        'end' => \Carbon\Carbon::parse('2026-06-30'),
    ]);

    expect($xml)->toContain('>IN<')->and($xml)->not->toContain('>OUT<');
});

test('the selected framework filters tables linked to domains', function () {
    $iso = Domain::factory()->create(['framework' => 'ISO']);
    $nis = Domain::factory()->create(['framework' => 'NIS2']);
    Control::factory()->create(['domain_id' => $iso->id, 'clause' => 'ISO-1']);
    Control::factory()->create(['domain_id' => $nis->id, 'clause' => 'NIS-1']);

    $xml = renderTemplate(makeTemplate(['${table:controls:clause,framework}']), ['framework' => 'ISO']);

    expect($xml)->toContain('ISO-1')->and($xml)->not->toContain('NIS-1');
});

test('an invalid tag is replaced by its error messages', function () {
    $xml = renderTemplate(makeTemplate(['${table:foo:bar}']));

    expect($xml)->toContain('foo')->and($xml)->not->toContain('${table');
});

// ---------------------------------------------------------------------------
// Chargement du modèle
// ---------------------------------------------------------------------------

test('a template with invalid table tags is rejected on upload', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $file = new UploadedFile(makeTemplate(['${table:actions:nope}']), 'pilotage.docx', null, null, true);

    $this->actingAs($admin)
        ->post('/doc/template', ['template2' => $file])
        ->assertRedirect('/config/parameters?tab=templates')
        ->assertSessionHasErrors('template2');

    Storage::disk('local')->assertMissing('models/pilotage_.docx');
});

test('a valid template is stored on upload', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $file = new UploadedFile(makeTemplate(['${table:actions:reference,name}']), 'pilotage.docx', null, null, true);

    $this->actingAs($admin)
        ->post('/doc/template', ['template2' => $file])
        ->assertRedirect('/config/parameters?tab=templates')
        ->assertSessionHasNoErrors();

    Storage::disk('local')->assertExists('models/pilotage_.docx');
});

test('the templates tab lists the available tables and columns', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/config/parameters?tab=templates')
        ->assertOk()
        ->assertSee('${table:', false)
        ->assertSee('report-table-actions', false)
        ->assertSee('due_date')
        ->assertDontSee('remember_token');
});

// ---------------------------------------------------------------------------
// Échappement XML des documents Word générés
// ---------------------------------------------------------------------------

/** XML de word/document.xml d'un fichier .docx renvoyé par une réponse de téléchargement. */
function downloadedDocumentXml($response): string
{
    $zip = new ZipArchive;
    $zip->open($response->baseResponse->getFile()->getPathname());
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();

    return $xml;
}

test('the steering report stays valid XML with special characters in the data', function () {
    $admin = User::factory()->admin()->create(['language' => 'fr']);
    // Le graphique du rapport a besoin d'au moins un domaine
    Control::factory()->create(['domain_id' => Domain::factory()->create(['description' => 'Gouvernance & conformité'])->id]);
    Action::factory()->create([
        'name' => 'Détection & réponse <EDR>',
        'cause' => 'Antivirus "classique" & obsolète',
        'remediation' => 'Déployer un EDR (Endpoint Detection & Response)',
        'status' => 0,
    ]);

    $response = $this->actingAs($admin)->get('/reports/pilotage?start_date=' . now()->subMonths(3)->format('Y-m-d')
        . '&end_date=' . now()->subDay()->format('Y-m-d'));

    $response->assertOk();
    $xml = downloadedDocumentXml($response);
    expect((new DOMDocument)->loadXML($xml))->toBeTrue()
        ->and($xml)->toContain('Endpoint Detection &amp; Response')
        ->and($xml)->toContain('Détection &amp; réponse &lt;EDR&gt;')
        ->and($xml)->toContain('Gouvernance &amp; conformité')
        ->and($xml)->not->toContain('&amp;amp;');
});

test('the control sheet stays valid XML with special characters in the data', function () {
    $admin = User::factory()->admin()->create(['language' => 'fr']);
    $measure = \App\Models\Measure::factory()->create([
        'name' => 'Sauvegarde & restauration',
        'objective' => "Vérifier les sauvegardes & <restaurations>\nsur deux lignes",
        'status' => 0,
    ]);
    $measure->controls()->attach(Control::factory()->create()->id);

    $response = $this->actingAs($admin)->post("/bob/template/{$measure->id}", ['observations' => 'RAS & conforme']);

    $response->assertOk();
    $xml = downloadedDocumentXml($response);
    expect((new DOMDocument)->loadXML($xml))->toBeTrue()
        ->and($xml)->toContain('Sauvegarde &amp; restauration')
        ->and($xml)->toContain('&lt;restaurations&gt;')
        ->and($xml)->not->toContain('&amp;amp;');
});
