function timeout(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
async function sleep(ms, fn, ...args) {
    await timeout(ms);
    return fn(...args);
}