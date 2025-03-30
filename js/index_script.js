document.addEventListener('DOMContentLoaded', function() {
    // Table pagination functionality
    const rowsPerPage = 5;
    const table = document.getElementById('inventoryTable');
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const totalEntries = rows.length;
    const totalPages = Math.ceil(totalEntries / rowsPerPage);
    let currentPage = 1;
    
    const startEntry = document.getElementById('startEntry');
    const endEntry = document.getElementById('endEntry');
    const totalEntriesSpan = document.getElementById('totalEntries');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    totalEntriesSpan.textContent = totalEntries;
    
    function updatePagination() {
        const start = (currentPage - 1) * rowsPerPage + 1;
        const end = Math.min(currentPage * rowsPerPage, totalEntries);
        
        startEntry.textContent = start;
        endEntry.textContent = end;
        
        // Show/hide rows
        rows.forEach((row, index) => {
            if (index >= start - 1 && index < end) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Update button states
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages;
    }
    
    prevBtn.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            updatePagination();
        }
    });
    
    nextBtn.addEventListener('click', function() {
        if (currentPage < totalPages) {
            currentPage++;
            updatePagination();
        }
    });
    
    // Initialize pagination
    updatePagination();
    
    // Add event listeners for edit and delete buttons
    const editButtons = document.querySelectorAll('.btn-edit');
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const itemName = row.cells[1].textContent;
            alert(`Edit functionality for ${itemName} would go here`);
        });
    });
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const itemName = row.cells[1].textContent;
            if (confirm(`Are you sure you want to delete ${itemName}?`)) {
                row.remove();
                // Update pagination after deletion
                updatePagination();
            }
        });
    });
});

document.querySelector('.back-btn').addEventListener('click', () => {

    window.location.href = 'Dashboard.php';

});


// Get all menu items (li elements)
const menuItems = document.querySelectorAll('.menu-items li');

// Add click event listener to each menu item
menuItems.forEach(item => {
  item.addEventListener('click', function() {
    // Remove 'active' class from all items
    menuItems.forEach(i => i.classList.remove('active'));
    
    // Add 'active' class to the clicked item
    this.classList.add('active');
  });
});

// Optional: Automatically set the active link on page load based on the current URL
const currentPage = window.location.pathname.split('/').pop(); // Get current page file name

menuItems.forEach(item => {
  const link = item.querySelector('a');
  if (link && link.href.includes(currentPage)) {
    item.classList.add('active');
  }
});



