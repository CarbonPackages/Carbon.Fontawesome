import React from "react";
import * as stylex from "@stylexjs/stylex";
import { animationIconNames, parseSettings } from "../Helper";
import SVG from "react-inlinesvg";

const styles = stylex.create({
    svg: {
        width: "100%",
        height: "auto",
        objectFit: "contain",
        display: "block",
        margin: "0 auto",
        overflow: "visible",
        fill: "currentColor",
        aspectRatio: 2,
    },
    maxHeight: (maxHeight) => ({
        maxHeight,
    }),
    transform: (rotate, flip) => ({
        display: "flex",
        width: "100%",
        transform: `rotate(${rotate || 0}deg) ${flip == "horizontal" ? "scaleX(-1)" : flip == "vertical" ? "scaleY(-1)" : flip == "both" ? "scale(-1, -1)" : ""}`,
    }),
});

export default function Icon({ icon, maxHeight }) {
    if (!icon) {
        return null;
    }

    const [folder, name, ...rest] = icon.split(":");
    const src = `/_Resources/Static/Packages/Carbon.Fontawesome.Icons/${folder}/${name}.svg`;
    const { animation, rotate, flip } = parseSettings(rest.join(":"));
    const animationClass =
        animation && animationIconNames?.[animation]?.className ? ` ${animationIconNames[animation].className}` : "";

    const svgProps = {
        src,
        className: stylex.props(styles.svg, maxHeight && styles.maxHeight(maxHeight)).className + animationClass,
        style: stylex.props(maxHeight && styles.maxHeight(maxHeight)).style,
    };

    if (rotate || flip) {
        return (
            <div {...stylex.props(styles.transform(rotate, flip))}>
                <SVG {...svgProps} />
            </div>
        );
    }

    return <SVG {...svgProps} />;
}
