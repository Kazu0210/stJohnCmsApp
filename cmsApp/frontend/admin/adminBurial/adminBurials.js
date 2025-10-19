$(document).ready(function() {
	$.ajax({
		url: '../../../../cms.api/fetchAdminBurials.php',
		method: 'GET',
		dataType: 'json',
		success: function(data) {
			console.log('Burial data:', data);
			populateBurialTable(data);
		},
		error: function(xhr, status, error) {
			console.error('Error fetching burial data:', error);
		}
	});

	function populateBurialTable(data) {
		var $tbody = $('#burialTableBody');
		$tbody.empty();
		if (!Array.isArray(data) || data.length === 0) {
			$tbody.append('<tr><td colspan="13" class="text-center">No records found.</td></tr>');
		} else {
			data.forEach(function(record) {
				$tbody.append(`
					<tr>
						<td>${record.deceasedName || ''}</td>
						<td>${record.burialDate || ''}</td>
						<td class="text-center">${record.deathCertificate ? `<a href="${record.deathCertificate}" target="_blank"><i class="fas fa-file-pdf text-primary"></i></a>` : '-'}</td>
						<td class="text-center">${record.burialPermit ? `<a href="${record.burialPermit}" target="_blank"><i class="fas fa-file-image text-primary"></i></a>` : '-'}</td>
						<td class="text-center">${record.deceasedValidId ? `<a href="${record.deceasedValidId}" target="_blank"><i class="fas fa-id-card text-primary"></i></a>` : '-'}</td>
						<td>${record.area || ''}</td>
						<td>${record.block || ''}</td>
						<td>${record.rowNumber || ''}</td>
						<td>${record.lotNumber || ''}</td>
						<td class="text-center">${record.lotType || ''}</td>
						<td>${record.createdAt || ''}</td>
						<td>${record.updatedAt || ''}</td>
						<td class="text-center">
							<button class="btn btn-sm btn-primary" data-id="${record.burialId}">View</button>
						</td>
					</tr>
				`);
			});
		}
		// Initialize or re-draw DataTable
		if ($.fn.DataTable.isDataTable('.table')) {
			$('.table').DataTable().clear().destroy();
		}
		$('.table').DataTable({
			order: [],
			responsive: true
		});
	}
});
