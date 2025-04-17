/**
 * Enhanced Table Features for Filament Tables
 *
 * This script adds:
 * - Drag-and-drop column reordering with localStorage persistence
 * - Enhanced column visibility toggle UI
 */

document.addEventListener("DOMContentLoaded", function () {
    initTableEnhancements();
});

// Initialize when Livewire updates the DOM
document.addEventListener("livewire:navigated", function () {
    initTableEnhancements();
});

document.addEventListener("livewire:initialized", function () {
    initTableEnhancements();
});

/**
 * Initialize table enhancements for all Filament tables
 */
function initTableEnhancements() {
    const tables = document.querySelectorAll(".fi-ta-table");

    tables.forEach((table, tableIndex) => {
        // Get table identifier for storage
        const tableId = getTableIdentifier(table, tableIndex);

        // Initialize column drag-and-drop
        initColumnDragDrop(table, tableId);

        // Enhance column visibility toggle
        enhanceColumnVisibilityToggle(table, tableId);

        // Restore column visibility from localStorage
        restoreColumnVisibility(table, tableId);
    });
}

/**
 * Get a unique identifier for the table
 */
function getTableIdentifier(table, fallbackIndex) {
    // Try to find a table identifier from data attributes or nearby elements
    const resource = table.closest("[data-resources-name]")?.dataset
        .resourcesName;
    const page = window.location.pathname.split("/").filter(Boolean).join("-");

    return resource ? `${resource}-${page}` : `table-${fallbackIndex}-${page}`;
}

/**
 * Initialize drag-and-drop functionality for table columns
 */
function initColumnDragDrop(table, tableId) {
    const headerRow = table.querySelector("thead tr");
    if (!headerRow) return;

    const headers = Array.from(headerRow.querySelectorAll("th"));
    const storageKey = `table-column-order-${tableId}`;

    // Make headers draggable
    headers.forEach((header, index) => {
        if (!header.classList.contains("draggable-header")) {
            header.classList.add("draggable-header");
            header.draggable = true;
            header.dataset.originalIndex = index;

            header.addEventListener("dragstart", (e) => {
                e.dataTransfer.setData("text/plain", index);
                header.classList.add("dragging");
            });

            header.addEventListener("dragover", (e) => {
                e.preventDefault();
            });

            header.addEventListener("drop", (e) => {
                e.preventDefault();
                const sourceIndex = e.dataTransfer.getData("text/plain");
                moveColumn(table, parseInt(sourceIndex), index);
                saveColumnOrder(table, tableId);
            });

            header.addEventListener("dragend", () => {
                header.classList.remove("dragging");
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
    const rows = Array.from(table.querySelectorAll("tr"));

    rows.forEach((row) => {
        const cells = Array.from(row.children);

        if (
            sourceIndex !== targetIndex &&
            cells.length > Math.max(sourceIndex, targetIndex)
        ) {
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
    const headerCells = Array.from(table.querySelectorAll("thead th"));
    const columnOrder = headerCells.map((cell) => cell.dataset.originalIndex);

    localStorage.setItem(
        `table-column-order-${tableId}`,
        JSON.stringify(columnOrder)
    );
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
            for (
                let targetIndex = 0;
                targetIndex < columnOrder.length;
                targetIndex++
            ) {
                const sourceIndex = columnOrder.findIndex(
                    (index) => index == targetIndex
                );
                if (sourceIndex !== -1 && sourceIndex !== targetIndex) {
                    moveColumn(table, sourceIndex, targetIndex);
                }
            }
        } catch (e) {
            console.error("Error restoring column order:", e);
            localStorage.removeItem(storageKey);
        }
    }
}

/**
 * Save the current column visibility state to localStorage
 */
function saveColumnVisibility(table, tableId) {
    // Find all column toggle checkboxes
    const columnToggles = table
        .closest(".fi-ta")
        .querySelectorAll(
            '[wire\\:key$="-column-toggle-form"] input[type="checkbox"]'
        );

    if (columnToggles.length === 0) return;

    // Create an object mapping column names to visibility state
    const visibilityState = {};
    columnToggles.forEach((toggle) => {
        // Extract column name from the input name attribute
        // Format is typically tableColumnToggles[column_name]
        const matches = toggle.name.match(/tableColumnToggles\[(.*?)\]/);
        if (matches && matches[1]) {
            visibilityState[matches[1]] = toggle.checked;
        }
    });

    // Save to localStorage
    localStorage.setItem(
        `table-${tableId}-column-visibility`,
        JSON.stringify(visibilityState)
    );
}

/**
 * Restore column visibility from localStorage
 */
function restoreColumnVisibility(table, tableId) {
    const storageKey = `table-${tableId}-column-visibility`;
    const savedVisibility = localStorage.getItem(storageKey);

    if (!savedVisibility) return;

    try {
        const visibilityState = JSON.parse(savedVisibility);

        // Wait for Livewire to fully initialize the column toggle form
        setTimeout(() => {
            // Find all column toggle checkboxes
            const columnToggles = table
                .closest(".fi-ta")
                .querySelectorAll(
                    '[wire\\:key$="-column-toggle-form"] input[type="checkbox"]'
                );

            // Apply the saved visibility state
            columnToggles.forEach((toggle) => {
                const matches = toggle.name.match(
                    /tableColumnToggles\[(.*?)\]/
                );
                if (
                    matches &&
                    matches[1] &&
                    visibilityState.hasOwnProperty(matches[1])
                ) {
                    if (toggle.checked !== visibilityState[matches[1]]) {
                        // Programmatically click the checkbox to trigger Livewire events
                        toggle.checked = visibilityState[matches[1]];
                        toggle.dispatchEvent(
                            new Event("change", { bubbles: true })
                        );
                    }
                }
            });
        }, 500); // Give Livewire time to initialize
    } catch (e) {
        console.error("Error restoring column visibility:", e);
        localStorage.removeItem(storageKey);
    }
}

/**
 * Enhance the column visibility toggle with better UI
 */
function enhanceColumnVisibilityToggle(table, tableId) {
    // Find the column picker button
    const columnPickerButton = table
        .closest(".fi-ta")
        .querySelector(".fi-ta-col-picker");
    if (!columnPickerButton) return;

    // Add a tooltip
    if (!columnPickerButton.hasAttribute("title")) {
        columnPickerButton.setAttribute(
            "title",
            "Show/hide columns & drag headers to reorder"
        );
    }

    // Add a hint about drag & drop
    const menu = document.querySelector(".fi-dropdown-panel");
    if (menu && !menu.querySelector(".column-reorder-hint")) {
        const hint = document.createElement("div");
        hint.className =
            "column-reorder-hint text-sm text-gray-500 p-2 border-t";
        hint.innerHTML = "Tip: Drag table headers to reorder columns";
        menu.appendChild(hint);
    }

    // Add a reset button
    if (
        columnPickerButton &&
        !columnPickerButton.querySelector(".reset-columns-button")
    ) {
        const resetButton = document.createElement("button");
        resetButton.className =
            "reset-columns-button ml-2 text-sm text-red-500";
        resetButton.innerHTML = "Reset";
        resetButton.title = "Reset column order and visibility";

        resetButton.addEventListener("click", (e) => {
            e.stopPropagation();
            localStorage.removeItem(`table-column-order-${tableId}`);
            localStorage.removeItem(`table-${tableId}-column-visibility`);
            window.location.reload();
        });

        // Append to the column picker area
        const buttonContainer = columnPickerButton.closest(".fi-ta-options");
        if (buttonContainer) {
            buttonContainer.appendChild(resetButton);
        }
    }

    // Listen for changes to column visibility
    const observer = new MutationObserver((mutations) => {
        // Check if the column toggle form is available
        const toggleForm = table
            .closest(".fi-ta")
            .querySelector('[wire\\:key$="-column-toggle-form"]');

        if (toggleForm) {
            // Add change listeners to all checkboxes
            const checkboxes = toggleForm.querySelectorAll(
                'input[type="checkbox"]'
            );

            checkboxes.forEach((checkbox) => {
                if (!checkbox.dataset.visibilityListenerAdded) {
                    checkbox.dataset.visibilityListenerAdded = "true";
                    checkbox.addEventListener("change", () => {
                        // Allow some time for Livewire to update the DOM
                        setTimeout(
                            () => saveColumnVisibility(table, tableId),
                            200
                        );
                    });
                }
            });

            // Initial save
            saveColumnVisibility(table, tableId);
        }
    });

    // Observe the table container for column toggle form
    observer.observe(table.closest(".fi-ta"), {
        childList: true,
        subtree: true,
    });
}
