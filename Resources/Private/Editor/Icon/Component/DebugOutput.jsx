import React from "react";

// Don't include this in production builds
export default function DebugOutput({ children }) {
    DEV: {
        return (
            <pre>
                <code>{children}</code>
            </pre>
        );
    }
    return null;
}
