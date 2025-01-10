
const debounce = (func, delay) => {
	let timeoutId;
	return ((...args) => {
		clearTimeout(timeoutId);
		timeoutId = window.setTimeout(() => {
			func.apply(null, args);
		}, delay);
	});
};
