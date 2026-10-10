<?php

return [
    'title' => 'Scan to BIM Explained: From Point Cloud to Revit Model',
    'slug' => 'scan-to-bim-explained',
    'category' => 'BIM and Revit',
    'tags' => ['Scan to BIM', 'Revit', 'Point Cloud', 'Existing Conditions'],
    'days_ago' => 31,
    'featured' => false,
    'excerpt' => 'Renovating a building you only have old drawings of? Scan to BIM turns a laser scan into an accurate Revit model. Here is how it works and what to prepare.',
    'meta_title' => 'Scan to BIM Explained: Point Cloud to Revit Model',
    'meta_description' => 'Scan to BIM turns a point cloud into an accurate Revit model of an existing building. Learn the workflow, accuracy levels, deliverables and common pitfalls.',
    'focus_keyword' => 'scan to BIM',
    'keywords' => 'scan to BIM, point cloud to Revit, existing conditions model, laser scan, as-built Revit model, BIM for renovation',
    'image_alt' => 'Architect reviewing building models and designs at a desk in a modern office',
    'inline_alt' => 'Demolition and construction site next to modern buildings, showing the kind of existing conditions that are scanned',
    'content' => <<<'BODY'
<p>Renovation and retrofit projects share one problem: the drawings you have rarely match the building you are standing in. Walls were moved, ceilings dropped, services added. <strong>Scan to BIM</strong> solves this by capturing the real building and turning it into a model you can design in.</p>
<p>This article explains what scan to BIM is, how the workflow runs from site to Revit model, and what to prepare so the result is useful from day one.</p>

<h2>What is scan to BIM?</h2>
<p>Scan to BIM is the process of converting a 3D laser scan (a "point cloud") of an existing building into an intelligent BIM model, usually in Revit. A scanner records millions of measured points on every visible surface. A modeler then builds walls, floors, openings, structure and other elements to follow those points, so the model reflects the building as it truly stands.</p>
<p>The result is not just a picture. Each element in the model is a real Revit object, so you can tag it, schedule it, section it and design against it.</p>

<h2>Why not just measure by hand?</h2>
<p>Hand measuring works for a single room. On a whole building it is slow, easy to get wrong, and gives you dimensions rather than context. A scan captures everything at once: out-of-plumb walls, uneven floors, ceiling heights that change, and the exact position of beams and openings. That means fewer surprises when construction begins and less time spent re-measuring on site.</p>

<img src="/assets/img/blog/scan-to-bim-explained-inline.webp" alt="Demolition and construction site next to modern buildings, showing the kind of existing conditions that are scanned" width="1200" height="800">

<h2>The workflow, step by step</h2>
<ol>
<li><strong>Scan the building.</strong> A surveyor or scanning provider captures the interior and exterior and delivers the point cloud, commonly in RCP, RCS, E57 or LAS format.</li>
<li><strong>Prepare the cloud.</strong> The data is cleaned, aligned and, for large sites, split into manageable regions so Revit stays responsive.</li>
<li><strong>Agree the scope.</strong> Decide what gets modeled and to what level of detail. Not every pipe and bracket needs to be a model element.</li>
<li><strong>Model in Revit.</strong> Walls, floors, roofs, stairs, openings, columns and beams are built to follow the cloud, with levels and grids set up properly.</li>
<li><strong>Check against the scan.</strong> The model is compared to the point cloud to confirm it falls within the agreed tolerance.</li>
<li><strong>Deliver and hand over.</strong> You receive the Revit model, and usually a set of plans, sections and elevations extracted from it.</li>
</ol>

<h2>How accurate does it need to be?</h2>
<p>Accuracy is a decision, not a default. A model for early feasibility can accept a looser tolerance than one used to fabricate steel or coordinate tight service routes. Agree three things up front:</p>
<ul>
<li><strong>Tolerance:</strong> how close the model must be to the scan, for example a few millimeters or a couple of centimeters.</li>
<li><strong>Level of detail:</strong> which elements are modeled, and how much geometry each one carries.</li>
<li><strong>What is excluded:</strong> furniture, fine finishes and hidden services are typically left out unless you ask for them.</li>
</ul>
<p>Putting these in writing prevents the most common disagreement in scan to BIM projects: the client expects detail the scope never included.</p>

<h2>What you get at the end</h2>
<ul>
<li>A Revit model of the existing building with levels, grids and clean, named elements.</li>
<li>Floor plans, sections and elevations generated from that model.</li>
<li>Optionally, a linked point cloud so your team can verify any dimension later.</li>
</ul>

<h2>Common pitfalls to avoid</h2>
<ul>
<li><strong>Scanning without a scope.</strong> A beautiful cloud with no agreed model requirements produces arguments, not drawings.</li>
<li><strong>Missed areas.</strong> Ceiling voids, plant rooms and roof spaces are often skipped. Tell the scanning team what the model must cover.</li>
<li><strong>Overmodeling.</strong> Modeling every irregularity makes the file heavy and slow. Model what the design needs.</li>
<li><strong>No coordinate plan.</strong> Make sure the scan and the model share a coordinate system, especially if other models will be linked later.</li>
</ul>

<h2>What to prepare before you start</h2>
<p>To get an accurate scope and quote, gather the point cloud files, any existing drawings (even if they are outdated), the target Revit version, and a short description of what the model will be used for. If you only have rough scans or photos, say so; there are options at different levels of detail.</p>

<h2>Next step</h2>
<p>Architive builds Revit models from point clouds and from existing drawings, with a clear scope agreed before work begins. See how it works on our <a href="/bim-revit-scan-to-bim/">BIM and Revit services</a> page, or <a href="/contact/">send us your files</a> for a free consultation and a practical recommendation.</p>
BODY,
];
