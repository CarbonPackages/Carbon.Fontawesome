# Carbon.Fontawesome

Inline [Fontawesome 7] Icons into Neos with [AlpineJS]

To make it work, you have to include [Main.js] (as Module) and [Main.css] in your installation by yourself. Or, you
include it into your Build Pipeline from the [Assets folder]. Of course you can write also your own implemenation.

## [Carbon.Fontawesome:Icon]

Look out for several examples in the `@styleguide` section in the Fusion file

### Property `icon` `string|array`, required

Icon has style and icon name combined, e.g. `solid:check`
And also something like `duotone-solid:check:{animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"}`
or `duotone-solid:check:animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"`

`duotone` will be converted to `duotone-solid`

settings props can be camelCase or kebab-case, e.g. `animation:spin-reverse` or `animation:spinReverse` / `"secondaryColor":"blue"` or `"secondary-color":"blue"`

You can also put the parts into a multiple array `['solid', 'check', 'color:green']`

### Property `settings` `string|DataStructure`

Add settings from the props below to the icon, e.g. `animation:spin-reverse,rotate:90,swapOpacity,'primaryColor':'red',"secondaryColor":"blue"`
You can also warap in brackets, e.g. `{animation:spin-reverse,rotate:90,swap-opacity,'primary-color':'red',"secondary-color":"blue"}`
settings props can be camelCase or kebab-case, e.g. `animation:spin-reverse` or `animation:spinReverse` / `"secondaryColor":"blue"` or `"secondary-color":"blue"`

The string as the same as third argument of the icon, or an object with the settings

#### Properties of inside `settings`

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

## [Carbon.Fontawesome:Processor]

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

## Further prototypes

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

[fontawesome 7]: https://fontawesome.com/icons
[alpinejs]: http://alpinejs.dev
[main.js]: Resources/Public/Modules/Main.js
[main.css]: Resources/Public/Styles/Main.css
[assets folder]: Resources/Private/Assets
[carbon.fontawesome:icon]: Resources/Private/Fusion/Component/Icon.fusion
[carbon.fontawesome:layers]: Resources/Private/Fusion/Component/Layers/Layers.fusion
[carbon.fontawesome:list]: Resources/Private/Fusion/Component/List.fusion
[carbon.fontawesome:processor]: Resources/Private/Fusion/Component/Processor.fusion
