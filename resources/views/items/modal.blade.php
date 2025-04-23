<div class="p-6">
    <div class="flex justify-between items-start mb-4">
        <h3 class="text-xl font-bold text-gray-900">{{ $item->name }}</h3>
        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full 
            @if($item->isCurrentlyLoaned())
                bg-yellow-100 text-yellow-800
            @else
                        {{ $item->status === 'available' ? 'bg-green-100 text-green-800' :
                ($item->status === 'under_repair' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}
            @endif">
            @if($item->isCurrentlyLoaned())
                Borrowed
            @else
                {{ ucfirst($item->status) }}
            @endif
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            @if($item->thumbnail)
                <img src="{{ Storage::url($item->thumbnail) }}" alt="{{ $item->name }}"
                    class="w-full h-48 object-cover rounded-lg shadow-sm mb-4">
            @else
                <div class="w-full h-48 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                    <span class="text-gray-400">No image available</span>
                </div>
            @endif

            <h4 class="font-medium text-gray-700 mb-2">Basic Information</h4>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Category</p>
                        <p class="font-medium">{{ $item->category->name ?? 'Not categorized' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Type</p>
                        <p class="font-medium">Individual Item</p>
                    </div>
                    @if($item->serial_number)
                        <div>
                            <p class="text-sm text-gray-500">Serial Number</p>
                            <p class="font-medium">{{ $item->serial_number }}</p>
                        </div>
                    @endif
                    @if($item->asset_tag)
                        <div>
                            <p class="text-sm text-gray-500">Asset Tag</p>
                            <p class="font-medium">{{ $item->asset_tag }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div>
            <h4 class="font-medium text-gray-700 mb-2">Purchase Information</h4>
            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Purchase Date</p>
                        <p class="font-medium">
                            {{ $item->purchase_date ? $item->purchase_date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Purchase Cost</p>
                        <p class="font-medium">
                            {{ $item->purchase_cost ? '$' . number_format($item->purchase_cost, 2) : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Warranty Expiry</p>
                        <p class="font-medium">
                            {{ $item->warranty_expiry ? $item->warranty_expiry->format('M d, Y') : 'N/A' }}</p>
                    </div>
                </div>
            </div>

            @if($item->description)
                <h4 class="font-medium text-gray-700 mb-2">Description</h4>
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <p class="text-gray-600">{{ $item->description }}</p>
                </div>
            @endif

            @if($item->loans && $item->loans->count() > 0)
                <h4 class="font-medium text-gray-700 mb-2">Current Loan</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Borrower</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($item->loans as $loan)
                                            <tr>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">{{ $loan->getBorrowerName() }}
                                                </td>
                                                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $loan->due_date ? $loan->due_date->format('M d, Y') : 'N/A' }}</td>
                                                <td class="px-3 py-2 whitespace-nowrap">
                                                    <span
                                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        {{ $loan->status === 'active' ? 'bg-green-100 text-green-800' :
                                ($loan->status === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800') }}">
                                                        {{ ucfirst($loan->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
        <button @click="itemModal = false"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition-colors">
            Close
        </button>
        <a href="{{ route('items.show', $item) }}"
            class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors">
            View Details
        </a>
        @if(!$item->isCurrentlyLoaned() && $item->status === 'available')
            <a href="{{ route('loan.item', $item) }}"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                Create Loan
            </a>
        @endif
    </div>
</div>