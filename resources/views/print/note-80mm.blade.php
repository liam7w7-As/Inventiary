<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega {{ $sale->code }}</title>
    <style>
        @media print {
            @page { margin: 2mm; }
            body { 
                width: 76mm; 
                font-size: 12px;
                font-family: monospace; 
                margin: 0;
                padding: 0;
            }
            .no-print { display: none; }
        }
        body { 
            width: 76mm; 
            font-size: 12px;
            font-family: monospace; 
            margin: 0 auto;
            padding: 10px;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .mb-1 { margin-bottom: 5px; }
        .mb-2 { margin-bottom: 10px; }
        .mt-1 { margin-top: 5px; }
        .mt-2 { margin-top: 10px; }
        .divider { border-top: 1px dashed #000; margin: 8px 0; }
        .flex { display: flex; justify-content: space-between; }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 3px 0; border-bottom: 1px dashed #000; }
        td { padding: 4px 0; vertical-align: top; }
        .col-qty { width: 15%; }
        .col-price { width: 25%; text-align: right; }
        .col-disc { width: 20%; text-align: right; }
        .col-total { width: 40%; text-align: right; }
    </style>
</head>
<body>
    <div class="text-center font-bold mb-1" style="font-size: 16px;">
        {{ $system->name ?? 'Z8Venta' }}
    </div>
    <div class="text-center mb-2">
        {{ $system->alias ?? 'Sistema POS' }}<br>
        {{ $sale->branch->name }}
    </div>
    
    <div class="divider"></div>
    <div class="text-center font-bold">NOTA DE ENTREGA</div>
    <div class="divider"></div>
    
    <div>
        <div><span class="font-bold">Código:</span> {{ $sale->code }}</div>
        <div><span class="font-bold">Fecha:</span> {{ $sale->created_at->format('d/m/Y H:i:s') }}</div>
        <div><span class="font-bold">Encargado:</span> {{ $sale->user->name }}</div>
    </div>
    
    <div class="divider"></div>
    
    @if($sale->client)
    <div>
        <div><span class="font-bold">Cliente:</span> {{ $sale->client->name }}</div>
        @if($sale->client->phone)
        <div><span class="font-bold">Tel:</span> {{ $sale->client->phone }}</div>
        @endif
        @if($sale->client->email)
        <div><span class="font-bold">Email:</span> {{ $sale->client->email }}</div>
        @endif
    </div>
    <div class="divider"></div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th colspan="4">Producto</th>
            </tr>
            <tr>
                <th class="col-qty">Cant</th>
                <th class="col-price">Precio</th>
                <th class="col-disc">Desc</th>
                <th class="col-total">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
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
                <td colspan="4" class="font-bold" style="padding-top: 5px;">{{ $item->product->name }}</td>
            </tr>
            <tr>
                <td class="col-qty">{{ $qtyText }}</td>
                <td class="col-price">{{ number_format($item->sale_price, 2) }}</td>
                <td class="col-disc">{{ floatval($item->discount) > 0 ? floatval($item->discount).'%' : '-' }}</td>
                <td class="col-total">{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="divider"></div>
    
    <div class="flex mb-1 mt-2">
        <span>Subtotal:</span>
        <span>{{ number_format($sale->subtotal, 2) }}</span>
    </div>
    @if($sale->discount > 0)
    <div class="flex mb-1">
        <span>Descuento:</span>
        <span>-{{ number_format($sale->discount, 2) }}</span>
    </div>
    @endif
    <div class="flex font-bold mt-1" style="font-size: 15px;">
        <span>TOTAL:</span>
        <span>{{ number_format($sale->total, 2) }} {{ $system->currency ?? 'Bs.' }}</span>
    </div>
    
    <div class="mt-2 mb-1">
        @php
            $payments = ['cash' => 'Efectivo', 'credit' => 'Crédito', 'transfer' => 'Transferencia', 'other' => 'Otro'];
            $paymentType = $payments[$sale->payment_type] ?? $sale->payment_type;
        @endphp
        <span class="font-bold">Tipo de Pago:</span> {{ $paymentType }}
    </div>
    
    @if($sale->notes)
    <div class="mb-1">
        <span class="font-bold">Observaciones:</span><br>
        {{ $sale->notes }}
    </div>
    @endif
    
    <div class="divider mt-2"></div>
    <div class="text-center mt-2">
        ¡Gracias por su preferencia!
    </div>

    <script>
        window.onload = () => window.print();
    </script>
</body>
</html>
