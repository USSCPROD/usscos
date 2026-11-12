/*
 * Keep the bench screen awake.
 *
 * The shipping station is a Fire tablet. Its screen timeout tops out at 30
 * minutes and the "stay awake while charging" switch lives behind Developer
 * Options, which Amazon has been quietly removing. So the tablet sleeps
 * mid-pick and somebody puts a box down to wake it up.
 *
 * A screen wake lock asks the browser to keep the display on while one of the
 * bench pages is open. It applies to this site only, it needs nothing set up
 * on the device, and a factory update cannot undo it.
 *
 * Three things the browser does on its own, which is why this is not simply
 * "request once and forget":
 *   - the lock is dropped whenever the page is hidden, so it has to be taken
 *     again when the tab comes back
 *   - it is released on navigation, so every page takes its own
 *   - it can be refused outright - unsupported browser, low battery, a policy
 *     we do not get told about
 *
 * The last one is the reason for the indicator. A wake lock that silently
 * failed would look exactly like one that worked, right up until the screen
 * went dark with someone's hands full.
 */
(function () {
    'use strict';

    var PAGES = [
        /^\/shipping\/?$/,              // the queue
        /^\/shipping\/pull\/?$/,        // pull list
        /^\/shipping\/\d+\/pick\/?$/,
        /^\/shipping\/\d+\/pack\/?$/,
        /^\/shipping\/\d+\/label\/?$/,
        /^\/receiving/
    ];

    function wanted() {
        var path = window.location.pathname;
        for (var i = 0; i < PAGES.length; i++) {
            if (PAGES[i].test(path)) { return true; }
        }
        return false;
    }

    if (!wanted()) { return; }

    var lock  = null;
    var badge = null;

    function show(state, detail) {
        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'wakeBadge';
            badge.style.cssText =
                'position:fixed;right:.8rem;bottom:.8rem;z-index:900;border-radius:999px;' +
                'padding:.4rem .85rem;font-size:.78rem;font-weight:600;line-height:1;' +
                'box-shadow:0 1px 6px rgba(0,0,0,.18);cursor:default;' +
                'font-family:inherit;max-width:16rem';
            document.body.appendChild(badge);
        }

        if (state === 'on') {
            badge.style.background = '#f0fdf4';
            badge.style.border     = '1px solid #bbf7d0';
            badge.style.color      = '#166534';
            badge.textContent      = '● Screen staying on';
            badge.title            = 'This page is holding the screen awake.';
        } else {
            badge.style.background = '#fffbeb';
            badge.style.border     = '1px solid #fcd34d';
            badge.style.color      = '#92400e';
            badge.textContent      = '○ Screen will sleep';
            // Said in full on the badge itself, because the bench is a tablet
            // and there is no hover to reveal a title.
            badge.title            = detail || 'The browser would not hold the screen awake.';
        }
    }

    function request() {
        if (!('wakeLock' in navigator)) {
            show('off', 'This browser cannot hold the screen awake.');
            return;
        }
        if (lock && !lock.released) { return; }

        navigator.wakeLock.request('screen').then(function (l) {
            lock = l;
            show('on');
            l.addEventListener('release', function () {
                // Only a surprise release is worth reporting. Leaving the page
                // releases it too, and saying so on the way out is noise.
                if (document.visibilityState === 'visible') { show('off'); }
            });
        }).catch(function (err) {
            show('off', 'The screen lock was refused: ' + (err && err.name ? err.name : 'unknown') +
                        '. Battery saver will do this.');
        });
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') { request(); }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', request);
    } else {
        request();
    }
}());
