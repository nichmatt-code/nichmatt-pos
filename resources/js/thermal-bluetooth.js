// Prints plain-text receipt lines to a generic Bluetooth (BLE) thermal
// printer using raw ESC/POS commands via the Web Bluetooth API.
//
// Only Chrome/Edge on Android, Windows, macOS and Linux support this
// (navigator.bluetooth). Safari/iOS does not implement Web Bluetooth at all.
//
// Cheap generic 58mm/80mm thermal printer modules (the kind sold under many
// rebranded names, e.g. "PandaTM") overwhelmingly use one of a small set of
// OEM Bluetooth LE modules. We try their known service/characteristic UUIDs
// in order since Web Bluetooth requires declaring services up front.
const SERVICE_CANDIDATES = [
    { service: '000018f0-0000-1000-8000-00805f9b34fb', characteristic: '00002af1-0000-1000-8000-00805f9b34fb' },
    { service: '0000ff00-0000-1000-8000-00805f9b34fb', characteristic: '0000ff02-0000-1000-8000-00805f9b34fb' },
    { service: '49535343-fe7d-4ae5-8fa9-9fafd205e455', characteristic: '49535343-8841-43f4-a8d4-ecbe34729bb3' },
];

let cachedDevice = null;
let cachedCharacteristic = null;

function isSupported() {
    return typeof navigator !== 'undefined' && !!navigator.bluetooth;
}

async function findWritableCharacteristic(server) {
    for (const candidate of SERVICE_CANDIDATES) {
        try {
            const service = await server.getPrimaryService(candidate.service);
            const characteristic = await service.getCharacteristic(candidate.characteristic);

            return characteristic;
        } catch (e) {
            // This device doesn't expose this candidate service, try the next one.
        }
    }

    throw new Error('Printer ini menggunakan protokol Bluetooth yang belum dikenali aplikasi.');
}

async function connect() {
    const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: SERVICE_CANDIDATES.map((c) => c.service),
    });

    const server = await device.gatt.connect();
    const characteristic = await findWritableCharacteristic(server);

    cachedDevice = device;
    cachedCharacteristic = characteristic;

    device.addEventListener('gattserverdisconnected', () => {
        cachedDevice = null;
        cachedCharacteristic = null;
    });

    return characteristic;
}

async function getCharacteristic() {
    if (cachedDevice && cachedDevice.gatt.connected && cachedCharacteristic) {
        return cachedCharacteristic;
    }

    return connect();
}

function buildEscPosBytes(lines) {
    const encoder = new TextEncoder();
    const init = new Uint8Array([0x1b, 0x40]); // ESC @ — reset printer
    const feedAndCut = new Uint8Array([0x0a, 0x0a, 0x0a, 0x0a]); // feed a few lines for tear-off

    const bodyText = lines.join('\n') + '\n';
    const body = encoder.encode(bodyText);

    const bytes = new Uint8Array(init.length + body.length + feedAndCut.length);
    bytes.set(init, 0);
    bytes.set(body, init.length);
    bytes.set(feedAndCut, init.length + body.length);

    return bytes;
}

async function writeInChunks(characteristic, bytes) {
    const chunkSize = 180;
    const write = characteristic.properties.writeWithoutResponse
        ? (chunk) => characteristic.writeValueWithoutResponse(chunk)
        : (chunk) => characteristic.writeValue(chunk);

    for (let offset = 0; offset < bytes.length; offset += chunkSize) {
        const chunk = bytes.slice(offset, offset + chunkSize);
        await write(chunk);
        await new Promise((resolve) => setTimeout(resolve, 20));
    }
}

async function printLines(lines) {
    if (!isSupported()) {
        throw new Error('Browser ini tidak mendukung Bluetooth (Web Bluetooth API).');
    }

    const characteristic = await getCharacteristic();
    const bytes = buildEscPosBytes(lines);
    await writeInChunks(characteristic, bytes);
}

window.NichmattThermalPrinter = { isSupported, printLines };
