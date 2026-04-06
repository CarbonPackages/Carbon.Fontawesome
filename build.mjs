import esbuild from "esbuild";
import extensibilityMap from "@neos-project/neos-ui-extensibility/extensibilityMap.json" with { type: "json" };
import stylex from "@stylexjs/unplugin";

const watch = process.argv.includes("--watch");
const dev = process.argv.includes("--dev");
const editor = process.argv.includes("--editor");
const minify = !dev && !watch;

/** @type {import("esbuild").BuildOptions} */
const defaultOptions = {
    logLevel: "info",
    bundle: true,
    minify,
    sourcemap: watch,
    target: "es2020",
    legalComments: "none",
    format: "esm",
    splitting: true,
};

if (minify) {
    defaultOptions.drop = ["debugger"];
    defaultOptions.pure = ["console.log"];
    defaultOptions.dropLabels = ["DEV"];
}

const options = [
    {
        ...defaultOptions,
        entryPoints: ["Resources/Private/Editor/Main.js"],
        outdir: "Resources/Public/Editor",
        alias: extensibilityMap,
        loader: {
            ".js": "jsx",
        },
        metafile: true,
        plugins: [
            stylex.esbuild({
                useCSSLayers: false,
                classNamePrefix: "fontawesome-",
                dev: false,
                lightningcssOptions: {
                    minify: true,
                },
            }),
        ],
    },
    {
        ...defaultOptions,
        entryPoints: ["Resources/Private/Assets/Main.ts"],
        outdir: "Resources/Public/Modules",
    },
];

if (watch) {
    options.forEach((opt) => esbuild.context(opt).then((ctx) => ctx.watch()));
} else {
    options.forEach((opt) => esbuild.build(opt));
}
