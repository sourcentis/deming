@extends("layout")

@section("content")
<div class="p-3">
    <div class="card">
    <div class="card-header">@include('partials.help', ['page' => 'about'])<i class="bi bi-question-circle"></i> About</div>
    <div class="card-body">
		<div class="container-fluid px-0">
	    	<div class="row">
	    		<div class="col-md-8 col-lg-4 text-center">
                    <img src="/images/deming.png" alt="Deming" width="400" class="img-fluid"/>
                </div>
	    		<div class="col-md-12 col-lg-7">
@if(Auth::User()->language==="fr")
    <h1>À propos de Deming</h1>

    <p><strong>Deming</strong> est un projet open-source conçu pour faciliter la gestion de la sécurité de l'information. Il offre aux organisations les outils nécessaires pour surveiller et mesurer efficacement leurs contrôles de sécurité tout en optimisant la gestion de leur Système de Management de la Sécurité de l'Information (SMSI).</p>

    <h2>Fonctionnalités clés</h2>
    <ul>
        <li>Suivi des performances de la sécurité de l'information.</li>
        <li>Planification et gestion des contrôles de sécurité.</li>
        <li>Génération de rapports détaillés pour une prise de décision éclairée.</li>
    </ul>

    <h2>Open Source</h2>

    <p>Distribué sous licence <a href="https://www.gnu.org/licenses/licenses.html">GPL</a>, Deming s'adapte aux besoins des entreprises en quête d'amélioration continue de leur sécurité de l'information. Pour en savoir plus sur l’utilisation et les fonctionnalités, consultez la <a href="https://sourcentis.github.io/deming/fr/">documentation utilisateur</a> et le  <a href="https://github.com/sourcentis">GitHub</a> du projet.</p>
@elseif(Auth::User()->language==="de")
<h1>Über Deming</h1>

<p><strong>Deming</strong> ist ein Open-Source-Projekt, das entwickelt wurde, um die Verwaltung der Informationssicherheit zu erleichtern. Es bietet Organisationen die notwendigen Werkzeuge, um ihre Sicherheitskontrollen effektiv zu überwachen und zu messen, während sie ihr Informationssicherheits-Managementsystem (ISMS) optimieren.</p>

<h2>Wichtige Funktionen</h2>
<ul>
    <li>Überwachung der Informationssicherheitsleistung.</li>
    <li>Planung und Verwaltung von Sicherheitskontrollen.</li>
    <li>Erstellung detaillierter Berichte für fundierte Entscheidungen.</li>
</ul>

<h2>Open Source</h2>

<p>Unter der <a href="https://www.gnu.org/licenses/licenses.html">GPL-Lizenz</a> vertrieben, passt sich Deming den Bedürfnissen von Unternehmen an, die eine kontinuierliche Verbesserung ihrer Informationssicherheit anstreben. Weitere Informationen zur Nutzung und den Funktionen finden Sie in der <a href="https://sourcentis.github.io/deming/de/">Benutzerdokumentation</a> und auf dem <a href="https://github.com/sourcentis">GitHub-Projekt</a>.</p>
@elseif(Auth::User()->language==="es")
    <h1>Acerca de Deming</h1>

    <p><strong>Deming</strong> es un proyecto de código abierto diseñado para facilitar la gestión de la seguridad de la información. Proporciona a las organizaciones las herramientas necesarias para supervisar y medir de forma eficaz sus controles de seguridad, a la vez que optimiza la gestión de su Sistema de Gestión de Seguridad de la Información (SGSI).</p>

    <h2>Funciones principales</h2>
    <ul>
        <li>Seguimiento del rendimiento de la seguridad de la información.</li>
        <li>Planificación y gestión de los controles de seguridad.</li>
        <li>Generación de informes detallados para tomar decisiones fundamentadas.</li>
    </ul>

    <h2>Código abierto</h2>

    <p>Distribuido bajo la licencia <a href="https://www.gnu.org/licenses/licenses.html">GPL</a>, Deming se adapta a las necesidades de las empresas que buscan la mejora continua de su seguridad de la información. Para obtener más información sobre su uso y sus funciones, consulta la <a href="https://sourcentis.github.io/deming/">documentación de usuario</a> (en inglés) y el <a href="https://github.com/sourcentis">GitHub</a> del proyecto.</p>
@else
    <h1>About Deming</h1>

    <p><strong>Deming</strong> is an open-source project designed to facilitate the management of information security. It provides organizations with the necessary tools to effectively monitor and measure their security controls while optimizing the management of their Information Security Management System (ISMS).</p>

    <h2>Key Features</h2>
    <ul>
        <li>Monitoring information security performance.</li>
        <li>Planning and managing security measures.</li>
        <li>Generating detailed reports for informed decision-making.</li>
    </ul>

    <h2>Open Source</h2>

    <p>Distributed under the <a href="https://www.gnu.org/licenses/licenses.html">GPL</a> license, Deming adapts to the needs of companies seeking continuous improvement in their information security. For more information on usage and features, refer to the <a href="https://sourcentis.github.io/deming/">user documentation</a> and the project's <a href="https://github.com/sourcentis">GitHub</a>.</p>
@endif
</div>
</div>
@endsection
