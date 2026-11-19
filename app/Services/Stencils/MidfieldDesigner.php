<?php

declare(strict_types=1);

namespace App\Services\Stencils;

/**
 * Custom midfield logos: the customer's art, placed on a football field and priced.
 *
 * A port of the stencil shop's midfield.py. The pricing is the Stencil Quote Calculator
 * exactly, on the logo's width x height in feet. Logos run 15' to 55' wide, 28' by
 * default; a logo taller than it is wide starts small enough to fit between the hashmarks.
 *
 * THE UPLOAD, and where it differs from the shop. The shop traced every raster logo to a
 * vector with potrace on upload. This server has no tracer, and the trace was for the
 * cutting file, not for the customer: the preview is a PNG either way. So here:
 *
 *   PNG, JPG, WebP, GIF  decoded with GD, scaled, a plain background made transparent,
 *                        cropped to the logo
 *   PDF, Illustrator     page 1 rendered with pdftocairo (already on the server), and
 *                        also converted to SVG, so the vector the customer sent is kept
 *   SVG                  rasterised IN THE BROWSER (there is no SVG renderer here) and
 *                        sent alongside as 'preview'; the SVG itself is kept as the vector
 *
 * The art team prepares the cut file from the original, which is always kept.
 *
 * Files live in storage/uploads/midfield/{id}/, outside the web root: original.{ext},
 * logo.png (the cleaned preview, re-encoded by GD so nothing else rides along),
 * logo.svg when the customer sent a vector, and meta.json.
 */
class MidfieldDesigner
{
    public const DIR        = '/storage/uploads/midfield';
    public const MAX_BYTES  = 25_000_000;
    public const PREVIEW_PX = 1200;
    public const END_ZONE   = 30.0;
    public const MATERIAL   = '10mil';
    public const WIDTH_MIN  = 15.0;
    public const WIDTH_MAX  = 55.0;
    public const WIDTH_DEFAULT = 28.0;

    /** hash: hashmarks' distance in from each sideline; size: length x width, feet. */
    public const FIELDS = [
        'hs'      => ['label' => 'High school',       'hash' => 53 + 1 / 3, 'size' => [360.0, 160.0]],
        'college' => ['label' => 'College',           'hash' => 60.0,       'size' => [360.0, 160.0]],
        'hs8'     => ['label' => 'High school 8-man', 'hash' => 48 + 1 / 3, 'size' => [300.0, 120.0]],
    ];

    public static function options(): array
    {
        $fields = [];

        foreach (self::FIELDS as $k => $f) {
            $fields[$k] = ['label' => $f['label'], 'hashGap' => round(self::hashGap($k), 2), 'hash' => round($f['hash'], 2),
                           'length' => $f['size'][0], 'width' => $f['size'][1], 'endZone' => self::END_ZONE];
        }

        return ['fields' => $fields, 'field' => ['length' => 360.0, 'width' => 160.0],
                'widthMin' => self::WIDTH_MIN, 'widthMax' => self::WIDTH_MAX, 'widthDefault' => self::WIDTH_DEFAULT,
                'maxBytes' => self::MAX_BYTES];
    }

    public static function hashGap(string $field): float
    {
        return self::FIELDS[$field]['size'][1] - 2 * self::FIELDS[$field]['hash'];
    }

    /** 28' wide, unless the logo is taller than wide and would then run past the hashmarks. */
    public static function defaultWidth(float $aspect, string $field): float
    {
        $w = self::WIDTH_DEFAULT;

        if ($aspect > 1 && $w * $aspect > self::hashGap($field)) {
            $w = floor(self::hashGap($field) / $aspect * 2) / 2;
        }

        return max(self::WIDTH_MIN, min(self::WIDTH_MAX, $w));
    }

    // ── Price ────────────────────────────────────────────────────────────────

    /** stencil-quote-full.php, line for line, as midfield.py has it. */
    public static function calculator(float $orgLength, float $orgHeight): array
    {
        $e = EndzoneDesigner::class;
        $height = $orgHeight + $e::BORDER;
        $length = $orgLength + $e::BORDER;

        if ($height > $length) {                  // height is always the smaller dimension
            [$height, $length] = [$length, $height];
        }

        $columns = (int)ceil($height / $e::PANEL_W);
        $rows    = (int)ceil($length / $e::PANEL_L);
        $panels  = $columns * $rows;
        $panelL  = $length / $rows;
        $linear  = $panels * $panelL;
        $boxes   = ($height > 25 || $length > 25) ? 2 : 1;

        $cost = $linear * $e::PLASTIC + ($panels * 2 + 2) * $e::GROMMET + $linear * $e::SEAM_TAPE
              + ($panels * 2 + 6) * $e::TAPE + ($e::BASE_HOURS + $panels * $e::HOURS_PER_PANEL) * $e::MAN_HOUR
              + $boxes * $e::BOX_COST + $e::OVERHEAD;

        return ['height' => round($height, 2), 'length' => round($length, 2),
                'weight' => (int)ceil($height * $length * $e::LBS_PER_SQ_FT),
                'columns' => $columns, 'rows' => $rows, 'panels' => $panels,
                'panelWidth' => round($height / $columns, 2), 'panelLength' => round($panelL, 2),
                'boxes' => $boxes, 'price' => round($cost * $e::RETAIL, 2), 'wholesale' => round($cost * $e::WHOLESALE, 2)];
    }

    /** @throws DesignerError */
    public function quote(string $uploadId, string $field, $width): array
    {
        $m = $this->meta($uploadId);

        if (!isset(self::FIELDS[$field])) {
            throw new DesignerError('Choose a high school, 8-man or college field.');
        }

        if (!is_numeric($width)) {
            throw new DesignerError('Choose a logo size.');
        }

        $w = round((float)$width, 1);

        if ($w < self::WIDTH_MIN || $w > self::WIDTH_MAX) {
            throw new DesignerError(sprintf("Midfield logos run %s to %s wide.", EndzoneDesigner::ft(self::WIDTH_MIN), EndzoneDesigner::ft(self::WIDTH_MAX)));
        }

        $h = round($w * $m['aspect'], 1);
        [$length, $fieldW] = self::FIELDS[$field]['size'];

        if ($h > $fieldW - 10) {
            throw new DesignerError('At this width the logo runs off the field. Choose a smaller size.');
        }

        $c   = self::calculator($w, $h);
        $gap = self::hashGap($field);
        $b   = EndzoneDesigner::BORDER;

        return ['logo' => $uploadId, 'logoUrl' => self::logoUrl($uploadId), 'field' => $field,
                'fieldLabel' => self::FIELDS[$field]['label'], 'width' => $w, 'height' => $h, 'aspect' => $m['aspect'],
                'hash' => round(self::FIELDS[$field]['hash'], 2), 'hashGap' => round($gap, 2), 'insideHashes' => $h <= $gap + 0.05,
                'fieldSize' => ['length' => $length, 'width' => $fieldW, 'endZone' => self::END_ZONE], 'material' => self::MATERIAL,
                'price' => $c['price'], 'wholesale' => $c['wholesale'], 'panels' => $c['panels'], 'columns' => $c['columns'], 'rows' => $c['rows'],
                'panelWidth' => $c['panelWidth'], 'panelLength' => $c['panelLength'],
                'sheet' => ['width' => round($w + $b, 2), 'height' => round($h + $b, 2)], 'border' => $b / 2,
                'weight' => $c['weight'], 'boxes' => (int)ceil($c['panels'] / EndzoneDesigner::PANELS_PER_BOX),
                'boxDims' => EndzoneDesigner::BOX_DIMS, 'name' => $m['name'] ?? ''];
    }

    // ── Upload ───────────────────────────────────────────────────────────────

    /**
     * Save an uploaded logo and make its preview. $file is one $_FILES entry; $preview a
     * browser-rendered PNG for an SVG upload. Returns the upload's details.
     *
     * @throws DesignerError
     */
    public function process(array $file, ?array $preview): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string)$file['tmp_name'])) {
            throw new DesignerError('The file did not upload. Please try again.');
        }

        if ((int)$file['size'] > self::MAX_BYTES) {
            throw new DesignerError('That file is too large. Please keep it under 25 MB.');
        }

        $data = (string)file_get_contents((string)$file['tmp_name']);

        if ($data === '') {
            throw new DesignerError('That file is empty.');
        }

        $kind = self::sniff($data, (string)$file['name']);
        $id   = bin2hex(random_bytes(16));
        $dir  = BASE_PATH . self::DIR . '/' . $id;

        if (!mkdir($dir, 0775, true)) {
            throw new DesignerError("We couldn't save that file just now. Please try again.");
        }

        try {
            file_put_contents("{$dir}/original.{$kind}", $data);

            if ($kind === 'pdf') {
                $this->run(['pdftocairo', '-png', '-transp', '-singlefile', '-f', '1', '-l', '1',
                            '-scale-to', (string)self::PREVIEW_PX, "{$dir}/original.pdf", "{$dir}/page"]);
                $this->run(['pdftocairo', '-svg', '-f', '1', '-l', '1', "{$dir}/original.pdf", "{$dir}/logo.svg"]);
                $img = @imagecreatefrompng("{$dir}/page.png");
                @unlink("{$dir}/page.png");
                $source = 'vector';
            } elseif ($kind === 'svg') {
                // Drawn by the customer's browser; the server only accepts a real PNG back.
                if ($preview === null || ($preview['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                    || (int)$preview['size'] > self::MAX_BYTES
                    || substr((string)file_get_contents((string)$preview['tmp_name'], false, null, 0, 8), 0, 8) !== "\x89PNG\r\n\x1a\n") {
                    throw new DesignerError("We couldn't read that SVG. Please try a PDF or PNG of your logo.");
                }

                $img = @imagecreatefrompng((string)$preview['tmp_name']);
                copy("{$dir}/original.svg", "{$dir}/logo.svg");
                $source = 'vector';
            } else {
                $img = @imagecreatefromstring($data);
                $img = $img !== false && $kind === 'jpg' ? self::orient($img, "{$dir}/original.jpg") : $img;
                $source = 'raster';
            }

            if (!$img instanceof \GdImage) {
                throw new DesignerError("We couldn't open that image. Please try another file.");
            }

            $img = self::fit($img, self::PREVIEW_PX);
            [$img, $background] = self::removeBackground($img);
            [$img, $box] = self::crop($img);

            if ($kind === 'pdf' && $box !== null) {
                self::svgCrop("{$dir}/logo.svg", $box);
            }

            imagesavealpha($img, true);
            imagepng($img, "{$dir}/logo.png", 6);

            $w = imagesx($img);
            $h = imagesy($img);
        } catch (\Throwable $e) {
            self::rmdir($dir);

            if ($e instanceof DesignerError) {
                throw $e;
            }

            error_log('midfield upload failed: ' . $e->getMessage());
            throw new DesignerError("We couldn't process that file. Please try a PNG, JPG or PDF of your logo.");
        }

        $meta = ['aspect' => round($h / $w, 4), 'kind' => $kind, 'source' => $source, 'background' => $background,
                 // A picture with a busy background (a photo) rather than a logo file.
                 'photo' => $background === 'kept' && $source === 'raster',
                 'name' => mb_substr((string)preg_replace('/[^\w .()-]/u', '', (string)$file['name']), 0, 80),
                 'created' => time()];

        file_put_contents("{$dir}/meta.json", json_encode($meta));

        $defaults = [];

        foreach (array_keys(self::FIELDS) as $k) {
            $defaults[$k] = self::defaultWidth($meta['aspect'], $k);
        }

        return $meta + ['id' => $id, 'logo' => self::logoUrl($id), 'defaults' => $defaults];
    }

    public static function logoUrl(string $id): string
    {
        // A query, not /…/logo.png: nginx answers any .png path from disk itself.
        return '/custom/midfield/logo?id=' . $id;
    }

    /** A saved file's path, or null. $name: logo.png, logo.svg, meta.json or 'original'. */
    public static function path(string $id, string $name): ?string
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $id)) {
            return null;
        }

        $dir = BASE_PATH . self::DIR . '/' . $id;

        if ($name === 'original') {
            $found = glob($dir . '/original.*') ?: [];
            return $found[0] ?? null;
        }

        return is_file("{$dir}/{$name}") ? "{$dir}/{$name}" : null;
    }

    /** @throws DesignerError */
    public function meta(string $id): array
    {
        $f = self::path($id, 'meta.json');

        if ($f === null) {
            throw new DesignerError('Please upload your logo again.');
        }

        return json_decode((string)file_get_contents($f), true) ?: [];
    }

    // ── Image work ───────────────────────────────────────────────────────────

    /** @throws DesignerError */
    private static function sniff(string $data, string $filename): string
    {
        $head = ltrim(substr($data, 0, 2048));

        if (str_starts_with($data, "\x89PNG\r\n\x1a\n")) return 'png';
        if (str_starts_with($data, "\xff\xd8\xff"))      return 'jpg';
        if (str_starts_with($data, 'RIFF') && substr($data, 8, 4) === 'WEBP') return 'webp';
        if (str_starts_with($data, 'GIF87a') || str_starts_with($data, 'GIF89a')) return 'gif';
        // Illustrator .ai files saved PDF-compatible are PDFs.
        if (str_starts_with($data, '%PDF-') || str_contains(substr($data, 0, 1024), '%PDF-')) return 'pdf';

        if (preg_match('/<svg[\s>]/i', $head) && (str_starts_with($head, '<') || str_starts_with($head, "\xef\xbb\xbf<"))) {
            return 'svg';
        }

        if (preg_match('/\.(ai|eps)$/i', $filename) || str_starts_with($data, '%!PS')) {
            throw new DesignerError("This Illustrator file isn't PDF-compatible. In Illustrator, choose Save As and tick "
                . "\"Create PDF Compatible File\", or export a PDF, SVG or PNG.");
        }

        throw new DesignerError('Please upload your logo as a JPG, PNG, SVG, PDF or Illustrator (.ai) file.');
    }

    /** @throws DesignerError */
    private function run(array $cmd): void
    {
        $line = implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>&1';
        exec('timeout 90 ' . $line, $out, $code);

        if ($code !== 0) {
            error_log('midfield: ' . $cmd[0] . ' failed: ' . substr(implode("\n", $out), -400));
            throw new DesignerError("We couldn't read that file. Please try a PNG, JPG or PDF of your logo.");
        }
    }

    /** A JPEG's EXIF rotation applied, so a phone photo is the right way up. */
    private static function orient(\GdImage $img, string $file): \GdImage
    {
        $exif = function_exists('exif_read_data') ? @exif_read_data($file) : false;
        $o = (int)($exif['Orientation'] ?? 1);

        return match ($o) {
            3 => imagerotate($img, 180, 0) ?: $img,
            6 => imagerotate($img, -90, 0) ?: $img,
            8 => imagerotate($img, 90, 0) ?: $img,
            default => $img,
        };
    }

    /** True colour with alpha, longest side at most $max. */
    private static function fit(\GdImage $img, int $max): \GdImage
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $k = min(1.0, $max / max($w, $h));
        $nw = max(1, (int)round($w * $k));
        $nh = max(1, (int)round($h * $k));

        $out = imagecreatetruecolor($nw, $nh);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $out;
    }

    /**
     * A plain background (white, or any solid colour around the logo) made transparent:
     * the shop's _remove_background. Only background-coloured pixels CONNECTED TO THE EDGE
     * go, so white inside the logo stays.
     *
     * @return array{0:\GdImage,1:string}  the image, and 'transparent' | 'removed' | 'kept'
     */
    private static function removeBackground(\GdImage $img): array
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $step = max(1, intdiv($w + $h, 400));

        // Already transparent somewhere? GD alpha runs 0 (opaque) … 127 (clear); the shop's
        // test was alpha < 250 of 255.
        $minA = 0;
        for ($y = 0; $y < $h && $minA === 0; $y += $step) {
            for ($x = 0; $x < $w; $x += $step) {
                if ((imagecolorat($img, $x, $y) >> 24) > 2) {
                    $minA = 1;
                    break;
                }
            }
        }

        if ($minA) {
            return [$img, 'transparent'];
        }

        $edge = [];
        for ($x = 0; $x < $w; $x += $step) { $edge[] = [$x, 0]; $edge[] = [$x, $h - 1]; }
        for ($y = 0; $y < $h; $y += $step) { $edge[] = [0, $y]; $edge[] = [$w - 1, $y]; }

        $rgb = static fn(int $c) => [($c >> 16) & 255, ($c >> 8) & 255, $c & 255];
        $cols = array_map(static fn($p) => $rgb(imagecolorat($img, $p[0], $p[1])), $edge);
        $bg = [];

        for ($i = 0; $i < 3; $i++) {
            $ch = array_column($cols, $i);
            sort($ch);
            $bg[$i] = $ch[intdiv(count($ch), 2)];
        }

        $close = static fn(array $c) => max(abs($c[0] - $bg[0]), abs($c[1] - $bg[1]), abs($c[2] - $bg[2])) <= 40;
        $near = count(array_filter($cols, $close)) / max(1, count($cols));

        if ($near < 0.8) {
            return [$img, 'kept'];               // a photo, or a logo running to the edges
        }

        // Flood from the edge through background-coloured pixels. $seen is one byte per pixel.
        $seen  = str_repeat("\0", $w * $h);
        $stack = [];

        foreach ($edge as [$x, $y]) {
            $stack[] = $y * $w + $x;
        }

        imagealphablending($img, false);
        $clear = imagecolorallocatealpha($img, 255, 255, 255, 127);

        while ($stack !== []) {
            $i = array_pop($stack);

            if ($seen[$i] !== "\0") {
                continue;
            }

            $seen[$i] = "\1";
            $x = $i % $w;
            $y = intdiv($i, $w);

            if (!$close($rgb(imagecolorat($img, $x, $y)))) {
                continue;
            }

            imagesetpixel($img, $x, $y, $clear);

            if ($x > 0)      $stack[] = $i - 1;
            if ($x < $w - 1) $stack[] = $i + 1;
            if ($y > 0)      $stack[] = $i - $w;
            if ($y < $h - 1) $stack[] = $i + $w;
        }

        return [$img, 'removed'];
    }

    /**
     * Cropped to what is visible.
     *
     * @return array{0:\GdImage,1:?array}  the image, and the box as fractions of the original
     * @throws DesignerError
     */
    private static function crop(\GdImage $img): array
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $x0 = $w; $y0 = $h; $x1 = -1; $y1 = -1;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                // Visible: alpha above 16 of 255, i.e. GD alpha below ~119 of 127.
                if ((imagecolorat($img, $x, $y) >> 24) < 119) {
                    if ($x < $x0) $x0 = $x;
                    if ($x > $x1) $x1 = $x;
                    if ($y < $y0) $y0 = $y;
                    if ($y > $y1) $y1 = $y;
                }
            }
        }

        if ($x1 < 0) {
            throw new DesignerError("We couldn't find a logo in that file. Please check it and try again.");
        }

        $cw = $x1 - $x0 + 1;
        $ch = $y1 - $y0 + 1;
        $out = imagecreatetruecolor($cw, $ch);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopy($out, $img, 0, 0, $x0, $y0, $cw, $ch);

        return [$out, [$x0 / $w, $y0 / $h, $cw / $w, $ch / $h]];
    }

    /** Narrow a converted page's SVG to the logo: the shop's _svg_crop, with the box as fractions. */
    private static function svgCrop(string $file, array $box): void
    {
        $s = (string)@file_get_contents($file);

        if (!preg_match('/viewBox="([-\d.]+) ([-\d.]+) ([\d.]+) ([\d.]+)"/', $s, $m)) {
            return;
        }

        [, $vx, $vy, $vw, $vh] = array_map('floatval', $m);
        $x = $vx + $box[0] * $vw;
        $y = $vy + $box[1] * $vh;
        $w = $box[2] * $vw;
        $h = $box[3] * $vh;

        $s = preg_replace('/viewBox="[^"]*"/', sprintf('viewBox="%.2f %.2f %.2f %.2f"', $x, $y, $w, $h), $s, 1);
        $s = preg_replace('/(<svg[^>]*?)\swidth="[^"]*"/', sprintf('$1 width="%.2fpt"', $w), $s, 1);
        $s = preg_replace('/(<svg[^>]*?)\sheight="[^"]*"/', sprintf('$1 height="%.2fpt"', $h), $s, 1);
        file_put_contents($file, $s);
    }

    private static function rmdir(string $dir): void
    {
        foreach (glob($dir . '/*') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($dir);
    }
}
