if (document.readyState !== "loading") {
	setTimeout(onReady, 0);
} else {
	document.addEventListener("DOMContentLoaded", onReady);
}

async function onReady() {
	// console.log(Date()); //*** DEBUGGING

	//Contains small tweaks to help development. 
	//NEEDS to be defined first.
	developmentTweaks = false;

	Promise.all([
		initializeListeners(),
		initSelect2Inputs()
	]);
}

async function initializeListeners() {
}

/***
* Swaps in the body of error pages returned from htmx requests 
*/
document.addEventListener("htmx:beforeOnLoad", function (event) {
	// @ts-ignore
	const xhr = event.detail.xhr
	if (xhr.status == 500 || xhr.status == 403 || xhr.status == 404) {
		event.stopPropagation() // Tell htmx not to process these requests
		document.children[0].innerHTML = xhr.response // Swap in body of response instead
	}
});

document.addEventListener('htmx:beforeHistorySave', async function (event) {
	destroySelect2();
});

document.addEventListener('htmx:afterSettle', async function (event) {
	initSelect2Inputs();
});

document.addEventListener('htmx:historyRestore', async function (event) {
	initSelect2Inputs();
});

/*
* Initializes all select2 inputs.
*/
async function initSelect2Inputs() {
	document.querySelectorAll('select.select2').forEach(async (element) => {
		let dropdownParent = jQuery(element).closest('[popover]');
		if (dropdownParent.length === 0)
			dropdownParent = document.body;

		jQuery(element).select2({
			dropdownParent: dropdownParent
		});
	});
}

/*
* Destroys select2 inputs.
*/
async function destroySelect2() {
	document.querySelectorAll('select.select2').forEach(async (element) => {
		jQuery(element).select2('destroy');
	});
}
