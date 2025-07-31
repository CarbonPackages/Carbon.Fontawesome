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

const svgs = {
    "solid/heart-half-stroke": {
        viewBox: "0 0 512 512",
        path: "M241.5 87.1l15 20.7 15-20.7c25-34.6 65.2-55.1 107.9-55.1 73.5 0 133.1 59.6 133.1 133.1l0 2.6c0 112.2-139.9 242.5-212.9 298.2-12.3 9.4-27.5 14.1-43.1 14.1s-30.8-4.7-43.1-14.1l0 0C140.4 410.2 .5 279.9 .5 167.7l0-2.6C.5 91.6 60.1 32 133.6 32 176.3 32 216.5 52.5 241.5 87.1zm15 328.9c2.9 0 4.1-.8 4.2-.9 33.8-25.8 83.1-69 123.3-117.8 42.3-51.4 64.5-97.1 64.5-129.6l0-2.6c0-38.2-30.9-69.1-69.1-69.1-22.2 0-43 10.6-56 28.6-29.8 41.2-52.1 72-66.9 92.4l0 198.9z",
    },

    "solid/tennis-ball": {
        viewBox: "0 0 512 512",
        path: "M256 512a256 256 0 1 0 0-512 256 256 0 1 0 0 512zM51.9 216c41.7-1 83.1-17.3 114.9-49.2S215 93.6 216 51.9c13-2.5 26.3-3.9 40-3.9 2.7 0 5.3 .1 8 .2 0 55.2-21.1 110.4-63.3 152.6S103.4 264 48.2 264c-.1-2.7-.2-5.3-.2-8 0-13.7 1.3-27.1 3.9-40zM460.2 296c-41.7 1-83.1 17.3-114.9 49.2S297 418.4 296 460.2c-13 2.5-26.3 3.8-40 3.8-2.7 0-5.3-.1-8-.2 0-55.2 21.1-110.4 63.3-152.6S408.6 248 463.8 248c.1 2.7 .2 5.3 .2 8 0 13.7-1.3 27.1-3.8 40z",
    },

    "solid/sliders-simple": {
        viewBox: "0 0 512 512",
        path: "M96 384a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm90.5-64c-13.2-37.3-48.7-64-90.5-64-53 0-96 43-96 96s43 96 96 96c41.8 0 77.4-26.7 90.5-64L480 384c17.7 0 32-14.3 32-32s-14.3-32-32-32l-293.5 0zM448 160a32 32 0 1 1 -64 0 32 32 0 1 1 64 0zM325.5 128L32 128c-17.7 0-32 14.3-32 32s14.3 32 32 32l293.5 0c13.2 37.3 48.7 64 90.5 64 53 0 96-43 96-96s-43-96-96-96c-41.8 0-77.4 26.7-90.5 64z",
    },
    "solid/bell-ring": {
        viewBox: "0 0 512 512",
        path: "M112.6 41.4c9.6-9.1 10-24.3 .8-33.9S89-2.5 79.4 6.6C30.5 53.2 0 119.1 0 192 0 205.3 10.7 216 24 216s24-10.7 24-24c0-59.3 24.8-112.7 64.6-150.6zm320-34.8c-9.6-9.1-24.8-8.8-33.9 .8s-8.8 24.8 .8 33.9c39.8 37.9 64.6 91.4 64.6 150.6 0 13.3 10.7 24 24 24s24-10.7 24-24c0-72.9-30.5-138.8-79.4-185.4zM256 0c-17.7 0-32 14.3-32 32l0 3.2C151 50 96 114.6 96 192l0 21.7c0 48.1-16.4 94.8-46.4 132.4l-9.8 12.2c-5 6.3-7.8 14.1-7.8 22.2 0 19.6 15.9 35.5 35.5 35.5l376.9 0c19.6 0 35.5-15.9 35.5-35.5 0-8.1-2.7-15.9-7.8-22.2l-9.8-12.2C432.4 308.5 416 261.8 416 213.7l0-21.7c0-77.4-55-142-128-156.8l0-3.2c0-17.7-14.3-32-32-32zM194 464c7.1 27.6 32.2 48 62 48s54.9-20.4 62-48l-124 0z",
    },

    "solid/reflect-horizontal": {
        viewBox: "0 0 512 512",
        path: "M256 32c13.3 0 24 10.7 24 24l0 400c0 13.3-10.7 24-24 24s-24-10.7-24-24l0-400c0-13.3 10.7-24 24-24zM14.8 97.8c9-3.7 19.3-1.7 26.2 5.2L177 239c9.4 9.4 9.4 24.6 0 33.9L41 409c-6.9 6.9-17.2 8.9-26.2 5.2S0 401.7 0 392L0 120c0-9.7 5.8-18.5 14.8-22.2zM471 103c6.9-6.9 17.2-8.9 26.2-5.2S512 110.3 512 120l0 272c0 9.7-5.8 18.5-14.8 22.2s-19.3 1.7-26.2-5.2L335 273c-9.4-9.4-9.4-24.6 0-33.9L471 103z",
    },

    "solid/reflect-vertical": {
        viewBox: "0 0 448 512",
        path: "M71 41c-6.9-6.9-8.9-17.2-5.2-26.2S78.3 0 88 0L360 0c9.7 0 18.5 5.8 22.2 14.8S383.8 34.1 377 41L241 177c-9.4 9.4-24.6 9.4-33.9 0L71 41zM0 256c0-13.3 10.7-24 24-24l400 0c13.3 0 24 10.7 24 24s-10.7 24-24 24L24 280c-13.3 0-24-10.7-24-24zM65.8 497.2c-3.7-9-1.7-19.3 5.2-26.2L207 335c9.4-9.4 24.6-9.4 33.9 0L377 471c6.9 6.9 8.9 17.2 5.2 26.2S369.7 512 360 512L88 512c-9.7 0-18.5-5.8-22.2-14.8z",
    },

    "solid/reflect-both": {
        viewBox: "0 0 448 512",
        path: "M87 73L207 193c9.4 9.4 24.6 9.4 33.9 0L361 73c6.9-6.9 8.9-17.2 5.2-26.2S353.7 32 344 32L104 32c-9.7 0-18.5 5.8-22.2 14.8S80.2 66.1 87 73zM41 119c-6.9-6.9-17.2-8.9-26.2-5.2S0 126.3 0 136L0 376c0 9.7 5.8 18.5 14.8 22.2S34.1 399.8 41 393L161 273c9.4-9.4 9.4-24.6 0-33.9L41 119zM87 439c-6.9 6.9-8.9 17.2-5.2 26.2S94.3 480 104 480l240 0c9.7 0 18.5-5.8 22.2-14.8s1.7-19.3-5.2-26.2L241 319c-9.4-9.4-24.6-9.4-33.9 0L87 439zM433.2 113.8c-9-3.7-19.3-1.7-26.2 5.2L287 239c-9.4 9.4-9.4 24.6 0 33.9L407 393c6.9 6.9 17.2 8.9 26.2 5.2S448 385.7 448 376l0-240c0-9.7-5.8-18.5-14.8-22.2z",
    },
};

function Markup({ src, maxHeight, animation }) {
    const svg = svgs[src] || null;
    const animationClass =
        animation && animationIconNames?.[animation]?.className ? ` ${animationIconNames[animation].className}` : "";
    const svgProps = {
        className: stylex.props(styles.svg, maxHeight && styles.maxHeight(maxHeight)).className + animationClass,
        style: stylex.props(maxHeight && styles.maxHeight(maxHeight)).style,
    };

    if (svg) {
        return (
            <svg xmlns="http://www.w3.org/2000/svg" viewBox={svg.viewBox} {...svgProps}>
                <path d={svg.path} />
            </svg>
        );
    }

    return <SVG {...svgProps} src={`/_Resources/Static/Packages/Carbon.Fontawesome.Icons/${src}.svg`} />;
}

export default function Icon({ icon, maxHeight, animation }) {
    if (!icon) {
        return null;
    }

    const [folder, name, ...rest] = icon.split(":");
    const settings = parseSettings(rest.join(":"));
    const { rotate, flip } = settings;
    animation = animation || settings.animation;

    const src = `${folder}/${name}`;

    if (rotate || flip) {
        return (
            <div {...stylex.props(styles.transform(rotate, flip))}>
                <Markup animation={animation} src={src} maxHeight={maxHeight} />
            </div>
        );
    }

    return <Markup animation={animation} src={src} maxHeight={maxHeight} />;
}
