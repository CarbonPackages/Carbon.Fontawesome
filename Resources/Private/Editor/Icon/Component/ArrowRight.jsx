import React from "react";
import * as stylex from "@stylexjs/stylex";

const styles = stylex.create({
    svg: {
        width: "100%",
        height: "auto",
        fill: "currentColor",
        display: "block",
    },
});

export default function ArrowRight(props) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" {...stylex.props(styles.svg)} {...props}>
            <path d="M443.3 267.3c6.2-6.2 6.2-16.4 0-22.6l-176-176c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L393.4 240 16 240c-8.8 0-16 7.2-16 16s7.2 16 16 16l377.4 0L244.7 420.7c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0l176-176z" />
        </svg>
    );
}
