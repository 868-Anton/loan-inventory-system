<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create New Item</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header with Back Button -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Create New Item</h1>
            <div>
                <a href="{{ route('items.index') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded">
                    &larr; Back to Items
                </a>
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded">
                        <h3 class="font-bold text-red-800 mb-2">Please fix the following errors:</h3>
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information Section -->
                        <div class="space-y-6">
                            <h2 class="text-lg font-medium text-gray-700 border-b pb-2">Basic Information</h2>
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="category_id" class="block text-sm font-medium text-gray-700">Category</label>
                                <select name="category_id" id="category_id" 
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Select Category --</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select name="status" id="status" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : ($status === 'available' ? 'selected' : '') }}>
                                            {{ ucfirst(str_replace('_', ' ', $status)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea name="description" id="description" rows="4"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <!-- Additional Information Section -->
                        <div class="space-y-6">
                            <h2 class="text-lg font-medium text-gray-700 border-b pb-2">Item Details</h2>
                            
                            <div>
                                <label for="serial_number" class="block text-sm font-medium text-gray-700">Serial Number</label>
                                <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="asset_tag" class="block text-sm font-medium text-gray-700">Asset Tag</label>
                                <input type="text" name="asset_tag" id="asset_tag" value="{{ old('asset_tag') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="purchase_date" class="block text-sm font-medium text-gray-700">Purchase Date</label>
                                <input type="date" name="purchase_date" id="purchase_date" value="{{ old('purchase_date') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="purchase_cost" class="block text-sm font-medium text-gray-700">Purchase Cost ($)</label>
                                <input type="number" name="purchase_cost" id="purchase_cost" value="{{ old('purchase_cost') }}" step="0.01" min="0"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="warranty_expiry" class="block text-sm font-medium text-gray-700">Warranty Expiry Date</label>
                                <input type="date" name="warranty_expiry" id="warranty_expiry" value="{{ old('warranty_expiry') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="thumbnail" class="block text-sm font-medium text-gray-700">Item Image</label>
                                <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
                                    class="mt-1 block w-full py-2 px-3">
                                <p class="mt-1 text-sm text-gray-500">Upload an image of the item (optional). Max size: 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-5 border-t border-gray-200">
                        <div class="flex justify-end">
                            <a href="{{ route('items.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 