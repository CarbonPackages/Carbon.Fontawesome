<?php

namespace Carbon\Fontawesome\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Utility\Files;
use SQLite3;
use function sprintf;

/**
 * @phpstan-type SidebarData array{icons: string[], packs: string[], styles: string[], categories: string[]}
 * @phpstan-type PacksOrStyles string[]
 * @phpstan-type FixedStyleUnderscored string
 * @phpstan-type FixedStylesUnderscored array<string|int,FixedStyleUnderscored>
 * @phpstan-type Styles array{constraints: array{packs: string[], styles: string[]}, styles: string[]}
 * @phpstan-type Item array{name: string, value: mixed}
 * @phpstan-type Icon array{label: string, name: string, categories: string}
 * @phpstan-type IconValue array{label: string, name: string, value: string, categories: string[]}
 */
#[Flow\Scope('singleton')]
class IconService
{
    protected ?SQLite3 $db = null;

    /**
     * @var mixed[]
     */
    protected $styleSelector = [];

    /**
     * @var mixed[]
     */
    protected $allStyles = [];

    /**
     * @var mixed[]
     */
    protected $allPacks = [];

    /**
     * Initializes the SQLite database connection and loads the style selector, styles, and packs.
     *
     * @return void
     */
    public function initializeObject()
    {
        if ($this->db) {
            return;
        }
        $this->db = new SQLite3(
            Files::concatenatePaths([
                FLOW_PATH_ROOT,
                'Packages/Carbon/Carbon.Fontawesome.Icons/Resources/Private/database.sqlite',
            ]),
            SQLITE3_OPEN_READONLY,
        );

        $styleSelector = [];
        foreach ($this->query('SELECT * FROM "styleSelector"') as $value) {
            $styleSelector[$value['name']] = $value;
        }
        $this->styleSelector = $styleSelector;
        $this->allStyles = $this->query('SELECT * FROM "styles"');
        $this->allPacks = $this->query('SELECT * FROM "packs"');
    }

    /**
     * Returns the label of the icon. Used for displaying the icon name in the inspector.
     *
     * @param string|null $name The name of the icon.
     * @return string|null The label of the icon or null if not found.
     */
    public function getLabel(?string $name = null): ?string
    {
        if (!$name) {
            return null;
        }
        return $this->query(
            sprintf(
                'SELECT "label" FROM "icons" WHERE "name"="%s" LIMIT 1',
                $name,
            ),
            true,
        );
    }

    /**
     * Returns the total number of icons across all packs.
     *
     * @return int The total number of icons.
     */
    public function getTotalNumberOfIcons(): int
    {
        $packs = $this->query('SELECT "count" FROM "packs"');
        return array_sum(array_map(fn($count) => $count['count'], $packs));
    }

    /**
     * Searches for icons based on the given search terms and type.
     *
     * @param string|mixed[]|null $searchTerm The search terms to filter icons. If null, a random set of icons will be returned.
     * @param mixed[]|null $packsSelected
     * @param mixed[]|null $stylesSelected
     * @param mixed[]|null $categoriesSelected
     * @param mixed[]|null $fixedStyles
     * @return SidebarData An array containing the items and total count.
     */
    public function search(
        string|array|null $searchTerm = null,
        ?array $packsSelected = null,
        ?array $stylesSelected = null,
        ?array $categoriesSelected = null,
        ?array $fixedStyles = null,
    ): array {
        [
            'constraints' => $constraints,
            'styles' => $styles,
        ] = $this->getStyles($packsSelected, $stylesSelected, $fixedStyles);

        $searchTerm = $this->normalizeSearchTerm($searchTerm);

        $noFilter =
            empty($packsSelected) &&
            empty($stylesSelected) &&
            empty($categoriesSelected);

        $previewMode = count($styles) > 1;
        $limit = $previewMode ? 25 : 500;

        if ($noFilter && empty($searchTerm)) {
            $icons = $this->noSelection($styles);
            return $this->addSidebarData($constraints, $icons);
        }

        if (!empty($categoriesSelected)) {
            $categoriesSelector = array_map(
                fn($category) => sprintf('_%s_', $category),
                $categoriesSelected,
            );
            $categoriesSelector = implode(' ', $categoriesSelector);
        }

        $result = [];
        $filterCategories = [];
        $filterStyles = [];
        foreach ($styles as $style) {
            $selector = $this->styleSelector[$style] ?? null;
            if (!$selector) {
                continue;
            }

            $iconSearchQuery = implode(
                ' ',
                array_filter([
                    $selector['selector'],
                    $categoriesSelector ?? null,
                    $searchTerm,
                ]),
            );

            $query = [
                sprintf(
                    'SELECT "name", "label", "styles", "categories" FROM icons("%s") ORDER BY rank',
                    $iconSearchQuery,
                ),
            ];

            $icons = $this->addValueToIcons(
                $style,
                $this->query(implode(' ', $query)),
            );

            $count = count($icons);
            if ($count === 0) {
                continue;
            }

            $filterCategories = array_merge(
                $filterCategories,
                array_reduce(
                    $icons,
                    fn($carry, $icon) => array_merge(
                        $carry,
                        !empty($icon['categories']) ? $icon['categories'] : [],
                    ),
                    [],
                ),
            );

            $filterStyles[] = $style;

            $preview = null;
            if ($count > $limit) {
                $icons = array_slice($icons, 0, $limit, false);
                $preview = [
                    'pack' => $selector['pack'],
                    'style' => $selector['style'],
                ];
            }

            $result[$style] = [
                'label' => $selector['label'],
                'name' => $style,
                'icons' => $icons,
                'preview' => $preview,
            ];
        }

        return $this->addSidebarData(
            $constraints,
            $result,
            $filterCategories,
            $filterStyles,
        );
    }

    /**
     * Adds sidebar data for packs, styles, and categories.
     *
     * @param mixed[] $constraints Which packs and styles are available.
     * @param mixed[]|null $icons The icons to include in the sidebar.
     * @param mixed[]|null $filterCategories The categories to filter by.
     * @param mixed[]|null $filterStyles The styles to filter by.
     * @return SidebarData An associative array containing the sidebar data.
     */
    private function addSidebarData(
        array $constraints,
        ?array $icons,
        ?array $filterCategories = null,
        ?array $filterStyles = null,
    ): array {
        // Handle categories
        $where = '';
        $showNoCategories =
            !empty($icons) &&
            empty($filterCategories) &&
            $filterCategories !== null;
        if (!empty($icons) && !empty($filterCategories)) {
            $filterCategories = array_unique($filterCategories);
            $whereArray = array_map(
                fn($cat) => sprintf('("name" = "%s")', $cat),
                $filterCategories,
            );
            $where = 'WHERE ' . implode(' OR ', $whereArray);
        }
        $categories = $showNoCategories
            ? []
            : $this->query(
                implode(' ', [
                    'SELECT "label", "name" FROM "categories"',
                    $where,
                    'ORDER BY "label"',
                ]),
            );

        $packs = $this->allPacks;
        if (!empty($constraints['packs'])) {
            $packs = array_filter(
                $packs,
                fn($pack) => in_array($pack['name'], $constraints['packs']),
            );
        }

        $hasOnlyBrand =
            is_array($filterStyles) &&
            count($filterStyles) === 1 &&
            $filterStyles[0] === 'brands';
        $styles = $hasOnlyBrand ? [] : $this->allStyles;
        if (!empty($constraints['styles'])) {
            $styles = array_filter(
                $styles,
                fn($style) => in_array($style['name'], $constraints['styles']),
            );
        }

        return [
            'icons' => empty($icons) ? [] : array_values($icons),
            'packs' => array_values($packs),
            'styles' => array_values($styles),
            'categories' => array_values($categories),
        ];
    }

    /**
     * Returns a set of default icons if no selection is made.
     *
     * @param string[] $styles The styles to filter the icons by.
     * @return array<string,array{label:string,name:string,icons:IconValue[],preview:array{pack:mixed,style:mixed}}> An array of default icons grouped by style.
     */
    private function noSelection(array $styles): array
    {
        $query[] = 'SELECT "name", "label", "styles" FROM icons WHERE';

        $defaultIconList = [
            'house',
            'circle-user',
            'image',
            'file',
            'camera',
            'calendar',
            'cloud',
            'alarm-clock',
            'truck',
            'thumbs-up',
            'face-smile',
            'headphones',
            'bell',
            'user',
            'comment',
            'envelope',
        ];

        $defaultBrandList = [
            'neos',
            'litefyr',
            'github',
            'bluesky',
            'facebook',
            'instagram',
            'linkedin',
            'discord',
            'slack',
            'x-twitter',
            'youtube',
            'vimeo',
            'apple',
            'google',
            'kickstarter',
            'docker',
        ];

        $icons = implode(
            ' OR ',
            array_map(
                fn($icon) => sprintf('("name" = \'%s\')', $icon),
                $defaultIconList,
            ),
        );
        $brands = implode(
            ' OR ',
            array_map(
                fn($icon) => sprintf('("name" = \'%s\')', $icon),
                $defaultBrandList,
            ),
        );

        $defaultIcons = $this->query(
            implode(' ', array_merge($query, [$icons])),
        );
        $defaultBrands = $this->query(
            implode(' ', array_merge($query, [$brands])),
        );
        $defaultIcons = $this->sortItemsByNameOrder(
            $defaultIcons,
            $defaultIconList,
        );
        $defaultBrands = $this->sortItemsByNameOrder(
            $defaultBrands,
            $defaultBrandList,
        );
        $returnIcons = [];
        foreach ($styles as $style) {
            $selector = $this->styleSelector[$style] ?? null;
            if (!$selector) {
                continue;
            }
            $icons = $style === 'brands' ? $defaultBrands : $defaultIcons;
            $icons = $this->addValueToIcons($style, $icons);

            $returnIcons[$style] = [
                'label' => $selector['label'],
                'name' => $style,
                'icons' => $icons,
                'preview' => [
                    'pack' => $selector['pack'],
                    'style' => $selector['style'],
                ],
            ];
        }
        return $returnIcons;
    }

    /**
     * Adds the style prefix to each icon and returns an array of icons with their labels, names, values, and categories.
     *
     * @param string $style The style prefix to add to each icon.
     * @param mixed[]|null $icons The icons to process.
     * @return IconValue[] An array of icons with their labels, names, values, and categories.
     */
    private function addValueToIcons(string $style, ?array $icons = null): array
    {
        if (empty($icons)) {
            return [];
        }
        return array_map(
            fn($icon) => [
                'label' => $icon['label'],
                'name' => $icon['name'],
                'value' => sprintf('%s:%s', $style, $icon['name']),
                'categories' => !empty($icon['categories'])
                    ? $this->trimUnderscores(explode(',', $icon['categories']))
                    : [],
            ],
            $icons,
        );
    }

    /**
     * Trims leading and trailing underscores from each string in the array.
     *
     * @param string[] $strings An array of strings to be trimmed.
     * @return string[] The array with each string trimmed of leading and trailing underscores.
     */
    private function trimUnderscores(array $strings): array
    {
        return array_map(function (string $s): string {
            return trim($s, '_');
        }, $strings);
    }

    /**
     * Executes a query on the SQLite database and returns the result.
     *
     * @param string $query The SQL query to execute.
     * @param bool $single Whether to return a single result or an array of results.
     * @return mixed The result of the query, either a single value or an array of results.
     */
    private function query(string $query, bool $single = false): mixed
    {
        if (!$this->db) {
            return null;
        }
        if ($single) {
            $resultSet = $this->db->querySingle($query);
            return $resultSet !== false ? $resultSet : null;
        }

        $resultSet = $this->db->query($query);
        if ($resultSet === false) {
            return null;
        }
        $result = [];
        while ($row = $resultSet->fetchArray(SQLITE3_ASSOC)) {
            foreach (['keywords', 'styles', 'icons'] as $key) {
                if (!empty($row[$key]) && is_string($row[$key])) {
                    $row[$key] = explode(',', $row[$key]);
                }
            }
            $result[] = $row;
        }
        return $result;
    }

    /**
     * Normalizes the search term by removing unwanted characters and formatting it.
     *
     * @param string[]|string|null $input The input to normalize.
     * @return string The normalized search term.
     */
    private function normalizeSearchTerm(
        array|string|null $input = null,
    ): string {
        if (is_array($input)) {
            $input = implode(',', $input);
        }
        $input = str_replace(' ', ',', $input ?? '');

        $neutralTerms = [];
        $positiveTerms = [];
        $negativeTerms = [];
        foreach (explode(',', $input) as $term) {
            $cleanedTerm = str_replace(
                [
                    ' ',
                    '+',
                    '*',
                    '-',
                    '!',
                    '?',
                    ':',
                    ';',
                    ',',
                    '.',
                    '(',
                    ')',
                    '[',
                    ']',
                    '{',
                    '}',
                    '"',
                    "'",
                    '`',
                ],
                '',
                trim($term),
            );
            if (!$cleanedTerm) {
                continue;
            }
            if (str_starts_with($term, '+')) {
                $positiveTerms[] = $cleanedTerm;
                continue;
            }
            if (str_starts_with($term, '-') || str_starts_with($term, '!')) {
                $negativeTerms[] = 'NOT ' . $cleanedTerm;
                continue;
            }
            $neutralTerms[] = $cleanedTerm . '*';
        }

        $terms = array_merge($positiveTerms, $neutralTerms, $negativeTerms);

        return count($terms) ? implode(' ', $terms) : '';
    }

    /**
     * Sorts $items (array of [‘name’=>..., ‘value’=>...])
     * according to the order in $order (array of names/strings).
     * Elements whose ‘name’ does not appear in $order are placed at the end.
     *
     * @param Item[] $items A numerically indexed array with sub-arrays:
     *                     [
     *                       ['name' => 'foo', 'value' => 123],
     *                       ['name' => 'bar', 'value' => 456],
     *                       ...
     *                     ]
     * @param string[] $order An array of strings (e.g. ['bar', 'baz', 'foo']), that specifies the desired sort order of the 'name' fields.
     * @return Item[] The newly sorted array.
     */
    private function sortItemsByNameOrder(
        array $items,
        array $order,
        string $key = 'name',
    ): array {
        // 1. first we build a lookup that assigns each name its index in $order.
        // array_flip converts ['bar', 'baz', 'foo'] to ['bar' => 0, 'baz' => 1, 'foo' => 2].
        $position = array_flip($order);

        // 2. then we sort $items with usort based on this position.
        usort($items, function (array $a, array $b) use ($position, $key) {
            // Hole die Namen beider Elemente
            $nameA = $a[$key];
            $nameB = $b[$key];

            // Determine the position in the desired order.
            // If the name is not in $position, we give a very high default value.
            $posA = !empty($position[$nameA]) ? $position[$nameA] : PHP_INT_MAX;
            $posB = !empty($position[$nameB]) ? $position[$nameB] : PHP_INT_MAX;

            // Now compare numerically only:
            if ($posA === $posB) {
                return 0; // if both are in the same position (or both are not in $order), the order remains unchanged
            }
            return $posA < $posB ? -1 : 1;
        });

        return $items;
    }

    /**
     * Returns the styles based on the selected packs and styles, or fixed styles.
     *
     * @param string[]|null $packsSelected The packs to filter by.
     * @param string[]|null $stylesSelected The styles to filter by.
     * @param string[]|null $fixedStyles The fixed styles to include.
     * @return Styles An array containing the constraints and the styles.
     */
    private function getStyles(
        ?array $packsSelected = null,
        ?array $stylesSelected = null,
        ?array $fixedStyles = null,
    ): array {
        $fixedStylesUnderscored = null;
        if (!empty($fixedStyles)) {
            $fixedStylesUnderscored = array_map(
                fn($style) => sprintf('_%s_', $style),
                $fixedStyles,
            );
        }

        $packs = $this->filterPacksOrStyles('packs', $fixedStylesUnderscored);
        $styles = $this->filterPacksOrStyles('styles', $fixedStylesUnderscored);

        $returnStyles = [];
        $hasBrand = false;
        $packsSelected = $packsSelected ?? $packs;
        $stylesSelected = $stylesSelected ?? $styles;
        foreach ($packsSelected as $pack) {
            if ($pack === 'brands') {
                $hasBrand = true;
                continue;
            }
            // $hasNotBrand = true;
            $prefix = $pack === 'classic' ? '' : $pack . '-';
            foreach ($stylesSelected as $style) {
                $returnStyles[] = $prefix . $style;
            }
        }

        // We want to keep the 'brands' style separate and put it at the end
        if ($hasBrand) {
            $returnStyles[] = 'brands';
        }

        if (!empty($fixedStyles)) {
            $returnStyles = array_values(
                array_intersect($returnStyles, $fixedStyles),
            );
        }

        return [
            'constraints' => [
                'packs' => $packs,
                'styles' => $styles,
            ],
            'styles' => $returnStyles,
        ];
    }

    /**
     * Filters packs or styles based on the given type and fixed styles.
     *
     * @param string $type The type to filter by ('packs' or 'styles').
     * @param FixedStylesUnderscored|null $fixedStylesUnderscored An array of fixed styles with underscores.
     * @return PacksOrStyles An array of filtered pack or style names.
     */
    private function filterPacksOrStyles(
        string $type,
        ?array $fixedStylesUnderscored = null,
    ): array {
        // Get all items
        // We show also items that are not selected, as it is a OR selection
        $items = $type === 'packs' ? $this->allPacks : $this->allStyles;

        // Filter items if fixed styles are given
        if (!empty($fixedStylesUnderscored)) {
            $items = array_filter(
                $items,
                fn($item) => (bool) array_intersect(
                    $fixedStylesUnderscored,
                    $item['styles'],
                ),
            );
        }

        // Get only the names of the items
        return array_map(fn($item) => $item['name'], $items);
    }
}
