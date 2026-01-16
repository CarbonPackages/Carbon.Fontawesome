import { Alpine as AlpineType } from "alpinejs";
import setIcon from "./Helper/ApiCache";

export default function (Alpine: typeof AlpineType) {
    Alpine.data("icon", (group: string, icon: string, version?: string, path?: string) => ({
        init() {
            // @ts-ignore
            const element = this.$el as HTMLElement;
            setIcon({ element, group, icon, path, version });
        },
    }));
}
