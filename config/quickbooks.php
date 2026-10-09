<?php

declare(strict_types=1);

/**
 * What the QuickBooks bridge is allowed to take.
 *
 * CHANNELS is the pilot gate. While it holds a non-empty list, only records whose sales
 * order came from one of those channels are ever offered to the bridge — everything else
 * stays pending and untouched, exactly as it is today.
 *
 * It is a gate rather than a setting screen on purpose. Opening the sync to every invoice
 * is a decision somebody should make deliberately, on the server, not something reachable
 * by a mis-click. Empty means no gate at all, which is the eventual destination and not
 * where we are starting.
 *
 * An invoice with NO sales order - a counter sale, a manual correction - has no channel,
 * so it is excluded while the gate is on. That is correct for the pilot: those are the
 * records whose double entry we are not ready to stop yet.
 */
return [
    'channels' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string)($_ENV['QB_SYNC_CHANNELS'] ?? 'website'))
    ))),
];
