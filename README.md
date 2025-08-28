# Carbon.Fontawesome

[!Editor]

Inline [Fontawesome 7] icons in **Neos CMS** using a small [AlpineJS] helper and handy Fusion prototypes.

> ✅ Works with Neos **8.3+ / 9.0+** and PHP **8.3+**.

## Installation

Install the package in your Neos project:

```bash
composer require carbon/fontawesome
```

This package requires:

- `neos/neos: ^8.3 || ^9.0`
- `php: ^8.3`
- `carbon/eel: ^2.21`
- `carbon/fontawesomeicons: ^1.0`

> The `carbon/fontawesomeicons` package ships the Font Awesome 7 Free icon data used by this package.

## Assets

This package ships a minimal JS module and CSS you need to include in your site:

- [`Resources/Public/Modules/Main.js`] (load as `type="module"`)
- [`Resources/Public/Styles/Main.css`]

You can either:

- reference the built files directly from `/_Resources/Static/Packages/Carbon.Fontawesome/...`, or
- copy them into your frontend build pipeline

Example (direct include in your layout):

```html
<link rel="stylesheet" href="/_Resources/Static/Packages/Carbon.Fontawesome/Styles/Main.css" />

<!-- Alpine Plugin (see “Alpine.js load order”) -->
<script type="module" defer src="/_Resources/Static/Packages/Carbon.Fontawesome/Modules/Main.js"></script>
```

## Alpine.js load order (important)

When using Alpine via `<script>` tags, include the **Carbon.Fontawesome plugin before Alpine core**, so Alpine picks up
the plugin during boot (same pattern as Alpine’s official plugins):

```html
<!-- 1) Carbon.Fontawesome plugin -->
<script type="module" defer src="/_Resources/Static/Packages/Carbon.Fontawesome/Modules/Main.js"></script>

<!-- 2) Alpine core -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

When bundling with ESM:

```js
import Alpine from "alpinejs";
// Ensure the plugin is imported BEFORE Alpine.start()
import "/Packages/Carbon/Resources/Private/Assets/Main.ts";

window.Alpine = Alpine;
Alpine.start();
```

> If Alpine auto-starts in your setup, make sure the plugin is loaded on the page before Alpine initializes.

## Usage in Fusion

### [Carbon.Fontawesome:Icon]

Basic example:

```elm
prototype(Vendor.Site:Icon.Check) < prototype(Carbon.Fontawesome:Icon) {
    icon = 'solid:check'
    settings = 'size:lg,color:#0ea5e9,animation:spin'
    attributes.class = 'my-icon'
}
```

> Look out for several examples in the `@styleguide` section in the Fusion file

#### Property `icon` `string|array`, required

Icon has style and icon name combined, e.g. `solid:check`
And also something like `duotone-solid:check:{animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"}`
or `duotone-solid:check:animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"`

`duotone` will be converted to `duotone-solid`

settings props can be camelCase or kebab-case, e.g. `animation:spin-reverse` or `animation:spinReverse` / `"secondaryColor":"blue"` or `"secondary-color":"blue"`

You can also put the parts into a multiple array `['solid', 'check', 'color:green']`

#### Property `settings` `string|DataStructure`

Add settings from the props below to the icon, e.g. `animation:spin-reverse,rotate:90,swapOpacity,'primaryColor':'red',"secondaryColor":"blue"`
You can also warap in brackets, e.g. `{animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"}`
settings props can be camelCase or kebab-case, e.g. `animation:spin-reverse` or `animation:spinReverse` / `"secondaryColor":"blue"` or `"secondary-color":"blue"`

The string as the same as third argument of the icon, or an object with the settings

The settings can have following properties, which will be applied to the icon (Remeber you can mix camelCase and kebab-case):
Some of the settings need the corresponding CSS classes

- `animation`: beat, bounce, fade, beat-fade, flip, shake, spin, spin-reverse, spin-pulse, spin-pulse-reverse
- `size`: 2xs, xs, sm, lg, xl, 2xl, 1x, 2x, 3x, 4x, 5x, 6x, 7x, 8x, 9x, 10x
- `fixedWidth`: boolean
- `pull`: left, right
- `border`: boolean
- `inverse`: boolean
- `color`: any valid CSS color value
- `opacity`: Integer, value between 0 and 1
- `fontSize`: float, will append `em`
- `label`: string, will create `aria-label` attribute
- `tooltip`: string, will create `aria-label` attribute with the `string`, and add the `x-tooltip` attribute, as well it ads the class `fa-icon-pointer-events`

#### Other properties

Any other props than `icon` and `settings` will be applied to the icon as attributes.

`styles` can be a string, array or a DataStructure and can also be written in Javascript style, e.g. `{marginTop: '10vh', height: '80vh'}`

`class` can be a string, array or a DataStructure

### [Carbon.Fontawesome:Processor]

This processor replaces icon placeholder in the form `[icon:IconDefinition]` with icons. For example,
`[icon:solid:tennis-ball]` will be replaced with an icon element. To define the size of the icon, you can use
`[icon-1.5:solid:tennis-ball]` for a 1.5em sized icon. Font sizes smaller than `0.1em` will be ignored.

If you set `renderIcon` to `false`, the icon will not be rendered and the input will be erased.

The easied way to active this, is to extend `Neos.Neos:Editable`:

```elm
prototype(Neos.Neos:Editable) {
    renderer.@process.replaceIcons = Carbon.Fontawesome:Processor
}
```

### Further prototypes

For [Carbon.Fontawesome:Layers] and [Carbon.Fontawesome:List] look at the `@styleguide` for examples

**Transform icon:**

- `rotate`: integer|float
- `flip`: horizontal, vertical, both
- `scale`: integer|float minimal value -15. Units are 1/16em
- `translateY`: integer|float Units are 1/16em. Move icon in Y axis
- `translateX`: integer|float Units are 1/16em. Move icon in X axis
- `shrink`: The same as `scale` with negative value. e.g. `shrink:3` is the same as `scale:-3`
- `grow`: The same as `scale` with positive value. e.g. `grow:3` is the same as `scale:3`
- `up`: The same as `translateY` with negative value. e.g. `up:3` is the same as `translateY:-3`
- `down`: The same as `translateY` with positive value. e.g. `down:3` is the same as `translateY:3`
- `left`: The same as `translateX` with negative value. e.g. `left:3` is the same as `translateX:-3`
- `right`: The same as `translateX` with positive value. e.g. `right:3` is the same as `translateX:3`

**If animation is enabled:**

- `delay`: overrides delay in seconds, float
- `duration`: overrides duration in seconds, float
- `direction`: Set the direction: normal, reverse, alternate, alternate-reverse
- `iteration`: integer, If set, set the max iterations, integer
- `timing`: Set the animation timing
- `scale`: (beat and beat-fade animation only), float
- `startX`: (bounce animation only), float
- `startY`: (bounce animation only), float
- `jumpX`: (bounce animation only), float
- `jumpY`: (bounce animation only), float
- `height`: (bounce animation only), float
- `landX`: (bounce animation only), float
- `landY`: (bounce animation only), float
- `rebound`: (bounce animation only), float
- `fadeOpacity`: (fade and beat-fade animation only), float
- `flipX`: (flip animation only), float
- `flipY`: (flip animation only), float
- `flipZ`: (flip animation only), float
- `flipAngle`: (flip animation only), float
- `steps`: (spin-pulse animation only), integer

**For duotone icons only:**

- `swapOpacity`: boolean
- `primaryOpacity`: float between 0 and 1, e.g. 0.4
- `secondaryOpacity`: float between 0 and 1, e.g. 0.4
- `primaryColor`: any valid CSS color value, e.g. '#ff0000', 'red', 'rgb(255,0,0)', 'rgba(255,0,0,1), 'var(--my-color)'
- `secondaryColor`: any valid CSS color value, e.g. '#00ff00', 'green', 'rgb(0,255,0)', 'rgba(0,255,0,1), 'var(--my-color)'

## Example of using the editor

The saved value will be `style:iconName`, e.g. `brands:neos`

```yaml
"Foo.Bar:Mixin.Icon":
  properties:
    icon:
      type: string
      ui:
        inspector:
          editor: "Carbon.Fontawesome/Icon"
          editorOptions:
            # You can disable features
            # disableFeatures:
            #   - `animation'
            #   - 'transform'
            # You can enable just certain styles like this:
            # fixedStyles:
            #   - 'solid'
            #   - 'regular'
            #   - 'light'
            #   - 'thin'
            #   - 'duotone-solid'
            #   - 'duotone-regular'
            #   - 'duotone-light'
            #   - 'duotone-thin'
            #   - 'sharp-solid'
            #   - 'sharp-regular'
            #   - 'sharp-light'
            #   - 'sharp-thin'
            #   - 'sharp-duotone-solid'
            #   - 'sharp-duotone-regular'
            #   - 'sharp-duotone-light'
            #   - 'sharp-duotone-thin'
            #   - 'brands'

            # If you want to preset a search (e.g. to only show icons who match the term "down"), you can do it like this:
            # Seperate multiple search terms with a comma or a space to search for multiple terms at once.
            # searchPreset: 'down -left -right'

            # You can also preselect a category
            # preselectedCategories:
            #   - arrows
```

## Using Font Awesome Pro (optional)

This package ships with FA7 Free. If you need Pro, follow the instructions in carbon/fontawesomeicons to regenerate the
icon data (copy icon-families.json and categories.yml from the Pro metadata, run make, then use your private repository
under the same package name).

## Troubleshooting

- **No icons render:** Ensure `Main.css` and `Main.js` are loaded, and verify the **Alpine load order** noted above.
- **Duotone colors don’t apply:** Use `primaryColor` / `secondaryColor` (or kebab-case variants) and verify the icon style is duotone.
- **Processor not active:** Confirm the `@process.replaceIcons` Fusion step is added to Neos.Neos:Editable.

[!editor]: https://github.com/user-attachments/assets/42309040-41b5-4c97-adbe-175a05f5b86f
[fontawesome 7]: https://fontawesome.com/icons
[alpinejs]: http://alpinejs.dev
[`resources/public/modules/main.js`]: Resources/Public/Modules/Main.js
[`resources/public/styles/main.css`]: Resources/Public/Styles/Main.css
[carbon.fontawesome:icon]: Resources/Private/Fusion/Component/Icon.fusion
[carbon.fontawesome:layers]: Resources/Private/Fusion/Component/Layers/Layers.fusion
[carbon.fontawesome:list]: Resources/Private/Fusion/Component/List.fusion
[carbon.fontawesome:processor]: Resources/Private/Fusion/Component/Processor.fusion
