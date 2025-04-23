<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $item->name }} - Item Details</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header with Back Button -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Item Details</h1>
            <div>
                <a href="{{ url()->previous() }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    &larr; Back
                </a>
            </div>
        </div>

        <!-- Item Header with Name and Status -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
            <div class="flex justify-between items-center p-6 bg-indigo-50 border-b border-indigo-100">
                <h2 class="text-xl font-bold text-indigo-800">{{ $item->name }}</h2>
                <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full 
                    {{ $item->status === 'available' ? 'bg-green-100 text-green-800' : 
                      ($item->status === 'borrowed' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                    {{ ucfirst($item->status) }}
                </span>
            </div>

            <!-- Item Details -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="font-medium text-gray-700 mb-2 border-b pb-2">Basic Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Category</p>
                                <p class="font-medium">{{ $item->category->name ?? 'Not categorized' }}</p>
                            </div>
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Type</p>
                                <p class="font-medium">Individual Item</p>
                            </div>
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Serial Number</p>
                                <p class="font-medium">{{ $item->serial_number ?? 'N/A' }}</p>
                            </div>
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Asset Tag</p>
                                <p class="font-medium">{{ $item->asset_tag ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-700 mb-2 border-b pb-2">Purchase Information</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Purchase Date</p>
                                <p class="font-medium">{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : 'N/A' }}</p>
                            </div>
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Purchase Cost</p>
                                <p class="font-medium">{{ $item->purchase_cost ? '$'.number_format($item->purchase_cost, 2) : 'N/A' }}</p>
                            </div>
                            <div class="py-2">
                                <p class="text-sm text-gray-500">Warranty Expiry</p>
                                <p class="font-medium">{{ $item->warranty_expiry ? $item->warranty_expiry->format('Y-m-d') : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($item->description)
                    <div class="mb-6">
                        <h3 class="font-medium text-gray-700 mb-2 border-b pb-2">Description</h3>
                        <p class="text-gray-600 py-2">{{ $item->description }}</p>
                    </div>
                @endif

                @if($item->loans && $item->loans->count() > 0)
                    <div class="mb-6">
                        <h3 class="font-medium text-gray-700 mb-2 border-b pb-2">Current Loan Information</h3>
                        <div class="overflow-x-auto mt-2">
                            <table class="min-w-full bg-white border border-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Loan Number</th>
                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Borrower</th>
                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Due Date</th>
                                        <th class="px-4 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($item->loans as $loan)
                                        <tr>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $loan->loan_number }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $loan->getBorrowerName() }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $loan->due_date ? $loan->due_date->format('Y-m-d') : 'N/A' }}</td>
                                            <td class="px-4 py-2 whitespace-nowrap">
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
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
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                    @if($item->status === 'available')
                        <a href="{{ route('loan.item', $item) }}" 
                          class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition-colors">
                            Create Loan
                        </a>
                    @endif
                    <a href="{{ route('filament.admin.resources.items.edit', $item) }}" 
                      class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition-colors">
                        Edit Item
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 