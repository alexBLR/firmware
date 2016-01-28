var components = {
    "packages": [
        {
            "name": "bootstrap",
            "main": "js/bootstrap.js"
        },
        {
            "name": "jquery",
            "main": "jquery.js"
        },
        {
            "name": "assetic"
        },
        {
            "name": "component-installer"
        },
        {
            "name": "process"
        },
        {
            "name": "package_name"
        }
    ],
    "shim": {
        "bootstrap": {
            "deps": [
                "jquery"
            ]
        }
    },
    "baseUrl": "components"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}