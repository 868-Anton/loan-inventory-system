<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name }} - Items</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                @if($filter === 'borrowed')
                    <span class="text-red-600">Borrowed</span> Items in Category: <span class="text-indigo-600">{{ $category->name }}</span>
                @else
                    Items in Category: <span class="text-indigo-600">{{ $category->name }}</span>
                @endif
            </h1>
            <div class="flex gap-2">
                @if($filter === 'borrowed')
                    <a href="{{ route('categories.items', $category) }}" class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-4 py-2 rounded">
                        Show All Items
                    </a>
                @else
                    <a href="{{ route('categories.items', ['category' => $category, 'filter' => 'borrowed']) }}" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded">
                        Show Borrowed Only
                    </a>
                @endif
                <a href="{{ url()->previous() }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    &larr; Back
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
                            <tr>
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
                                        {{ $item->status === 'available' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($item->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $item->total_quantity }} unit(s)
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="#" class="text-indigo-600 hover:text-indigo-900">View</a>
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
</body>
</html> 