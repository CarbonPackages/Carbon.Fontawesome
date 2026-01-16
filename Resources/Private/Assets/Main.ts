import icon from "./Plugin";

// Extend the Window interface to include Alpine
declare global {
    interface Window {
        Alpine: any;
    }
}

window.addEventListener("alpine:init", () => {
    window.Alpine.plugin(icon);
});
