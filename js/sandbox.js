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
    const fitAddon = new FitAddon.FitAddon();
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

    var socket;
    var isFirstOpen = true;
    const URL = `${wsApiUrl}/sandbox/ws/${Sandbox.id}`;
    function connectWebSocket() {
        socket = new WebSocket(URL);

        socket.onopen = () => {
            console.log('WebSocket connection established');
            if (isFirstOpen) {
                isFirstOpen = false;
                terminal.writeln('\x1B[1;3;31mТерминал запущен!\x1B[0m');
                terminal.write('$ ');
                // terminal.writeln('\x1B[1;3;31mRed bold italic\x1B[0m text');
                // terminal.writeln('\x1B[32mGreen text\x1B[0m');
                // terminal.writeln('\x1B[44;37mWhite on blue\x1B[0m');
                // socket.send("\n");

            }
            var attachAddon = new AttachAddon.AttachAddon(socket);
            terminal.loadAddon(attachAddon);

        };

        // socket.onmessage = (event) => {
        //     let message = event.data;
        //     console.log("message", message);
        //     terminal.write(message);
        // };

        socket.onerror = (error) => {
            console.error('WebSocket error:', error);
        };

        socket.onclose = (event) => {
            console.log('WebSocket connection closed:', event);
            if (event.code === 1006) {
                reconnectWebSocket(5);
            } else {
                reconnectWebSocket(15);
            }
        };

        async function reconnectWebSocket(s) {
            setTimeout(connectWebSocket, s * 1000);
        }

    }
    connectWebSocket();

})();