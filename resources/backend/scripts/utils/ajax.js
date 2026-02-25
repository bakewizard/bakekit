export async function ajax({
    url,
    method = "GET",
    data = null,
    headers = {},
    dataType = "json",
    timeout = 10000
}) {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeout);

    const acceptTypes = {
        json: "application/json",
        text: "text/plain",
        html: "text/html",
        any: "*/*"
    };

    headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': acceptTypes[dataType] || acceptTypes.any,
        ...headers
    };

    let options = { method, headers, signal: controller.signal };

    if (data) {
        if (method.toUpperCase() === "GET") {
            url += "?" + new URLSearchParams(data).toString();
        } else if (data instanceof FormData) {
            options.body = data; // Let fetch handle FormData
        } else {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(data);
        }
    }

    try {
        const response = await fetch(url, options);
        clearTimeout(timeoutId);

        if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);

        return dataType === "json" ? response.json() : response.text();
    } catch (err) {
        if (err.name === "AbortError") throw new Error("Request timed out");
        throw err;
    }
}
