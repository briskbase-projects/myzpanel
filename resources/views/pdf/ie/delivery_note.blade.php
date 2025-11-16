<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body{
            font-family: DejaVu Sans, Arial, sans-serif;font-size: 12px;
        }
    </style>
</head>
<body>

    <table width="100%">
        <tr valign="top">
            <td>
                <img src="{{ env('CLIENT_LOGO') }}" alt="" width="100">
                <h2 style="margin: 0;"><strong>Delivery note</strong></h2>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td width="50%">
                <p>{{ $detail->attributes->shipping_address->first_name }} {{ $detail->attributes->shipping_address->last_name }}<br>
                {{ $detail->attributes->shipping_address->address_line_1 }}<br>
                {{ $detail->attributes->shipping_address->zip_code }} {{ $detail->attributes->shipping_address->city }}</p>
            </td>
            <td align="right" width="50%">
                <table width="100%" style="border: 1px solid #ddd;" cellpadding="5">
                    <tr>
                        <th align="left">Customer number:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Order no.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Order date:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Delivery note date:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td><br>
            Your purchase:            </td>
        </tr>
    </table>
    <br>
    <table width="100%" cellpadding="5">
        
        <tr style="border-bottom: 1px solid #000;" align="left">
            <th width="15%">Zalando
reference no.</th>
            <th width="15%">Partner
reference no.</th>
            <th width="20%">Article</th>
            <th width="10%">Size</th>
            <th width="10%">Quantity</th>
            <th width="15%">Unit price</th>
            <th width="15%">Total price</th>
        </tr>
        <tr>
            <td colspan="7" style="padding: 0;"><hr style="margin: 0;"></td>
        </tr>
        @foreach($included as $oi)
        @if($oi->type == 'OrderItem')
            @php
            
                $sizeTitle = $ean = $sku = "";
                foreach($sizes as $size):
                foreach($size as $sd):
                    if($oi->attributes->external_id == $sd['sku']):
                    $sizeTitle = $sd['title'];
                    $ean = $sd['ean'];
                    $sku = $sd['sku'];
                    endif;
                endforeach;
                endforeach;
            @endphp
            <tr>
                <td>{{ $oi->attributes->article_id }}</td>
                <td>{{$sku}}</td>
                <td>{{ $oi->attributes->description }}</td>
                <td>({{$sizeTitle}})</td>
                <td>{{ $oi->attributes->quantity_initial + $oi->attributes->quantity_reserved + $oi->attributes->quantity_shipped + $oi->attributes->quantity_returned + $oi->attributes->quantity_canceled  }}</td>
                <td>@php
                        $sum = 0;
                    @endphp
                @foreach($included as $line)
                    @if($line->type == 'OrderLine' && $line->attributes->order_item_id == $oi->id)
                    @php
                    $sum = ($oi->attributes->quantity_initial + $oi->attributes->quantity_reserved + $oi->attributes->quantity_shipped + $oi->attributes->quantity_returned + $oi->attributes->quantity_canceled) * $line->attributes->price->amount;
                    @endphp
                    {{ $line->attributes->price->amount }} {{ $line->attributes->price->currency }}
                    @endif
                @endforeach</td>
                <td>{{ $sum }}</td>
            </tr>
            @endif
            @endforeach
        <tr>
            <td colspan="7" style="padding: 0;"><hr style="margin: 0;"></td>
        </tr>
        
        <tr>
            <td align="right" colspan="6"><strong>Total amount EUR</strong></td>
            <td><strong>{{ $detail->attributes->order_lines_price_amount }} {{ $detail->attributes->order_lines_price_currency }}</strong></td>
        </tr>
    </table>
    <br>
    
    <table width="100%">
        <tr>
            <td>
                <p>Need help? Visit <a href="https://www.zalando.ie/faq">https://www.zalando.ie/faq.</a></p>
                <p>Please handle the item(s) carefully as they can be returned in an unworn and undamaged condition,
only.</p>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%">
        <tr>
            <td colspan="3" style="padding: 0;"><br><br><hr style="margin: 0;"></td>
        </tr>
        <tr valign="top">
            <td>
                {{ env('ZALANDO_BRAND_NAME') }} <br>
                {{env('ZCLIENT_COMPANY_NAME')}}  <br>
                {{env('ZCLIENT_ADDRESS')}} <br>
                {{env('ZCLIENT_ZIPCODE')}} {{env('ZCLIENT_CITY')}} <br>
                Geschäftsführer : {{env('ZCLIENT_NAME')}}, USt-ID Nr. : {{env('ZUST_ID')}}
            </td>
            <td>
                <strong>Im Auftrag von:</strong> <br>
                Zalando SE <br>
                Valeska-Gert-Straße 5 <br>
                10243 Berlin
            </td>
            
        </tr>
    </table>
</body>
</html>