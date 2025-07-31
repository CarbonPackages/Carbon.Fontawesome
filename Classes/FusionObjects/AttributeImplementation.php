<?php

namespace Carbon\Fontawesome\FusionObjects;

use Carbon\Eel\Service\StringConversionService;
use Carbon\Eel\Service\MergeClassesService;
use Carbon\Eel\Service\StylesService;
use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\AbstractArrayFusionObject;

class AttributeImplementation extends AbstractArrayFusionObject
{
    #[Flow\InjectConfiguration('iconConfig')]
    protected $config;

    protected $isIcon = false;
    protected $icons = [];
    protected $tagSettings = [];
    protected $attributesFromFusion = [];

    /**
     * Return attributes for the icon.
     *
     * @return string|null
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

    private function getReplacements(): array
    {
        $replacements = $this->fusionValue('replacements');
        return is_array($replacements) ? $replacements : [];
    }

    /**
     * Get the attributes from Fusion and prepare them for the icon.
     *
     * @return array
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
        );
        $attributes['style'] = $styles;
        $attributes['class'] = [$baseClass, $class];
        return $attributes;
    }

    /**
     * Get the attributes for the icon based on the settings and icon.
     *
     * @param array $settings The settings for the icon.
     * @param array|null $iconParts The icon data, if available.
     * @return array|null The attributes for the icon, or null if not applicable.
     */
    private function getAttributes(
        array $settings,
        ?array $iconParts = null,
    ): ?array {
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
        $xData = $hasIcon
            ? sprintf(
                'icon(\'%s\',\'%s\')',
                $iconParts['group'],
                $iconParts['icon'],
            )
            : null;

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
     * @param array $settings The settings to use for the attributes.
     * @param array $attributes The initial attributes to merge with.
     * @return array The merged attributes.
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
     * @param array $classNames The initial class names to merge with.
     * @param array $settings The settings to use for the class names.
     * @return array The merged class names.
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
     * @param array $styles The initial styles to merge with.
     * @param array $settings The settings to use for the styles.
     * @param string|null $property Optional property to set the style value.
     * @return array The merged styles.
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
     * @param string $type The type of the value ('numeric', 'float, 'integer', 'boolean', 'string').
     * @param string $key The key to look up in the settings.
     * @param array $config Additional configuration for the value.
     * @return mixed The value from the settings, or null if not found or invalid.
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
     * @return bool True if the icon exists, false otherwise.
     */
    private function checkIfIconExists(string $group, string $icon): bool
    {
        $path = sprintf(
            'resource://Carbon.Fontawesome/Public/Icons/%s/%s.svg',
            $group,
            $icon,
        );
        return file_exists($path);
    }

    /**
     * Set the icon, style and settings based on the fusion values.
     */
    private function getFusionValues(): void
    {
        $this->attributesFromFusion = $this->getAttributesFromFusion() ?? [];

        $this->isIcon = $this->fusionValue('isIcon') ?? false;
        $settings = $this->parseSettings($this->fusionValue('settings')) ?? [];

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
                $iconParts = $this->trim($iconParts);

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
            $iconParts = $this->trim($iconParts);
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

            if ($this->checkIfIconExists($group, $icon)) {
                $icons[] = [
                    'group' => $group,
                    'icon' => $icon,
                    'settings' =>
                        $this->parseSettings(
                            $iconParts['settings'] ?? ($iconParts[2] ?? null),
                        ) ?? $settings,
                ];
            }
        }

        $this->icons = $icons;
    }

    /**
     * Converts a string in the format "{key1:value1,key2:value2,…}"
     * into an associative array. Numeric values are converted to int or float.
     *
     * @param string|array|null $input Input string or array, e.g. "{animation:spin,rotate:90}"
     * @return array            Associative array, e.g. ['animation'=>'spin','rotate'=>90]
     */
    private function parseSettings(mixed $input = null): ?array
    {
        if (is_array($input)) {
            if (empty($input)) {
                return null;
            }
            // If input is already an array, just convert keys and values
            return $this->keysToCamelCaseAndValuesToKebabCase($input);
        }

        if ($input === null || $input === '') {
            return null;
        }

        // 1. Remove the opening and closing braces
        $inner = $this->trim($input);
        if (substr($inner, 0, 1) === '{') {
            $inner = substr($inner, 1);
        }
        if (substr($inner, -1) === '}') {
            $inner = substr($inner, 0, -1);
        }

        // 2. If the string is empty after removing braces, return null
        if ($inner === '') {
            return null;
        }

        $result = [];
        // 3. Split by commas to get "key:value" pairs
        $pairs = explode(',', $inner);
        foreach ($pairs as $pair) {
            // 4. Split each pair by the first colon into key and value
            $parts = $this->trim(explode(':', $pair, 2));
            $key = $parts[0] ?: null;
            $value = $parts[1] ?? null;
            if (!$key) {
                // If there's no key, skip this pair
                continue;
            }
            if ($value === null) {
                // If there's no colon, treat the whole part as a key as true
                $result[$key] = true;
                continue;
            }

            // 5. Normalize lowercase for boolean/null check
            $lower = strtolower($value);
            if ($lower === 'true') {
                $result[$key] = true;
                continue;
            }
            if ($lower === 'false') {
                $result[$key] = false;
                continue;
            }
            if ($lower === 'null') {
                $result[$key] = null;
                continue;
            }

            // 6. If not boolean, check for numeric and convert accordingly
            $numeric = $this->normalizeNumericValue($value);
            if ($numeric !== null) {
                $result[$key] = $numeric;
                continue;
            }

            // 7. Otherwise trim the string
            $result[$key] = $this->trim($value);
        }

        return $this->keysToCamelCaseAndValuesToKebabCase($result);
    }

    /**
     * Converts an associative array to a new array where:
     * - Keys are converted to camelCase
     * - Values are converted to kebab-case if they are strings
     *
     * @param array $array The input associative array.
     * @return array The transformed array with camelCase keys and kebab-case values.
     */
    private function keysToCamelCaseAndValuesToKebabCase(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            // Convert key to camelCase
            $camelCaseKey = StringConversionService::toCamelCase($key);
            if (
                $camelCaseKey === 'label' ||
                $camelCaseKey === 'tooltip' ||
                $camelCaseKey === 'alt' ||
                $camelCaseKey === 'title'
            ) {
                $result[$camelCaseKey] = $this->trim($value);
            } elseif (is_string($value)) {
                // Convert value to kebab-case if it's a string
                $result[
                    $camelCaseKey
                ] = StringConversionService::convertCamelCase($value);
            } else {
                $result[$camelCaseKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Trims a value or each element in an array.
     * If the value is a string, it trims whitespace.
     * If it's an array, it trims each element.
     * Otherwise, it returns the value unchanged.
     *
     * @param mixed $value The value to trim.
     * @return mixed The trimmed value or array.
     */
    private function trim(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value, " \n\r\t\v\0'\"");
        }
        if (is_array($value)) {
            return array_map(fn($item) => $this->trim($item), $value);
        }
        return $value;
    }

    /**
     * Normalizes a value to an int or float if it's numeric.
     * Returns null if the value is not numeric.
     *
     * @param mixed $value The value to normalize.
     * @return int|float|null The normalized numeric value, or null if not numeric.
     */
    public function normalizeNumericValue(mixed $value): int|float|null
    {
        if (is_float($value) || is_int($value)) {
            // Already a numeric type
            return $value;
        }

        if (!is_numeric($value)) {
            return null;
        }

        if (ctype_digit($value)) {
            // Pure integer
            return (int) $value;
        }

        // Float (contains decimal point or exponent)
        return (float) $value;
    }
}
