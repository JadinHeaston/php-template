document.addEventListener('DOMContentLoaded', async function (event) {
	initializeListeners();
	initSelect2Inputs();

	// let table = createTable({
	// 	'data': await getTableAsArray(document.getElementById('table') as HTMLTableElement),
	// 	state: undefined,
	// 	onStateChange: function (updater: Updater<TableState>): void {
	// 		throw new Error('Function not implemented.');
	// 	},
	// 	getCoreRowModel: function (table: Table<any>): () => RowModel<any> {
	// 		throw new Error('Function not implemented.');
	// 	},
	// 	columns: [],
	// 	renderFallbackValue: undefined
	// });
});

async function initializeListeners(): Promise<void> {
	// document.getElementById('change-status')?.addEventListener('click', changeStatus, { passive: true });
	// document.getElementById('get-status').addEventListener('click', refreshData, { passive: true });
	// document.getElementById('main-menu-button').addEventListener('click', changeMainMenuState, { passive: true });
	// document.getElementById('login-menu-toggle').addEventListener('click', changeLoginMenuState, { passive: true });
	// document.getElementById('logout-button').addEventListener('click', logout, { passive: true });
	// document.getElementById('credential-submission-form').addEventListener('submit', authenticate, { passive: false }); //Form submission.
	// document.getElementById('theme-toggle').addEventListener('click', flipColorScheme, { passive: true });
}

// async function getTableAsArray(table: HTMLTableElement) {
// 	const tableData: object[] = [];
// 	const headers: string[] = [];

// 	// Get column headers from the first row (thead)
// 	const headerRow = table.querySelector('thead tr');
// 	if (headerRow) {
// 		headerRow.querySelectorAll('th').forEach((header) => {
// 			headers.push(header.textContent || '');
// 		});
// 	}

// 	// Iterate over the table body rows and convert them to objects
// 	const bodyRows = table.querySelectorAll('tbody tr');
// 	bodyRows.forEach((row) => {
// 		const rowData: any = {}; // Using 'any' here for simplicity, you can define an interface for your data structure

// 		// Iterate over each cell in the row and populate the rowData object
// 		row.querySelectorAll('td').forEach((cell, index) => {
// 			rowData[headers[index]] = cell.textContent || '';
// 		});

// 		tableData.push(rowData);
// 	});

// 	return tableData;
// }

//Initializes all select2 question inputs.
async function initSelect2Inputs() {
	var select2Inputs = document.querySelectorAll('select.select2');
	select2Inputs.forEach((element) => {
		if (element.getAttribute('data-query-id') !== null) {
			jQuery(element).select2({
				ajax: {
					cache: true,
					dataType: 'json',
					delay: 250,
					url: '/SecTrack2/Admin/includes/search.php',
					type: "POST",
					data: function (params) {
						if (params.term === undefined)
							params.term = "";

						var query = {
							Type: 'select-query',
							searchTerm: params.term,
							attributeTerm: jQuery(element).attr('data-department-code'),
							ID: jQuery(element).attr('data-query-id'),
							tags: jQuery(element).attr('data-tags')
						}

						// Query parameters will be ?type=select2Query&searchTerm=[term]&ID=[ID]
						return query;
					}
				},
				placeholder: jQuery(element).attr('placeholder')
			});

			//Attaching search listener.
			// jQuery(element).on('change.select2', performSearch);
		}
		else
			jQuery(element).select2();

		//Manually focusing the search field when opened.
		jQuery(element).on('select2:open', () => {
			let select = document.querySelector('.select2-search__field') as HTMLSelectElement;
			select.focus();
		});
	});
}