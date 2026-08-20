<?php

add_action('wp_footer', 'render_landing_svg_sprite', 99);
function render_landing_svg_sprite() {
    ?>
    <svg xmlns="http://www.w3.org/2000/svg" style="position: absolute; width: 0; height: 0; overflow: hidden;" aria-hidden="true">
        <style>
            @keyframes lpRotateRing { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
            @keyframes lpRotateRing2 { from { transform: rotate(0deg); } to { transform: rotate(-360deg); } }
            @keyframes lpPulse { 0%,100% { opacity:.6; } 50% { opacity: 1; } }
            @keyframes lpTwinkle { 0%,100% { opacity:.2; } 50% { opacity: .9; } }
            .ring-outer { transform-origin: 340px 200px; animation: lpRotateRing 18s linear infinite; }
            .ring-inner { transform-origin: 340px 200px; animation: lpRotateRing2 12s linear infinite; }
            .center-pulse { animation: lpPulse 3s ease-in-out infinite; }
            .star { animation: lpTwinkle var(--td, 2.5s) ease-in-out infinite; animation-delay: var(--dl, 0s); }
        </style>
        <defs>
            <?php render_hero_symbol(); ?>
            <?php render_indicators_symbol(); ?>
        </defs>
    </svg>
    <?php
}

function render_hero_symbol() {
    ?>
    <symbol id="svg-hero-illu" viewBox="0 0 680 400">
        <clipPath id="circ-clip"><circle cx="340" cy="200" r="130"/></clipPath>
        <circle class="star" style="--td:3.1s;--dl:0s" cx="60" cy="40" r="1.5" fill="var(--svg-ring-pale)"/>
        <circle class="star" style="--td:2.2s;--dl:.4s" cx="610" cy="60" r="1" fill="var(--svg-ring-pale)"/>
        <circle class="star" style="--td:4s;--dl:.8s" cx="90" cy="340" r="1.5" fill="var(--svg-ring-mid)"/>
        <circle class="star" style="--td:2.8s;--dl:.2s" cx="580" cy="320" r="1" fill="var(--svg-ring-pale)"/>
        <circle class="star" style="--td:3.5s;--dl:1s" cx="150" cy="100" r="1" fill="var(--svg-ring-mid)"/>
        <circle class="star" style="--td:2.4s;--dl:.6s" cx="530" cy="150" r="1.5" fill="var(--svg-ring-pale)"/>
        <circle class="star" style="--td:3.8s;--dl:.3s" cx="120" cy="270" r="1" fill="var(--svg-ring-mid)"/>
        <circle class="star" style="--td:2.6s;--dl:.9s" cx="560" cy="240" r="1" fill="var(--svg-ring-pale)"/>
        <g class="ring-outer">
            <circle cx="340" cy="200" r="170" fill="none" stroke="var(--svg-ring-pale)" stroke-width="0.5" stroke-dasharray="4 8"/>
            <line x1="340" y1="30" x2="340" y2="44" stroke="var(--svg-ring-mid)" stroke-width="1" transform="rotate(0 340 200)"/>
            <line x1="340" y1="30" x2="340" y2="44" stroke="var(--svg-ring-mid)" stroke-width="1" transform="rotate(30 340 200)"/>
            <line x1="340" y1="30" x2="340" y2="44" stroke="var(--svg-ring)" stroke-width="1.5" transform="rotate(90 340 200)"/>
            <line x1="340" y1="30" x2="340" y2="44" stroke="var(--svg-ring)" stroke-width="1.5" transform="rotate(180 340 200)"/>
            <line x1="340" y1="30" x2="340" y2="44" stroke="var(--svg-ring)" stroke-width="1.5" transform="rotate(270 340 200)"/>
            <circle cx="340" cy="37" r="3" fill="var(--svg-ring)" transform="rotate(40 340 200)"/>
            <circle cx="340" cy="37" r="2" fill="var(--svg-ring-pale)" transform="rotate(80 340 200)"/>
            <circle cx="340" cy="37" r="3" fill="var(--svg-ring)" transform="rotate(120 340 200)"/>
            <circle cx="340" cy="37" r="2" fill="var(--svg-ring-pale)" transform="rotate(160 340 200)"/>
            <circle cx="340" cy="37" r="3.5" fill="var(--svg-ring-mid)" transform="rotate(200 340 200)"/>
            <circle cx="340" cy="37" r="2" fill="var(--svg-ring-pale)" transform="rotate(240 340 200)"/>
            <circle cx="340" cy="37" r="3" fill="var(--svg-ring)" transform="rotate(280 340 200)"/>
            <circle cx="340" cy="37" r="3" fill="var(--svg-ring)" transform="rotate(360 340 200)"/>
        </g>
        <g class="ring-inner">
            <circle cx="340" cy="200" r="142" fill="none" stroke="var(--svg-ring-pale)" stroke-width="0.5"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(45 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(90 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(135 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(180 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(225 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(270 340 200)"/>
            <circle cx="340" cy="58" r="4" fill="var(--svg-ring-light)" stroke="var(--svg-ring)" stroke-width="1" transform="rotate(315 340 200)"/>
        </g>
        <circle cx="340" cy="200" r="110" fill="none" stroke="var(--svg-ring-pale)" stroke-width="0.5" stroke-dasharray="2 6"/>
        <circle cx="340" cy="200" r="80" fill="none" stroke="var(--svg-ring-pale)" stroke-width="0.5"/>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93">1</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(40 340 200)">2</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(80 340 200)">3</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(120 340 200)">4</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(160 340 200)">5</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(200 340 200)">6</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(240 340 200)">7</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(280 340 200)">8</text>
        <text font-size="11" fill="var(--svg-ring)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" dominant-baseline="central" x="340" y="93" transform="rotate(320 340 200)">9</text>
        <circle cx="340" cy="200" r="78" fill="var(--svg-ring-light)" opacity="0.4"/>
        <polygon points="340,138 366,183 340,228 314,183" fill="none" stroke="var(--svg-ring-mid)" stroke-width="0.8" opacity="0.6"/>
        <polygon points="340,172 370,200 340,228 310,200" fill="none" stroke="var(--svg-ring)" stroke-width="0.8" opacity="0.6" transform="rotate(60 340 200)"/>
        <circle class="center-pulse" cx="340" cy="200" r="14" fill="var(--primary)" opacity="0.15"/>
        <circle cx="340" cy="200" r="8" fill="var(--primary)" opacity="0.8"/>
        <circle cx="340" cy="200" r="3" fill="var(--primary-light)"/>
    </symbol>
    <?php
}

function render_indicators_symbol() {
    ?>
    <symbol id="svg-indicators-illu" viewBox="0 0 680 200">
        <rect x="40" y="30" width="130" height="140" rx="12" fill="var(--primary-light)" stroke="var(--primary-pale)" stroke-width="0.5"/>
        <polygon points="105,58 130,108 80,108" fill="none" stroke="var(--primary)" stroke-width="1.5"/>
        <polygon points="105,72 122,102 88,102" fill="var(--primary)" opacity="0.12"/>
        <line x1="105" y1="108" x2="105" y2="118" stroke="var(--primary-mid)" stroke-width="1" stroke-dasharray="2 3"/>
        <circle cx="105" cy="122" r="3" fill="var(--primary)"/>
        <text font-size="11" fill="var(--primary)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" x="105" y="148">Chủ đạo</text>
        <text font-size="10" fill="var(--primary)" font-family="Inter,sans-serif" text-anchor="middle" x="105" y="162">Con đường đời</text>

        <rect x="192" y="30" width="130" height="140" rx="12" fill="var(--green-light)" stroke="var(--green-light)" stroke-width="0.5"/>
        <path d="M257,112 C257,112 228,90 228,74 C228,64 235,58 243,58 C249,58 254,62 257,66 C260,62 265,58 271,58 C279,58 286,64 286,74 C286,90 257,112 257,112 Z" fill="none" stroke="var(--green)" stroke-width="1.5"/>
        <path d="M257,106 C257,106 236,88 236,75 C236,68 241,64 247,64 C252,64 256,67 257,70 C258,67 262,64 267,64 C273,64 278,68 278,75 C278,88 257,106 257,106 Z" fill="var(--green)" opacity="0.12"/>
        <text font-size="11" fill="var(--green)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" x="257" y="148">Linh hồn</text>
        <text font-size="10" fill="var(--green)" font-family="Inter,sans-serif" text-anchor="middle" x="257" y="162">Mong muốn sâu thẳm</text>

        <rect x="344" y="30" width="130" height="140" rx="12" fill="var(--orange-light)" stroke="var(--orange-light)" stroke-width="0.5"/>
        <circle cx="409" cy="83" r="32" fill="none" stroke="var(--orange)" stroke-width="0.8" opacity="0.4"/>
        <circle cx="409" cy="83" r="24" fill="none" stroke="var(--orange)" stroke-width="0.8"/>
        <line x1="409" y1="61" x2="409" y2="105" stroke="var(--orange)" stroke-width="1" opacity="0.5"/>
        <line x1="387" y1="83" x2="431" y2="83" stroke="var(--orange)" stroke-width="1" opacity="0.5"/>
        <polygon points="409,63 413,83 409,79 405,83" fill="var(--orange)"/>
        <circle cx="409" cy="83" r="3" fill="var(--orange)"/>
        <text font-size="11" fill="var(--orange)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" x="409" y="148">Sứ mệnh</text>
        <text font-size="10" fill="var(--orange)" font-family="Inter,sans-serif" text-anchor="middle" x="409" y="162">Vai trò hướng tới</text>

        <rect x="496" y="30" width="130" height="140" rx="12" fill="var(--blue-light)" stroke="var(--blue-light)" stroke-width="0.5"/>
        <circle cx="561" cy="80" r="28" fill="none" stroke="var(--blue)" stroke-width="1.2"/>
        <circle cx="552" cy="74" r="4" fill="none" stroke="var(--blue)" stroke-width="1"/>
        <circle cx="570" cy="74" r="4" fill="none" stroke="var(--blue)" stroke-width="1"/>
        <circle cx="552" cy="74" r="1.5" fill="var(--blue)"/>
        <circle cx="570" cy="74" r="1.5" fill="var(--blue)"/>
        <path d="M548,88 Q561,98 574,88" fill="none" stroke="var(--blue)" stroke-width="1.2" stroke-linecap="round"/>
        <circle cx="561" cy="80" r="34" fill="none" stroke="var(--blue)" stroke-width="0.5" stroke-dasharray="2 5" opacity="0.5"/>
        <text font-size="11" fill="var(--blue)" font-family="Inter,sans-serif" font-weight="600" text-anchor="middle" x="561" y="148">Nhân cách</text>
        <text font-size="10" fill="var(--blue)" font-family="Inter,sans-serif" text-anchor="middle" x="561" y="162">Hình ảnh bên ngoài</text>
    </symbol>
    <?php
}