import React from "react";
import * as stylex from "@stylexjs/stylex";
import Icon from "./Icon";

const styles = stylex.create({
    figure: {
        display: "grid",
        gridTemplateColumns: "80px minmax(0, 1fr)",
        width: "100%",
        color: "var(--colors-ContrastBrightest)",
        borderRadius: 8,
        backgroundColor: "var(--colors-ContrastNeutral)",
        margin: 0,
        aspectRatio: 3,
        alignItems: "center",
        paddingInline: "var(--spacing-Quarter)",
        pointerEvents: "none",
        userSelect: "none",
    },
    big: {
        gridTemplateRows: "auto",
        gridTemplateColumns: null,
        backgroundColor: null,
        aspectRatio: 1,
        justifyItems: "center",
        padding: "var(--spacing-GoldenUnit)",
    },
});

export default function CurrentIcon({ icon, label, big }) {
    return (
        <figure {...stylex.props(styles.figure, big && styles.big)}>
            <Icon icon={icon} />
            {Boolean(big) || <figcaption>{label}</figcaption>}
        </figure>
    );
}
