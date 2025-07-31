import manifest from "@neos-project/neos-ui-extensibility";
import Icon from "./Icon";

manifest("Carbon.Fontawesome:Editors", {}, (globalRegistry) => {
    const editorsRegistry = globalRegistry.get("inspector").get("editors");
    editorsRegistry.set("Carbon.Fontawesome/Icon", {
        component: Icon,
    });
});
