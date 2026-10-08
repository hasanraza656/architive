{{-- Interactive "split vs one team" diagram (auto-plays when scrolled into view). --}}
<div class="unify" data-unify data-state="split">
    <div class="unify__tabs" role="group" aria-label="Compare production models">
        <button type="button" class="is-active" data-state-btn="split" aria-pressed="true">Split across freelancers</button>
        <button type="button" data-state-btn="one" aria-pressed="false">One accountable team</button>
    </div>
    <svg class="unify__svg" viewBox="0 0 520 380" role="img" aria-labelledby="unifyT unifyD" focusable="false">
        <title id="unifyT">Comparison of split freelancers versus one coordinated team</title>
        <desc id="unifyD">With separate freelancers, you coordinate visualization, BIM and CAD yourself. With Architive, one brief goes to one team with one point of contact.</desc>

        {{-- tangled links (split state) --}}
        <g class="u-split" fill="none" stroke-linecap="round">
            <path class="u-line" d="M90 80 C 180 110, 220 150, 260 200"/>
            <path class="u-line" d="M430 80 C 350 110, 300 150, 260 200"/>
            <path class="u-line" d="M260 330 C 260 290, 260 250, 260 200"/>
            <path class="u-line u-line--dash" d="M90 80 C 200 40, 330 40, 430 80"/>
            <path class="u-line u-line--dash" d="M430 80 C 470 190, 380 300, 260 330"/>
            <path class="u-line u-line--dash" d="M260 330 C 140 300, 50 190, 90 80"/>
            <g class="u-warn" font-family="JetBrains Mono, monospace" font-size="9.5" letter-spacing="1">
                <text x="150" y="102" transform="rotate(24 150 102)">RE-BRIEF</text>
                <text x="330" y="102" transform="rotate(-24 330 102)">RE-BRIEF</text>
                <text x="268" y="270">RE-BRIEF</text>
                <text x="206" y="30">STANDARDS DRIFT</text>
            </g>
        </g>
        {{-- unified link (one state) --}}
        <g class="u-one" fill="none" stroke-linecap="round">
            <path class="u-line u-line--accent" d="M150 190 H 238"/>
            <path class="u-arrow" d="M230 182 L240 190 L230 198"/>
            <text x="194" y="176" text-anchor="middle" font-family="JetBrains Mono, monospace" font-size="9.5" letter-spacing="1" class="u-tag">ONE BRIEF</text>
        </g>

        <g class="u-hub"><circle cx="360" cy="190" r="104"/><circle cx="360" cy="190" r="104" class="u-hub__ring"/>
            <text x="360" y="76" text-anchor="middle" font-family="JetBrains Mono, monospace" font-size="10" letter-spacing="2" font-weight="700">ARCHITIVE</text>
        </g>

        <g class="u-node u-you"><circle r="42"/><text y="-3" text-anchor="middle">YOUR</text><text y="11" text-anchor="middle">TEAM</text></g>
        <g class="u-node u-cad"><circle r="32"/><text y="4" text-anchor="middle">CAD</text></g>
        <g class="u-node u-bim"><circle r="32"/><text y="4" text-anchor="middle">BIM</text></g>
        <g class="u-node u-viz"><circle r="32"/><text y="4" text-anchor="middle">VIZ</text></g>
    </svg>
    <p class="unify__caption" aria-live="polite">
        <span data-cap="split">Three suppliers. Three briefs. <b>You coordinate every handover.</b></span>
        <span data-cap="one" hidden>One brief. One team. <b>One accountable point of contact.</b></span>
    </p>
</div>
