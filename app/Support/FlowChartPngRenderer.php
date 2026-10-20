<?php

namespace App\Support;

use App\Models\FlowChart;
use Illuminate\Support\Collection;
use RuntimeException;

class FlowChartPngRenderer
{
    private const NODE_WIDTH = 190;

    private const NODE_MIN_HEIGHT = 82;

    private const GAP_X = 250;

    private const GAP_Y = 340;

    private const PADDING = 40;

    private const PIXEL_RATIO = 3;

    private const PAGE_WIDTH_MM = 170;

    private const PAGE_HEIGHT_MM = 185;

    public function render(FlowChart $flowChart): array
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('imagettftext')) {
            throw new RuntimeException('The GD extension with FreeType support is required to render flow charts in project PDFs.');
        }

        $font = base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans.ttf');
        $boldFont = base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSans-Bold.ttf');
        if (! is_file($font) || ! is_file($boldFont)) {
            throw new RuntimeException('The mPDF DejaVu fonts are required to render flow charts in project PDFs.');
        }

        $steps = collect($flowChart->steps ?? [])->values();
        if ($steps->isEmpty()) {
            return [
                'src' => '',
                'width' => 0,
                'height' => 0,
            ];
        }

        $labels = $steps->mapWithKeys(fn (array $step, int $index): array => [
            $step['id'] ?? (string) $index => (string) ($step['label'] ?? 'Step'),
        ]);
        $positions = $this->positions($steps, $labels);
        $wrappedLabels = $steps->map(fn (array $step, int $index): array => $this->wrapLabel(
            $labels[$step['id'] ?? (string) $index],
            $boldFont,
        ));
        $nodeHeights = $wrappedLabels->map(fn (array $lines): int => max(
            self::NODE_MIN_HEIGHT,
            42 + count($lines) * 18,
        ));

        $minX = min(array_column($positions, 'x'));
        $minY = min(array_column($positions, 'y'));
        $maxX = max(array_map(
            fn (array $position): int => $position['x'] + self::NODE_WIDTH,
            $positions,
        ));
        $maxY = max(array_map(
            fn (array $position): int => $position['y'] + $nodeHeights[$position['index']],
            $positions,
        ));
        $canvasWidth = max(1, $maxX - $minX + self::PADDING * 2);
        $canvasHeight = max(1, $maxY - $minY + self::PADDING * 2);
        $physicalScale = min(
            self::PAGE_WIDTH_MM / $canvasWidth,
            self::PAGE_HEIGHT_MM / $canvasHeight,
        );
        $scale = min(
            self::PIXEL_RATIO,
            1800 / $canvasWidth,
            2400 / $canvasHeight,
        );
        $imageWidth = max(1, (int) ceil($canvasWidth * $scale));
        $imageHeight = max(1, (int) ceil($canvasHeight * $scale));
        $image = imagecreatetruecolor($imageWidth, $imageHeight);

        if ($image === false) {
            throw new RuntimeException('Unable to allocate an image for the project flow chart.');
        }

        $colors = $this->allocateColors($image);
        imagefill($image, 0, 0, $colors['white']);

        $pixelPositions = [];
        foreach ($positions as $id => $position) {
            $pixelPositions[$id] = [
                'x' => (int) round((self::PADDING + $position['x'] - $minX) * $scale),
                'y' => (int) round((self::PADDING + $position['y'] - $minY) * $scale),
                'height' => (int) round($nodeHeights[$position['index']] * $scale),
                'index' => $position['index'],
            ];
        }

        foreach ($steps as $index => $step) {
            $sourceId = $step['id'] ?? (string) $index;
            $source = $pixelPositions[$sourceId];
            foreach ($step['connections'] ?? [] as $targetId) {
                if (! isset($pixelPositions[$targetId])) {
                    continue;
                }

                $target = $pixelPositions[$targetId];
                $sourceX = $source['x'] + (int) round(self::NODE_WIDTH * $scale / 2);
                $sourceY = $source['y'] + $source['height'];
                $targetX = $target['x'] + (int) round(self::NODE_WIDTH * $scale / 2);
                $targetY = $target['y'];
                $this->drawArrow($image, $sourceX, $sourceY, $targetX, $targetY, $colors['wire'], $scale);
            }
        }

        foreach ($steps as $index => $step) {
            $id = $step['id'] ?? (string) $index;
            $position = $pixelPositions[$id];
            $kind = (string) ($step['kind'] ?? 'page');
            $style = $this->nodeStyle($kind);
            $left = $position['x'];
            $top = $position['y'];
            $right = $left + (int) round(self::NODE_WIDTH * $scale);
            $bottom = $top + $position['height'];
            $radius = (int) round((in_array($kind, ['start', 'end'], true) ? 22 : 10) * $scale);

            $this->drawRoundedRectangle($image, $left, $top, $right, $bottom, $radius, $colors[$style['fill']], $colors[$style['border']]);

            $kindText = strtoupper($kind === 'page' && $flowChart->category !== 'website' ? 'step' : $kind);
            $kindFontSize = max(1, (int) round(9 * $scale));
            $labelFontSize = max(1, (int) round(13 * $scale));
            $centerX = (int) round(($left + $right) / 2);
            $kindBaseline = $top + (int) round(27 * $scale);
            $this->drawCenteredText(
                $image,
                $kindText,
                $kindBaseline,
                $centerX,
                $kindFontSize,
                $font,
                $colors['muted'],
            );

            $lines = $wrappedLabels[$index];
            $lineHeight = (int) round(18 * $scale);
            $labelStart = $top + (int) round(47 * $scale);
            foreach ($lines as $lineIndex => $line) {
                $this->drawCenteredText(
                    $image,
                    $line,
                    $labelStart + $lineIndex * $lineHeight,
                    $centerX,
                    $labelFontSize,
                    $boldFont,
                    $colors['text'],
                );
            }
        }

        ob_start();
        $written = imagepng($image);
        imagedestroy($image);
        $png = ob_get_clean();

        if (! $written || ! is_string($png)) {
            throw new RuntimeException('Unable to encode the project flow chart image.');
        }

        return [
            'src' => 'data:image/png;base64,'.base64_encode($png),
            'width' => round($canvasWidth * $physicalScale, 2),
            'height' => round($canvasHeight * $physicalScale, 2),
        ];
    }

    private function positions(Collection $steps, Collection $labels): array
    {
        $incoming = $labels->mapWithKeys(fn (string $label, string $id): array => [$id => 0])->all();
        foreach ($steps as $index => $step) {
            $sourceId = $step['id'] ?? (string) $index;
            foreach ($step['connections'] ?? [] as $targetId) {
                if (array_key_exists($targetId, $incoming)) {
                    $incoming[$targetId]++;
                }
            }
        }

        $levels = [];
        $placeComponent = function (string $start) use (&$levels, $steps): void {
            if (isset($levels[$start])) {
                return;
            }

            $queue = [$start];
            $levels[$start] = 0;
            for ($index = 0; $index < count($queue); $index++) {
                $sourceId = $queue[$index];
                $source = $steps->first(fn (array $step, int $stepIndex): bool => ($step['id'] ?? (string) $stepIndex) === $sourceId);
                if ($source === null) {
                    continue;
                }

                foreach ($source['connections'] ?? [] as $targetId) {
                    if (isset($levels[$targetId]) || ! $steps->contains(
                        fn (array $step, int $stepIndex): bool => ($step['id'] ?? (string) $stepIndex) === $targetId,
                    )) {
                        continue;
                    }

                    $levels[$targetId] = $levels[$sourceId] + 1;
                    $queue[] = $targetId;
                }
            }
        };

        foreach ($steps as $index => $step) {
            $id = $step['id'] ?? (string) $index;
            if ($incoming[$id] === 0) {
                $placeComponent($id);
            }
        }
        foreach ($steps as $index => $step) {
            $placeComponent($step['id'] ?? (string) $index);
        }

        $rows = [];
        foreach ($steps as $index => $step) {
            $id = $step['id'] ?? (string) $index;
            $rows[$levels[$id]][] = $id;
        }

        $widestRow = max(array_map('count', $rows));
        $canvasWidth = max(800, $widestRow * self::GAP_X + self::PADDING * 2);
        $positions = [];
        foreach ($steps as $index => $step) {
            $id = $step['id'] ?? (string) $index;
            $row = $rows[$levels[$id]];
            $rowWidth = count($row) * self::GAP_X;
            $startX = max(self::PADDING, (int) floor(($canvasWidth - $rowWidth) / 2));
            $defaultX = $startX + array_search($id, $row, true) * self::GAP_X;
            $positions[$id] = [
                'x' => isset($step['x']) ? (int) $step['x'] : $defaultX,
                'y' => isset($step['y']) ? (int) $step['y'] : self::PADDING + $levels[$id] * self::GAP_Y,
                'index' => $index,
            ];
        }

        return $positions;
    }

    private function wrapLabel(string $label, string $font): array
    {
        $maxWidth = self::NODE_WIDTH - 28;
        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/u', trim($label)) ?: [] as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            if ($line !== '' && $this->textWidth($candidate, 13, $font) > $maxWidth) {
                $lines[] = $line;
                $line = '';
            }

            while ($this->textWidth($word, 13, $font) > $maxWidth) {
                $characters = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $piece = '';
                while ($characters !== []) {
                    $next = $characters[0];
                    if ($piece !== '' && $this->textWidth($piece.$next, 13, $font) > $maxWidth) {
                        break;
                    }
                    $piece .= array_shift($characters);
                }
                $lines[] = $piece;
                $word = implode('', $characters);
            }

            $line = $line === '' ? $word : $line.' '.$word;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines === [] ? ['Step'] : $lines;
    }

    private function textWidth(string $text, int $fontSize, string $font): float
    {
        $bounds = imagettfbbox($fontSize, 0, $font, $text);

        return abs($bounds[2] - $bounds[0]);
    }

    private function drawCenteredText(
        \GdImage $image,
        string $text,
        int $baseline,
        int $centerX,
        int $fontSize,
        string $font,
        int $color,
    ): void {
        $bounds = imagettfbbox($fontSize, 0, $font, $text);
        $width = abs($bounds[2] - $bounds[0]);
        $left = (int) round($centerX - $width / 2);
        imagettftext($image, $fontSize, 0, $left, $baseline, $color, $font, $text);
    }

    private function drawArrow(\GdImage $image, int $x1, int $y1, int $x2, int $y2, int $color, float $scale): void
    {
        $controlY1 = $y1 + max((int) round(28 * $scale), (int) round(($y2 - $y1) / 2));
        $controlY2 = $y2 - max((int) round(28 * $scale), (int) round(($y2 - $y1) / 2));
        $previousX = $x1;
        $previousY = $y1;

        for ($step = 1; $step <= 24; $step++) {
            $t = $step / 24;
            $inverse = 1 - $t;
            $x = (int) round(($inverse ** 3) * $x1 + 3 * ($inverse ** 2) * $t * $x1 + 3 * $inverse * ($t ** 2) * $x2 + ($t ** 3) * $x2);
            $y = (int) round(($inverse ** 3) * $y1 + 3 * ($inverse ** 2) * $t * $controlY1 + 3 * $inverse * ($t ** 2) * $controlY2 + ($t ** 3) * $y2);
            imageline($image, $previousX, $previousY, $x, $y, $color);
            $previousX = $x;
            $previousY = $y;
        }

        $arrowSize = max(6, (int) round(8 * $scale));
        $baseY = $y2 - $arrowSize;
        imagefilledpolygon($image, [
            $x2, $y2,
            $x2 - $arrowSize, $baseY,
            $x2 + $arrowSize, $baseY,
        ], 3, $color);
    }

    private function drawRoundedRectangle(
        \GdImage $image,
        int $left,
        int $top,
        int $right,
        int $bottom,
        int $radius,
        int $fill,
        int $border,
    ): void {
        imagefilledrectangle($image, $left + $radius, $top, $right - $radius, $bottom, $fill);
        imagefilledrectangle($image, $left, $top + $radius, $right, $bottom - $radius, $fill);
        foreach ([
            [$left + $radius, $top + $radius],
            [$right - $radius, $top + $radius],
            [$left + $radius, $bottom - $radius],
            [$right - $radius, $bottom - $radius],
        ] as [$centerX, $centerY]) {
            imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $fill);
        }

        imageline($image, $left + $radius, $top, $right - $radius, $top, $border);
        imageline($image, $left + $radius, $bottom, $right - $radius, $bottom, $border);
        imageline($image, $left, $top + $radius, $left, $bottom - $radius, $border);
        imageline($image, $right, $top + $radius, $right, $bottom - $radius, $border);
        imagearc($image, $left + $radius, $top + $radius, $radius * 2, $radius * 2, 180, 270, $border);
        imagearc($image, $right - $radius, $top + $radius, $radius * 2, $radius * 2, 270, 360, $border);
        imagearc($image, $right - $radius, $bottom - $radius, $radius * 2, $radius * 2, 0, 90, $border);
        imagearc($image, $left + $radius, $bottom - $radius, $radius * 2, $radius * 2, 90, 180, $border);
    }

    private function nodeStyle(string $kind): array
    {
        return match ($kind) {
            'start' => ['fill' => 'startFill', 'border' => 'startBorder'],
            'end' => ['fill' => 'endFill', 'border' => 'endBorder'],
            'action' => ['fill' => 'actionFill', 'border' => 'actionBorder'],
            'decision' => ['fill' => 'decisionFill', 'border' => 'decisionBorder'],
            default => ['fill' => 'pageFill', 'border' => 'pageBorder'],
        };
    }

    private function allocateColors(\GdImage $image): array
    {
        return [
            'white' => imagecolorallocate($image, 255, 255, 255),
            'text' => imagecolorallocate($image, 30, 41, 59),
            'muted' => imagecolorallocate($image, 100, 116, 139),
            'wire' => imagecolorallocate($image, 100, 116, 139),
            'pageFill' => imagecolorallocate($image, 239, 246, 255),
            'pageBorder' => imagecolorallocate($image, 71, 85, 105),
            'startFill' => imagecolorallocate($image, 220, 252, 231),
            'startBorder' => imagecolorallocate($image, 34, 197, 94),
            'endFill' => imagecolorallocate($image, 254, 226, 226),
            'endBorder' => imagecolorallocate($image, 239, 68, 68),
            'actionFill' => imagecolorallocate($image, 224, 242, 254),
            'actionBorder' => imagecolorallocate($image, 14, 165, 233),
            'decisionFill' => imagecolorallocate($image, 254, 243, 199),
            'decisionBorder' => imagecolorallocate($image, 245, 158, 11),
        ];
    }
}
