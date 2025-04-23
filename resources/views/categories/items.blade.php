<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $category->name }} - Items</title>
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
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                @if($filter === 'borrowed')
                    <span class="text-red-600">Borrowed</span> Items in Category: <span class="text-indigo-600">{{ $category->name }}</span>
                @elseif($filter === 'available')
                    <span class="text-orange-600">Available</span> Items in Category: <span class="text-indigo-600">{{ $category->name }}</span>
                @else
                    Items in Category: <span class="text-indigo-600">{{ $category->name }}</span>
                @endif
            </h1>
            <div class="flex gap-2">
                @if($filter === 'borrowed')
                    <a href="{{ route('categories.items', $category) }}" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-4 py-2 rounded">
                        Show All Items
                    </a>
                    <a href="{{ route('categories.items', ['category' => $category, 'filter' => 'available']) }}" class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-4 py-2 rounded">
                        Show Available Only
                    </a>
                @elseif($filter === 'available')
                    <a href="{{ route('categories.items', $category) }}" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-4 py-2 rounded">
                        Show All Items
                    </a>
                    <a href="{{ route('categories.items', ['category' => $category, 'filter' => 'borrowed']) }}" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded">
                        Show Borrowed Only
                    </a>
                @else
                    <a href="{{ route('categories.items', ['category' => $category, 'filter' => 'borrowed']) }}" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded">
                        Show Borrowed Only
                    </a>
                    <a href="{{ route('categories.items', ['category' => $category, 'filter' => 'available']) }}" class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-4 py-2 rounded">
                        Show Available Only
                    </a>
                @endif
                <a href="{{ url()->previous() }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    &larr; Back
                </a>
                <a href="{{ route('filament.admin.resources.categories.index') }}" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded">
                    Back to Categories
                </a>
            </div>
        </div>

        @if($category->description)
            <div class="bg-white p-4 mb-6 rounded shadow">
                <h2 class="text-lg font-semibold mb-2">Category Description</h2>
                <p class="text-gray-700">{{ $category->description }}</p>
            </div>
        @endif

        <div class="bg-white rounded shadow overflow-hidden">
            @if($items->count() > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
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
                                    <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                    @if($item->description)
                                        <div class="text-sm text-gray-500">{{ Str::limit($item->description, 50) }}</div>
                                    @endif
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
                                        @if($item->isCurrentlyLoaned() || $item->status === 'borrowed')
                                            bg-yellow-100 text-yellow-800
                                        @else
                                            {{ $item->status === 'available' ? 'bg-green-100 text-green-800' : 
                                               ($item->status === 'under_repair' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}
                                        @endif">
                                        @if($item->isCurrentlyLoaned() || $item->status === 'borrowed')
                                            Borrowed
                                        @else
                                            {{ ucfirst($item->status) }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    Individual Item
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
                <div class="px-6 py-4">
                    {{ $items->appends(['filter' => $filter])->links() }}
                </div>
            @else
                <div class="p-6 text-center text-gray-500">
                    No items found in this category.
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