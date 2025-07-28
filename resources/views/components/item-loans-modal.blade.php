<div class="space-y-6">
    <!-- Item Information Header -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="flex items-center space-x-4">
            @if($item->thumbnail)
                <img src="{{ Storage::url($item->thumbnail) }}" alt="{{ $item->name }}" class="w-16 h-16 object-cover rounded-lg">
            @else
                <div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            @endif
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $item->name }}</h3>
                <p class="text-sm text-gray-600">{{ $item->category->name ?? 'No Category' }}</p>
                @if($item->serial_number)
                    <p class="text-xs text-gray-500">SN: {{ $item->serial_number }}</p>
                @endif
                <div class="mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        @if($item->status === 'available') bg-green-100 text-green-800
                        @elseif($item->status === 'borrowed') bg-yellow-100 text-yellow-800
                        @elseif($item->status === 'under_repair') bg-gray-100 text-gray-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($item->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Loans Table -->
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrower</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <a href="{{ route('filament.admin.resources.loans.view', $loan) }}" class="text-blue-600 hover:text-blue-900">
                                {{ $loan->loan_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($loan->borrower_type && $loan->borrower_id)
                                {{ $loan->borrower?->name ?? 'Unknown' }}
                                @if($loan->borrower_type === 'App\\Models\\GuestBorrower')
                                    <span class="text-xs text-gray-500">[Guest]</span>
                                @endif
                            @else
                                {{ $loan->getBorrowerName() }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $loan->department?->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $loan->loan_date?->format('M j, Y') ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $loan->due_date?->format('M j, Y') ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($loan->status === 'pending') bg-gray-100 text-gray-800
                                @elseif($loan->status === 'active') bg-green-100 text-green-800
                                @elseif($loan->status === 'overdue') bg-red-100 text-red-800
                                @elseif($loan->status === 'returned') bg-blue-100 text-blue-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $loanItem = $loan->items()->where('item_id', $item->id)->first();
                                $itemStatus = $loanItem?->pivot?->status ?? 'N/A';
                                $returnedAt = $loanItem?->pivot?->returned_at;
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($itemStatus === 'loaned') bg-yellow-100 text-yellow-800
                                @elseif($itemStatus === 'returned') bg-green-100 text-green-800
                                @elseif($itemStatus === 'damaged') bg-red-100 text-red-800
                                @elseif($itemStatus === 'lost') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($itemStatus) }}
                                @if($returnedAt)
                                    <span class="ml-1 text-xs">({{ $returnedAt->format('M j') }})</span>
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <!-- View Loan Button -->
                            <a href="{{ route('filament.admin.resources.loans.view', $loan) }}" 
                               class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                View
                            </a>
                            
                            <!-- Return Item Button (only for active loans where item is not returned) -->
                            @if($loan->status !== 'returned' && $loan->status !== 'canceled' && $itemStatus === 'loaned')
                                <button onclick="returnItemFromModal({{ $loan->id }}, {{ $item->id }})" 
                                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Return
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Return Item Modal (hidden by default) -->
    <div id="returnItemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Return Item</h3>
                <form id="returnItemForm" class="space-y-4">
                    <input type="hidden" id="returnLoanId" name="loan_id">
                    <input type="hidden" id="returnItemId" name="item_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Condition Tags</label>
                        <select id="conditionTags" name="condition_tags[]" multiple class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <optgroup label="✅ Good Condition">
                                <option value="returned-no-issues">Returned with no issues</option>
                                <option value="fully-functional">Fully functional</option>
                                <option value="clean-and-intact">Clean and intact</option>
                            </optgroup>
                            <optgroup label="🧩 Missing Parts">
                                <option value="missing-accessories">Missing accessories</option>
                                <option value="missing-components">Missing components</option>
                                <option value="incomplete-set">Incomplete set</option>
                                <option value="missing-manual-or-packaging">Missing manual or packaging</option>
                            </optgroup>
                            <optgroup label="🔨 Physical Damage">
                                <option value="damaged-cracked">Cracked</option>
                                <option value="damaged-dented">Dented</option>
                                <option value="broken-screen">Broken screen</option>
                                <option value="structural-damage">Structural damage</option>
                            </optgroup>
                            <optgroup label="🛠 Needs Repair">
                                <option value="non-functional">Non-functional</option>
                                <option value="requires-maintenance">Requires maintenance</option>
                                <option value="battery-issues">Battery issues</option>
                            </optgroup>
                            <optgroup label="🧼 Sanitation Issues">
                                <option value="dirty-needs-cleaning">Dirty, needs cleaning</option>
                                <option value="contaminated">Contaminated</option>
                                <option value="odor-present">Odor present</option>
                            </optgroup>
                            <optgroup label="⚠️ Other Conditions">
                                <option value="label-or-seal-removed">Label/seal removed</option>
                                <option value="unauthorized-modification">Unauthorized modification</option>
                                <option value="returned-late">Returned late</option>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Return Notes</label>
                        <textarea id="returnNotes" name="return_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Add any additional notes about the item's condition"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReturnModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Return Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function returnItemFromModal(loanId, itemId) {
    document.getElementById('returnLoanId').value = loanId;
    document.getElementById('returnItemId').value = itemId;
    document.getElementById('returnItemModal').classList.remove('hidden');
}

function closeReturnModal() {
    document.getElementById('returnItemModal').classList.add('hidden');
    document.getElementById('returnItemForm').reset();
}

document.getElementById('returnItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loanId = formData.get('loan_id');
    const itemId = formData.get('item_id');
    const conditionTags = Array.from(document.getElementById('conditionTags').selectedOptions).map(option => option.value);
    const returnNotes = formData.get('return_notes');
    
    // Send AJAX request to return the item
    fetch(`/admin/loans/${loanId}/return-item`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId,
            condition_tags: conditionTags,
            return_notes: returnNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert('Item returned successfully!');
            // Close modal and refresh the page
            closeReturnModal();
            window.location.reload();
        } else {
            alert('Error returning item: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error returning item. Please try again.');
    });
});

// Close modal when clicking outside
document.getElementById('returnItemModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeReturnModal();
    }
});
</script> 