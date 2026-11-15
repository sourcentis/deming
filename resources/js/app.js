// Bootstrap
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// jQuery + Select2
import $ from 'jquery';
window.$ = window.jQuery = $;
import select2 from 'select2';
select2($);

// DataTables
import jszip from 'jszip';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.colVis.mjs';
import 'datatables.net-buttons/js/buttons.html5.mjs';
import 'datatables.net-colreorder-bs5';
import 'datatables.net-responsive-bs5';
DataTable.Buttons.jszip(jszip);
window.DataTable = DataTable;

// DropZone
import Dropzone from 'dropzone';
import "dropzone/dist/dropzone.css";
window.Dropzone = Dropzone;

// Chart.js
import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(...registerables, ChartDataLabels);
window.Chart = Chart;

// EasyMDE
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';
window.EasyMDE = EasyMDE;

// DataTables defaults
const dtLanguages = {
    en: {
        buttons: { excel: 'Export' },
    },
    fr: {
        decimal: ',',
        emptyTable: 'Aucune donnée disponible',
        info: '_START_ à _END_ sur _TOTAL_ éléments',
        infoEmpty: '0 élément',
        infoFiltered: '(filtré de _MAX_ éléments au total)',
        lengthMenu: '_MENU_ éléments par page',
        loadingRecords: 'Chargement...',
        search: 'Rechercher :',
        zeroRecords: 'Aucun élément correspondant trouvé',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
        buttons: { colvis: 'Colonnes', copy: 'Copier', excel: 'Exporter' },
    },
    de: {
        decimal: ',',
        emptyTable: 'Keine Daten vorhanden',
        info: '_START_ bis _END_ von _TOTAL_ Einträgen',
        infoEmpty: '0 Einträge',
        infoFiltered: '(gefiltert von _MAX_ Einträgen)',
        lengthMenu: '_MENU_ Einträge pro Seite',
        loadingRecords: 'Wird geladen...',
        search: 'Suchen:',
        zeroRecords: 'Keine passenden Einträge gefunden',
        paginate: { first: '«', last: '»', next: '›', previous: '‹' },
        buttons: { colvis: 'Spalten', copy: 'Kopieren', excel: 'Exportieren' },
    },
};
const lang = (document.documentElement.lang || 'en').substring(0, 2);
if (dtLanguages[lang]) {
    DataTable.defaults.language = dtLanguages[lang];
}

/**
 * Initialise une table DataTables.
 * Les options peuvent être passées via les attributs HTML5 data-* supportés
 * nativement par DataTables (data-page-length, data-order, data-searching,
 * data-paging, data-info...) ou via le paramètre options.
 */
function initDataTable(el, options = {}) {
    if (DataTable.isDataTable(el)) {
        return new DataTable(el);
    }
    const title = el.dataset.title || document.title;
    const withButtons = el.dataset.buttons !== 'false';
    return new DataTable(el, Object.assign({
        // Réorganisation des colonnes par glisser-déposer des en-têtes
        colReorder: true,
        // Petits écrans : les colonnes qui ne tiennent pas sont repliées
        // dans une ligne de détail (clic sur la première cellule)
        responsive: true,
        // Colonnes masquées par défaut (affichables via le bouton "Colonnes")
        columnDefs: [
            { targets: 'dt-hidden', visible: false },
        ],
        // Mémorise nombre de lignes, tri, ordre et visibilité des colonnes (pas la recherche ni la page)
        stateSave: true,
        stateDuration: 0,
        stateSaveParams: function (settings, data) {
            data.start = 0;
            data.search.search = '';
            (data.columns || []).forEach(c => { if (c.search) c.search.search = ''; });
        },
        // Largeur des colonnes calculée par DataTables selon le contenu
        autoWidth: true,
        pageLength: 100,
        lengthMenu: [10, 25, 50, 100, 250, 500],
        layout: withButtons ? {
            topStart: ['buttons', 'pageLength'],
        } : undefined,
        buttons: withButtons ? [
            { extend: 'colvis', className: 'btn-sm btn-light' },
            { extend: 'copy', className: 'btn-sm btn-light' },
            { extend: 'excel', className: 'btn-sm btn-light', title },
        ] : [],
    }, options));
}
window.initDataTable = initDataTable;

// Les menus des boutons DataTables (ex. "Colonnes") portent la classe
// .dropdown-menu : le gestionnaire clavier global de Bootstrap tente alors d'y
// créer un Dropdown sans bouton déclencheur et lève une erreur (Échap, flèches).
// On intercepte ces touches avant Bootstrap (qui écoute en capture sur document,
// d'où l'écoute en capture sur window) ; DataTables ferme le menu sur keyup.
window.addEventListener('keydown', (e) => {
    if (['Escape', 'ArrowUp', 'ArrowDown'].includes(e.key)
        && e.target instanceof Element && e.target.closest('.dt-button-collection')) {
        e.stopPropagation();
    }
}, true);

/**
 * Initialise Select2 sur un élément (ou une collection jQuery).
 */
function initSelect2(el) {
    $(el).each(function () {
        if ($(this).hasClass('select2-hidden-accessible')) {
            return;
        }
        const tags = $(this).hasClass('select2-free');
        // Effacement possible uniquement si une option vide existe
        const emptyOption = Array.from(this.options).find(o => o.value === '');
        const allowClear = !this.required && !this.multiple && $(this).data('allow-clear') !== false
            && !!emptyOption;
        $(this).select2({
            theme: 'bootstrap-5',
            width: '100%',
            // Le texte de l'option vide ("-- Choisir ... --") sert de placeholder
            placeholder: $(this).data('placeholder') || (emptyOption ? (emptyOption.text.trim() || ' ') : ''),
            allowClear: allowClear,
            tags: tags,
            minimumResultsForSearch: $(this).data('search') === false ? Infinity : 0,
            dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body),
        });
        // Select2 déclenche un 'change' jQuery uniquement : on le relaie en
        // événement natif pour les écouteurs posés avec addEventListener.
        $(this).on('change', function (e) {
            if (!e.originalEvent) {
                this.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
}
window.initSelect2 = initSelect2;

window.editors = {};

const easyMdeIcons = {
    'bold': 'bi bi-type-bold',
    'italic': 'bi bi-type-italic',
    'strikethrough': 'bi bi-type-strikethrough',
    'heading': 'bi bi-type-h1',
    'heading-smaller': 'bi bi-type-h2',
    'heading-bigger': 'bi bi-type-h1',
    'heading-1': 'bi bi-type-h1',
    'heading-2': 'bi bi-type-h2',
    'heading-3': 'bi bi-type-h3',
    'code': 'bi bi-code',
    'quote': 'bi bi-quote',
    'ordered-list': 'bi bi-list-ol',
    'unordered-list': 'bi bi-list-ul',
    'check-list': 'bi bi-check2-square',
    'clean-block': 'bi bi-eraser',
    'link': 'bi bi-link-45deg',
    'image': 'bi bi-image',
    'upload-image': 'bi bi-image',
    'table': 'bi bi-table',
    'horizontal-rule': 'bi bi-dash-lg',
    'preview': 'bi bi-eye',
    'side-by-side': 'bi bi-layout-split',
    'fullscreen': 'bi bi-arrows-fullscreen',
    'guide': 'bi bi-question-circle',
    'undo': 'bi bi-arrow-counterclockwise',
    'redo': 'bi bi-arrow-clockwise',
};
document.addEventListener("DOMContentLoaded", function () {
    // EasyMDE
    document.querySelectorAll('.easymde').forEach(function (textarea) {
        const instance = new EasyMDE({
            element: textarea,
            minHeight: "200px",
            maxHeight: "200px",
            status: false,
            spellChecker: false,
            toggleFullscreen: false,
            sideBySideFullscreen: false,
            // Pas de Font Awesome depuis un CDN (bloqué par la CSP) : icônes Bootstrap Icons
            autoDownloadFontAwesome: false,
            iconClassMap: easyMdeIcons,
        });
        // Stocke l'instance par id pour un accès futur
        if (textarea.id) {
            editors[textarea.id] = instance;
        }
    });

    // Select2
    initSelect2('select.select2');

    // DataTables
    document.querySelectorAll('table.datatable').forEach(el => initDataTable(el));

    // Tables hors DataTables : défilement horizontal si elles débordent
    document.querySelectorAll('.main-content table.table:not(.datatable)').forEach(table => {
        if (!table.closest('.table-responsive, .overflow-auto')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el));

    // Sidebar : bouton d'affichage / masquage
    document.getElementById('toggle-sidebar-btn')
        ?.addEventListener('click', toggleSidebar);

    // Lignes cliquables : <tr data-href="..."> ouvre la page indiquée
    // (Ctrl/Cmd + clic ou clic du milieu : nouvel onglet). Les liens, boutons
    // et champs présents dans la ligne gardent leur propre comportement.
    const openRow = (e) => {
        const row = e.target.closest('tr[data-href]');
        if (!row || e.target.closest('a, button, input, select, textarea, label')) {
            return;
        }
        // Cellule de dépliage DataTables Responsive (colonnes repliées)
        if (e.target.closest('table.collapsed td.dtr-control')) {
            return;
        }
        if (e.type === 'auxclick' && e.button !== 1) {
            return;
        }
        // Ne pas naviguer si l'utilisateur sélectionne du texte
        if (window.getSelection().toString() !== '') {
            return;
        }
        if (e.ctrlKey || e.metaKey || e.type === 'auxclick') {
            window.open(row.dataset.href, '_blank');
        } else {
            window.location.href = row.dataset.href;
        }
    };
    document.addEventListener('click', openRow);
    document.addEventListener('auxclick', openRow);

    // Mobile : un clic hors de la sidebar ouverte la referme. Ce clic est
    // consommé (capture sur window) pour ne pas activer l'élément situé dessous.
    window.addEventListener('click', (e) => {
        const sidebar = document.getElementById('sidebar');
        if (sidebar?.classList.contains('sidebar-open')
            && !e.target.closest('#sidebar, #toggle-sidebar-btn')) {
            sidebar.classList.remove('sidebar-open');
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);

    // Raccourcis clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.getElementById('btn-cancel')?.click();
        }
    });
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content-home');
    // Mobile / tablette : la sidebar s'ouvre par-dessus le contenu, sans mémoriser l'état
    if (window.matchMedia('(max-width: 991.98px)').matches) {
        sidebar.classList.toggle('sidebar-open');
        return;
    }
    sidebar.classList.toggle('sidebar-hidden');
    content.classList.toggle('content-expanded');
    try {
        localStorage.setItem('sidebar_hidden', sidebar.classList.contains('sidebar-hidden'));
    } catch (_) {
        // Storage indisponible
    }
}
window.toggleSidebar = toggleSidebar;

// ---------------------------------------------------------------------------
// Comportements déclaratifs (remplacent les handlers inline onclick=...,
// interdits par la Content-Security-Policy)
//
//   data-confirm="message"         confirmation avant le clic ou l'envoi du formulaire
//   data-confirm-if="#selecteur"   ... seulement si cette case est cochée
//   data-href="/url"               clic = navigation (bouton, ligne de tableau)
//   data-form-action="/url"        clic = change l'action du formulaire parent
//   data-form-method="GET"         ... et sa méthode
//   data-range-label               affiche la valeur du curseur dans l'élément suivant
//   data-logout                    envoie le formulaire #logout-form
// ---------------------------------------------------------------------------
function confirmed(el) {
    const condition = el.dataset.confirmIf;
    if (condition && !document.querySelector(condition)?.checked) {
        return true;
    }
    return window.confirm(el.dataset.confirm);
}

document.addEventListener('click', function (e) {
    const confirmEl = e.target.closest('[data-confirm]:not(form)');
    if (confirmEl && !confirmed(confirmEl)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return;
    }

    const actionEl = e.target.closest('[data-form-action]');
    if (actionEl && actionEl.form) {
        actionEl.form.action = actionEl.dataset.formAction;
        if (actionEl.dataset.formMethod) {
            actionEl.form.method = actionEl.dataset.formMethod;
        }
    }

    if (e.target.closest('[data-logout]')) {
        e.preventDefault();
        document.getElementById('logout-form').submit();
        return;
    }

    const hrefEl = e.target.closest('[data-href]');
    // Un lien à l'intérieur (ex. ligne de tableau cliquable) garde sa propre cible
    if (hrefEl && !e.target.closest('a, input, select, textarea, label')?.closest('[data-href]')) {
        e.preventDefault();
        window.location.href = hrefEl.dataset.href;
    }
});

document.addEventListener('submit', function (e) {
    const form = e.target;
    if (form.matches('form[data-confirm]') && !confirmed(form)) {
        e.preventDefault();
        e.stopImmediatePropagation();
    }
});

document.addEventListener('input', function (e) {
    if (e.target.matches('[data-range-label]') && e.target.nextElementSibling) {
        e.target.nextElementSibling.textContent = e.target.value + '%';
    }
});

// Chart colors
window.chartColors = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};

// Maximize the font contrast of the text with the given background color
function getContrastColor(hexColor) {
    // Convertit #rrggbb en composantes 0-255
    const r = parseInt(hexColor.slice(1, 3), 16);
    const g = parseInt(hexColor.slice(3, 5), 16);
    const b = parseInt(hexColor.slice(5, 7), 16);

    // Linéarisation sRGB (WCAG 2.1)
    const toLinear = c => {
        const s = c / 255;
        return s <= 0.04045 ? s / 12.92 : Math.pow((s + 0.055) / 1.055, 2.4);
    };

    const L = 0.2126 * toLinear(r)
        + 0.7152 * toLinear(g)
        + 0.0722 * toLinear(b);

    return L > 0.8 ? '#2d2d2d' : '#ffffff';
    // return L > 0.179 ? '#000000' : '#ffffff';
}

window.getContrastColor = getContrastColor;
