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
                <h1 style="margin: 0;"><strong>Rechnung</strong></h1>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td width="70%">
                <table width="100%">
                    <tr>
                        <td width="100%">
                            <h4>Facturatie adres:</h4>
                            <p>{{ $detail->attributes->billing_address->first_name }} {{ $detail->attributes->billing_address->last_name }}<br>
                                {{ $detail->attributes->billing_address->address_line_1 }}<br>
                                {{ $detail->attributes->billing_address->zip_code }} {{ $detail->attributes->billing_address->city }} <br>
                                {{ $detail->attributes->billing_address->country_code }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
            <td align="right" width="20%">
                <table width="100%" style="text-align: right;" cellpadding="5">
                    <tr>
                        <td align="right">Klantnummer:</td>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Bestellingsnummer:</td>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Factuurnummer:</td>
                        <td>{{ $detail->attributes->shipment_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Rekening van:</td>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <td align="right">Levering vanaf:</td>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>
    <br>
    <table width="100%" cellpadding="5">
        
        <tr style="border-bottom: 1px solid #000;" align="left">
            <!-- <th width="15%">Zalando Art. Nr.</th> -->
            <th width="5%">Leverancier
art-nr.</th>
            <th width="35%">Benaming</th>
            <!-- <th width="10%">Größe</th> -->
            <th width="20%">Kwantiteit</th>
            <th width="20%">Stukprijs {{ $detail->attributes->order_lines_price_currency }}</th>
            <th width="15%"VAT Totale inzet vod {{ $detail->attributes->order_lines_price_currency }}</th>
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
            <td align="right" colspan="5">Totaal nettobedrag {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalWPrice) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5">BTW totaalbedrag {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalVat) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5"><strong>Bruto totaalbedrag {{ $detail->attributes->order_lines_price_currency }}</strong></td>
            <td><strong>{{ $detail->attributes->order_lines_price_amount }}</strong></td>
        </tr>
    </table>
    <br>
    
    <table width="100%">
        <tr>
            <td>
                <p><b>Opmerking</b>: Wanneer u heeft gekozen voor de betaalmethode 'betalen na ontvangst', ontvangt u een e-mail van Zalando met de orderbevestiging en het openstaande totaalbedrag</p>
                <p>Heb je een vraag? Neem eens een kijkje bij onze veelgestelde vragen: <a href="https://zalando.be/faq">zalando.be/faq</a>.</p>
                <p><strong>Gelieve openstaande bedragen uitsluitend aan Zalando overmaken.</strong></p>
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
            <td>
                <strong>Bankgegevens:</strong>  <br>
                Begunstigde: Zalando Payments  <br>
                GmbH  <br>
                IBAN: {{ config('bank')[$country]['IBAN']??"" }}  <br>
                BIC: {{ config('bank')[$country]['BIC']??"" }}  <br>
                Rekeningnumber: {{ config('bank')[$country]['NUMBER']??"" }}  <br>
                Bank: {{ config('bank')[$country]['BANK']??"" }}  <br>
                Gebruiksdoeleinde: UW Bestellnummer
            </td>
        </tr>
    </table>
</body>
</html>