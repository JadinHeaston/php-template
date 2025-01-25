document.addEventListener("DOMContentLoaded", async function () {
	initializeListeners();
	initSelect2Inputs();
});

async function initializeListeners() {
}

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
