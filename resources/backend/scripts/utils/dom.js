export function offset(el) {
    const box = el.getBoundingClientRect();
    return {
        top: box.top + window.scrollY,
        left: box.left + window.scrollX,
    };
}

export function stripTrailingSlash(str) {
    return str.endsWith('/') ? str.slice(0, -1) : str;
}
