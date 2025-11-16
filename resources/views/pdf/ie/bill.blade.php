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
                <h1 style="margin: 0;"><strong>Invoice</strong></h1>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td width="60%">
                <table width="100%">
                    <tr>
                        <td width="50%">
                            <h4>Billing address:</h4>
                            <p>{{ $detail->attributes->billing_address->first_name }} {{ $detail->attributes->billing_address->last_name }}<br>
                                {{ $detail->attributes->billing_address->address_line_1 }}<br>
                                {{ $detail->attributes->billing_address->zip_code }} {{ $detail->attributes->billing_address->city }} <br>
                                {{ $detail->attributes->billing_address->country_code }}
                            </p>
                        </td>
                        <td width="50%">
                            <h4>Shipping address:</h4>
                            <p>{{ $detail->attributes->shipping_address->first_name }} {{ $detail->attributes->shipping_address->last_name }}<br>
                                {{ $detail->attributes->shipping_address->address_line_1 }}<br>
                                {{ $detail->attributes->shipping_address->zip_code }} {{ $detail->attributes->shipping_address->city }} <br>
                                {{ $detail->attributes->shipping_address->country_code }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
            <td align="right" width="40%">
                <table width="100%" style="text-align: right;" cellpadding="5">
                    <tr>
                        <td align="right">Customer number: {{ $detail->attributes->customer_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Order number: {{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Invoice number:{{ $detail->attributes->shipment_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Invoice from: {{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <td align="right">Shipping from : {{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <hr>
                <p>Hello {{ $detail->attributes->billing_address->first_name }},</p>
                <p>thank you for your purchase at Zalando! Because of your <br>delivery you will receive the following invoice:</p>
            </td>
            <td align="right">
                <h3>Page 1 of 1</h3>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" cellpadding="5">
        
        <tr style="border-bottom: 1px solid #000;" align="left">
            <!-- <th width="15%">Zalando Art. Nr.</th> -->
            <th width="5%">No.</th>
            <th width="35%">Articlenumber</th>
            <!-- <th width="10%">Größe</th> -->
            <th width="20%">Article</th>
            <th width="20%">Net unit price {{ $detail->attributes->order_lines_price_currency }}</th>
            <th width="15%">VAT
total amount
{{ $detail->attributes->order_lines_price_currency }}</th>
            <th width="15%">VAT %</th>
        </tr>
        <tr>
            <td colspan="6" style="padding: 0;"><hr style="margin: 0;"></td>
        </tr>
        @php
            $totalVat = 0;
            $totalWPrice = 0;
        @endphp
        @foreach($included as $key => $oi)
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
                <td>{{ ++$key }}</td>
                <td>{{ $oi->attributes->article_id }}</td>
                <td>{{$sku}}</td>
                <!-- <td>{{ $oi->attributes->description }} ({{$sizeTitle}})</td> -->
                <!-- <td>({{$sizeTitle}})</td> -->
                <!-- <td>{{ $oi->attributes->quantity_initial + $oi->attributes->quantity_reserved + $oi->attributes->quantity_shipped + $oi->attributes->quantity_returned + $oi->attributes->quantity_canceled  }}</td> -->
                <td>@php
                        $sum = 0;
                        $sumVat = 0;
                    @endphp
                @foreach($included as $line)
                    @if($line->type == 'OrderLine' && $line->attributes->order_item_id == $oi->id)
                    @php
                    $price = $line->attributes->price->amount;
                    $vat = ($price*$tax);
                    $totalVat += $vat;
                    $priceWithoutVat = $price-$vat; 
                    $totalWPrice += $priceWithoutVat;
                    $sumVat += $vat;
                    $sum = ($oi->attributes->quantity_initial + $oi->attributes->quantity_reserved + $oi->attributes->quantity_shipped + $oi->attributes->quantity_returned + $oi->attributes->quantity_canceled) * $price;
                    @endphp
                    {{ str_replace(".", ",", $priceWithoutVat) }} {{ $line->attributes->price->currency }}
                    @endif
                @endforeach</td>
                <td>{{ str_replace(".", ",", $sumVat) }}</td>
                <td>{{ $tax*100 }}%</td>
            </tr>
            @endif
            @endforeach
        <tr>
            <td colspan="6" style="padding: 0;"><hr style="margin: 0;"></td>
        </tr>
        
        <tr>
            <td align="right" colspan="5">Net total amount {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalWPrice) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5">VAT total amount  {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalVat) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5"><strong>Gross total amount  {{ $detail->attributes->order_lines_price_currency }}</strong></td>
            <td><strong>{{ $detail->attributes->order_lines_price_amount }}</strong></td>
        </tr>
    </table>
    <br>
    
    <table width="100%">
        <tr>
            <td>
                <p>This invoice cannot be used to claim VAT deductions. The purchase was made for personal use only.</p>
                <p><strong>Zalando team wishes you lots of fun with your purchase!</strong></p>
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
                <p style="font-size: 10px;">Zalando SE, Valeska-Gert-Straße 5, 10243 Berlin eingetragen beim: Amtsgericht Charlottenburg, HRB 158855 B USt-IdNr.: ATU74796116 Vorstand: Robert Gentz & David
Schneider (beide Co- Vorstandsvorsitzende), Dr. Astrid Arndt, Dr. Sandra Dembeck, James Freeman II, David Schrö der Aufsichtsratsvorsitzende: Cristina Stenbeck Sitz: Berlin |
WEEE-Reg.-Nr. DE: 72754189. Zalando SE hat deine Forderungen aus dem oben genannten Kauf an Zalando Payments GmbH abgetreten.</p>
            </td>
        </tr>
    </table>
</body>
</html>