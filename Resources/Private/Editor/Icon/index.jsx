import React, { useState, useCallback, useEffect, Suspense, lazy } from "react";
import { neos } from "@neos-project/neos-ui-decorators";
import { connect } from "react-redux";
import { selectors } from "@neos-project/neos-ui-redux-store";
import { Button, Icon, IconButton } from "@neos-project/react-ui-components";
import LoadingAnimation from "carbon-neos-loadinganimation/LoadingWithStyleX";
import Dialog from "carbon-neos-editor-styling/Dialog";
import * as stylex from "@stylexjs/stylex";
import { parseSettings, getEditorValues } from "./Helper";
import PreviewIcon from "./Component/Icon";

const defaultOptions = {
    allowEmpty: true,
    disabled: false,
    disableFeatures: [],
    placeholder: "Carbon.Fontawesome:Main:noIconSelected",
    placeholderIcon: null,
};

const styles = stylex.create({
    wrapper: {
        borderRadius: 2,
        overflow: "hidden",
        display: "flex",
    },
    highlight: {
        boxShadow: "0 0 0 2px var(--colors-Warn)",
    },
    primaryButton: {
        textAlign: "left",
        backgroundColor: "var(--colors-ContrastNeutral)",
        border: 0,
        cursor: "pointer",
        minHeight: 40,
        maxHeight: 40,
        width: "100%",
        display: "grid",
        gridTemplateColumns: "calc(var(--spacing-GoldenUnit) - var(--spacing-Half)) minmax(0, 1fr)",
        alignItems: "center",
        justifyContent: "start",
        gap: "var(--spacing-Half)",
        transition:
            "background-color var(--transition-Default) ease-in-out, color var(--transition-Default) ease-in-out",
        padding: "var(--spacing-Half) var(--spacing-Full) var(--spacing-Half) var(--spacing-Quarter)",
        color: "var(--colors-ContrastBrighter)",

        ":is(:hover,:focus-visible)": {
            backgroundColor: "var(--colors-PrimaryBlue)",
            color: "var(--colors-ContrastBrightest)",
        },
    },
    preview: {
        color: "var(--colors-ContrastBrightest)",
    },
    previewIcon: {
        height: "calc(var(--spacing-GoldenUnit) - var(--spacing-Full))",
        aspectRatio: 1,
        display: "flex",
        width: "100%",
    },
    emptyIcon: {
        opacity: 0.3,
    },
});

const getDataLoaderOptions = (label) => ({
    contextNodePath: null,
    dataSourceIdentifier: "carbon-fontawesome",
    dataSourceUri: null,
    dataSourceDisableCaching: false,
    dataSourceAdditionalData: {
        label,
    },
});

const LazyPanel = lazy(() => import("./Panel"));

function Editor({ id, value, commit, highlight, i18nRegistry, dataSourcesDataLoader, nodeIdentifier, ...props }) {
    const options = { ...defaultOptions, ...props.options };
    const [open, setOpen] = useState(false);
    const [valueFromPanel, setValueFromPanel] = useState(value);
    const [preview, setPreview] = useState(null);
    const [label, setLabel] = useState(null);
    const [hasValue, setHasValue] = useState(false);
    const editorValues = getEditorValues({ nodeIdentifier, id });
    const placeholder = options.placeholder ? i18nRegistry.translate(options.placeholder) : null;

    useEffect(() => {
        const hasValue = value && value.includes(":");
        setHasValue(hasValue);
        const iconValue = hasValue ? value : options.placeholderIcon;
        if (!iconValue) {
            setPreview(null);
            setLabel(null);
            return;
        }

        setPreview(iconValue);

        dataSourcesDataLoader.resolveValue(getDataLoaderOptions(iconValue)).then((label) => {
            setLabel(label);
        });
    }, [value]);

    const onApply = useCallback(() => {
        setOpen(false);
        if (valueFromPanel !== value) {
            commit(valueFromPanel);
        }
    }, [valueFromPanel, value]);

    return (
        <div {...stylex.props(styles.wrapper, highlight && styles.highlight)}>
            <button
                id={id}
                type="button"
                {...stylex.props(styles.primaryButton, preview && hasValue && styles.preview)}
                onClick={() => setOpen(true)}
                title={i18nRegistry.translate("Carbon.Fontawesome:Main:editIcon")}
            >
                {preview ? (
                    <span {...stylex.props(styles.previewIcon, !hasValue && styles.emptyIcon)}>
                        <PreviewIcon icon={preview} />
                    </span>
                ) : (
                    <Icon
                        className={stylex.props(styles.previewIcon, styles.emptyIcon).className}
                        icon="pencil"
                        size="2x"
                        mask={["fas", "circle"]}
                        transform="shrink-8"
                    />
                )}
                <span>{preview ? label || placeholder : placeholder}</span>
            </button>
            {options.allowEmpty && value && (
                <IconButton
                    style="light"
                    icon="times"
                    title={i18nRegistry.translate("Carbon.Fontawesome:Main:resetIcon")}
                    onClick={() => {
                        commit("");
                    }}
                />
            )}
            <Dialog
                open={open}
                setOpen={setOpen}
                onCancel={() => setOpen(false)}
                showCloseButton={true}
                onApply={onApply}
                disabledApply={valueFromPanel === value}
                fullWidth
                fullHeight
                blurFooterBackground={true}
            >
                {open && (
                    <Suspense fallback={<LoadingAnimation isLoading={true} />}>
                        <LazyPanel
                            id={id}
                            onChange={setValueFromPanel}
                            label={label}
                            value={value}
                            options={options}
                            nodeIdentifier={nodeIdentifier}
                            editorValues={editorValues}
                        />
                    </Suspense>
                )}
            </Dialog>
        </div>
    );
}

const neosifier = neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get("i18n"),
    dataSourcesDataLoader: globalRegistry.get("dataLoaders").get("DataSources"),
}));

const connector = connect((state) => ({
    nodeIdentifier: selectors.CR.Nodes.focusedNodeIdentifierSelector(state),
}));

export default neosifier(connector(Editor));
