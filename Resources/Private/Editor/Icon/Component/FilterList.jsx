import React from "react";
import * as stylex from "@stylexjs/stylex";
import { neos } from "@neos-project/neos-ui-decorators";
import { CheckBox } from "@neos-project/react-ui-components";

const styles = stylex.create({
    headline: {
        textTransform: "uppercase",
        opacity: 0.8,
        paddingTop: "calc(var(--spacing-GoldenUnit) / 2)",
        marginTop: "calc(var(--spacing-GoldenUnit) / 2)",
        marginBottom: "var(--spacing-Half)",
    },
    filter: {
        display: "grid",
        gridTemplateColumns: "30px minmax(0, 1fr)",
        gap: "var(--spacing-Quarter)",
        marginBottom: "var(--spacing-Full)",
        alignItems: "center",
        cursor: "pointer",
        userSelect: "none",
    },
});

function FilterList({ items, headline, value, onChange, i18nRegistry, disabled }) {
    if (!items || items.length === 0) {
        return null;
    }
    if (!value) {
        value = [];
    }

    return (
        <>
            <h3 {...stylex.props(styles.headline)}>{i18nRegistry.translate(`Carbon.Fontawesome:Main:${headline}`)}</h3>
            {items.map(({ label, name }) => (
                <label {...stylex.props(styles.filter)} key={headline + name}>
                    <CheckBox
                        isChecked={value.includes(name)}
                        disabled={disabled}
                        onChange={(checked) => {
                            const newValue = checked ? [...value, name] : value.filter((v) => v !== name);
                            onChange(newValue);
                        }}
                    />
                    {label}
                </label>
            ))}
        </>
    );
}

const neosifier = neos((globalRegistry) => ({
    i18nRegistry: globalRegistry.get("i18n"),
}));

export default neosifier(FilterList);
