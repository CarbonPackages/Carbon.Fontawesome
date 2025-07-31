function getArrayValue(array, fallback) {
    return Array.isArray(array) && array.length ? array : fallback;
}

export function saveEditorValues({ search, packs, styles, categories, nodeIdentifier, id }) {
    packs = getArrayValue(packs, []);
    styles = getArrayValue(styles, []);
    categories = getArrayValue(categories, []);
    search = search || "";

    localStorage.setItem(
        `carbon-fontawesome-${nodeIdentifier}${id}`,
        JSON.stringify({ search, packs, styles, categories }),
    );
}

const sortingArrayPacksAndStyles = [
    "brands",
    "classic",
    "duotone",
    "sharp",
    "sharp-duotone",
    "chisel",
    "etch",
    "jelly",
    "jelly-duo",
    "jelly-fill",
    "notdog",
    "notdog-duo",
    "slab",
    "slab-press",
    "thumbprint",
    "whiteboard",
    "solid",
    "regular",
    "light",
    "thin",
    "semibold",
];
export function sortPacksAndStyles(array) {
    if (!Array.isArray(array)) {
        return [];
    }
    return array.sort((a, b) => sortingArrayPacksAndStyles.indexOf(a) - sortingArrayPacksAndStyles.indexOf(b));
}

export function getEditorValues({ nodeIdentifier, id }) {
    const localStorageValue = localStorage.getItem(`carbon-fontawesome-${nodeIdentifier}${id}`);
    if (localStorageValue) {
        let { search, packs, styles, categories } = JSON.parse(localStorageValue);
        search = search || null;
        packs = getArrayValue(packs, null);
        styles = getArrayValue(styles, null);
        categories = getArrayValue(categories, null);
        if (!search && !packs && !styles && !categories) {
            return null;
        }
        return { search, packs, styles, categories };
    }
    return null;
}

export function parseSettings(settings) {
    const result = {};
    if (!settings) {
        return result;
    }
    const parts = settings.split(",");
    for (const part of parts) {
        const [key, value] = part.split(":");
        if (!key) {
            continue;
        }
        if (value === undefined) {
            result[key] = true;
            continue;
        }
        try {
            const parse = JSON.parse(value);
            result[key] = parse;
        } catch (error) {
            result[key] = value;
        }
    }
    return result;
}

export function stringifySettings(settings) {
    const result = [];
    for (const key in settings) {
        const value = settings[key];
        if (value === true) {
            result.push(key);
            continue;
        }
        if (value) {
            result.push(`${key}:${value}`);
        }
    }
    if (!result.length) {
        return "";
    }
    return ":" + result.join(",");
}

export const animationIconNames = {
    none: {
        icon: "ban",
        className: "",
    },
    beat: {
        icon: "heart",
        className: "fa-icon-beat",
    },
    "beat-fade": {
        icon: "heart-half-stroke",
        className: "fa-icon-beat-fade",
    },
    bounce: {
        icon: "tennis-ball",
        className: "fa-icon-bounce",
    },
    fade: {
        icon: "sliders-simple",
        className: "fa-icon-fade",
    },
    flip: {
        icon: "reflect-horizontal",
        className: "fa-icon-flip",
    },
    shake: {
        icon: "bell-ring",
        className: "fa-icon-shake",
    },
    spin: {
        icon: "rotate-right",
        className: "fa-icon-spin",
    },
    "spin-reverse": {
        icon: "rotate-left",
        className: "fa-icon-spin fa-icon-spin-reverse",
    },
    "spin-pulse": {
        icon: "spinner",
        className: "fa-icon-spin-pulse",
    },
    "spin-pulse-reverse": {
        icon: "spinner",
        className: "fa-icon-spin-pulse fa-icon-spin-reverse",
    },
};
