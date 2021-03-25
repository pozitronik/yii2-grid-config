// $(function() {
// 	updateJSON('#visibleColumnsJson', '#visibleColumnsItems');
// });


function itemSerializer(serializedItem, sortableContainer) {
	return {
		// position: serializedItem.index,
		label: serializedItem.node.innerHTML
	}
}

function updateJSON(containerSelector, e) {
	$(containerSelector).val(JSON.stringify(sortable(e, 'serialize')[0].items));
}
