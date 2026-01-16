export const CACHE_NAME = "carbon-fontawesome";

const MAX_AGE_IN_DAYS = 30;
const milisecondsInADay = 8.64e7;

export const MAX_AGE = MAX_AGE_IN_DAYS * milisecondsInADay;

export function replaceTag(element: Element, markup: string | false) {
    if (!markup) {
        element.remove();
        return;
    }

    const attributes = [...element.attributes]
        .filter((item) => item.name != "x-data")
        .map((item) => [item.name, item.value]);
    const div = document.createElement("div");
    div.innerHTML = markup;
    const newElement = div.firstElementChild;
    if (!newElement) {
        return;
    }

    attributes.forEach(([name, value]) => {
        if (name === "x-tooltip") {
            newElement.setAttribute("x-data", "");
        }
        newElement.setAttribute(name, value);
    });

    element.replaceWith(newElement);
}
