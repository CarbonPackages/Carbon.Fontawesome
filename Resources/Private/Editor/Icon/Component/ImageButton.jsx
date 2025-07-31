import React, { useState } from "react";
import * as stylex from "@stylexjs/stylex";
import Icon from "./Icon";

const styles = stylex.create({
    button: {
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
        justifyContent: "space-around",
        textAlign: "center",
        color: "var(--colors-ContrastBrightest)",
        borderRadius: 8,
        padding: "var(--spacing-Full)",
        gap: "var(--spacing-Half)",
        cursor: "pointer",
        border: "none",
        backgroundColor: "var(--colors-ContrastNeutral)",
        transition: "background-color 0.2s ease-in-out",
        maxWidth: 200,

        ":is(:hover,:focus-visible)": {
            backgroundColor: "var(--colors-PrimaryBlue)",
        },

        ":disabled": {
            backgroundColor: "var(--colors-ContrastNeutral) !important",
            cursor: "not-allowed",
            opacity: 0.8,
        },
    },
    panelButton: {
        padding: "var(--spacing-Quarter)",
        minHeight: "var(--spacing-GoldenUnit)",
    },
    label: {
        minHeight: "3em",
        display: "flex",
        alignItems: "center",
        marginBottom: "calc(var(--spacing-Full) * -1)",
    },
});

export default function ImageButton({ icon, title, onClick, panel = false, label, disabled, animationOnHover = null }) {
    const [animation, setAnimation] = useState(null);
    return (
        <button
            type="button"
            title={title}
            onClick={onClick}
            onMouseEnter={() => {
                if (animationOnHover) {
                    // Trigger animation on hover
                    setAnimation(animationOnHover);
                }
            }}
            onMouseLeave={() => {
                setAnimation(null);
            }}
            disabled={disabled}
            {...stylex.props(styles.button, panel && styles.panelButton)}
        >
            <Icon icon={icon} maxHeight={panel ? 20 : null} animation={animation} />
            {label && <span {...stylex.props(styles.label)}>{label}</span>}
        </button>
    );
}
