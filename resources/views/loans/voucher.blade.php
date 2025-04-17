<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Loan Voucher #{{ $loan->loan_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .loan-info {
            margin-bottom: 20px;
        }
        .loan-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .loan-info th {
            text-align: left;
            width: 150px;
            padding: 5px;
        }
        .loan-info td {
            padding: 5px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature-box {
            margin-top: 20px;
            border-bottom: 1px dashed #333;
            height: 60px;
        }
        .notes {
            margin-top: 30px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOAN VOUCHER</h1>
        <h2>Loan #{{ $loan->loan_number }}</h2>
    </div>
    
    <div class="loan-info">
        <table>
            <tr>
                <th>Borrower:</th>
                <td>{{ $borrower_name }}</td>
                <th>Email:</th>
                <td>{{ $borrower_email }}</td>
            </tr>
            <tr>
                <th>Department:</th>
                <td>{{ $loan->department?->name ?? 'N/A' }}</td>
                <th>Status:</th>
                <td>{{ ucfirst($loan->status) }}</td>
            </tr>
            <tr>
                <th>Loan Date:</th>
                <td>{{ $loan->loan_date->format('M d, Y') }}</td>
                <th>Due Date:</th>
                <td>{{ $loan->due_date->format('M d, Y') }}</td>
            </tr>
            @if($loan->return_date)
            <tr>
                <th>Return Date:</th>
                <td colspan="3">{{ $loan->return_date->format('M d, Y') }}</td>
            </tr>
            @endif
        </table>
    </div>
    
    <h3>Items Borrowed</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Serial/Asset</th>
                <th>Quantity</th>
                <th>Condition</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->name }}</td>
                <td>
                    @if($item->pivot->serial_numbers)
                        {{ implode(', ', (array)$item->pivot->serial_numbers) }}
                    @else
                        {{ $item->serial_number ?: $item->asset_tag ?: 'N/A' }}
                    @endif
                </td>
                <td>{{ $item->pivot->quantity }}</td>
                <td>{{ $item->pivot->condition_before ?: 'Not specified' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    @if($loan->notes)
    <div class="notes">
        <strong>Notes:</strong>
        <p>{{ $loan->notes }}</p>
    </div>
    @endif
    
    <div class="footer">
        <div>
            <p><strong>Borrower's Signature:</strong></p>
            <div class="signature-box">
                @if($loan->signature)
                    <img src="{{ $loan->signature }}" alt="Signature" style="max-height: 60px;">
                @endif
            </div>
            <p>I acknowledge receipt of the above items and agree to return them in the same condition by the due date.</p>
        </div>
        
        <div style="margin-top: 20px;">
            <p><strong>Issued By:</strong> __________________________ Date: __________________</p>
        </div>
    </div>
</body>
</html> 