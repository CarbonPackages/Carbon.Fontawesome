import { replaceTag, CACHE_NAME, MAX_AGE } from "./Helper";

export default async function setIcon({
    element,
    group,
    icon,
    path,
    version,
}: {
    element: HTMLElement;
    group: string;
    icon: string;
    path?: string;
    version?: string;
}) {
    const segment = `${group}/${icon}`;
    const url = `${path || "/_Resources/Static/Packages/Carbon.Fontawesome.Icons"}/${segment}.svg`;
    const suffix = version ? `-${version}` : "";
    const cacheName = `${CACHE_NAME}${suffix}`;
    const cachedData = await getCachedData(cacheName, url, MAX_AGE);
    if (cachedData) {
        return replaceTag(element, cachedData);
    }
    const cacheStorage = await caches.open(cacheName);
    await cacheStorage.add(url);
    const fetchedData = await getCachedData(cacheName, url);
    await deleteOldCaches(cacheName);
    return replaceTag(element, fetchedData);
}

// Get data from the cache.
async function getCachedData(cacheName: string, url: string, maxAge = 0) {
    const cacheStorage = await caches.open(cacheName);
    const cachedResponse = await cacheStorage.match(url);

    if (!cachedResponse || !cachedResponse.ok) {
        return false;
    }

    const dateHeader = maxAge > 0 ? cachedResponse.headers.get("date") : null;

    if (dateHeader) {
        const date = new Date(dateHeader);
        // if cached file is older maxAge in seconds
        const needRefetch = date && Date.now() > date.getTime() * maxAge;

        if (needRefetch) {
            return false;
        }
    }

    return await cachedResponse.text();
}

// Delete any old caches to respect user's disk space.
async function deleteOldCaches(currentCache: string) {
    const keys = await caches.keys();

    for (const key of keys) {
        const isOurCache = key.startsWith(CACHE_NAME);
        if (currentCache === key || !isOurCache) {
            continue;
        }
        caches.delete(key);
    }
}
