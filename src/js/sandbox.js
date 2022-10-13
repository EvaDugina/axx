const apiUrl = `https://vega.fcyb.mirea.ru/sandbox`;
const wsUrl = `wss://vega.fcyb.mirea.ru/sandbox/sandbox/ws`;

const Sandbox = {
    id: null
};

export default Sandbox;

(async () => {
    Sandbox.id = window.location.hash.slice(1);
    if (!Sandbox.id) {
        const res = await fetch(`${apiUrl}/sandbox/`, { method: "POST" });
        Sandbox.id = (await res.json()).id;
        window.location.hash = Sandbox.id;
    }

    const terminal = new Terminal();

    const socket = new WebSocket(`${wsUrl}/${Sandbox.id}`);

    socket.addEventListener('message', ({data}) => {
        if(data.indexOf(`# Sandbox with id ${Sandbox.id} does not exist`) === 0)
            window.location.hash = ``;
    }, { once: true });

    const attachAddon = new AttachAddon.AttachAddon(socket);
    const fitAddon = new FitAddon.FitAddon();
    terminal.loadAddon(attachAddon);
    terminal.open(document.querySelector("#terminal"));
    terminal.loadAddon(fitAddon);
    new ResizeObserver(() => {
        fitAddon.fit();
        fetch(`${apiUrl}/sandbox/${Sandbox.id}/resize-tty`, {
            method: "POST",
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cols: terminal._core._bufferService.cols,
                rows: terminal._core._bufferService.rows
            })
        });
    }).observe(document.querySelector("#terminal"));

    window.terminal = terminal;
})();