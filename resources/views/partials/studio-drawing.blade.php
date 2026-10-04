{{-- Animated studio line drawing (CAD / DWG → Revit LOD 350), echoing the client dummy --}}
<svg class="studio-drawing" viewBox="0 0 520 280" role="img" aria-label="Line drawing showing a CAD drawing evolving into a Revit model" focusable="false">
    <defs><pattern id="sg" width="26" height="26" patternUnits="userSpaceOnUse"><path d="M26 0H0V26" fill="none" stroke="currentColor" stroke-opacity=".08"/></pattern></defs>
    <rect width="520" height="280" fill="url(#sg)"/>
    <line class="sd-ground draw" x1="30" y1="232" x2="490" y2="232"/>
    <g class="sd-cad">
        <rect class="draw" x="70" y="132" width="170" height="100"/>
        <path class="sd-dash" d="M70 170h170"/>
        <text x="155" y="156" text-anchor="middle">CAD / DWG</text>
    </g>
    <g class="sd-bim">
        <rect class="draw" x="240" y="72" width="140" height="160"/>
        <path class="sd-accent draw" d="M356 72v160"/>
        <path class="sd-thin" d="M240 112h140M240 152h140M240 192h140"/>
        <text x="310" y="100" text-anchor="middle">REVIT LOD 350</text>
    </g>
    <g class="sd-dim" font-size="9">
        <path d="M70 252h170M240 252h140" /><path d="M70 246v12M240 246v12M380 246v12"/>
        <text x="155" y="270" text-anchor="middle">6 800</text><text x="310" y="270" text-anchor="middle">5 400</text>
    </g>
</svg>
