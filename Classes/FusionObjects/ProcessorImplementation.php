<?php

namespace Carbon\Fontawesome\FusionObjects;

use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

class ProcessorImplementation extends AbstractArrayFusionObject
{
    private function getContent(): string
    {
        return (string) $this->fusionValue('content');
    }

    private function getRenderIcon(): bool
    {
        return (bool) $this->fusionValue('renderIcon');
    }

    public function evaluate(): string
    {
        $content = $this->getContent();
        if (empty($content)) {
            return '';
        }
        $renderIcon = $this->getRenderIcon();
        return preg_replace_callback(
            '/\[icon(?<size>-[\d]*\.?[\d]*)?:(?<icon>[^]]*)\]/i',
            function ($match) use ($renderIcon) {
                if (!$renderIcon) {
                    return '';
                }
                $size = null;
                if ($match['size']) {
                    $match['size'] = str_replace('-', '', $match['size']);

                    if (is_numeric($match['size'])) {
                        $size = (float) $match['size'];
                    }

                    // Ignore sizes smaller than 0.1
                    if ($size < 0.1) {
                        $size = null;
                    }
                }

                return $this->buildIcon($match['icon'], $size);
            },
            $content,
        );
    }

    protected function buildIcon(
        string $iconDefinition,
        ?float $size = null,
    ): string {
        $this->runtime->pushContextArray([
            'icon' => $iconDefinition,
            'size' => $size,
        ]);
        $icon = $this->runtime->render($this->path . '/iconRenderer') ?: '';
        $this->runtime->popContext();
        return $icon;
    }
}
