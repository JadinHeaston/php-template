
const debounce = (func, delay) => {
	let timeoutId;
	return ((...args) => {
		clearTimeout(timeoutId);
		timeoutId = window.setTimeout(() => {
			func.apply(null, args);
		}, delay);
	});
};

async function generateUUIDFromString(inputString) {
	// Convert the string to a Uint8Array
	const encoder = new TextEncoder();
	const data = encoder.encode(inputString);

	// Hash the input string using SHA-256
	const hashBuffer = await crypto.subtle.digest('SHA-256', data);

	// Convert the hash to a hexadecimal string
	const hashArray = Array.from(new Uint8Array(hashBuffer));
	let hashHex = hashArray.map(byte => byte.toString(16).padStart(2, '0')).join('');

	// Format the hash as a UUID (8-4-4-4-12 pattern)
	const uuid = `${hashHex.slice(0, 8)}-${hashHex.slice(8, 12)}-${hashHex.slice(12, 16)}-${hashHex.slice(16, 20)}-${hashHex.slice(20, 32)}`;

	return uuid;
}

async function generateUUID() {
	const arr = new Uint8Array(16);
	crypto.getRandomValues(arr);

	// Set version to 4 (UUID version 4)
	arr[6] = (arr[6] & 0x0f) | 0x40;
	// Set variant to RFC4122 (bits 6 and 7 of byte 8)
	arr[8] = (arr[8] & 0x3f) | 0x80;

	// Format the UUID string
	return arr.reduce((str, byte, index) => {
		str += (index === 4 || index === 6 || index === 8 || index === 10) ? '-' : '';
		str += byte.toString(16).padStart(2, '0');
		return str;
	}, '');
}