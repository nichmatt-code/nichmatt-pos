// Prints plain-text receipt lines to a generic Bluetooth (BLE) thermal
// printer using raw ESC/POS commands via the Web Bluetooth API.
//
// Only Chrome/Edge on Android, Windows, macOS and Linux support this
// (navigator.bluetooth). Safari/iOS does not implement Web Bluetooth at all.
//
// Cheap generic 58mm/80mm thermal printer modules (the kind sold under many
// rebranded names, e.g. "PandaTM") overwhelmingly use one of a small set of
// OEM Bluetooth LE modules. We try their known service UUIDs in order since
// Web Bluetooth requires declaring services up front, then look for whatever
// writable characteristic that service exposes (exact characteristic UUIDs
// vary more than service UUIDs across OEM firmware builds).
const SERVICE_CANDIDATES = [
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2', // nRF51822-based generic printer module (confirmed present on the store's MPT-II printer)
    '000018f0-0000-1000-8000-00805f9b34fb', // generic ESC/POS BLE printer service
    '0000ff00-0000-1000-8000-00805f9b34fb', // GOOJPRT / Zjiang-style clones
    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC / Microchip transparent UART
    '6e400001-b5a3-f393-e0a9-e50e24dcca9e', // Nordic UART Service (very common in cheap BLE modules)
    '0000ae30-0000-1000-8000-00805f9b34fb', // "cat printer" style mini printer service
    '0000fff0-0000-1000-8000-00805f9b34fb', // HM-10 / HC-08 clone service
];

let cachedDevice = null;
let cachedCharacteristic = null;

function isSupported() {
    return typeof navigator !== 'undefined' && !!navigator.bluetooth;
}

async function findWritableCharacteristic(server) {
    for (const serviceUuid of SERVICE_CANDIDATES) {
        try {
            const service = await server.getPrimaryService(serviceUuid);
            const characteristics = await service.getCharacteristics();
            const writable = characteristics.find((c) => c.properties.write || c.properties.writeWithoutResponse);

            if (writable) {
                return writable;
            }
        } catch (e) {
            // This device doesn't expose this candidate service, try the next one.
        }
    }

    throw new Error(
        'Printer ini menggunakan protokol Bluetooth yang belum dikenali aplikasi. ' +
        'Buka chrome://bluetooth-internals di tab baru, hubungkan ke printer, dan kirim daftar UUID service/characteristic yang muncul.'
    );
}

async function connect() {
    const device = await navigator.bluetooth.requestDevice({
        acceptAllDevices: true,
        optionalServices: SERVICE_CANDIDATES,
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
