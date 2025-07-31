import React, { useState } from "react";
import * as stylex from "@stylexjs/stylex";
import Dialog from "carbon-neos-editor-styling/Dialog";
import CustomTextInput from "carbon-neos-editor-styling/TextInput";
import { ReactAnglePicker } from "react-angle-picker";
import CurrentIcon from "./CurrentIcon";
import ImageButton from "./ImageButton";
import Icon from "./Icon";
import { animationIconNames } from "../Helper";

const styles = stylex.create({
    buttons: {
        display: "grid",
        gridAutoFlow: "column",
        gap: "var(--spacing-Quarter)",
        marginTop: "var(--spacing-Quarter)",

        ":empty": {
            display: "none",
        },
    },
    panel: {
        overflow: "scroll",
        maxHeight: "var(--dialog-total-max-height)",
    },
    panelInner: {
        minWidth: 520,
        maxWidth: 660,
        padding: "var(--spacing-GoldenUnit)",
        overflow: "clip",
    },
    label: {
        marginBottom: "var(--spacing-Quarter)",
        display: "block",
        userSelect: "none",
    },
    toolbar: {
        display: "flex",
        gap: "var(--spacing-Full)",
    },
    tools: {
        display: "grid",
        gridAutoFlow: "column",
        gap: "var(--spacing-Quarter)",
        marginTop: "var(--spacing-Quarter)",

        ":empty": {
            display: "none",
        },
    },
    anglePicker: {
        display: "grid",
        gridTemplate: '"content" 1fr / 1fr',
        alignItems: "center",
        justifyItems: "center",

        ":is(*) > *": {
            gridArea: "content",
        },

        ":is(*) > div": {
            cursor: "grab",
        },

        ":is(*) > div:active": {
            cursor: "grabbing",
        },
    },
});

export default function Tools({
    currentSettings,
    current,
    setCurrentSettings,
    currentValue,
    options,
    i18nRegistry,
    id,
}) {
    const features = ["animation", "transform"].filter((feature) => !options?.disableFeatures?.includes(feature));

    const [dialog, setDialog] = useState(false);

    if (!features.length) {
        return null;
    }

    return (
        <>
            <div {...stylex.props(styles.buttons)}>
                {features.includes("animation") && (
                    <ImageButton
                        panel
                        title={i18nRegistry.translate("Carbon.Fontawesome:Main:showAnimationOptions")}
                        icon="solid:film"
                        onClick={() => {
                            setDialog("animation");
                        }}
                    />
                )}
                {features.includes("transform") && (
                    <ImageButton
                        panel
                        title={i18nRegistry.translate("Carbon.Fontawesome:Main:showStylingTools")}
                        icon="solid:palette"
                        onClick={() => {
                            setDialog("styling");
                        }}
                    />
                )}
                <ImageButton
                    panel
                    title={i18nRegistry.translate("Carbon.Fontawesome:Main:resetStyling")}
                    icon="solid:ban"
                    disabled={!(currentSettings?.rotate || currentSettings?.flip || currentSettings?.animation)}
                    onClick={() => setCurrentSettings({})}
                />
            </div>

            <Dialog open={dialog === "animation"} setOpen={setDialog} showCloseButton={true}>
                <div {...stylex.props(styles.panel)}>
                    <div {...stylex.props(styles.panelInner)}>
                        <CurrentIcon icon={currentValue} big />
                        <div {...stylex.props(styles.tools)}>
                            {Object.entries(animationIconNames).map(([name, { icon, className }]) => (
                                <ImageButton
                                    panel
                                    key={name}
                                    title={i18nRegistry.translate(`Carbon.Fontawesome:Main:animation.${name}`)}
                                    icon={`solid:${icon}:animation:${className}`}
                                    onClick={() =>
                                        setCurrentSettings((prev) => ({
                                            ...prev,
                                            animation: prev?.animation === name || name === "none" ? "" : name,
                                        }))
                                    }
                                />
                            ))}
                        </div>
                    </div>
                </div>
            </Dialog>
            <Dialog open={dialog === "styling"} setOpen={setDialog} showCloseButton={true}>
                <div {...stylex.props(styles.panel)}>
                    <div {...stylex.props(styles.panelInner)}>
                        <div {...stylex.props(styles.anglePicker)}>
                            <CurrentIcon icon={currentValue} big />
                            <ReactAnglePicker
                                value={(currentSettings?.rotate || 0) - 90}
                                onChange={(value) => {
                                    let parsedValue = (parseInt(value) || 0) + 90;
                                    if (parsedValue >= 360) {
                                        parsedValue -= 360;
                                    }
                                    setCurrentSettings((prev) => ({
                                        ...prev,
                                        rotate: parsedValue,
                                    }));
                                }}
                                width={400}
                                pointerWidth={20}
                                borderWidth={2}
                                pointerColor="var(--colors-PrimaryBlue)"
                                borderColor="var(--colors-ContrastNeutral)"
                            />
                        </div>

                        <div {...stylex.props(styles.toolbar)}>
                            <div>
                                <label htmlFor={`${id}-rotate`} {...stylex.props(styles.label)}>
                                    {i18nRegistry.translate("Carbon.Fontawesome:Main:rotate")}
                                </label>
                                <div {...stylex.props(styles.tools)}>
                                    <CustomTextInput
                                        type="number"
                                        id={`${id}-rotate`}
                                        value={currentSettings?.rotate || 0}
                                        onChange={(value) => {
                                            setCurrentSettings((prev) => ({
                                                ...prev,
                                                rotate: parseInt(value) || 0,
                                            }));
                                        }}
                                        unit={i18nRegistry.translate("Carbon.Fontawesome:Main:rotateUnit")}
                                        placeholder="0"
                                        min={-360}
                                        max={360}
                                        textAlign="right"
                                    />
                                    <ImageButton
                                        panel
                                        title={i18nRegistry.translate("Carbon.Fontawesome:Main:resetRotation")}
                                        icon="solid:ban"
                                        disabled={!currentSettings?.rotate}
                                        onClick={() =>
                                            setCurrentSettings((prev) => ({
                                                ...prev,
                                                rotate: 0,
                                            }))
                                        }
                                    />
                                </div>
                            </div>
                            <div>
                                <div {...stylex.props(styles.label)}>
                                    {i18nRegistry.translate("Carbon.Fontawesome:Main:flip")}
                                </div>
                                <div {...stylex.props(styles.tools)}>
                                    {["horizontal", "vertical", "both"].map((direction) => (
                                        <ImageButton
                                            panel
                                            key={direction}
                                            title={i18nRegistry.translate(`Carbon.Fontawesome:Main:flip.${direction}`)}
                                            icon={`solid:reflect-${direction}`}
                                            onClick={() =>
                                                setCurrentSettings((prev) => ({
                                                    ...prev,
                                                    flip: prev?.flip === direction ? "" : direction,
                                                }))
                                            }
                                        />
                                    ))}
                                    <ImageButton
                                        panel
                                        title={i18nRegistry.translate("Carbon.Fontawesome:Main:resetStyling")}
                                        icon="solid:ban"
                                        disabled={!currentSettings?.flip}
                                        onClick={() =>
                                            setCurrentSettings((prev) => ({
                                                ...prev,
                                                flip: "",
                                            }))
                                        }
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Dialog>
        </>
    );
}
