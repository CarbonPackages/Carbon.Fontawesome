<?php

namespace Carbon\Fontawesome\FusionObjects;

use Carbon\Eel\Service\StringConversionService;
use Carbon\Eel\Service\MergeClassesService;
use Carbon\Fontawesome\Service\ParseSettingsService;
use Carbon\Eel\Service\StylesService;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

/**
 * @phpstan-type IconSettings array<string,mixed>
 * @phpstan-type IconEntry array{group:string,icon:string,settings:IconSettings}
 * @phpstan-type Wrapper array{class:?string,style:?string}
 * @phpstan-type Attributes array{item:IconSettings,wrapper:Wrapper|null}
 * @phpstan-type MultiAttributes array{multipleIcons:true,items:list<Attributes>}
 */
class AttributeImplementation extends AbstractArrayFusionObject
{
    #[Flow\Inject]
    protected ParseSettingsService $parseSettingsService;

    /**
     * Global configuration (iconConfig)
     * @var array<string,mixed>
     */
    #[Flow\InjectConfiguration('iconConfig')]
    protected array $config;

    #[Flow\InjectConfiguration('version', 'Carbon.Fontawesome')]
    protected string $version;

    /** @var bool */
    protected bool $isIcon = false;

    /**
     * @var list<IconEntry>
     */
    protected array $icons = [];

    /**
     * @var IconSettings
     */
    protected array $tagSettings = [];

    /**
     * @var array<string,mixed> Composed raw attributes from Fusion (class as string[] / style as array)
     */
    protected array $attributesFromFusion = [];

    /**
     * Determine final attributes.
     *
     * @return Attributes|MultiAttributes|null Null when no icon; Attributes for single icon or tag; MultiAttributes for multiple icons.
     */
    public function evaluate()
    {
        $this->getFusionValues();

        if (!$this->isIcon) {
            return $this->getAttributes($this->tagSettings);
        }

        if (empty($this->icons)) {
            // If no icons are set, return null
            return null;
        }

        if (count($this->icons) == 1) {
            // @phpstan-ignore arrayValues.list
            $icon = array_values($this->icons)[0];
            return $this->getAttributes($icon['settings'], $icon);
        }

        // If multiple icons are set, we return an array of attributes
        $attributes = [
            'multipleIcons' => true,
            'items' => [],
        ];
        foreach ($this->icons as $icon) {
            $attributes['items'][] = $this->getAttributes(
                $icon['settings'],
                $icon,
            );
        }
        return $attributes;
    }

    /**
     * @return array<string,string>
     */
    private function getReplacements(): array
    {
        $replacements = $this->fusionValue('replacements');
        return is_array($replacements) ? $replacements : [];
    }

    /**
     * Build the base attributes (class/style) from Fusion.
     *
     * @return array{class:list<string|null>,style:array<string,mixed>}
     */
    private function getAttributesFromFusion(): array
    {
        // We destruct the Fusion attributes to be able to unset some of them later
        $attributes = [...$this->fusionValue('attributes') ?? []];
        $baseClass = $this->fusionValue('baseClass') ?? null;
        $class = $attributes['class'] ?? null;
        $styles = StylesService::styleToArray($attributes['style'] ?? []);
        unset(
            $attributes['class'],
            $attributes['style'],
            $attributes['icon'],
            $attributes['settings'],
            $attributes['theme'],
            $attributes['content'],
            $attributes['items'],
            $attributes['tagName'],
            $attributes['layers'],
            $attributes['replacements'],
            $attributes['wrapper'],
            $attributes['iconPath'],
        );
        $attributes['style'] = $styles;
        $attributes['class'] = [$baseClass, $class];
        return $attributes;
    }

    /**
     * Get the attributes for the icon based on the settings and icon.
     *
     * @param IconSettings $settings
     * @param array{group?:string,icon?:string,settings:IconSettings}|null $iconParts
     * @return Attributes
     */
    private function getAttributes(
        array $settings,
        ?array $iconParts = null,
    ): array {
        $classNames = [];
        $styles = [];
        $hasIcon = isset($iconParts['group']) && isset($iconParts['icon']);

        // Special case data-theme
        $theme = $this->getStringSettingsValue('theme', $settings);

        $classNames = $this->getClassNames('global', $classNames, $settings);
        $styles = $this->getStyles('global', $styles, $settings);

        // Check for 'duotone' in the style
        if (
            $hasIcon &&
            (str_contains($iconParts['group'], 'duotone-') ||
                str_contains($iconParts['group'], '-duo-') ||
                str_contains($iconParts['group'], 'thumbprint'))
        ) {
            $classNames = $this->getClassNames(
                'duotone',
                $classNames,
                $settings,
            );
            $styles = $this->getStyles('duotone', $styles, $settings);
        }

        // Animations
        $animation = $this->getStringSettingsValue('animation', $settings);

        // Get transform settings
        $transformClassNames = $this->getClassNames('transform', [], $settings);
        $transformStyles = $this->getStyles(
            'transform',
            [],
            $settings,
            'transform',
        );
        $needWrapper =
            isset($animation) &&
            !(empty($transformClassNames) && empty($transformStyles));

        if ($needWrapper) {
            $wrapper = [
                'class' => MergeClassesService::merge($transformClassNames),
                'style' => StylesService::styles($transformStyles),
            ];
        } else {
            // If no wrapper is needed, we can directly add the transform classes and styles
            $classNames = array_merge($classNames, $transformClassNames);
            $styles = array_merge($styles, $transformStyles);
        }

        if (isset($animation)) {
            $animationSettingString = StringConversionService::toCamelCase(
                'animation-' . $animation,
            );
            $classNames = $this->getClassNames(
                $animationSettingString,
                $classNames,
                $settings,
            );
            $styles = $this->getStyles(
                $animationSettingString,
                $styles,
                $settings,
            );

            if (
                str_ends_with($animation, '-reverse') &&
                str_contains($animation, 'spin')
            ) {
                // If the animation ends with '-reverse', we add the reverse class
                $animation = substr($animation, 0, -8);
                $classNames[] = 'fa-icon-spin-reverse';
            }

            $classNames[] = 'fa-icon-' . $animation;
        }

        // Build up attributes
        $xData = null;
        if ($hasIcon) {
            if (empty($iconParts['path'])) {
                $xData = sprintf(
                    'icon(\'%s\',\'%s\',\'%s\')',
                    $iconParts['group'],
                    $iconParts['icon'],
                    $this->version,
                );
            } else {
                if (str_starts_with($iconParts['path'], 'resource://')) {
                    $iconParts['path'] = substr($iconParts['path'], 11);
                    if (str_ends_with($iconParts['path'], '/Public')) {
                        $iconParts['path'] = substr($iconParts['path'], 0, -7);
                    }
                    $iconParts['path'] = sprintf(
                        '/_Resources/Static/Packages/%s',
                        $iconParts['path'],
                    );
                }

                $xData = sprintf(
                    'icon(\'%s\',\'%s\',\'%s\',\'%s\')',
                    $iconParts['group'],
                    $iconParts['icon'],
                    $this->version,
                    $iconParts['path'],
                );
            }
        }

        $attributes = [
            'x-data' => $xData,
            'class' => MergeClassesService::merge(
                $this->attributesFromFusion['class'],
                $classNames,
            ),
            'style' => StylesService::styles(
                array_merge($this->attributesFromFusion['style'], $styles),
            ),
        ];
        if ($theme) {
            $attributes['data-theme'] = $theme;
        }

        return [
            'item' => $this->getAttributesFromSettings(
                $settings,
                array_merge($this->attributesFromFusion, $attributes),
            ),
            'wrapper' => $needWrapper ? $wrapper : null,
        ];
    }

    /**
     * Get the attributes from the settings based on the global configuration.
     *
     * @param array<string,mixed> $settings The settings to use for the attributes.
     * @param array<string,mixed> $attributes The initial attributes to merge with.
     * @return array<string,mixed> The merged attributes.
     */
    /**
     * @param IconSettings $settings
     * @param IconSettings $attributes
     * @return IconSettings
     */
    private function getAttributesFromSettings(
        array $settings,
        array $attributes,
    ): array {
        foreach ($this->config['global'] as $key => $config) {
            $attribute = $config['attribute'] ?? null;
            if (empty($attribute)) {
                // If no attribute is set, we skip this configuration
                continue;
            }
            $value = $this->getSettingsValue($key, $config, $settings);
            if (!$value) {
                continue;
            }

            $removeAttributes = $config['removeAttributes'] ?? [];
            $addAttributes = $config['addAttributes'] ?? [];

            foreach ($removeAttributes as $key) {
                if (isset($attributes[$key])) {
                    unset($attributes[$key]);
                }
                if (isset($attributes[$config['attribute']])) {
                    unset($attributes[$config['attribute']]);
                }
            }
            foreach ($addAttributes as $key => $attrValue) {
                $attributes[$key] = $attrValue;
            }
            $attributes[$config['attribute']] = $value;
        }

        // Cleanup attributes: remove null values
        $attributes = array_filter(
            $attributes,
            fn($value) => $value !== null && $value !== '',
        );

        return $attributes;
    }

    /**
     * Get the class names based on the configuration and settings.
     *
     * @param string $key The configuration key to look up.
     * @param string[] $classNames The initial class names to merge with.
     * @param mixed[] $settings The settings to use for the class names.
     * @return string[] The merged class names.
     */
    /**
     * @param string $key
     * @param list<string> $classNames
     * @param IconSettings $settings
     * @return list<string>
     */
    private function getClassNames(
        string $key,
        array $classNames,
        array $settings,
    ): array {
        if (empty($this->config[$key])) {
            return $classNames;
        }

        foreach ($this->config[$key] as $key => $config) {
            $className = $config['className'] ?? null;
            if (empty($className)) {
                // If no className is set, we skip this configuration
                continue;
            }

            $value = $this->getSettingsValue($key, $config, $settings);

            if ($value === null || $value === false || $value === '') {
                // If the value is null, false or an empty string, skip it
                continue;
            }

            if (is_string($className)) {
                $classNames[] = $className;
                continue;
            }

            $classNames[] = sprintf(
                '%s%s%s',
                $className['prepend'] ?? '',
                (string) $value,
                $className['append'] ?? '',
            );
            continue;
        }

        return $classNames;
    }

    /**
     * Get the styles based on the configuration and settings.
     *
     * @param string $key The configuration key to look up.
     * @param mixed[] $styles The initial styles to merge with.
     * @param mixed[] $settings The settings to use for the styles.
     * @param string|null $property Optional property to set the style value.
     * @return mixed[] The merged styles.
     */
    /**
     * @param string $key
     * @param array<string,mixed> $styles
     * @param IconSettings $settings
     * @return array<string,mixed>
     */
    private function getStyles(
        string $key,
        array $styles,
        array $settings,
        ?string $property = null,
    ): array {
        if (empty($this->config[$key])) {
            return $styles;
        }

        foreach ($this->config[$key] as $key => $config) {
            if (isset($config['className']) || isset($config['attribute'])) {
                // If a className or attribute is set, we skip this configuration for styles
                continue;
            }

            $value = $this->getSettingsValue($key, $config, $settings);

            if ($value === null || $value === false || $value === '') {
                // If the value is null, false or an empty string, skip it
                continue;
            }

            $defaultValue = $config['defaultValue'] ?? null;
            if ($value == $defaultValue) {
                // If the value is the default, skip it
                continue;
            }

            // Calucalte the value based on the configuration
            if (!empty($config['scaleFraction'])) {
                $value = 1 + $value / $config['scaleFraction'];
            }
            if (!empty($config['translateFraction'])) {
                $value = ($value / $config['translateFraction']) * 100 . '%';
            }

            if (isset($config['prepend'])) {
                $value = $config['prepend'] . $value;
            }
            if (isset($config['append'])) {
                $value .= $config['append'];
            }

            if (isset($property)) {
                if (empty($styles[$property])) {
                    $styles[$property] = $value;
                    continue;
                }
                // If the property is already set, append the value with a space
                $styles[$property] .= ' ' . $value;
                continue;
            }

            $styles[$config['property'] ?? $key] = $value;
        }

        return $styles;
    }

    /**
     * Get a value from the settings based on its type.
     *
     * @param string $key The key to look up in the settings.
     * @param array<string,mixed> $config Additional configuration for the value.
     * @param array<string,mixed> $settings The settings to use for the value.
     * @return mixed The value from the settings, or null if not found or invalid.
     */
    /**
     * @param string $key
     * @param array<string,mixed> $config
     * @param IconSettings $settings
     * @return mixed
     */
    private function getSettingsValue(
        string $key,
        array $config = [],
        array $settings = [],
    ): mixed {
        if (!isset($settings[$key])) {
            return null;
        }

        $type = $config['type'] ?? 'string';
        $allowZero = false;
        if (isset($config['min']) && $config['min'] === 0) {
            // If the minimum is set to 0, we allow zero as a valid value
            $allowZero = true;
        }

        switch ($type) {
            case 'numeric':
            case 'integer':
            case 'float':
                $value = $settings[$key] ?? null;
                if ($value === null || ($value == 0 && !$allowZero)) {
                    $value = null;
                }
                break;
            case 'boolean':
                $value = !!($settings[$key] ?? false);
                break;
            default:
                $value = $this->getStringSettingsValue($key, $settings);
                break;
        }

        $valueMap = $config['valueMap'] ?? null;
        if (isset($valueMap)) {
            return $valueMap[$value] ?? null;
        }
        return $value;
    }

    /**
     * Get a string value from the settings
     *
     * @param string $key
     * @param IconSettings $settings
     * @return string|null
     */
    private function getStringSettingsValue(
        string $key,
        array $settings = [],
    ): ?string {
        $value = $settings[$key] ?? null;
        if (empty($value) || !is_string($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Check if the icon exists in the specified group.
     *
     * @param string $group The icon group (e.g., 'solid', 'regular', 'brands').
     * @param string $icon The icon name (e.g., 'check', 'times').
     * @param ?string $iconPath The path to the icons, if null the default path will be used.
     * @return bool True if the icon exists, false otherwise.
     */
    private function checkIfIconExists(
        string $group,
        string $icon,
        ?string $iconPath = null,
    ): bool {
        if (empty($iconPath)) {
            $iconPath = 'resource://Carbon.Fontawesome.Icons/Public';
        }
        $path = sprintf('%s/%s/%s.svg', $iconPath, $group, $icon);
        return file_exists($path);
    }

    /**
     * Set the icon, style and settings based on the fusion values.
     */
    private function getFusionValues(): void
    {
        $this->attributesFromFusion = $this->getAttributesFromFusion();

        $this->isIcon = $this->fusionValue('isIcon') ?? false;
        $settings =
            $this->parseSettingsService->parse(
                $this->fusionValue('settings'),
            ) ?? [];

        if (!$this->isIcon) {
            $this->tagSettings = $settings;
            return;
        }

        $value = $this->fusionValue('icon');
        $replacements = $this->getReplacements();

        if (!$value || !(is_string($value) || is_array($value))) {
            return;
        }

        if (is_array($value)) {
            $singleIconArray = true;
            foreach ($value as $iconPart) {
                if (
                    is_array($iconPart) ||
                    (is_string($iconPart) && str_contains($iconPart, ':'))
                ) {
                    // If any part is a string with a colon, we treat it as a multiple icon
                    $singleIconArray = false;
                    break;
                }
            }
        }

        if (is_string($value)) {
            // Split the string by || to support multiple icons
            $value = explode('||', $value);
        } elseif ($singleIconArray) {
            $value = [$value];
        }

        // If the value is not an array, we will treat it as a single icon
        $icons = [];
        foreach ($value as $iconParts) {
            // If the single icon is a string, we split it by ':'
            if (is_string($iconParts)) {
                $iconParts = explode(':', $iconParts, 3);
                $iconParts = $this->parseSettingsService->trim($iconParts);

                // handle shorthand replacements
                if (isset($replacements[$iconParts[0]])) {
                    $replacementValue = $replacements[$iconParts[0]];
                    if (isset($iconParts[1])) {
                        $seperator =
                            substr_count($replacementValue, ':') == 1
                                ? ':'
                                : ',';
                        $replacementValue .= $seperator . $iconParts[1]; // Append the rest of the icon definition
                    }
                    if (isset($iconParts[2])) {
                        $replacementValue .= ':' . $iconParts[2];
                    }
                    $iconParts = explode(':', $replacementValue, 3);
                }
            }
            // Trim the icon values
            $iconParts = $this->parseSettingsService->trim($iconParts);
            $count = count($iconParts);
            // Nothing to do if the value is empty
            if ($count === 0) {
                continue;
            }

            if ($count === 1) {
                $iconName = $iconParts['icon'] ?? ($iconParts[0] ?? null);
                // If no group is set, we will take solid as default
                if ($this->checkIfIconExists('solid', $iconName)) {
                    $icons[] = [
                        'group' => 'solid',
                        'icon' => $iconName,
                        'settings' => $settings,
                    ];
                }
                continue;
            }

            $group = $iconParts['group'] ?? $iconParts[0];
            $icon = $iconParts['icon'] ?? $iconParts[1];

            // If the group is set to 'duotone', we will use 'duotone-solid' as the group
            if ($group === 'duotone') {
                $group = 'duotone-solid';
            }

            $iconPath = $this->fusionValue('iconPath');
            $iconPath = is_string($iconPath)
                ? trim(rtrim($iconPath, '/'))
                : null;
            if ($this->checkIfIconExists($group, $icon, $iconPath)) {
                $icons[] = [
                    'group' => $group,
                    'icon' => $icon,
                    'path' => $iconPath ?: null,
                    'settings' =>
                        $this->parseSettingsService->parse(
                            $iconParts['settings'] ?? ($iconParts[2] ?? null),
                        ) ?? $settings,
                ];
            }
        }

        $this->icons = $icons;
    }
}
