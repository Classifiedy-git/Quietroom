<?php
/**
 * Quietroom: icon system
 *
 * A small, dependency-free line-icon set (24x24 grid, 1.75 stroke) drawn in
 * the same visual language as the brand mark. Icons are shipped as an inline
 * SVG sprite so a page never waits on an extra request and every icon can
 * inherit `currentColor`.
 *
 * Usage:
 *   icon('home')                  → <svg class="icon"><use href="#ic-home"/></svg>
 *   icon('home', 'icon-lg')       → with an extra class
 *   icon_sprite()                 → the sprite itself, printed once per page
 */

class Icons
{
    /** @var array<string,string>|null */
    private static ?array $set = null;

    /**
     * The icon set. Each entry is the inner markup of a <symbol>.
     * All icons share a 24x24 viewBox and inherit stroke/fill from <symbol>.
     *
     * @return array<string,string>
     */
    public static function all(): array
    {
        if (self::$set !== null) {
            return self::$set;
        }

        $s = [];

        /* ---------- navigation ---------- */
        $s['home'] = '<path d="M3 10.6 12 3.2l9 7.4"/><path d="M5.6 9.4V19a2 2 0 0 0 2 2h3.1v-5.4h2.6V21h3.1a2 2 0 0 0 2-2V9.4"/>';
        $s['compass'] = '<circle cx="12" cy="12" r="9.2"/><path d="m15.9 8.1-2 5.8-5.8 2 2-5.8z"/>';
        $s['chart'] = '<path d="M3 3v16.2a1.8 1.8 0 0 0 1.8 1.8H21"/><path d="M7.5 15.4l3.4-4.3 2.9 2.5 4.4-5.6"/>';
        $s['trend'] = '<path d="m3 16.5 5.6-5.6 3.6 3.6L21 5.5"/><path d="M15.4 5.5H21v5.6"/>';
        $s['grid'] = '<rect x="3.2" y="3.2" width="7.4" height="7.4" rx="2.1"/><rect x="13.4" y="3.2" width="7.4" height="7.4" rx="2.1"/><rect x="3.2" y="13.4" width="7.4" height="7.4" rx="2.1"/><rect x="13.4" y="13.4" width="7.4" height="7.4" rx="2.1"/>';
        $s['chevron-down'] = '<path d="m6 9.5 6 6 6-6"/>';
        $s['arrow-right'] = '<path d="M4 12h15.5"/><path d="m13.4 5.6 6.4 6.4-6.4 6.4"/>';
        $s['arrow-left'] = '<path d="M20 12H4.5"/><path d="m10.6 5.6-6.4 6.4 6.4 6.4"/>';
        $s['external'] = '<path d="M18.5 13.4v5.2a2 2 0 0 1-2 2H5.2a2 2 0 0 1-2-2V7.5a2 2 0 0 1 2-2h5.2"/><path d="M14.8 3.2h6v6"/><path d="m10.4 13.6 9.4-9.4"/>';

        /* ---------- actions ---------- */
        $s['plus'] = '<path d="M12 4.8v14.4M4.8 12h14.4"/>';
        $s['check'] = '<path d="m20 6.5-11 11-5-5"/>';
        $s['circle'] = '<circle cx="12" cy="12" r="9.2"/>';
        $s['square'] = '<rect x="3.4" y="3.4" width="17.2" height="17.2" rx="3.4"/>';
        $s['x'] = '<path d="M18.2 5.8 5.8 18.2M5.8 5.8l12.4 12.4"/>';
        $s['edit'] = '<path d="M11 4.5H5.2a2 2 0 0 0-2 2v12.3a2 2 0 0 0 2 2h12.3a2 2 0 0 0 2-2V13"/><path d="M18.4 2.6a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>';
        $s['trash'] = '<path d="M3.8 6.2h16.4"/><path d="M8.4 6.2V4.6a1.8 1.8 0 0 1 1.8-1.8h3.6a1.8 1.8 0 0 1 1.8 1.8v1.6"/><path d="M18.6 6.2 17.7 19a2 2 0 0 1-2 1.9H8.3a2 2 0 0 1-2-1.9L5.4 6.2"/><path d="M10.4 10.6v6M13.6 10.6v6"/>';
        $s['download'] = '<path d="M20.8 15.4v3.4a2 2 0 0 1-2 2H5.2a2 2 0 0 1-2-2v-3.4"/><path d="m7.6 10.4 4.4 4.4 4.4-4.4"/><path d="M12 14.6V3.2"/>';
        $s['copy'] = '<rect x="8.6" y="8.6" width="11.8" height="11.8" rx="2.4"/><path d="M5.4 15.4H4.6A1.6 1.6 0 0 1 3 13.8V4.6A1.6 1.6 0 0 1 4.6 3h9.2a1.6 1.6 0 0 1 1.6 1.6v.8"/>';
        $s['refresh'] = '<path d="M20.6 4.6v5.6H15"/><path d="M3.4 19.4v-5.6H9"/><path d="M5.1 9.2a7.6 7.6 0 0 1 12.8-2.7l2.7 2.7"/><path d="M18.9 14.8a7.6 7.6 0 0 1-12.8 2.7l-2.7-2.7"/>';
        $s['send'] = '<path d="M21.4 2.6 2.9 9.9a.6.6 0 0 0-.1 1.1l7.4 3.2 3.2 7.4a.6.6 0 0 0 1.1-.1z"/><path d="m10.2 14.2 4.2-4.2"/>';
        $s['search'] = '<circle cx="10.8" cy="10.8" r="7"/><path d="m21 21-5.1-5.1"/>';
        $s['save'] = '<path d="M19.4 21.2H4.6a2 2 0 0 1-2-2V4.8a2 2 0 0 1 2-2h11l5.4 5.4v11a2 2 0 0 1-2 2z"/><path d="M7.4 2.8v5.6h8.2V2.8"/><path d="M7.4 21.2v-6.8h9.2v6.8"/>';
        $s['print'] = '<path d="M6.4 9.2V3.4h11.2v5.8"/><path d="M6.4 18.2H4.8a2 2 0 0 1-2-2v-4.4a2 2 0 0 1 2-2h14.4a2 2 0 0 1 2 2v4.4a2 2 0 0 1-2 2h-1.6"/><rect x="6.4" y="14.4" width="11.2" height="6.4" rx="1"/>';
        $s['link'] = '<path d="M9.6 13.8a4.4 4.4 0 0 0 6.6.5l2.6-2.6a4.4 4.4 0 0 0-6.2-6.2l-1.5 1.5"/><path d="M14.4 10.2a4.4 4.4 0 0 0-6.6-.5l-2.6 2.6a4.4 4.4 0 0 0 6.2 6.2l1.5-1.5"/>';

        /* ---------- status & messaging ---------- */
        $s['info'] = '<circle cx="12" cy="12" r="9.2"/><path d="M12 16.4v-4.8"/><path d="M12 7.9h.01"/>';
        $s['alert'] = '<path d="M10.3 3.9 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9.4v4.4"/><path d="M12 17.6h.01"/>';
        $s['check-circle'] = '<path d="M21.6 11.1V12a9.6 9.6 0 1 1-5.7-8.8"/><path d="m21.6 4.6-9.8 9.8-3-3"/>';
        $s['x-circle'] = '<circle cx="12" cy="12" r="9.2"/><path d="m15.1 8.9-6.2 6.2M8.9 8.9l6.2 6.2"/>';
        $s['bell'] = '<path d="M18.4 8.6a6.4 6.4 0 1 0-12.8 0c0 7-3 9-3 9h18.8s-3-2-3-9"/><path d="M13.7 20.6a2 2 0 0 1-3.4 0"/>';
        $s['shield'] = '<path d="M12 21.6s7.6-3.8 7.6-9.6V5.4L12 2.4 4.4 5.4V12c0 5.8 7.6 9.6 7.6 9.6z"/>';
        $s['shield-check'] = '<path d="M12 21.6s7.6-3.8 7.6-9.6V5.4L12 2.4 4.4 5.4V12c0 5.8 7.6 9.6 7.6 9.6z"/><path d="m8.9 11.8 2.2 2.2 4-4.2"/>';
        $s['shield-alert'] = '<path d="M12 21.6s7.6-3.8 7.6-9.6V5.4L12 2.4 4.4 5.4V12c0 5.8 7.6 9.6 7.6 9.6z"/><path d="M12 8.2v4.2"/><path d="M12 15.7h.01"/>';
        $s['sparkle'] = '<path d="M12 2.8 13.9 8 19 9.9 13.9 11.8 12 17l-1.9-5.2L5 9.9 10.1 8z"/><path d="M18.6 15.4l.8 2.1 2.1.8-2.1.8-.8 2.1-.8-2.1-2.1-.8 2.1-.8z"/>';
        $s['lightbulb'] = '<path d="M9.4 18.4h5.2M10.4 21.6h3.2"/><path d="M12 2.6a6.6 6.6 0 0 0-3.9 11.9v2.1h7.8v-2.1A6.6 6.6 0 0 0 12 2.6z"/>';
        $s['target'] = '<circle cx="12" cy="12" r="9.2"/><circle cx="12" cy="12" r="5.2"/><circle cx="12" cy="12" r="1.4"/>';
        $s['flame'] = '<path d="M12 2.4c.4 3.2 2.4 4.3 3.8 6.3A7.6 7.6 0 0 1 12 21.6a6.6 6.6 0 0 1-6.6-6.6c0-3 1.6-4.7 2.6-6.4.3 1.4 1 2.2 1.9 2.2 1.2 0 1.8-1.2 1.8-2.5 0-2.1-.7-3.6.3-5.9z"/>';
        $s['zap'] = '<path d="M13.2 2.4 3.8 13.6h7.2l-1 8 9.4-11.2h-7.2z"/>';
        $s['clock'] = '<circle cx="12" cy="12" r="9.2"/><path d="M12 6.6V12l3.6 2"/>';
        $s['calendar'] = '<rect x="3.2" y="5" width="17.6" height="16" rx="2.4"/><path d="M8 2.8v4.4M16 2.8v4.4M3.2 10.2h17.6"/>';
        $s['moon'] = '<path d="M21 12.9A9 9 0 1 1 11.1 3a7 7 0 0 0 9.9 9.9z"/>';
        $s['sun'] = '<circle cx="12" cy="12" r="4.4"/><path d="M12 1.8v2.4M12 19.8v2.4M4.8 4.8l1.7 1.7M17.5 17.5l1.7 1.7M1.8 12h2.4M19.8 12h2.4M4.8 19.2l1.7-1.7M17.5 6.5l1.7-1.7"/>';
        $s['wind'] = '<path d="M9.6 4.6A2 2 0 1 1 11 8H2.6"/><path d="M12.6 19.4A2 2 0 1 0 14 16H2.6"/><path d="M17.7 7.7A2.5 2.5 0 1 1 19.5 12H2.6"/>';
        $s['anchor'] = '<circle cx="12" cy="5.2" r="2.6"/><path d="M12 7.8v13.6"/><path d="M4.8 12.4H2.2a10 10 0 0 0 19.6 0h-2.6"/><path d="M8.6 12.4h6.8"/>';

        /* ---------- people & support ---------- */
        $s['user'] = '<path d="M20 21v-1.8a4.2 4.2 0 0 0-4.2-4.2H8.2A4.2 4.2 0 0 0 4 19.2V21"/><circle cx="12" cy="7.4" r="4.2"/>';
        $s['users'] = '<path d="M16.4 21v-1.8a4.2 4.2 0 0 0-4.2-4.2H6.6a4.2 4.2 0 0 0-4.2 4.2V21"/><circle cx="9.4" cy="7.4" r="4.2"/><path d="M21.6 21v-1.8a4.2 4.2 0 0 0-3.2-4.1"/><path d="M15.6 3.3a4.2 4.2 0 0 1 0 8.2"/>';
        $s['headset'] = '<path d="M3.4 17.6v-3.4a8.6 8.6 0 0 1 17.2 0v3.4"/><path d="M20.6 18.4a2.4 2.4 0 0 1-2.4 2.4h-1.2v-7.2h1.2a2.4 2.4 0 0 1 2.4 2.4z"/><path d="M3.4 18.4a2.4 2.4 0 0 0 2.4 2.4H7v-7.2H5.8a2.4 2.4 0 0 0-2.4 2.4z"/>';
        $s['heart'] = '<path d="M20.6 5.1a5.1 5.1 0 0 0-7.2 0L12 6.5l-1.4-1.4a5.1 5.1 0 1 0-7.2 7.2L12 21.1l8.6-8.8a5.1 5.1 0 0 0 0-7.2z"/>';
        $s['life-buoy'] = '<circle cx="12" cy="12" r="9.2"/><circle cx="12" cy="12" r="3.8"/><path d="m5.7 5.7 3.8 3.8M14.5 14.5l3.8 3.8M18.3 5.7l-3.8 3.8M9.5 14.5l-3.8 3.8"/>';
        $s['phone'] = '<path d="M21.4 16.9v2.9a2 2 0 0 1-2.2 2 19.4 19.4 0 0 1-8.5-3 19 19 0 0 1-5.9-5.9 19.4 19.4 0 0 1-3-8.6 2 2 0 0 1 2-2.2h2.9a2 2 0 0 1 2 1.7c.1.9.3 1.8.7 2.7a2 2 0 0 1-.5 2.1L8.1 9.3a15.6 15.6 0 0 0 5.9 5.9l1.2-1.2a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.4 1.6z"/>';
        $s['message-circle'] = '<path d="M20.8 11.8a8 8 0 1 1-3.4-6.6"/><path d="M20.8 11.8v4.4a1.8 1.8 0 0 1-3.1 1.3l-2.3-1.4a8 8 0 0 1-6.6-3.3 8 8 0 0 1-.6-6.6 8 8 0 0 1 6.6-3.3 8 8 0 0 1 6 3.3 8 8 0 0 1 0 6.6z"/>';
        $s['quote'] = '<path d="M9.4 6.4C6.6 7.4 5 9.6 5 12.6c0 3 1.6 5 3.9 5 2 0 3.5-1.5 3.5-3.4 0-1.9-1.3-3.3-3.1-3.3-.3 0-.6 0-.8.1.3-1.5 1.4-2.6 3-3.2z"/><path d="M19 6.4c-2.8 1-4.4 3.2-4.4 6.2 0 3 1.6 5 3.9 5 2 0 3.5-1.5 3.5-3.4 0-1.9-1.3-3.3-3.1-3.3-.3 0-.6 0-.8.1.3-1.5 1.4-2.6 3-3.2z"/>';

        /* ---------- content & objects ---------- */
        $s['book'] = '<path d="M3.4 4.8A2.4 2.4 0 0 1 5.8 2.4h12.8a1.4 1.4 0 0 1 1.4 1.4v15.4a1.4 1.4 0 0 1-1.4 1.4H5.8a2.4 2.4 0 0 0-2.4 2.4z"/><path d="M3.4 18.2a2.4 2.4 0 0 1 2.4-2.4h14"/><path d="M7.6 6.6h7.6M7.6 10.2h5.2"/>';
        $s['pen'] = '<path d="M16.6 3.4a2.3 2.3 0 0 1 3.3 3.3L7.5 19.1 3 20.4l1.3-4.5z"/><path d="m14.6 5.4 3.3 3.3"/>';
        $s['feather'] = '<path d="M20.2 3.8a5.6 5.6 0 0 0-8 0L4.6 11.4a5.6 5.6 0 0 0 8 8l7.6-7.6a5.6 5.6 0 0 0 0-8z"/><path d="M16 8 3.6 20.4"/><path d="M20.2 3.8 12.4 11.6a5.6 5.6 0 0 0-8 0"/>';
        $s['list'] = '<path d="M8.4 6.2h12.2M8.4 12h12.2M8.4 17.8h12.2"/><path d="M4 6.2h.01M4 12h.01M4 17.8h.01"/>';
        $s['clipboard'] = '<path d="M9 4.4H7.4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h9.2a2 2 0 0 0 2-2v-13a2 2 0 0 0-2-2H15"/><rect x="9" y="2.4" width="6" height="4" rx="1.4"/><path d="M8.8 11.4h6.4M8.8 15.4h4.2"/>';
        $s['award'] = '<circle cx="12" cy="8.4" r="5.8"/><path d="m8.4 13.4-1.2 7.8 4.8-2.8 4.8 2.8-1.2-7.8"/>';
        $s['star'] = '<path d="m12 2.8 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.6l6.5-.9z"/>';
        $s['lock'] = '<rect x="4.4" y="10.4" width="15.2" height="11" rx="2.4"/><path d="M8 10.4V7.2a4 4 0 0 1 8 0v3.2"/><path d="M12 14.6v2.8"/>';
        $s['eye'] = '<path d="M2 12s3.8-7 10-7 10 7 10 7-3.8 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3.1"/>';
        $s['key'] = '<circle cx="7.6" cy="16.4" r="3.8"/><path d="m10.4 13.6 8.2-8.2"/><path d="m15.6 8.4 2.2 2.2"/><path d="m18.2 5.8 2.4 2.4"/>';
        $s['mail'] = '<rect x="2.6" y="4.8" width="18.8" height="14.4" rx="2.4"/><path d="m21.4 7-8.5 5.4a2 2 0 0 1-2 0L2.6 7"/>';
        $s['globe'] = '<circle cx="12" cy="12" r="9.2"/><path d="M2.8 12h18.4"/><path d="M12 2.8a14 14 0 0 1 0 18.4 14 14 0 0 1 0-18.4z"/>';
        $s['settings'] = '<circle cx="12" cy="12" r="3.2"/><path d="M19.5 14.6a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.5v.2a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.5-1H2.4a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 1-1.5V2.4a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5 1h.2a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>';
        $s['sliders'] = '<path d="M4 21v-6.6M4 10.2V3M12 21v-8.8M12 8V3M20 21v-4.6M20 12.2V3"/><path d="M1.6 14.4h4.8M9.6 8h4.8M17.6 16.4h4.8"/>';
        $s['log-out'] = '<path d="M9.4 21H5.2a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4.2"/><path d="m16 16.8 4.8-4.8L16 7.2"/><path d="M20.8 12H9.4"/>';
        $s['log-in'] = '<path d="M14.6 21h4.2a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2h-4.2"/><path d="m8 16.8-4.8-4.8L8 7.2"/><path d="M3.2 12h11.4"/>';
        $s['database'] = '<ellipse cx="12" cy="5.6" rx="8" ry="3.2"/><path d="M4 5.6v12.8c0 1.8 3.6 3.2 8 3.2s8-1.4 8-3.2V5.6"/><path d="M4 12c0 1.8 3.6 3.2 8 3.2s8-1.4 8-3.2"/>';
        $s['cloud'] = '<path d="M17.5 19.4H7a4.6 4.6 0 0 1-.8-9.1 6.2 6.2 0 0 1 11.9 1.6 3.8 3.8 0 0 1-.6 7.5z"/>';
        $s['play'] = '<path d="M6.4 3.6 20 12 6.4 20.4z"/>';
        $s['pause'] = '<rect x="6.4" y="4.4" width="4" height="15.2" rx="1.4"/><rect x="13.6" y="4.4" width="4" height="15.2" rx="1.4"/>';
        $s['rotate'] = '<path d="M2.6 12a9.4 9.4 0 1 0 2.8-6.6"/><path d="M2.6 3.6v5h5"/>';
        $s['brain'] = '<path d="M9.6 3.4A3 3 0 0 0 6.6 6.4a2.8 2.8 0 0 0-2 4.6 3 3 0 0 0 .8 4.6 3 3 0 0 0 3.2 4.4 2.6 2.6 0 0 0 3.4-2.2V5.8a2.4 2.4 0 0 0-2.4-2.4z"/><path d="M14.4 3.4a3 3 0 0 1 3 3 2.8 2.8 0 0 1 2 4.6 3 3 0 0 1-.8 4.6 3 3 0 0 1-3.2 4.4 2.6 2.6 0 0 1-3.4-2.2V5.8a2.4 2.4 0 0 1 2.4-2.4z"/>';
        $s['sun-moon'] = '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.4v2.2M12 19.4v2.2M4.4 4.4l1.6 1.6M18 18l1.6 1.6M2.4 12h2.2M19.4 12h2.2M4.4 19.6 6 18M18 6l1.6-1.6"/>';
        $s['hand'] = '<path d="M8.6 11V5.6a1.7 1.7 0 1 1 3.4 0V11"/><path d="M12 10.4V4.8a1.7 1.7 0 1 1 3.4 0V11"/><path d="M15.4 11V6.8a1.7 1.7 0 1 1 3.4 0v7.4a7.4 7.4 0 0 1-7.4 7.4H10a6 6 0 0 1-6-6v-3.6a1.7 1.7 0 1 1 3.4 0V15"/>';
        $s['droplet'] = '<path d="M12 2.6s6.6 6.5 6.6 11a6.6 6.6 0 0 1-13.2 0c0-4.5 6.6-11 6.6-11z"/>';
        $s['bed'] = '<path d="M2.8 19.4v-11M2.8 8.6h18.4a1.6 1.6 0 0 1 1.6 1.6v3.8H2.8"/><path d="M2.8 14h20v5.4"/><circle cx="6.8" cy="11" r="1.8"/>';
        $s['strength'] = '<path d="M6.4 9.2v5.6M3.4 11v2M18.6 9.2v5.6M21.4 11v2"/><path d="M6.4 12h11.2M17.6 12h1"/>';
        $s['footprints'] = '<path d="M8.2 13.4c1.9 0 3.4-1.4 3.4-3.2S10.1 6.6 8.2 6.6 4.8 8.2 4.8 10.2s1.5 3.2 3.4 3.2z"/><path d="M6.6 15.6c0 1.5-.9 2.6-2 2.6s-2-1.1-2-2.6"/><path d="M15.8 12.6c1.9 0 3.4-1.4 3.4-3.2s-1.5-3.6-3.4-3.6-3.4 1.6-3.4 3.6 1.5 3.2 3.4 3.2z"/><path d="M17.4 14.8c0 1.5-.9 2.6-2 2.6s-2-1.1-2-2.6"/>';
        $s['lungs'] = '<path d="M12 3.4v9.4"/><path d="M9.4 8.2c0 2-1.2 2.8-2.6 3.8C5.2 13.4 4 15 4 17.2 4 19.4 5.6 21 7.8 21c1.8 0 2.8-1.3 2.8-3V11"/><path d="M14.6 8.2c0 2 1.2 2.8 2.6 3.8 1.6 1.4 2.8 3 2.8 5.2 0 2.2-1.6 3.8-3.8 3.8-1.8 0-2.8-1.3-2.8-3V11"/>';
        $s['wrench'] = '<path d="M14.6 6.4a4.6 4.6 0 0 0 5.9 5.9l-8 8a2.6 2.6 0 0 1-3.7-3.7l8-8z"/><path d="m14.6 6.4-2.2 2.2 1.8 1.8 2.2-2.2"/>';
        $s['umbrella'] = '<path d="M2.6 12a9.4 9.4 0 0 1 18.8 0z"/><path d="M12 12v7.2a2.3 2.3 0 0 0 4.6 0"/>';
        $s['lifebuoy'] = $s['life-buoy'];

        /* ---------- mood faces (1 = lowest, 10 = brightest) ---------- */
        $face = '<circle cx="12" cy="12" r="9.2"/>';
        $eyes  = '<circle cx="9.1" cy="10.2" r="1.05"/><circle cx="14.9" cy="10.2" r="1.05"/>';
        $eyesUp = '<path d="M7.5 11c.65-1 2.35-1 3 0"/><path d="M13.5 11c.65-1 2.35-1 3 0"/>';
        $eyesDown = '<path d="M7.5 9.9c.65 1 2.35 1 3 0"/><path d="M13.5 9.9c.65 1 2.35 1 3 0"/>';
        $browSad = '<path d="M7.3 7.7 9.9 8.9"/><path d="M14.1 8.9l2.6-1.2"/>';
        $browSoft = '<path d="M7.5 7.9 9.9 8.7"/><path d="M14.1 8.7l2.4-.8"/>';

        $s['mood-1']  = $face . $browSad . $eyesDown . '<path d="M7.8 14.6Q12 10.3 16.2 14.6"/>';
        $s['mood-2']  = $face . $browSad . $eyes     . '<path d="M8.1 14.9Q12 11.7 15.9 14.9"/>';
        $s['mood-3']  = $face . $browSoft . $eyes    . '<path d="M8.4 15.1Q12 12.9 15.6 15.1"/>';
        $s['mood-4']  = $face . $eyes                . '<path d="M8.6 15.3Q12 14.1 15.4 15.3"/>';
        $s['mood-5']  = $face . $eyes                . '<path d="M8.7 15.4H15.3"/>';
        $s['mood-6']  = $face . $eyes                . '<path d="M8.6 15.3Q12 16.2 15.4 15.3"/>';
        $s['mood-7']  = $face . $eyes                . '<path d="M8.4 15.1Q12 16.8 15.6 15.1"/>';
        $s['mood-8']  = $face . $eyes                . '<path d="M8.1 14.9Q12 17.2 15.9 14.9"/>';
        $s['mood-9']  = $face . $eyesUp              . '<path d="M7.9 14.7Q12 17.4 16.1 14.7"/>';
        $s['mood-10'] = $face . $eyesUp              . '<path d="M7.5 14.4Q12 17.6 16.5 14.4"/>';

        return self::$set = $s;
    }

    public static function has(string $name): bool
    {
        return isset(self::all()[$name]);
    }

    /**
     * Render one icon.
     */
    public static function render(string $name, string $class = 'icon', string $title = ''): string
    {
        $set = self::all();
        if (!isset($set[$name])) {
            return '';
        }
        $cls = trim('icon ' . $class);
        $label = $title !== ''
            ? ' role="img" aria-label="' . e($title) . '"'
            : ' aria-hidden="true" focusable="false"';
        return '<svg class="' . e($cls) . '" viewBox="0 0 24 24"' . $label . '>'
            . '<use href="#ic-' . e($name) . '"/></svg>';
    }

    /** The sprite itself, printed once at the top of <body>. */
    public static function sprite(): string
    {
        $out = '<svg xmlns="http://www.w3.org/2000/svg" class="icon-sprite" aria-hidden="true" focusable="false" '
            . 'fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">';
        foreach (self::all() as $name => $body) {
            $out .= '<symbol id="ic-' . e($name) . '" viewBox="0 0 24 24">' . $body . '</symbol>';
        }
        return $out . '</svg>';
    }
}

/** Render an icon. */
function icon(string $name, string $class = '', string $title = ''): string
{
    return Icons::render($name, $class, $title);
}

/** Print the icon sprite. Call once, at the top of <body>. */
function icon_sprite(): string
{
    return Icons::sprite();
}
