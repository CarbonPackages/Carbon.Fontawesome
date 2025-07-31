import React, { useState, useEffect, useMemo } from "react";
import { neos } from "@neos-project/neos-ui-decorators";
import { TextInput, CheckBox } from "@neos-project/react-ui-components";
import ArrowRight from "./Component/ArrowRight";
import FilterList from "./Component/FilterList";
import CurrentIcon from "./Component/CurrentIcon";
import ImageButton from "./Component/ImageButton";
import DebugOutput from "./Component/DebugOutput";
import Tools from "./Component/Tools";
import { parseSettings, stringifySettings, animationIconNames, saveEditorValues, sortPacksAndStyles } from "./Helper";
import { useDebouncedCallback } from "use-debounce";
import * as stylex from "@stylexjs/stylex";
// import { useRenderCount } from "@uidotdev/usehooks";

const stylesX = stylex.create({
    headline: {
        textTransform: "uppercase",
        opacity: 0.8,
        paddingTop: "calc(var(--spacing-GoldenUnit) / 2)",
        marginTop: "calc(var(--spacing-GoldenUnit) / 2)",
        marginBottom: "var(--spacing-Half)",
    },
    mainPanel: {
        display: "grid",
        gridTemplateColumns: "200px minmax(0, 1fr)",
        gap: "var(--spacing-GoldenUnit)",
        marginBottom: "calc(var(--spacing-GoldenUnit) * 2)",
    },
    iconsWrapper: {
        maxWidth: "calc(8 * var(--spacing-Full) + 9 * 200px)",
        width: "100%",
        marginInline: "auto",
    },
    belowSearchField: {
        display: "flex",
        alignItems: "center",
        justifyContent: "space-between",
        fontSize: "var(--fontSize-Small)",
        marginTop: "var(--spacing-Half)",
        opacity: 0.8,
    },
    filter: {
        display: "grid",
        gridTemplateColumns: "30px minmax(0, 1fr)",
        gap: "var(--spacing-Quarter)",
        marginBottom: "var(--spacing-Full)",
        alignItems: "center",
        cursor: "pointer",
    },
    currentIcon: {
        position: "sticky",
        top: 108,
        zIndex: 1,
        background: "color-mix(in srgb, var(--colors-ContrastDarker), transparent 30%)",
        backdropFilter: "blur(6px)",
        marginInline: "calc(var(--spacing-Full) * -1)",
        paddingInline: "var(--spacing-Full)",
    },
    wrapper: {
        maxHeight: "var(--dialog-max-height)",
        minHeight: "var(--dialog-max-height)",
        overflow: "auto",
    },
    srOnly: {
        position: "absolute",
        width: 1,
        height: 1,
        padding: 0,
        margin: -1,
        overflow: "hidden",
        clip: "rect(0, 0, 0, 0)",
        whiteSpace: "nowrap",
        borderWidth: 0,
    },
    paddingX: {
        paddingInline: "var(--spacing-Full)",
    },
    stickyHeader: {
        position: "sticky",
        top: 0,
        zIndex: 2,
        background: "color-mix(in srgb, var(--colors-ContrastDarker), transparent 30%)",
        backdropFilter: "blur(6px)",
        paddingTop: "var(--spacing-Full)",
        paddingLeft: "var(--spacing-Full)",
        paddingRight: "calc(var(--spacing-Full) + var(--spacing-GoldenUnit))",
    },
    iconGrid: {
        "--grid-min-col-size": "140px",
        display: "grid",
        gridTemplateColumns: "repeat(auto-fill, minmax(min(var(--grid-min-col-size), 100%), 1fr))",
        gap: "var(--spacing-Full)",
    },
    moreButton: {
        display: "grid",
        gridTemplateColumns: "minmax(0, 1fr) 30px",
        alignItems: "center",
        justifyContent: "center",
        backgroundColor: "var(--colors-ContrastBrightest)",
        borderRadius: 8,
        gap: "var(--spacing-Full)",
        padding: "var(--spacing-Full)",
        cursor: "pointer",
        border: "none",
        gridColumn: "span 2",
        background: "var(--colors-ContrastNeutral)",
        color: "var(--colors-ContrastBrightest)",
        transition: "background-color 0.2s ease-in-out",

        ":is(:hover,:focus-visible)": {
            backgroundColor: "var(--colors-PrimaryBlue)",
        },
    },
});

function Panel({
    id,
    label,
    onChange,
    value,
    i18nRegistry,
    options,
    config,
    dataSourcesDataLoader,
    nodeIdentifier,
    editorValues,
    ...props
}) {
    const localStorageKey = `carbon-fontawesome-${nodeIdentifier}${id}`;
    const preselectedCategories = options?.preselectedCategories || [];
    const searchPreset = options?.searchPreset ? `${options.searchPreset} ` : "";
    const fixedStyles = options?.fixedStyles || null;
    const [total, setTotal] = useState(0);
    const [icons, setIcons] = useState([]);
    const [sidebar, setSidebar] = useState({});
    const [current, setCurrent] = useState({});
    const [currentSettings, setCurrentSettings] = useState({});
    const [currentValue, setCurrentValue] = useState(value);

    // Sidebar filters
    const [packs, setPacks] = useState([]);
    const [styles, setStyles] = useState([]);
    const [categories, setCategories] = useState(preselectedCategories);

    const [searchTerm, setSearchTerm] = useState("");
    const [search, setSearch] = useState("");
    const [disabled, setDisabled] = useState(true);
    const [firstFetch, setFirstFetch] = useState(true);
    const debouncedSetDisabled = useDebouncedCallback((value) => {
        setDisabled(value);
    }, 100);
    const debouncedSetSearch = useDebouncedCallback((value) => {
        setSearch(value);
    }, 500);

    // const renderCount = useRenderCount();

    const getDataLoaderOptions = (data) => ({
        contextNodePath: null,
        dataSourceIdentifier: "carbon-fontawesome",
        dataSourceUri: null,
        dataSourceDisableCaching: false,
        dataSourceAdditionalData: data,
    });

    useMemo(() => {
        if (editorValues) {
            const { search, packs, styles, categories } = editorValues;
            if (packs) {
                setPacks(packs);
            }
            if (styles) {
                setStyles(styles);
            }
            if (categories) {
                setCategories(categories);
            }
            if (search) {
                setSearchTerm(search);
                setSearch(search);
            }
            debouncedSetDisabled(false);
        } else {
            setDisabled(false);
        }

        if (value) {
            const [folder, icon, ...rest] = value.split(":");
            setCurrent({ label, value: `${folder}:${icon}` });
            setCurrentSettings(parseSettings(rest.join(":")));
        }

        dataSourcesDataLoader.resolveValue(getDataLoaderOptions({ total: true })).then((total) => {
            setTotal(new Intl.NumberFormat().format(total));
        });
    }, []);

    useMemo(() => {
        if (disabled) {
            return;
        }
        dataSourcesDataLoader
            .resolveValue(
                getDataLoaderOptions({
                    search: searchPreset + search,
                    packs: sortPacksAndStyles(packs),
                    styles: sortPacksAndStyles(styles),
                    categories: categories || [],
                    fixedStyles,
                }),
            )
            .then((values) => {
                setSidebar({
                    packs: values.packs || [],
                    styles: values.styles || [],
                    categories: values.categories || [],
                });
                setIcons(values.icons || []);
                if (firstFetch) {
                    setFirstFetch(false);
                }
                saveEditorValues({ search, packs, styles, categories, nodeIdentifier, id });
            });
    }, [search, packs, styles, categories, disabled]);

    useEffect(() => {
        const newValue = current.value + stringifySettings(currentSettings);
        setCurrentValue(newValue);
        onChange(newValue);
    }, [current, currentSettings]);

    return (
        <div {...stylex.props(stylesX.wrapper)}>
            <div {...stylex.props(stylesX.stickyHeader)}>
                <label htmlFor={`${id}-search`} {...stylex.props(stylesX.srOnly)}>
                    {i18nRegistry.translate("Carbon.Fontawesome:Main:search")}
                </label>
                <TextInput
                    id={`${id}-search`}
                    value={searchTerm}
                    onChange={(value) => {
                        setSearchTerm(value);
                        debouncedSetSearch(value);
                    }}
                    disabled={disabled}
                    placeholder={i18nRegistry.translate("Carbon.Fontawesome:Main:searchNumberOfIcons", "", { total })}
                />
                <div {...stylex.props(stylesX.belowSearchField)}>
                    <label htmlFor={`${id}-search`}>{i18nRegistry.translate("Carbon.Fontawesome:Main:help")}</label>
                    <DebugOutput>{currentValue}</DebugOutput>
                    {config?.version || ""}
                </div>
            </div>
            <div {...stylex.props(stylesX.paddingX, stylesX.mainPanel)}>
                <div {...stylex.props(stylesX.sidebar)}>
                    {Boolean(current?.value && current?.label) && (
                        <>
                            <h3 {...stylex.props(stylesX.headline)}>
                                {i18nRegistry.translate("Carbon.Fontawesome:Main:currentIcon")}
                            </h3>
                            <div {...stylex.props(stylesX.currentIcon)}>
                                <CurrentIcon icon={currentValue} label={current.label} />
                                <Tools
                                    currentSettings={currentSettings}
                                    current={current}
                                    setCurrentSettings={setCurrentSettings}
                                    options={options}
                                    i18nRegistry={i18nRegistry}
                                    id={id}
                                    currentValue={currentValue}
                                />
                            </div>
                        </>
                    )}
                    <FilterList
                        headline="iconPacks"
                        items={sidebar?.packs}
                        value={packs}
                        onChange={setPacks}
                        disabled={disabled}
                    />
                    <FilterList
                        headline="style"
                        items={sidebar?.styles}
                        value={styles}
                        onChange={setStyles}
                        disabled={disabled}
                    />
                    <FilterList
                        headline="categories"
                        items={sidebar?.categories}
                        value={categories}
                        onChange={setCategories}
                        disabled={disabled}
                    />
                </div>
                <div {...stylex.props(stylesX.iconsWrapper)}>
                    {Boolean(!icons?.length) && !disabled && !firstFetch && (
                        <h3 {...stylex.props(stylesX.headline)}>
                            {i18nRegistry.translate("Carbon.Fontawesome:Main:notFound")}
                        </h3>
                    )}
                    {icons.map(({ name, label, icons, preview }) => {
                        if (!icons?.length) {
                            return null;
                        }
                        return (
                            <section key={name}>
                                <h3 {...stylex.props(stylesX.headline)}>{label}</h3>
                                <div {...stylex.props(stylesX.iconGrid)}>
                                    {icons.map(({ label, value }) => (
                                        <ImageButton
                                            key={value}
                                            icon={value}
                                            label={label}
                                            onClick={() => {
                                                setCurrent({ label, value });
                                            }}
                                        />
                                    ))}
                                    {Boolean(preview && preview.pack && preview.style) && (
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setPacks([preview.pack]);
                                                setStyles([preview.style]);
                                            }}
                                            {...stylex.props(stylesX.moreButton)}
                                        >
                                            {i18nRegistry.translate("Carbon.Fontawesome:Main:viewMore")}
                                            <ArrowRight />
                                        </button>
                                    )}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

const neosifier = neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get("i18n"),
    dataSourcesDataLoader: globalRegistry.get("dataLoaders").get("DataSources"),
    config: globalRegistry.get("frontendConfiguration").get("Carbon.Fontawesome"),
}));

export default neosifier(Panel);
