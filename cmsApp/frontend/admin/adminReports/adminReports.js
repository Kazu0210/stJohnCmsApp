document.addEventListener('DOMContentLoaded', () => {
    
    // Function to set all date inputs to the current date, for convenience
    const setDefaultDates = (form, isStart = true) => {
        const today = new Date().toISOString().split('T')[0];
        const inputId = isStart ? `${form.dataset.reportType}TimeframeStart` : `${form.dataset.reportType}TimeframeEnd`;
        const inputElement = form.querySelector(`#${inputId}`);
        if (inputElement) {
            inputElement.value = today;
        }
    };
    
    const handleReportGeneration = (event) => {
        event.preventDefault(); // Stop the form from traditional submission

        const form = event.target.closest('form');
        if (!form) return;

        const reportType = form.dataset.reportType;
        const button = event.target.closest('.generate-report-btn');
        if (!button) return;

        const format = button.dataset.format;
        
        // All forms now use a standard date range
        const startDate = form.querySelector(`#${reportType}TimeframeStart`).value;
        const endDate = form.querySelector(`#${reportType}TimeframeEnd`).value;

        if (!startDate || !endDate) {
            alert(`Please select both a Start Date and an End Date for the ${reportType.charAt(0).toUpperCase() + reportType.slice(1)} Report.`);
            return;
        }
        
        if (new Date(startDate) > new Date(endDate)) {
             alert('The Start Date cannot be after the End Date. Please check your date range.');
            return;
        }

        // Simulate API call and button disabling
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Generating...';

        console.log(`Requesting: ${reportType} report for range ${startDate} to ${endDate} in ${format} format.`);

        // --- Backend API Call ---
        fetch(`/stJohnCmsApp/cms.api/generateReport.php?type=${reportType}&start=${startDate}&end=${endDate}&format=${format}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error('Authentication required. Please log in again.');
                } else if (response.status === 403) {
                    throw new Error('Access denied. Admin or Secretary role required.');
                } else if (response.status === 400) {
                    throw new Error('Invalid request parameters. Please check your date range.');
                } else if (response.status === 500) {
                    throw new Error('Server error occurred while generating report.');
                } else {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
            }
            return response.json();
        })
        .then(data => {
            // Re-enable button and reset text
            button.disabled = false;
            button.innerHTML = `<i class="fas fa-file-${format === 'pdf' ? 'pdf' : 'excel'}"></i> ${format.toUpperCase()}`;
            
            if (data.status === 'success') {
                // Success feedback with more detailed information
                const reportName = reportType.charAt(0).toUpperCase() + reportType.slice(1);
                const totalRecords = data.metadata?.totalRecords || 0;
                const generatedAt = data.metadata?.generatedAt || 'Unknown';
                
                alert(`✅ ${reportName} Report (${format.toUpperCase()}) generated successfully!\n\n📊 Total Records: ${totalRecords}\n📅 Date Range: ${startDate} to ${endDate}\n⏰ Generated: ${generatedAt}\n\nNote: Report data is available in the browser console for review.`);
                
                // Display report data in console for debugging
                console.group(`📊 ${reportName} Report - ${format.toUpperCase()}`);
                console.log('Report Metadata:', data.metadata);
                console.log('Report Data:', data.data);
                
                if (data.data && data.data.summary) {
                    console.log('Report Summary:', data.data.summary);
                }
                console.groupEnd();
                
                // TODO: In production, implement actual file generation and download
                // For now, we'll show the data structure
                console.log(`💡 In production, this would generate a downloadable ${format.toUpperCase()} file.`);
                
            } else {
                throw new Error(data.message || 'Unknown error occurred while generating report');
            }
        })
        .catch(error => {
            // Re-enable button and reset text
            button.disabled = false;
            button.innerHTML = `<i class="fas fa-file-${format === 'pdf' ? 'pdf' : 'excel'}"></i> ${format.toUpperCase()}`;
            
            // Enhanced error feedback
            console.error('Report generation error:', error);
            
            const reportName = reportType.charAt(0).toUpperCase() + reportType.slice(1);
            alert(`❌ Error generating ${reportName} Report (${format.toUpperCase()}):\n\n${error.message}\n\nPlease check your date range and try again. If the problem persists, contact the system administrator.`);
        });
    };

    // Initialize listeners and optionally set default date for ALL date range forms
    document.querySelectorAll('[data-report-type]').forEach(form => {
        // Set default date range to last 30 days for convenience
        const today = new Date();
        const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
        
        const startInput = form.querySelector('input[type="date"]:first-of-type');
        const endInput = form.querySelector('input[type="date"]:last-of-type');
        
        if (startInput && endInput) {
            startInput.value = thirtyDaysAgo.toISOString().split('T')[0];
            endInput.value = today.toISOString().split('T')[0];
        }
        
        // Attach listener to all report buttons within the form
        form.querySelectorAll('.generate-report-btn').forEach(button => {
            button.addEventListener('click', handleReportGeneration);
        });
    });

    // ✅ FIXED LOGOUT FUNCTIONALITY (Ensures proper alert + redirect)
    const logoutLinks = document.querySelectorAll('#logoutLinkDesktop, #logoutLinkMobile');

    if (logoutLinks.length === 0) {
        console.warn("⚠️ No logout link found (IDs: #logoutLinkDesktop or #logoutLinkMobile). Add these elements to enable logout functionality.");
    }

    logoutLinks.forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            if (confirm('Are you sure you want to log out?')) {
                const redirectPath = link.getAttribute('href') && link.getAttribute('href') !== '#'
                    ? link.getAttribute('href')
                    : '../../frontend/auth/login/login.php'; // fallback
                alert('You have successfully logged out.');
                window.location.href = redirectPath;
            }
        });
    });
});
