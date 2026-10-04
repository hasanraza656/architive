{{-- Animated line-art thumbnail. $kind: visualization | cad | bim (illustrative, not project imagery) --}}
<svg class="bp" viewBox="0 0 320 170" role="img" aria-label="Illustrative line drawing for a {{ $kind }} project" focusable="false">
    <rect class="bp__dash" x="22" y="14" width="276" height="130" rx="2"/>
    <line class="bp__ground" x1="6" y1="144" x2="314" y2="144"/>
    @if ($kind === 'visualization')
        <path class="bp__line" d="M44 144V82l52-30 52 30v62Z"/>
        <path class="bp__line" d="M148 144V70h104v74"/>
        <path class="bp__accent" d="M96 52v92M200 70v74"/>
        <path class="bp__line" d="M170 98h24v28h-24zM214 98h24v28h-24z"/>
        <path class="bp__cam" d="M286 40 252 82M286 40l-8 46M286 40l34 6" />
        <circle class="bp__accent-dot" cx="286" cy="40" r="4"/>
    @elseif ($kind === 'cad')
        <rect class="bp__line" x="44" y="50" width="96" height="94"/>
        <rect class="bp__line" x="140" y="30" width="116" height="114"/>
        <path class="bp__accent" d="M92 50v94M198 30v114"/>
        <path class="bp__line" d="M44 38h96M140 18h116"/>
        <path class="bp__line bp__thin" d="M44 34v8M140 34v8M44 14v8M140 14v8M256 14v8"/>
        <path class="bp__line" d="M72 144a20 20 0 0 1 20-20M170 144a22 22 0 0 0-22-22"/>
    @else
        <path class="bp__line" d="M60 144V92l44-22 44 22v52Z"/>
        <path class="bp__line" d="M148 144V76l50-26 50 26v68Z"/>
        <path class="bp__accent" d="M104 70v74M198 50v94"/>
        <path class="bp__line bp__thin" d="M60 92h88M148 76h100M148 110h100M60 118h88"/>
        <text class="bp__tag" x="162" y="102">LOD 300</text>
    @endif
</svg>
