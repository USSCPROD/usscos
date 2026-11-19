<?php

declare(strict_types=1);

namespace App\Services\Web;

/**
 * How a stencil order goes into boxes: the stencil shop's fedex.build_packages, ported.
 *
 * Stencils ship rolled. The roll is as wide as the widest stencil's short side; stencils
 * are added to a roll until it no longer fits any box or passes the weight limit, then a
 * new box starts. 10-mil film folds into the smallest box. Endzone and midfield stencils
 * ship in their own fixed boxes. The boxes and constants are the shop's config.json.
 *
 * USSCOS's own CartonizerService packs cased goods from product dimensions; stencils have
 * no case dimensions (a rolled sheet's box is a packing rule, not a measurement), so the
 * web cart uses this for them, and both feed the same FedEx rate request.
 */
class StencilPacking
{
    public const MAX_LBS = 90;
    public const BOX_WEIGHT = 1.5;
    public const CORE = 3;
    public const FILL = 0.85;
    public const END_ROOM = 0;
    public const SIDE_ROOM = 0.5;
    public const THICKNESS_IN = ['1/8' => 0.125, '3/32' => 0.09375];
    public const LBS_PER_SQ_IN = ['1/8' => 0.00415, '3/32' => 0.00312];
    public const FOLDED = ['10mil'];
    public const BOXES = [[18, 12, 10], [24, 12, 12], [28, 12, 12], [28, 18, 12], [36, 12, 12], [40, 12, 12], [48, 10, 10], [48, 12, 12]];

    /**
     * @param array<int,array{weight:float,thickness:string,short_side?:float,fixed?:array}> $pieces
     *        one per unit ordered (a multi-piece stencil is several)
     * @return array<int,array{weight:int,dims:array{0:float,1:float,2:float}}>
     */
    public static function packages(array $pieces): array
    {
        $boxes = self::BOXES;
        usort($boxes, static fn($a, $b) => $a[0] * $a[1] * $a[2] <=> $b[0] * $b[1] * $b[2]);
        $packed = [];

        foreach ($pieces as $p) {
            if (!empty($p['fixed'])) {
                $per = $p['weight'] / $p['fixed']['boxes'] + self::BOX_WEIGHT;
                for ($i = 0; $i < $p['fixed']['boxes']; $i++) {
                    $packed[] = ['weight' => max(1, (int)ceil($per)), 'dims' => $p['fixed']['dims']];
                }
            }
        }

        $rest   = array_values(array_filter($pieces, static fn($p) => empty($p['fixed'])));
        $folded = array_values(array_filter($rest, static fn($p) => in_array($p['thickness'], self::FOLDED, true)));
        $rolled = array_values(array_filter($rest, static fn($p) => !in_array($p['thickness'], self::FOLDED, true)));

        // Widest first, so narrow stencils roll inside wider ones.
        usort($rolled, static fn($a, $b) => [-$a['short_side'], -$a['weight']] <=> [-$b['short_side'], -$b['weight']]);

        $current = [];
        foreach ($rolled as $piece) {
            $trial = [...$current, $piece];

            if ($current !== [] && (self::fit($trial)[0] === null || self::weight($trial) > self::MAX_LBS)) {
                $packed[] = self::close($current);
                $trial = [$piece];
            }

            $current = $trial;
        }

        if ($current !== []) {
            $packed[] = self::close($current);
        }

        // Thin film folds up small: fill the smallest box by weight.
        $small = $boxes[0];
        $load = [];

        foreach ($folded as $piece) {
            if ($load !== [] && self::weight($load) + $piece['weight'] > self::MAX_LBS) {
                $packed[] = ['weight' => max(1, (int)ceil(self::weight($load) + self::BOX_WEIGHT)), 'dims' => $small];
                $load = [];
            }
            $load[] = $piece;
        }

        if ($load !== []) {
            $packed[] = ['weight' => max(1, (int)ceil(self::weight($load) + self::BOX_WEIGHT)), 'dims' => $small];
        }

        return $packed;
    }

    private static function weight(array $group): float
    {
        return array_sum(array_column($group, 'weight'));
    }

    /** Outside diameter of the group rolled together: sqrt(core^2 + 4 * area * t / (pi * width)). */
    private static function diameter(array $group, float $width): float
    {
        $volume = 0.0;
        foreach ($group as $p) {
            $volume += $p['weight'] / self::LBS_PER_SQ_IN[$p['thickness']] * self::THICKNESS_IN[$p['thickness']];
        }

        return sqrt(self::CORE ** 2 + 4 * $volume / (M_PI * $width));
    }

    /** The shortest box the roll fits (closest to the stencil's width), then the smallest. */
    private static function fit(array $group): array
    {
        $width = max(array_column($group, 'short_side'));
        $d = self::diameter($group, $width);
        $end = M_PI * $d * $d / 4;
        $boxes = self::BOXES;
        usort($boxes, static fn($a, $b) => [$a[0], $a[0] * $a[1] * $a[2]] <=> [$b[0], $b[0] * $b[1] * $b[2]]);

        foreach ($boxes as $b) {
            if ($b[0] >= $width + self::END_ROOM && $end <= self::FILL * ($b[1] - self::SIDE_ROOM) * ($b[2] - self::SIDE_ROOM)) {
                return [$b, $width, $d];
            }
        }

        return [null, $width, $d];
    }

    private static function close(array $group): array
    {
        [$box, $width, $d] = self::fit($group);

        if ($box === null) {           // no listed box fits one oversized roll
            $side = (int)ceil($d + self::SIDE_ROOM);
            $box = [(int)ceil($width + self::END_ROOM), $side, $side];
        }

        return ['weight' => max(1, (int)ceil(self::weight($group) + self::BOX_WEIGHT)), 'dims' => $box];
    }
}
