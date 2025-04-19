<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Inventory Items</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .item-row {
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .item-row:hover {
            background-color: rgba(251, 191, 36, 0.2); /* #fbbf24 with 20% opacity */
            border-radius: 0.25rem;
        }
        /* Make sure buttons stay clickable and don't trigger the row click */
        .item-row-action {
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body class="bg-gray-100" x-data="{ itemModal: false, modalContent: '' }">
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Inventory Items</h1>
            <div>
                <a href="{{ route('filament.admin.resources.items.create') }}" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded transition-colors">
                    Create New Item
                </a>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="bg-white p-4 rounded shadow mb-6">
            <form action="{{ route('items.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                           class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                           placeholder="Search by name, serial number or asset tag">
                </div>
                <div class="w-full md:w-48">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="status" 
                            class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="borrowed" {{ request('status') == 'borrowed' ? 'selected' : '' }}>Borrowed</option>
                        <option value="under_repair" {{ request('status') == 'under_repair' ? 'selected' : '' }}>Under Repair</option>
                        <option value="lost" {{ request('status') == 'lost' ? 'selected' : '' }}>Lost</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" id="category_id" 
                            class="w-full rounded border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Filter
                    </button>
                </div>
            </form>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded shadow overflow-hidden">
            @if($items->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial/Asset Tag</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($items as $item)
                            <tr class="item-row" onclick="viewItem({{ $item->id }})">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($item->thumbnail)
                                            <div class="flex-shrink-0 h-10 w-10 mr-4">
                                                <img class="h-10 w-10 rounded-full object-cover" 
                                                     src="{{ Storage::url($item->thumbnail) }}" 
                                                     alt="{{ $item->name }}">
                                            </div>
                                        @else
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-4">
                                                <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                            @if($item->description)
                                                <div class="text-sm text-gray-500">{{ Str::limit($item->description, 40) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item->category->name ?? 'Uncategorized' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $item->serial_number ?? 'N/A' }}
                                    </div>
                                    @if($item->asset_tag)
                                        <div class="text-xs text-gray-500">Tag: {{ $item->asset_tag }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($item->isCurrentlyLoaned())
                                            bg-yellow-100 text-yellow-800
                                        @else
                                            {{ $item->status === 'available' ? 'bg-green-100 text-green-800' : 
                                               ($item->status === 'under_repair' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}
                                        @endif">
                                        @if($item->isCurrentlyLoaned())
                                            Borrowed
                                        @else
                                            {{ ucfirst(str_replace('_', ' ', $item->status)) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item->total_quantity }} unit(s)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" onclick="event.stopPropagation()">
                                    <a 
                                        href="{{ route('items.show', $item) }}"
                                        class="item-row-action text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-md transition-colors"
                                    >
                                        View
                                    </a>
                                    @if(!$item->isCurrentlyLoaned() && $item->status === 'available')
                                        <a href="{{ route('loan.item', $item) }}" 
                                           class="item-row-action ml-3 px-3 py-1 bg-green-100 hover:bg-green-200 text-green-800 rounded-md">
                                            Loan
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-4 bg-gray-50">
                    {{ $items->withQueryString()->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    No items found matching your criteria.
                </div>
            @endif
        </div>
    </div>

    <!-- Item Details Modal -->
    <div
        x-show="itemModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <!-- Modal Backdrop -->
        <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="itemModal = false"
        ></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen">
            <div
                class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto"
                @click.outside="itemModal = false"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
            >
                <!-- Close Button -->
                <button
                    @click="itemModal = false"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-500"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Modal Body (will be filled dynamically) -->
                <div x-html="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        function viewItem(itemId) {
            // Fetch item details
            fetch(`/items/${itemId}/view`)
                .then(response => response.json())
                .then(data => {
                    // Set modal content
                    Alpine.store('modalData', data);
                    document.querySelector('[x-data]').__x.$data.modalContent = data.html;
                    document.querySelector('[x-data]').__x.$data.itemModal = true;
                })
                .catch(error => {
                    console.error('Error fetching item details:', error);
                    alert('Failed to load item details. Please try again.');
                });
        }
    </script>
</body>
</html> 