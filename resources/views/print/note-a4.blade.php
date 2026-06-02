<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega {{ $sale->code }}</title>
    <style>
        @media print {
            @page { size: A4; margin: 15mm; }
            body { 
                font-family: Arial, sans-serif;
                font-size: 12px; 
                margin: 0;
            }
            .no-print { display: none; }
        }
        body { 
            font-family: Arial, sans-serif;
            font-size: 12px; 
            margin: 0 auto;
            max-width: 210mm;
            padding: 20px;
            background: #fff;
            color: #333;
        }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
        .header-left h1 { margin: 0 0 5px 0; font-size: 24px; color: #000; }
        .header-left p { margin: 0; color: #555; }
        .header-right { text-align: right; }
        .header-right h2 { margin: 0 0 5px 0; font-size: 20px; color: #333; }
        .header-right p { margin: 0; font-size: 14px; font-weight: bold; color: #000; }
        
        .divider { border-top: 2px solid #333; margin: 15px 0; }
        
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .info-col { width: 48%; }
        .info-item { margin-bottom: 5px; }
        .info-label { font-weight: bold; display: inline-block; width: 100px; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f0f0f0; border-bottom: 2px solid #ccc; padding: 10px; text-align: left; font-weight: bold; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .totals-container { display: flex; justify-content: flex-end; }
        .totals-table { width: 300px; }
        .totals-table th { background: transparent; border: none; text-align: left; padding: 5px 10px; }
        .totals-table td { border: none; text-align: right; padding: 5px 10px; font-weight: bold; }
        .total-row th, .total-row td { border-top: 2px solid #333; font-size: 16px; padding-top: 10px; color: #000; }
        
        .footer { margin-top: 40px; text-align: center; color: #777; font-size: 11px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <h1>{{ $system->name ?? 'Z8Venta' }}</h1>
            <p>{{ $system->alias ?? 'Sistema POS' }}</p>
            <p>Sucursal: {{ $sale->branch->name }}</p>
        </div>
        <div class="header-right">
            <h2>NOTA DE ENTREGA</h2>
            <p>N° {{ $sale->code }}</p>
        </div>
    </div>
    
    <div class="divider"></div>
    
    <div class="info-grid">
        <div class="info-col">
            <div class="info-item"><span class="info-label">Fecha:</span> {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
            <div class="info-item"><span class="info-label">Encargado:</span> {{ $sale->user->name }}</div>
            @php
                $payments = ['cash' => 'Efectivo', 'credit' => 'Crédito', 'transfer' => 'Transferencia', 'other' => 'Otro'];
                $paymentType = $payments[$sale->payment_type] ?? $sale->payment_type;
            @endphp
            <div class="info-item"><span class="info-label">Forma de Pago:</span> {{ $paymentType }}</div>
        </div>
        <div class="info-col">
            <div class="info-item"><span class="info-label">Cliente:</span> {{ $sale->client ? $sale->client->name : 'Consumidor Final' }}</div>
            @if($sale->client && $sale->client->phone)
            <div class="info-item"><span class="info-label">Teléfono:</span> {{ $sale->client->phone }}</div>
            @endif
            @if($sale->client && $sale->client->email)
            <div class="info-item"><span class="info-label">Email:</span> {{ $sale->client->email }}</div>
            @endif
        </div>
    </div>
    
    @if($sale->notes)
    <div style="margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-left: 4px solid #ddd;">
        <strong>Observaciones:</strong> {{ $sale->notes }}
    </div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 40%">Producto</th>
                <th style="width: 10%" class="text-center">Cant.</th>
                <th style="width: 15%" class="text-right">Precio Unit.</th>
                <th style="width: 15%" class="text-right">Desc.</th>
                <th style="width: 15%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $index => $item)
            @php
                $qty = (float)$item->quantity;
                $upb = (float)($item->product->units_per_box ?? 0);
                $isBoxable = $item->product->product_type === 'caja' || $upb > 0;
                if ($isBoxable && $upb > 0 && $qty >= $upb) {
                    $boxes = floor($qty / $upb);
                    $remainder = fmod($qty, $upb);
                    $qtyText = $remainder == 0 ? "{$boxes} Caja(s) ({$qty} u.)" : "{$boxes} Caja(s), {$remainder} u.";
                } else {
                    $qtyText = $qty;
                }
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->name }}</td>
                <td class="text-center">{{ $qtyText }}</td>
                <td class="text-right">{{ number_format($item->sale_price, 2) }}</td>
                <td class="text-right">{{ floatval($item->discount) > 0 ? floatval($item->discount).'%' : '-' }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <th>Subtotal:</th>
                <td>{{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if($sale->discount > 0)
            <tr>
                <th>Descuento:</th>
                <td>-{{ number_format($sale->discount, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <th>TOTAL:</th>
                <td>{{ number_format($sale->total, 2) }} {{ $system->currency ?? 'Bs.' }}</td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        <p>¡Gracias por su preferencia!</p>
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
