<script>
    /**
     * Enhanced Table Features for Filament Tables
     */
    document.addEventListener('DOMContentLoaded', function() {
        initTableEnhancements();
    });

    // Initialize when Livewire updates the DOM
    document.addEventListener('livewire:navigated', function() {
        initTableEnhancements();
    });

    document.addEventListener('livewire:initialized', function() {
        initTableEnhancements();
    });

    /**
     * Initialize table enhancements for all Filament tables
     */
    function initTableEnhancements() {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach((table, tableIndex) => {
            // Get table identifier for storage
            const tableId = getTableIdentifier(table, tableIndex);
            
            // Initialize column drag-and-drop
            initColumnDragDrop(table, tableId);
            
            // Enhance column visibility toggle
            enhanceColumnVisibilityToggle(table, tableId);
        });
    }

    /**
     * Get a unique identifier for the table
     */
    function getTableIdentifier(table, fallbackIndex) {
        // Try to find a table identifier from data attributes or nearby elements
        const resource = table.closest('[data-resources-name]')?.dataset.resourcesName;
        const page = window.location.pathname.split('/').filter(Boolean).join('-');
        
        return resource ? `${resource}-${page}` : `table-${fallbackIndex}-${page}`;
    }

    /**
     * Initialize drag-and-drop functionality for table columns
     */
    function initColumnDragDrop(table, tableId) {
        const headerRow = table.querySelector('thead tr');
        if (!headerRow) return;
        
        const headers = Array.from(headerRow.querySelectorAll('th'));
        const storageKey = `table-column-order-${tableId}`;
        
        // Make headers draggable
        headers.forEach((header, index) => {
            if (!header.classList.contains('draggable-header')) {
                header.classList.add('draggable-header');
                header.draggable = true;
                header.dataset.originalIndex = index;
                
                header.addEventListener('dragstart', (e) => {
                    e.dataTransfer.setData('text/plain', index);
                    header.classList.add('dragging');
                });
                
                header.addEventListener('dragover', (e) => {
                    e.preventDefault();
                });
                
                header.addEventListener('drop', (e) => {
                    e.preventDefault();
                    const sourceIndex = e.dataTransfer.getData('text/plain');
                    moveColumn(table, parseInt(sourceIndex), index);
                    saveColumnOrder(table, tableId);
                });
                
                header.addEventListener('dragend', () => {
                    header.classList.remove('dragging');
                });
            }
        });
        
        // Restore column order from localStorage
        restoreColumnOrder(table, tableId);
    }

    /**
     * Move a column from source index to target index
     */
    function moveColumn(table, sourceIndex, targetIndex) {
        const rows = Array.from(table.querySelectorAll('tr'));
        
        rows.forEach(row => {
            const cells = Array.from(row.children);
            
            if (sourceIndex !== targetIndex && cells.length > Math.max(sourceIndex, targetIndex)) {
                const sourceCell = cells[sourceIndex];
                const targetCell = cells[targetIndex];
                
                if (sourceIndex < targetIndex) {
                    row.insertBefore(sourceCell, targetCell.nextSibling);
                } else {
                    row.insertBefore(sourceCell, targetCell);
                }
            }
        });
    }

    /**
     * Save the current column order to localStorage
     */
    function saveColumnOrder(table, tableId) {
        const headerCells = Array.from(table.querySelectorAll('thead th'));
        const columnOrder = headerCells.map(cell => cell.dataset.originalIndex);
        
        localStorage.setItem(`table-column-order-${tableId}`, JSON.stringify(columnOrder));
    }

    /**
     * Restore column order from localStorage
     */
    function restoreColumnOrder(table, tableId) {
        const storageKey = `table-column-order-${tableId}`;
        const savedOrder = localStorage.getItem(storageKey);
        
        if (savedOrder) {
            try {
                const columnOrder = JSON.parse(savedOrder);
                
                // Apply the saved order
                for (let targetIndex = 0; targetIndex < columnOrder.length; targetIndex++) {
                    const sourceIndex = columnOrder.findIndex(index => parseInt(index) === targetIndex);
                    if (sourceIndex !== -1 && sourceIndex !== targetIndex) {
                        moveColumn(table, sourceIndex, targetIndex);
                    }
                }
            } catch (e) {
                console.error('Error restoring column order:', e);
                localStorage.removeItem(storageKey);
            }
        }
    }

    /**
     * Enhance the column visibility toggle with better UI
     */
    function enhanceColumnVisibilityToggle(table, tableId) {
        // Find the column picker button
        const columnPickerButton = table.closest('.fi-ta').querySelector('.fi-ta-col-picker');
        if (!columnPickerButton) return;
        
        // Add a tooltip
        if (!columnPickerButton.hasAttribute('title')) {
            columnPickerButton.setAttribute('title', 'Show/hide columns & drag headers to reorder');
        }
        
        // Add a reset button
        if (columnPickerButton && !document.querySelector('.reset-columns-button')) {
            const resetButton = document.createElement('button');
            resetButton.className = 'reset-columns-button ml-2 text-xs text-red-500 px-2 py-1 border border-gray-300 rounded';
            resetButton.innerHTML = 'Reset Table';
            resetButton.title = 'Reset column order and visibility';
            
            resetButton.addEventListener('click', (e) => {
                e.stopPropagation();
                localStorage.removeItem(`table-column-order-${tableId}`);
                localStorage.removeItem(`table-${tableId}-column-visibility`);
                window.location.reload();
            });
            
            // Append to the column picker area
            const buttonContainer = columnPickerButton.closest('.fi-ta-options');
            if (buttonContainer) {
                buttonContainer.appendChild(resetButton);
            }
        }
    }
</script>

<style>
    /* Table enhancements */
    .draggable-header {
        cursor: grab;
        position: relative;
    }

    .draggable-header.dragging {
        cursor: grabbing;
        opacity: 0.8;
        background-color: rgba(243, 244, 246, 0.5);
    }

    .draggable-header::before {
        content: "⋮⋮";
        position: absolute;
        left: 4px;
        top: 50%;
        font-size: 10px;
        transform: translateY(-50%);
        color: #9ca3af;
        visibility: hidden;
    }

    .draggable-header:hover::before {
        visibility: visible;
    }

    .reset-columns-button:hover {
        background-color: #fee2e2;
        border-color: #ef4444;
    }
</style> 