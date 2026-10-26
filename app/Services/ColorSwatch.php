<?php

declare(strict_types=1);

namespace App\Services;

/**
 * A color name to something that can be painted on screen.
 *
 * Thirty-six colors as labelled buttons is ten rows of the product page, which
 * leaves no room for the description. As dots it is two rows, which is what the
 * live site does and why its page has space for the copy.
 *
 * A color we do not recognise gets a neutral chip with its name in it rather than a
 * guessed hue. Inventing a shade for 'Vegas Gold' and getting it wrong is worse than
 * admitting we do not know: the customer is choosing paint, and a swatch that lies
 * about the color is the one thing this control must never do.
 */
class ColorSwatch
{
    /** Name (lowercased, punctuation stripped) => CSS color. */
    private const MAP = [
        'aluminum'           => '#b9bcc0',
        'augusta green'      => '#1e7a4b',
        'black'              => '#1a1a1a',
        'blue'               => '#1f5fd0',
        'brown'              => '#6b4423',
        'burgundy'           => '#7b1f3a',
        'burnt orange'       => '#c2521b',
        'cardinal red'       => '#b3122b',
        'cover up green'     => '#4c8c3f',
        'florescent green'   => '#7cfc3a',
        'fluorescent green'  => '#7cfc3a',
        'fluorescent orange' => '#ff6a13',
        'fluorescent pink'   => '#ff4fa3',
        'forest green'       => '#1b5e34',
        'gray'               => '#8a8d91',
        'grey'               => '#8a8d91',
        'green'              => '#1e8e3e',
        'handicap blue'      => '#0057b8',
        'maroon'             => '#6e1a2e',
        'nc blue'            => '#4b9cd3',
        'navy blue'          => '#0b2d5b',
        'old gold'           => '#c8a200',
        'orange'             => '#f57c00',
        'pink'               => '#ff69b4',
        'purple'             => '#6a2c91',
        'red'                => '#d32f2f',
        'royal blue'         => '#1a4fbf',
        'royal purple'       => '#5b2d8e',
        'syracuse orange'    => '#f76900',
        'tan'                => '#d2b48c',
        'teal'               => '#1a8a8a',
        'turf green'         => '#2e7d32',
        'vanderbilt gold'    => '#c9a227',
        'vandy gold'         => '#c9a227',
        'vegas gold'         => '#c5b358',
        'white'              => '#ffffff',
        'yellow'             => '#ffd400',
    ];

    /**
     * Spellings in the catalogue that mean a color already in the map.
     *
     * Left as aliases rather than corrected in the data: the product names are what
     * QuickBooks and the labels say, and quietly rewriting them here would make the
     * website disagree with the invoice.
     */
    private const ALIASES = [
        'pu'       => 'purple',
        'puurple'  => 'purple',
        'flg'      => 'fluorescent green',
        'n c blue' => 'nc blue',
        'white s'  => 'white',
    ];

    /** Colors that are not one color. */
    private const MULTI = ['mix case', 'mixed case', 'custom color'];

    /** Normalise a label for lookup: case, punctuation and spacing all vary. */
    private static function key(string $name): string
    {
        $k = strtolower(trim($name));
        $k = preg_replace('/\s*\(.*\)$/', '', $k) ?? $k;   // drop '(Low Pressure)' etc
        $k = preg_replace('/[^a-z0-9]+/', ' ', $k) ?? $k;
        $k = trim(preg_replace('/\s+/', ' ', $k) ?? $k);

        return self::ALIASES[$k] ?? $k;
    }

    /** The CSS background for a swatch, or null when we do not know the color. */
    public static function css(string $name): ?string
    {
        $k = self::key($name);

        if (in_array($k, self::MULTI, true)) {
            // Several colors in one box: shown as a wheel rather than a single dot.
            return 'conic-gradient(#d32f2f,#f57c00,#ffd400,#1e8e3e,#1f5fd0,#6a2c91,#d32f2f)';
        }

        if ($k === 'clear') {
            // Clear coat is not white; a white dot would read as the white product.
            return 'repeating-linear-gradient(45deg,#fff,#fff 3px,#e5e7eb 3px,#e5e7eb 6px)';
        }

        return self::MAP[$k] ?? null;
    }

    /** True when the swatch needs a visible outline to be seen on a white card. */
    public static function needsOutline(string $name): bool
    {
        $k = self::key($name);

        return $k === 'white' || $k === 'clear' || str_starts_with($k, 'white');
    }
}
