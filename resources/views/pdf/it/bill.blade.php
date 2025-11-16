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
                <h1 style="margin: 0;"><strong>Fattura</strong></h1>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td width="40%">
                <table width="100%">
                    <tr>
                        <td width="100%">
                            <h4>Indirizzo di fatturazione:</h4>
                            <p>{{ $detail->attributes->billing_address->first_name }} {{ $detail->attributes->billing_address->last_name }}<br>
                                {{ $detail->attributes->billing_address->address_line_1 }}<br>
                                {{ $detail->attributes->billing_address->zip_code }} {{ $detail->attributes->billing_address->city }} <br>
                                {{ $detail->attributes->billing_address->country_code }}
                            </p>
                        </td>
                        
                    </tr>
                </table>
            </td>
            <td align="right" width="60%">
                <table width="100%" style="text-align: right;" cellpadding="5">
                    <tr>
                        <td align="right">Numero cliente:</td>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Numero d'ordine:</td>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">Numero di fattura:</td>
                        <td>{{ $detail->attributes->shipment_number }}</td>
                    </tr>
                    <tr>
                        <td align="right">fattura di:</td>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <td align="right">consegna da:</td>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>
                <p>Gentile Cliente,</p>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" cellpadding="5">
        
        <tr style="border-bottom: 1px solid #000;" align="left">
            <!-- <th width="15%">Zalando Art. Nr.</th> -->
            <th width="5%">pz.</th>
            <th width="35%">Codice Articolo</th>
            <!-- <th width="10%">Größe</th> -->
            <th width="20%">Articoli</th>
            <th width="20%">Prezzo unitario netto {{ $detail->attributes->order_lines_price_currency }}</th>
            <th width="15%">Importo totale dell'IVA {{ $detail->attributes->order_lines_price_currency }}</th>
            <th width="15%">I.V.A. %</th>
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
                    $vat = ($price*0.21);
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
            <td align="right" colspan="5">Importo totale netto {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalWPrice) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5">Importo totale dell'IVA {{ $detail->attributes->order_lines_price_currency }}</td>
            <td>{{ str_replace(".", ",", $totalVat) }}</td>
        </tr>
        <tr>
            <td align="right" colspan="5"><strong>Importo totale lordo {{ $detail->attributes->order_lines_price_currency }}</strong></td>
            <td><strong>{{ $detail->attributes->order_lines_price_amount }}</strong></td>
        </tr>
    </table>
    <br>
    
    <table width="100%">
        <tr>
            <td>
                <p>Domande? Puoi trovare le risposte qui: <a href="https://www.zalando.it/aiuto">https://www.zalando.it/aiuto</a>.</p>
                <p>In caso di reso, verranno accettati solo articoli integri e non usati.</p>
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