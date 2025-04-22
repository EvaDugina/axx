import { httpApiUrl, wsApiUrl } from "./api.js";
import { storeInHash, loadFromHash } from "./hashStorage.js";

const Sandbox = {
    id: null,
};

export default Sandbox;

(async () => {
    let { sandboxId = null } = loadFromHash();
    if (!sandboxId) {
        const res = await fetch(`${httpApiUrl}/sandbox/`, { method: "POST" });
        sandboxId = (await res.json()).id;
        storeInHash({ sandboxId });
    }
    Sandbox.id = sandboxId;

    const terminal = new Terminal();
    const socket = new WebSocket(`${wsApiUrl}/sandbox/ws/${Sandbox.id}`);

    socket.onopen = () => {
        console.log('WebSocket connection established');
        socket.send("\n");
    };

    socket.onmessage = (event) => {
        console.log('Message from server:', event.data);
    };

    socket.onerror = (error) => {
        console.error('WebSocket error:', error);
    };

    socket.onclose = (event) => {
        console.log('WebSocket connection closed:', event);
    };

    terminal.onData(data => {
        if (socket.readyState === WebSocket.OPEN) {
            // socket.send(data); // Отправка данных через WebSocket
        } else {
            console.error('WebSocket is not open. Current state:', socket.readyState);
        }
    });

    const attachAddon = new AttachAddon.AttachAddon(socket);
    const fitAddon = new FitAddon.FitAddon();
    terminal.loadAddon(attachAddon);
    terminal.loadAddon(fitAddon);

    let div_terminal = document.querySelector("#terminal");
    terminal.open(div_terminal);

    // ResizeObserver для отслеживания изменений размера
    new ResizeObserver(() => {
        let width = div_terminal.clientWidth;
        let height = div_terminal.clientHeight;

        // console.log(width, height)

        // Проверяем, что ширина и высота больше нуля
        if (width > 0 && height > 0) {
            fitAddon.fit();
        }

    }).observe(div_terminal);

})();